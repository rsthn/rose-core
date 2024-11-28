<?php

namespace Rose\Data\Drivers;

use Rose\Text;
use Rose\Regex;
use Rose\Strings;
use Rose\Arry;
use Rose\JSON;
use Rose\Configuration;

use Rose\Errors\Error;
use Rose\Data\Driver;
use Rose\Data\Connection;

class PostgreSQL extends Driver
{
    private $affected_rows = 0;
    private $types = false;

    public static function register() {
        Connection::registerDriver ('postgres', new PostgreSQL());
    }

    public function open ($server, $port, $user, $password, $database)
    {
        if (Configuration::getInstance()?->Database?->postgres_types !== 'false')
            $this->types = true;
        else
            $this->types = false;

        if ($port !== null)
            $conn = pg_connect('host='.$server.' port='.$port.' dbname='.$database.' user='.$user.' password='.$password);
        else
            $conn = pg_connect('host='.$server.' dbname='.$database.' user='.$user.' password='.$password);
        return $conn;
    }

    public function close ($conn) {
        return pg_close ($conn);
    }

    private static function process_error ($error)
    {
        if (Text::indexOf($error, 'check constraint') !== false) {
            $code = Regex::_getString('/constraint &quot;(.+?)&quot;/', $error, 1);
            return Strings::get('@messages.'.$code);
        }

        if (Text::indexOf($error, 'foreign key constraint') !== false) {
            $code = Regex::_getString('/constraint &quot;(.+?)&quot;/', $error, 1);
            return Strings::get('@messages.'.$code);
        }

        $msg = Regex::_getString('/ERROR:(.+?)CONTEXT:.+RAISE/s', $error, 1);
        if ($msg) {
            $msg = Text::trim($msg);
            if (Text::startsWith($msg, '@messages')) $msg = Strings::get($msg);
            return $msg;
        }

        $code = Regex::_getString('/&quot;(.+?)&quot;/', $error, 1);
        if (!$code) return $error;

        if (Text::indexOf($error, 'not-null constraint') !== false)
            return Strings::get('@messages.not_null_'.$code);

        if (Text::indexOf($error, 'unique constraint') !== false)
            return Strings::get('@messages.unique_'.$code);

        return $error;
    }

    public function getLastError ($conn) {
        return $conn ? self::process_error(pg_last_error($conn)) : '(Undefined)';
    }

    public function getLastInsertId ($conn) {
        try {
            $rs = $this->query('SELECT LASTVAL()', $conn, null);
        } catch (\Exception $e) {
            return 0;
        }
        if ($rs === false || $rs === true || $rs === null) return 0;

        $ret = $this->fetchRow($rs, $conn);
        $this->freeResult($rs, $conn);
        return $ret[0];
    }

    public function getAffectedRows ($conn) {
        return $this->affected_rows;
    }

    public function isAlive ($conn) {
        return pg_ping ($conn);
    }

    public function prepare_param (&$value, &$query_part, &$index, &$extra)
    {
        if (\Rose\isBool($value)) {
            $query_part = $value ? "true" : "false";
            return 1;
        }

        if (\Rose\isInteger($value)) {
            $query_part = $value;
            return 1;
        }

        if (\Rose\isNumber($value)) {
            $query_part = $value;
            return 1;
        }

        $query_part = '$'.($index++);
        return 3;
    }

    private static function parse_value($value, $n, &$offs)
    {
        $delim = $value[$offs++];
        $val = '';

        while ($offs < $n)
        {
            $ch = $value[$offs++];
            if ($ch === '\\')
            {
                switch ($value[$offs++])
                {
                    case '0': $val .= "\0"; break;
                    case 'b': $val .= "\x08"; break;
                    case 'n': $val .= "\n"; break;
                    case 'r': $val .= "\r"; break;
                    case 'f': $val .= "\f"; break;
                    case 'v': $val .= "\v"; break;
                    case 't': $val .= "\t"; break;
                    case 'e': $val .= "\x1B"; break;
                    case '"': $val .= "\""; break;
                    case "'": $val .= "\'"; break;
                    case "`": $val .= "`"; break;
                    case "(": $val .= "("; break;
                    case ")": $val .= ")"; break;
                    case "{": $val .= "{"; break;
                    case "}": $val .= "}"; break;
                    case "[": $val .= "["; break;
                    case "]": $val .= "]"; break;
                    case "\\": $val .= "\\"; break;
                    case 'x': $val .= chr(hexdec(Text::substring($value, $offs, 2))); $offs += 2; break;
                    default:
                        \Rose\trace('Invalid escape sequence: ' . $value[$offs-2] . $value[$offs-1]);
                        throw new Error('Invalid escape sequence: ' . $value[$offs-2] . $value[$offs-1]);
                }

                continue;
            }

            if ($ch === $delim)
                break;

            $val .= $ch;
        }

        return $val;
    }

    private static function parse_sequence($value)
    {
        $list = new Arry();

        $value = Text::slice($value, 1, -1);
        $n = Text::length($value);
        $offs = 0;

        while ($offs < $n)
        {
            if ($value[$offs] === '"') {
                $list->push(self::parse_value($value, $n, $offs));
                $offs += 1;
                continue;
            }

            $i = Text::indexOf($value, ',', $offs);
            if ($i === false) break;
            $list->push(Text::slice($value, $offs, $i));
            $offs = $i+1;
        }

        if ($offs < $n)
            $list->push(Text::slice($value, $offs));

        return $list;
    }

    private static function cast_to($value, $type)
    {
        if ($value === null)
            return null;

        switch ($type)
        {
            case 'int':
                return (int)$value;
            case '_int':
                return Text::split(',', Text::slice($value, 1, -1))->map(function($val) { return (int)$val; });

            case 'float':
                return (float)$value;
            case '_float':
                return Text::split(',', Text::slice($value, 1, -1))->map(function($val) { return (float)$val; });
    
            case 'bool':
                return $value === 't';
            case '_bool':
                return Text::split(',', Text::slice($value, 1, -1))->map(function($val) { return $val === 't'; });

            case 'json':
                return JSON::parse($value);
            case '_json':
                return self::parse_sequence($value)->map(function($val) { return JSON::parse($val); });

            case '_string':
                return self::parse_sequence($value);
        }

        return $value;
    }

    private function map_pg_to_php_type($pg_type)
    {
        $prefix = '';
        if ($pg_type[0] === '_') {
            $prefix = '_';
            $pg_type = Text::substring($pg_type, 1);
        }

        switch ($pg_type)
        {
            case 'int2':
            case 'int4':
            case 'int8':
            case 'serial':
            case 'bigserial':
                return $prefix.'int';

            case 'float4':
            case 'float8':
            case 'numeric':
                return $prefix.'float';

            case 'bool':
                return $prefix.'bool';

            case 'citext':
            case 'text':
            case 'varchar':
            case 'char':
            case 'date':
            case 'timestamp':
            case 'timestamptz':
                return $prefix.'string';

            case 'json':
            case 'jsonb':
                return $prefix.'json';
        }

        \Rose\trace('Unmapped PostgreSQL type: '.$prefix.$pg_type);
        return 'string';
    }

    private function get_pg_column_types($rs)
    {
        $column_types = [];
        $num_fields = pg_num_fields($rs);
    
        for ($i = 0; $i < $num_fields; $i++) {
            $pg_type = pg_field_type($rs, $i);
            $php_type = $this->map_pg_to_php_type($pg_type);  // Map PostgreSQL type to PHP type
            $column_name = pg_field_name($rs, $i);  // Get the column name
            $column_types[$column_name] = $php_type;
            $column_types[$i] = $php_type;
        }

        return $column_types;
    }

    public function query ($query, $conn, $params)
    {
        $this->affected_rows = 0;

        if ($params === null)
        {
            try {
                $rs = pg_query($conn, $this->log_query($query));
                if ($rs === false) return false;
            }
            catch (\Exception $e) {
                throw new Error (self::process_error($e->getMessage()));
            }

            $this->affected_rows = pg_affected_rows($rs);
            if (pg_num_fields($rs) == 0) return true;

            if ($this->types !== false)
                $this->types = $this->get_pg_column_types($rs);

            return $rs;
        }

        [$query, $params, $extra] = $this->prepare_query($query, $params);

        try {
            $rs = pg_query_params($conn, $this->log_query($query), $params);
            if ($rs === false) return false;
        }
        catch (\Exception $e) {
            throw new Error (self::process_error($e->getMessage()));
        }

        $this->affected_rows = pg_affected_rows($rs);
        if (pg_num_fields($rs) == 0) return true;

        if ($this->types !== false)
            $this->types = $this->get_pg_column_types($rs);

        return $rs;
    }

    public function reader ($query, $conn, $params)
    {
        $this->affected_rows = 0;

        if ($params === null) {
            $rs = pg_query($conn, $this->log_query($query));
            if ($rs === false) return false;

            $this->affected_rows = pg_affected_rows($rs);
            if (pg_num_fields($rs) == 0) return true;

            if ($this->types !== false)
                $this->types = $this->get_pg_column_types($rs);
            return $rs;
        }

        [$query, $params, $extra] = $this->prepare_query($query, $params);

        try {
            $rs = pg_query_params($conn, $this->log_query($query), $params);
            if ($rs === false) return false;
        }
        catch (\Exception $e) {
            throw new Error (self::process_error($e->getMessage()));
        }

        $this->affected_rows = pg_affected_rows($rs);
        if (pg_num_fields($rs) == 0) return true;

        if ($this->types !== false)
            $this->types = $this->get_pg_column_types($rs);

        return $rs;
    }

    public function getNumRows ($rs, $conn) {
        return pg_num_rows($rs);
    }

    public function getNumFields ($rs, $conn) {
        return pg_num_fields($rs);
    }

    public function getFieldName ($rs, $i, $conn) {
        return pg_field_name($rs, $i);
    }

    private function prepare_data($value)
    {
        foreach ($value as $key => &$val)
            $val = self::cast_to($val, $this->types[$key]);

        return $value;
    }

    public function fetchAssoc ($rs, $conn) {
        $tmp = pg_fetch_assoc ($rs);
        if ($tmp === false || $tmp == null) $tmp = null;
        return $tmp && $this->types !== false ? $this->prepare_data($tmp) : $tmp;
    }

    public function fetchRow ($rs, $conn) {
        $tmp = pg_fetch_row ($rs);
        if ($tmp === false || $tmp == null) $tmp = null;
        return $tmp && $this->types !== false ? $this->prepare_data($tmp) : $tmp;
    }

    public function freeResult ($rs, $conn) {
        pg_free_result($rs);
    }

    public function escapeName ($value) {
        return Connection::escape($value, '"', '"', null, null, null, null);
    }

    public function escapeValue ($value) {
        return 'E' . Connection::escape($value);
    }
};
