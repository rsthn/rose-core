<?php

namespace Rose\Data\Drivers;

use Rose\Errors\Error;
use Rose\Data\Driver;
use Rose\Data\Connection;
use Rose\Text;

class MySQLi extends Driver
{
	public static function register() {
		Connection::registerDriver('mysql', new MySQLi());
		Connection::registerDriver('mysqli', new MySQLi());
	}

    public function open ($server, $port, $user, $password, $database)
    {
		$conn = mysqli_init();
		if (!$conn) return null;

		if (!mysqli_real_connect ($conn, $server, $user, $password, $database, $port))
			throw new Error (mysqli_connect_error());

		if ($conn == null) return null;

		mysqli_options ($conn, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
		mysqli_query ($conn, 'SET time_zone = \'+00:00\'');
		mysqli_query ($conn, 'SET @@group_concat_max_len = 16777216');

		try {
			if (!mysqli_set_charset($conn, 'UTF8MB4'))
				throw new Error ('Unsupported charset UTF8MB4');

			mysqli_query ($conn, 'SET collation_connection = \'utf8mb4_unicode_ci\'');
            mysqli_query ($conn, 'SET NAMES utf8mb4');
		}
		catch (\Throwable $e) {
			mysqli_set_charset ($conn, 'UTF8');
			mysqli_query ($conn, 'SET collation_connection = \'utf8_unicode_ci\'');
            mysqli_query ($conn, 'SET NAMES utf8');
		}

        return $conn;
    }

    public function close ($conn) {
		return mysqli_close($conn);
    }

    public function getLastError ($conn) {
		return $conn ? mysqli_error($conn) : '(Undefined)';
    }

    public function getLastInsertId ($conn) {
		return mysqli_insert_id($conn);
    }

    public function getAffectedRows ($conn) {
		return mysqli_affected_rows ($conn);
    }

    public function isAlive ($conn) {
		return mysqli_ping($conn);
    }

    public function prepare_param (&$value, &$query_part, &$index, &$extra)
    {
        $query_part = '?';

        if ($value === null) {
            $extra['types'] .= 's';
            return 3;
        }

        if ($value === true || $value === false) {
            $extra['types'] .= 'i';
            return 3;
        }

        if (\Rose\isInteger($value)) {
            $extra['types'] .= 'i';
            return 3;
        }

        if (\Rose\isNumber($value)) {
            $extra['types'] .= 'd';
            return 3;
        }

        if (Text::length($value) >= 1024)
            $extra['types'] .= 'b';
        else
            $extra['types'] .= 's';

        return 3;
    }

    public function query ($query, $conn, $params)
    {
        if ($params === null)
            return mysqli_query($conn, $this->log_query($query), MYSQLI_STORE_RESULT);

        [$query, $params, $extra] = $this->prepare_query($query, $params, ['types' => '']);
        $stmt = $conn->prepare($query);
        $stmt->bind_param($extra['types'], ...$params);

        try {
            $success = $stmt->execute();
            if (!$success) return false;
            $result = $stmt->get_result();
            return $result === false ? true : $result;
        }
        finally {
            $stmt->close();
        }
    }

    public function reader ($query, $conn, $params) {
		if ($params === null)
            return mysqli_query($conn, $this->log_query($query), MYSQLI_USE_RESULT);

        [$query, $params, $extra] = $this->prepare_query($query, $params, ['types' => '']);
        $stmt = $conn->prepare($query);
        $stmt->bind_param($extra['types'], ...$params);

        $success = $stmt->execute();
        if (!$success) {
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        $stmt->close();
        return $result === false ? true : $result;
    }

    public function getNumRows ($rs, $conn) {
        try {
		    return mysqli_num_rows($rs);
        }
        catch (\Error $e) {
            if (Text::indexOf($e->getMessage(), 'MYSQLI_USE_RESULT') !== false)
                return -1;
            throw $e;
        }
    }

    public function getNumFields ($rs, $conn) {
        return mysqli_num_fields($rs);
    }

    public function getFieldName ($rs, $i, $conn)
    {
		mysqli_field_seek ($rs, $i);
		$rs = mysqli_fetch_field($rs);
		return $rs->name;
    }

    public function fetchAssoc ($rs, $conn) {
		$tmp = mysqli_fetch_assoc ($rs);
        if ($tmp === false || $tmp == null) $tmp = null;
        return $tmp;
    }

    public function fetchRow ($rs, $conn) {
		$tmp = mysqli_fetch_row ($rs);
        if ($tmp === false || $tmp == null) $tmp = null;
        return $tmp;
    }

    public function freeResult ($rs, $conn)
    {
		mysqli_free_result($rs);
		while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
		};
	}
	
	public function escapeName ($value) {
		return Connection::escape($value, '`', '`', null, null, null, null);
	}

	public function escapeValue ($value) {
		return Connection::escape($value);
	}
};
