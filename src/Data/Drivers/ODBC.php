<?php
/*
**	Rose\Data\Drivers\ODBC
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

class ODBC extends Driver
{
	private $affected_rows = 0;

	public static function register ()
	{
		Connection::registerDriver ('odbc', new ODBC());
	}

    public function open ($server, $user, $password, $database)
    {
		return odbc_connect ($server, $user, $password);
    }

    public function close ($conn)
    {
		return odbc_close ($conn);
    }

    public function getLastError ($conn)
    {
		return $conn ? odbc_errormsg($conn) : '(Undefined)';
    }

    public function getLastInsertId ($conn)
    {
		trace('WARNING: ODBC driver does not support geLastInsertId()');
		return 0;
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

		$rs = odbc_exec($conn, $query);
		if ($rs === false) return false;

		$this->affected_rows = odbc_num_rows($rs);
		if (odbc_num_fields($rs) == 0) return true;

		return $rs;
	}

    public function reader ($query, $conn)
	{
		$this->affected_rows = 0;

		$rs = odbc_exec($conn, $query);
		if ($rs === false) return false;

		$this->affected_rows = odbc_num_rows($rs);
		if (odbc_num_fields($rs) == 0) return true;

		return $rs;
	}

	public function getNumRows ($rs, $conn)
	{
		return odbc_num_rows ($rs);
	}

	public function getNumFields ($rs, $conn)
	{
		return odbc_num_fields($rs);
	}

	public function getFieldName ($rs, $i, $conn)
	{
		return odbc_field_name($rs, $i+1);
	}

	public function fetchAssoc ($rs, $conn)
	{
		$tmp = odbc_fetch_object($rs);

		if ($tmp === false || $tmp == null)
			$tmp = null;

		return (array)$tmp;
	}

	public function fetchRow ($rs, $conn)
	{
		$tmp = odbc_fetch_object ($rs);

		if ($tmp === false || $tmp == null)
			$tmp = null;

		return array_values((array)$tmp);
	}

	public function freeResult ($rs, $conn)
	{
		odbc_free_result ($rs);
	}

	public function escapeName ($value)
	{
		return Connection::escape($value);
	}

	public function escapeValue ($value)
	{
		return Connection::escape($value);
	}
};
