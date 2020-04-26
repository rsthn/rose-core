<?php
/*
**	Rose\Data\Drivers\MSSQL
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

class MSSQL extends Driver
{
	public static function register ()
	{
		Connection::registerDriver ('mssql', new MSSQL());
	}

    public function open ($server, $user, $password, $database)
    {
		$conn = mssql_connect ($server, $user, $password);
		if ($conn == null) return null;

		if ($database != null)
			mssql_select_db ($database, $conn);

        return $conn;
    }

    public function close ($conn)
    {
		return mssql_close ($conn);
    }

    public function getLastError ($conn)
    {
		return mssql_get_last_message();
    }

    public function getLastInsertId ($conn)
    {
		return $conn->execScalar('SELECT SCOPE_IDENTITY()');
    }

    public function getAffectedRows ($conn)
    {
		return mssql_rows_affected ($conn);
    }

    public function isAlive ($conn)
    {
		return true;
    }

    public function query ($query, $conn)
	{
		return mssql_query($query, $conn);
    }

	public function getNumRows ($rs, $conn)
	{
		return mssql_num_rows ($rs);
	}

	public function getNumFields ($rs, $conn)
	{
		return mssql_num_fields($rs);
	}

	public function getFieldName ($rs, $i, $conn)
	{
		return mssql_field_name($rs, $i);
	}

	public function fetchAssoc ($rs, $conn)
	{
		$tmp = mssql_fetch_assoc ($rs);

		if ($tmp === false || $tmp == null)
			$tmp = null;

		return $tmp;
	}

	public function fetchRow ($rs, $conn)
	{
		$tmp = mssql_fetch_row ($rs);

		if ($tmp === false || $tmp == null)
			$tmp = null;

		return $tmp;
	}

	public function freeResult ($rs, $conn)
	{
		mssql_free_result($rs);
	}
};
