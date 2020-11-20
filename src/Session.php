<?php
/*
**	Rose\Session
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

namespace Rose;

use Rose\Errors\Error;

use Rose\Data\Connection;

use Rose\Configuration;
use Rose\Resources;
use Rose\Filter;
use Rose\Text;
use Rose\Map;
use Rose\Math;
use Rose\DateTime;
use Rose\Gateway;
use Rose\Cookies;
use Rose\Regex;

/*
**	Stores and retrieves persistent session information. Data is stored on the database or on the session, controlled by Configuration.Session.database.
*/

class Session
{
	/*
	**	Indicates if session is open.
	*/
	public static $sessionOpen;

	/*
	**	Contains the session data objects, the contents of this attribute will be filled by the framework initializer.
	*/
	public static $data;

	/*
	**	Contains the session ID for the current session.
	*/
	public static $sessionId;
	
	/*
	**	Contains the name of the session cookie.
	*/
	public static $sessionName;
	
	/*
	**	Charset used by generateId() to generate session IDs (ensure there are 48 characters).
	*/
    public static $charset = 'uY3m8v7fJegDp4cnq6zb0w9kaRxhST25';

	/*
	**	Initializes the Session singleton.
	*/
    public static function init ()
    {
		Session::$data = new Map();

		Session::$sessionOpen = false;
		Session::$sessionName = Configuration::getInstance()->Session->name;
		Session::$sessionId = '';

		// Verify if m_<SessionName> was provided over POST or GET to override session id.
		if (Session::$sessionName)
		{
			if (Gateway::getInstance()->requestParams->has('m_'.Session::$sessionName))
				Session::$sessionId = Gateway::getInstance()->requestParams->get('m_'.Session::$sessionName);
			else
				Session::$sessionId = Cookies::getInstance()->get(Session::$sessionName);

			Session::$sessionId = Regex::_extract('/^['.Session::$charset.']+$/', Session::$sessionId);

			if (!Session::$sessionId || Text::length(Session::$sessionId) != 48)
				Session::$sessionId = '';
		}
    }

	/*
	**	Invalidates the session data and destroys any related information (cookies included).
	*/
    public static function destroy()
    {
		Session::$data = new Map();

		if (Session::$sessionOpen == false)
			return;

		if (Configuration::getInstance()->Session->database == 'true')
		{
			Session::dbSessionDelete();
		}
		else
		{
			session_destroy();
		}

		Cookies::getInstance()->remove(Session::$sessionName);
		Session::$sessionOpen = false;
	}

	/*
	**	Clears the session data.
	*/
    public static function clear ()
    {
		Session::$data = new Map();
    }

	/*
	**	Opens the session by loading data from session storage (database or PHP's session). The createSession parameter
	**	controls whether the session will be loaded if exists only, or if it will be created if it doesn't exist.
	*/
    public static function open ($createSession=true)
    {
		if (Session::$sessionOpen == true || !Session::$sessionName)
			return true;

		// Load session data from the database if specified in the configuration field 'Session.database'.
		if (Configuration::getInstance()->Session->database == 'true')
		{
			try {
				if (!Session::dbSessionLoad($createSession))
					return false;
			}
			catch (\Exception $e) {
				throw new Error ('Fatal: Unable to connect to database for session initialization.');
			}
		}
		// Load session data from regular PHP session storage.
		else
		{
			session_name (Session::$sessionName);

			if (Session::$sessionId)
				session_id (Session::$sessionId);

			try {
				if (!Session::$sessionId)
					throw new \Exception();

				session_cache_limiter(false);
				session_start([ 'use_cookies' => 0 ]);
			}
			catch (\Exception $e)
			{
				if (!$createSession)
					return false;

				session_id (Session::generateId(48));
				session_cache_limiter(false);
				session_start([ 'use_cookies' => 0 ]);
			}

			if (!isset($_SESSION['session']))
			{
				if (!$createSession)
				{
					Cookies::getInstance()->remove(Session::$sessionName);
					session_destroy();
					return false;
				}

				Session::$data = new Map ();
			}
			else
				Session::$data = unserialize ($_SESSION['session']);

			Session::$sessionId = session_id();
		}

		Cookies::getInstance()->setCookie (Session::$sessionName, Session::$sessionId, Configuration::getInstance()->Session->expires);

        $expires = Configuration::getInstance()->Session->expires;
		if ($expires > 0)
		{
			if (Session::$data->last_activity != null)
			{
				if ((new DateTime())->sub(Session::$data->last_activity) > $expires)
					Session::clear();
			}
		}

		Session::$data->last_activity = (string)(new DateTime());
		Session::$sessionOpen = true;

		return true;
    }

	/*
	**	Closes the session and flushes the data to storage. If nosave is true, session will be closed but no data will be saved.
	*/
    public static function close($nosave=false)
    {
		if (Session::$sessionOpen == false)
			return;

		if (Configuration::getInstance()->Session->database == 'true')
		{
			if (!$nosave)
				Session::dbSessionSave();
		}
		else
		{
			if (!$nosave)
				$_SESSION['session'] = serialize (Session::$data);
				
			session_write_close();
		}
	
		Session::$sessionOpen = false;
    }

	/*
	**	Generates a random session ID of the specified length.
	*/
    private static function generateId ($m)
    {
		$n = (int)(($m*5+7) / 8);

		$value = 0;
		$state = 0;

		$bytes = random_bytes($n);
		$s = '';

		for ($i = 0; $m > 0; $i++, $m--)
		{
			$val = ord($bytes[$i]);

			switch ($state)
			{
				case 0: // $value = 0-bits
					$value = (($val >> 5) & 0x07) << 0;
					$val &= 0x1F;
					$state++;
					break;

				case 1: // $value = 3-bits
					$value |= (($val >> 5) & 0x07) << 3;
					$val &= 0x1F;
					$state++;
					break;

				case 2: // $value = 6-bits
					$val = $value & 0x1F;
					$value >>= 5;
					$state++; $i--;
					break;

				case 3: // $value = 1-bits
					$value |= (($val >> 5) & 0x07) << 1;
					$val &= 0x1F;
					$state++;
					break;

				case 4: // $value = 4-bits
					$value |= (($val >> 5) & 0x07) << 4;
					$val &= 0x1F;
					$state++;
					break;

				case 5: // $value = 7-bits
					$val = $value & 0x1F;
					$value >>= 5;
					$state++; $i--;
					break;

				case 6: // $value = 2-bits
					$value |= (($val >> 5) & 0x07) << 2;
					$val &= 0x1F;
					$state++;
					break;

				case 7: // $value = 5-bits
					$val = $value & 0x1F;
					$value >>= 5;
					$state = 0; $i--;
					break;
			}

			$s .= Session::$charset[$val];
		}

		return $s;
    }

	/*
	**	Loads session data from the database. Fields sessionId and sessionName must already be set.
	*/
    public static function dbSessionLoad ($createSession)
    {
		$conn = Resources::getInstance()->Database;
		$create = false;

		Session::$sessionId = Regex::_extract('/^['.Session::$charset.']+$/', Session::$sessionId);
        if (!Session::$sessionId || Text::length(Session::$sessionId) != 48)
        {
			if (!$createSession)
				return false;

            Session::$data = new Map();
			$create = true;

            Session::$sessionId = Session::generateId(48);
        }
        else
        {
            Session::$data = $conn->execAssoc("SELECT * FROM ##sessions WHERE session_id=".Connection::escape(Session::$sessionId));
            if (!Session::$data)
            {
				if (!$createSession)
					return false;

                Session::$data = new Map();
                $create = true;
            }
            else
            {
                try
                {
					Session::$data = Map::fromNativeArray(json_decode(base64_decode (Session::$data->data), true));
                    if (!Session::$data || \Rose\typeOf(Session::$data) != 'Rose\\Map')
                        Session::$data = new Map();
                }
                catch (\Exception $e)
                {
                    Session::$data = new Map();
                    \Rose\trace ('(Error: Session): ' . $e->getMessage());
                }
            }
		}

        if ($create == true)
            $conn->execQuery("INSERT INTO ##sessions SET created='".(string)(new DateTime())."', last_activity='".(string)(new DateTime())."', session_id=".Connection::escape(Session::$sessionId).", data=''");

		return true;
    }

	/*
	**	Saves the session data to the database. Fields sessionId and sessionName must already be set.
	*/
    public static function dbSessionSave ()
    {
        $user_id = Session::$data->has('user') && Session::$data->user->user_id ? Connection::escape(Session::$data->user->user_id) : 'NULL';
        $device_id = Session::$data->has('device_id') ? Connection::escape(Session::$data->device_id) : 'NULL';
		$data = Connection::escape(base64_encode((string)(Session::$data)));

        Resources::getInstance()->Database->execQuery(
			"UPDATE ##sessions SET last_activity='".(string)(new DateTime())."', user_id=".$user_id.", device_id=".$device_id.", data=".$data." WHERE session_id=".Connection::escape(Session::$sessionId)
		);
	}

	/*
	**	Deletes the session data from the database.
	*/
    public static function dbSessionDelete ()
    {
        Resources::getInstance()->Database->execQuery(
			"DELETE FROM ##sessions WHERE session_id=".Connection::escape(Session::$sessionId)
		);
    }
};

/*
	**NOTE** : utf8mb4_bin collation is required to make lowercase and uppercase distinction.

	DROP TABLE IF EXISTS sessions;
	CREATE TABLE sessions
	(
		session_id varchar(48) primary key unique not null,

		created datetime default null,
		last_activity datetime default null,

		device_id varchar(48) default null,

		user_id int unsigned default null,
		constraint foreign key (user_id) references users (user_id) on delete cascade,

		data varchar(8192) default null
	)
	ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

	ALTER TABLE sessions ADD INDEX n_device_id (device_id);


	DROP PROCEDURE IF EXISTS session_cleanup;
	DELIMITER //
	CREATE PROCEDURE session_cleanup (timeout INT UNSIGNED)
	BEGIN
		DELETE FROM sessions
		WHERE TIMESTAMPDIFF(SECOND, last_activity, NOW()) >= timeout;
	END //
	DELIMITER ;

	DROP EVENT IF EXISTS session_cleanup_evt;
	CREATE EVENT session_cleanup_evt
	ON SCHEDULE EVERY 1 DAY
	DO CALL session_cleanup (86400);
*/
