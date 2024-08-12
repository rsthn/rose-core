<?php

namespace Rose\Data\Drivers;

use Rose\Text;
use Rose\Regex;
use Rose\Strings;

use Rose\Errors\Error;
use Rose\Data\Driver;
use Rose\Data\Connection;

class PostgreSQL extends Driver
{
    private $affected_rows = 0;

    public static function register() {
        Connection::registerDriver ('postgres', new PostgreSQL());
    }

    public function open ($server, $port, $user, $password, $database) {
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
            $rs = $this->query ('SELECT LASTVAL()', $conn, null);
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

    public function query ($query, $conn, $params)
    {
        $this->affected_rows = 0;

        if ($params === null) {
            try {
                $rs = pg_query ($conn, $query);
                if ($rs === false) return false;
            }
            catch (\Exception $e) {
                throw new Error (self::process_error($e->getMessage()));
            }
            $this->affected_rows = pg_affected_rows($rs);
            if (pg_num_fields($rs) == 0) return true;
            return $rs;
        }

        $arg_num = 1;
        $_params = [];
        foreach ($params->__nativeArray as $param)
        {
            $type = \Rose\typeOf($param);
            if ($type === 'Rose\Arry') {
                $query = preg_replace('/\?/', $param->join(','), $query, 1);
            }
            else {
                $_params[] = $param;
                $query = preg_replace('/\?/', '\$'.$arg_num, $query, 1);
                $arg_num++;
            }
        }

        try {
            $rs = pg_query_params($conn, $query, $_params);
            if ($rs === false) return false;
        }
        catch (\Exception $e) {
            throw new Error (self::process_error($e->getMessage()));
        }

        $this->affected_rows = pg_affected_rows($rs);
        if (pg_num_fields($rs) == 0) return true;
        return $rs;
    }

    public function reader ($query, $conn, $params)
    {
        $this->affected_rows = 0;

        if ($params === null) {
            $rs = pg_query ($conn, $query);
            if ($rs === false) return false;
            $this->affected_rows = pg_affected_rows($rs);
            if (pg_num_fields($rs) == 0) return true;
            return $rs;
        }

        $rs = pg_query_params($conn, $query, $params->__nativeArray);
        if ($rs === false) return false;

        $this->affected_rows = pg_affected_rows($rs);
        if (pg_num_fields($rs) == 0) return true;
        return $rs;
    }

    public function getNumRows ($rs, $conn) {
        return pg_num_rows ($rs);
    }

    public function getNumFields ($rs, $conn) {
        return pg_num_fields($rs);
    }

    public function getFieldName ($rs, $i, $conn) {
        return pg_field_name($rs, $i);
    }

    public function fetchAssoc ($rs, $conn) {
        $tmp = pg_fetch_assoc ($rs);
        if ($tmp === false || $tmp == null) $tmp = null;
        return $tmp;
    }

    public function fetchRow ($rs, $conn) {
        $tmp = pg_fetch_row ($rs);
        if ($tmp === false || $tmp == null) $tmp = null;
        return $tmp;
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
