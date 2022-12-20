<?php
/*
**	Rose\Data\Drivers\MySQL
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

class MySQL extends Driver
{
	public static function register ()
	{
		Connection::registerDriver ('mysql', new MySQL());
	}

    public function open ($server, $user, $password, $database)
    {
		$conn = mysql_connect ($server, $user, $password);
		if ($conn == null) return null;

		if ($database != null)
			mysql_select_db ($database, $conn);

		mysql_query('SET time_zone = \'+00:00\'', $conn);
		mysql_query('SET @@group_concat_max_len = 16777216', $conn);

		try
		{
			if (!mysql_set_charset('UTF8MB4', $conn))
				throw new Error ('Unsupported charset UTF8MB4');

			mysql_query ('SET collation_connection = \'utf8mb4_unicode_ci\'', $conn);
		}
		catch (\Throwable $e)
		{
			mysql_set_charset ('UTF8', $conn);
			mysql_query ('SET collation_connection = \'utf8_unicode_ci\'', $conn);
		}

        return $conn;
    }

    public function close ($conn)
    {
		return mysql_close ($conn);
    }

    public function getLastError ($conn)
    {
		return mysql_error();
    }

    public function getLastInsertId ($conn)
    {
		return mysql_insert_id ($conn);
    }

    public function getAffectedRows ($conn)
    {
		return mysql_affected_rows ($conn);
    }

    public function isAlive ($conn)
    {
		return mysql_ping ($conn);
    }

    public function query ($query, $conn)
	{
        return mysql_query ($query, $conn);
    }

    public function getNumRows ($rs, $conn)
    {
		return mysql_num_rows ($rs);
    }

    public function getNumFields ($rs, $conn)
    {
        return mysql_num_fields($rs);
    }

    public function getFieldName ($rs, $i, $conn)
    {
        return mysql_field_name($rs, $i);
    }

    public function fetchAssoc ($rs, $conn)
    {
		$tmp = mysql_fetch_assoc ($rs);

        if ($tmp === false || $tmp == null)
            $tmp = null;

        return $tmp;
    }

    public function fetchRow ($rs, $conn)
    {
		$tmp = mysql_fetch_row ($rs);

        if ($tmp === false || $tmp == null)
            $tmp = null;

        return $tmp;
    }

    public function freeResult ($rs, $conn)
    {
        mysql_free_result($rs);
	}

	public function escapeName ($value)
	{
		return Connection::escape($value, '`', '`', null, null, null, null);
	}

	public function escapeValue ($value)
	{
		return Connection::escape($value);
	}
};
