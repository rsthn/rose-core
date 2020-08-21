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
		$s = '';

		foreach(sqlsrv_errors() as $a)
			$s .= $a['message'].'<br/>';

		return $s;
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

    public function query ($query, $conn)
	{
		$this->affected_rows = 0;

		$rs = sqlsrv_query ($conn, $query, null, $this->options);
		if (!$rs) return null;

		if (!sqlsrv_field_metadata($rs))
		{
			$this->affected_rows = sqlsrv_rows_affected($rs);
			return true;
		}

		return $rs;
    }

	public function getNumRows ($rs, $conn)
	{
		return sqlsrv_num_rows ($rs);
	}

	public function getNumFields ($rs, $conn)
	{
		return sqlsrv_num_fields($rs);
	}

	public function getFieldName ($rs, $i, $conn)
	{
		$rs = sqlsrv_field_metadata($rs);
		return $rs[$i]['Name'];
	}

	public function fetchAssoc ($rs, $conn)
	{
		$tmp = sqlsrv_fetch_object($rs);

		if ($tmp === false || $tmp == null)
			$tmp = null;

		return (array)$tmp;
	}

	public function fetchRow ($rs, $conn)
	{
		$tmp = sqlsrv_fetch_array($rs, SQLSRV_FETCH_NUMERIC);

		if ($tmp === false || $tmp == null)
			$tmp = null;

		return $tmp;
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
