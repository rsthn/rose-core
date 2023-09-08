<?php

namespace Rose\Data\Drivers;

use Rose\Errors\Error;
use Rose\Text;
use Rose\Regex;

use Rose\Data\Driver;
use Rose\Data\Connection;

class SQLServer extends Driver
{
	private $affected_rows = 0;
	private $last_id = null;
	private $num_rows = 0;
    private $num_fields = 0;
	private $field_metadata = null;
	private $last_error = null;
	private $data_rs = null;
	private $data = null;

	private $options = null;

	public static function register ()
	{
		Connection::registerDriver ('sqlserver', new SQLServer());
	}

    public function open ($server, $user, $password, $database)
    {
		$conn = sqlsrv_connect ($server, array(
			'Database' => $database, 
			'UID' => $user, 
			'PWD' => $password, 
			'ReturnDatesAsStrings' => true, 
			'CharacterSet' => 'UTF-8',
			'TrustServerCertificate' => true
		));
		if ($conn == null) return null;

		$this->options = array('Scrollable' => SQLSRV_CURSOR_STATIC);
		//$this->query('SET NOCOUNT OFF', $conn);

		sqlsrv_configure ('WarningsReturnAsErrors', 0);
        return $conn;
    }

    public function close ($conn)
    {
		return sqlsrv_close ($conn);
    }

    public function getLastError ($conn)
    {
        if ($this->last_error === null)
            $this->loadLastError();
		return $this->last_error;
    }

    public function getLastInsertId ($conn)
    {
		return $this->last_id;
    }

    public function getAffectedRows ($conn)
    {
		return $this->affected_rows;
    }

    public function isAlive ($conn)
    {
		return true;
    }

	private function cleanup_message ($s)
	{
		return Regex::_replace('|\[[^\]]+\]|', '', $s);
	}

	private function loadLastError ()
	{
		$s = '';

		foreach(sqlsrv_errors() as $a)
			$s .= $this->cleanup_message($a['code'] . ': ' . $a['message'])."\n";

		$this->last_error = $s;
		return null;
	}

    public function query ($query, $conn)
	{
		$this->affected_rows = 0;
		$this->num_rows = 0;
		$this->field_metadata = null;
		$this->last_error = null;
		$this->last_id = null;
		$this->data = null;
		$this->data_rs = null;

		$fetch_last_id = false;

		if (Text::startsWith(Text::toUpperCase(Text::trim($query)), 'INSERT INTO') !== false)
		{
			$query .= '; SELECT SCOPE_IDENTITY();';
			$fetch_last_id = true;
		}

		$rs = sqlsrv_query ($conn, $query, null, $this->options);
		if (!$rs) return $this->loadLastError();

		$return = $rs;

		while (true)
		{
			$this->field_metadata = sqlsrv_field_metadata($rs);
			if (!$this->field_metadata)
			{
				$this->affected_rows = sqlsrv_rows_affected($rs);
				$return = true;
			}
			else
			{
				$this->num_rows = sqlsrv_num_rows($rs);
				$this->num_fields = sqlsrv_num_fields($rs);
				$this->data = [];

				while (true)
				{
					$tmp = sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC);
					if ($tmp === null || $tmp === false) break;

					$this->data[] = $tmp;

					if ($fetch_last_id)
					{
						$this->last_id = array_values($tmp)[0];
						$fetch_last_id = false;
					}
				}

				$this->data_rs = $rs;
				$return = $rs;
			}

			$result = sqlsrv_next_result($rs);
			if ($result === false)
				return $this->loadLastError();

			if (!$result) break;
		}

		return $return;
    }

    public function reader ($query, $conn)
	{
		$this->affected_rows = 0;
		$this->num_rows = 0;
		$this->field_metadata = null;
		$this->last_error = null;

		$rs = sqlsrv_query ($conn, $query, null, $this->options);
		if (!$rs) return $this->loadLastError();

		$return = $rs;

		while (true)
		{
			$this->field_metadata = sqlsrv_field_metadata($rs);
			if (!$this->field_metadata)
			{
				$this->affected_rows = sqlsrv_rows_affected($rs);
				$return = true;
			}
			else
			{
				$this->num_rows = sqlsrv_num_rows($rs);
				$this->num_fields = sqlsrv_num_fields($rs);

				$return = $rs;
				break;
			}

			$result = sqlsrv_next_result($rs);
			if ($result === false)
				return $this->loadLastError();

			if (!$result) break;
		}

		return $return;
    }

	public function getNumRows ($rs, $conn)
	{
		return $this->num_rows;
	}

	public function getNumFields ($rs, $conn)
	{
		return $this->num_fields;
	}

	public function getFieldName ($rs, $i, $conn)
	{
		return $this->field_metadata[$i]['Name'];
	}

	public function fetchAssoc ($rs, $conn)
	{
		if ($rs !== $this->data_rs)
			return sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC);

		if (count($this->data) == 0)
			return null;

		return array_shift($this->data);
	}

	public function fetchRow ($rs, $conn)
	{
		if ($rs !== $this->data_rs)
			return array_values(sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC));

		if (count($this->data) == 0)
			return null;

		return array_values(array_shift($this->data));
	}

	public function freeResult ($rs, $conn)
	{
		sqlsrv_free_stmt ($rs);
	}

	public function escapeName ($value)
	{
		return Connection::escape($value, '[', ']', null, null, null, null);
	}

	public function escapeValue ($value)
	{
		$value = Text::replace ("'", "''", $value);

		//$value = Text::split("\t", $value)->join("'+CHAR(9)+'");
		//$value = Text::split("\r", $value)->join("'+CHAR(13)+'");
		//$value = Text::split("\n", $value)->join("'+CHAR(10)+'");

		return "'" . $value . "'";
	}
};
