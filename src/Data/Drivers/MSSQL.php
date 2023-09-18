<?php

namespace Rose\Data\Drivers;

use Rose\Errors\Error;
use Rose\Data\Driver;
use Rose\Data\Connection;

/**
 * Legacy driver for Microsoft SQL Server. When possible use the SQLServer driver instead (using the `sqlserver` driver name).
 */
class MSSQL extends Driver
{
	public static function register() {
		Connection::registerDriver ('mssql', new MSSQL());
	}

    public function open ($server, $user, $password, $database)
    {
		$conn = mssql_connect($server, $user, $password);
		if ($conn == null) return null;

		if ($database != null)
			mssql_select_db($database, $conn);

        return $conn;
    }

    public function close ($conn) {
		return mssql_close($conn);
    }

    public function getLastError ($conn) {
		return mssql_get_last_message();
    }

    public function getLastInsertId ($conn) {
		return $conn->execScalar('SELECT SCOPE_IDENTITY()');
    }

    public function getAffectedRows ($conn) {
		return mssql_rows_affected($conn);
    }

    public function isAlive ($conn) {
		return true;
    }

    public function query ($query, $conn, $params) {
		return mssql_query($query, $conn);
    }

    public function reader ($query, $conn, $params) {
		return mssql_query($query, $conn);
    }

	public function getNumRows ($rs, $conn) {
		return mssql_num_rows($rs);
	}

	public function getNumFields ($rs, $conn) {
		return mssql_num_fields($rs);
	}

	public function getFieldName ($rs, $i, $conn) {
		return mssql_field_name($rs, $i);
	}

	public function fetchAssoc ($rs, $conn) {
		$tmp = mssql_fetch_assoc ($rs);
		if ($tmp === false || $tmp == null) $tmp = null;
		return $tmp;
	}

	public function fetchRow ($rs, $conn) {
		$tmp = mssql_fetch_row ($rs);
		if ($tmp === false || $tmp == null) $tmp = null;
		return $tmp;
	}

	public function freeResult ($rs, $conn) {
		mssql_free_result($rs);
	}

	public function escapeName ($value) {
		return Connection::escape($value, '[', ']', null, null, null, null);
	}

	public function escapeValue ($value) {
		return Connection::escape($value);
	}
};
