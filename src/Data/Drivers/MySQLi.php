<?php
/*
**	Rose\Data\Drivers\MySQLi
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

class MySQLi extends Driver
{
	public static function register ()
	{
		Connection::registerDriver ('mysqli', new MySQLi());
	}

    public function open ($server, $user, $password, $database)
    {
		$conn = mysqli_init();
		if (!$conn) return null;

		if (!mysqli_real_connect ($conn, $server, $user, $password, $database))
			throw new Error (mysqli_connect_error());

		if ($conn == null) return null;

		mysqli_query ($conn, 'SET time_zone = \'+00:00\'');
		mysqli_query ($conn, 'SET @@group_concat_max_len = 16777216');

		try
		{
			if (!mysqli_set_charset($conn, 'UTF8MB4'))
				throw new Error ('Unsupported charset UTF8MB4');

			mysqli_query ($conn, 'SET collation_connection = \'utf8mb4_unicode_ci\'');
		}
		catch (\Exception $e)
		{
			mysqli_set_charset ($conn, 'UTF8');
			mysqli_query ($conn, 'SET collation_connection = \'utf8_unicode_ci\'');
		}

        return $conn;
    }

    public function close ($conn)
    {
		return mysqli_close ($conn);
    }

    public function getLastError ($conn)
    {
		return $conn ? mysqli_error($conn) : '(Undefined)';
    }

    public function getLastInsertId ($conn)
    {
		return mysqli_insert_id ($conn);
    }

    public function getAffectedRows ($conn)
    {
		return mysqli_affected_rows ($conn);
    }

    public function isAlive ($conn)
    {
		return mysqli_ping ($conn);
    }

    public function query ($query, $conn)
	{
        return mysqli_query ($conn, $query);
    }

    public function getNumRows ($rs, $conn)
    {
		return mysqli_num_rows ($rs);
    }

    public function getNumFields ($rs, $conn)
    {
        return mysqli_num_fields($rs);
    }

    public function getFieldName ($rs, $i, $conn)
    {
		mysqli_field_seek ($rs, $i);
		$rs = mysqli_fetch_field($rs);
		return $rs->name;
    }

    public function fetchAssoc ($rs, $conn)
    {
		$tmp = mysqli_fetch_assoc ($rs);

        if ($tmp === false || $tmp == null)
            $tmp = null;

        return $tmp;
    }

    public function fetchRow ($rs, $conn)
    {
		$tmp = mysqli_fetch_row ($rs);

        if ($tmp === false || $tmp == null)
            $tmp = null;

        return $tmp;
    }

    public function freeResult ($rs, $conn)
    {
		mysqli_free_result($rs);

		while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
		};
    }
};
