<?php
/*
**	Rose\Data\Drivers\SQLServer
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose\Data\Drivers;

use Rose\Errors\Error;

use Rose\Data\Driver;
use Rose\Data\Connection;

class SQLServer extends Driver
{
	private $affected_rows = 0;
	private $num_rows = 0;
	private $field_metadata = null;
	private $last_error = null;
	private $data = null;

	public static function register ()
	{
		Connection::registerDriver ('sqlserver', new SQLServer());
	}

    public function open ($server, $user, $password, $database)
    {
		$conn = sqlsrv_connect ($server, array('Database' => $database, 'UID' => $user, 'PWD' => $password, 'ReturnDatesAsStrings' => true, 'CharacterSet' => 'UTF-8'));
		if ($conn == null) return null;

		sqlsrv_configure ('WarningsReturnAsErrors', 0);
        return $conn;
    }

    public function close ($conn)
    {
		return sqlsrv_close ($conn);
    }

    public function getLastError ($conn)
    {
		return $this->last_error;
    }

    public function getLastInsertId ($conn)
    {
		return $conn->execScalar('SELECT SCOPE_IDENTITY()');
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
		return preg_replace('|\[[^\]]+\]|', '', $s);
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
		$this->data = null;

		$rs = sqlsrv_query ($conn, $query, null, null);
		if (!$rs) return $this->loadLastError();

		$return = $rs;

		while (true)
		{
			$this->field_metadata = sqlsrv_field_metadata($rs);
			if (!$this->field_metadata)
			{
				$this->affected_rows = sqlsrv_rows_affected($rs);
				$result = true;
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
				}

				$return = $rs;
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
		if (count($this->data) == 0)
			return null;

		return array_shift($this->data);
	}

	public function fetchRow ($rs, $conn)
	{
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
};
