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

    public function open ($server, $user, $password, $database) {
        $conn = pg_connect('host='.$server.' dbname='.$database.' user='.$user.' password='.$password);
        return $conn;
    }

    public function close ($conn) {
        return pg_close ($conn);
    }

    private static function process_error ($error)
    {
        $code = Regex::_getString('/&quot;(.+?)&quot;/', $error, 1);
        if (!$code) return $error;

        if (Text::indexOf($error, 'not-null constraint') !== false)
            return Strings::get('@messages.not_null').': '.$code;

        if (Text::indexOf($error, 'constraint') !== false)
            return Strings::get('@messages.'.$code);

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
        foreach ($params->__nativeArray as $param) {
            $query = preg_replace('/\?/', '\$'.$arg_num, $query, 1);
            $arg_num++;
        }

        try {
            $rs = pg_query_params($conn, $query, $params->__nativeArray);
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
