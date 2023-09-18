<?php

namespace Rose\Data\Drivers;

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

    public function getLastError ($conn) {
        return $conn ? pg_last_error($conn) : '(Undefined)';
    }

    public function getLastInsertId ($conn) {
        $rs = $this->query ('SELECT LASTVAL()', $conn);
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

    public function query ($query, $conn, $params) {
        $this->affected_rows = 0;

        $rs = pg_query ($conn, $query);
        if ($rs === false) return false;

        $this->affected_rows = pg_affected_rows($rs);
        if (pg_num_fields($rs) == 0) return true;
        return $rs;
    }

    public function reader ($query, $conn, $params) {
        $this->affected_rows = 0;

        $rs = pg_query ($conn, $query);
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
