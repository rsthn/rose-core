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
	**	Indicates if session has been closed.
	*/
	public static $sessionClosed;

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
	**	Charset used by generateId() to generate session IDs.
	*/
    public static $charset = '78JKLMFOPQRST0GHI6N12UV34WXYZ5MFOEBPQR9ACD';

	/*
	**	Initializes the Session singleton.
	*/
    public static function init ()
    {
		$config = Configuration::getInstance();
		$gateway = Gateway::getInstance();

		Session::$sessionClosed = false;
		Session::$sessionName = '';
		Session::$sessionId = '';

		// Load session name from the config unless parameter "sysnoss" (no session storage) is set to 1.
		if ($config->Session->name != null && !$gateway->requestParams->get('sysnoss'))
			Session::$sessionName = $config->Session->name;

		// If session name was not specified use session-less method.
		if (!Session::$sessionName)
		{
			Session::$sessionClosed = true;
			Session::invalidate();
		}
		else
		{
			// Load session data from the database if configuration indicates so.
			if ($config->Session->database == 'true')
			{
				// Verify if m_<SessionName> was provided over POST or GET to override session id.
				if ($gateway->requestParams->has('m_'.Session::$sessionName))
					Session::$sessionId = $gateway->requestParams->get('m_'.Session::$sessionName);
				else
					Session::$sessionId = Cookies::getInstance()->get(Session::$sessionName);

				try {
					Session::dbSessionLoad();
				}
				catch (\Exception $e) {
					throw new Error ('Fatal: Unable to connect to database for session initialization.');
				}
			}
			// Load session data from regular PHP session storage.
			else
			{
				session_name (Session::$sessionName);

				// Verify if m_<SessionName> was provided over POST or GET to override session id.
				if ($gateway->requestParams->has('m_'.Session::$sessionName))
					Session::$sessionId = $gateway->requestParams->get('m_'.Session::$sessionName);

				try {
					session_set_cookie_params (0, gateway_root());
					session_start();
				}
				catch (\Exception $e) {
					session_regenerate_id();
					session_start();
				}

				if (isset($_SESSION['session']))
					Session::$data = unserialize ($_SESSION['session']);
				else
					Session::$data = new Map ();

				Session::$sessionId = session_id();
			}
		}

        $expires = $config->Session->expires;
        if ($expires < 1) return;

        if (Session::$data->last_activity != null)
        {
			if ((new DateTime())->sub(Session::$data->last_activity) > $expires)
                Session::invalidate();
        }

		Session::$data->last_activity = (string)(new DateTime());
    }

	/*
	**	Invalidates the session and destroys any information stored.
	*/
    public static function invalidate()
    {
        Session::$data = new Map();
    }

	/*
	**	Closes the session and flushes the data to storage.
	*/
    public static function close()
    {
		if (Session::$sessionClosed == true)
			return;

		if (Configuration::getInstance()->Session->database == 'true')
		{
			Session::dbSessionSave();
		}
		else
		{
			$_SESSION['session'] = serialize (Session::$data);
			session_write_close();
		}
	
		Session::$sessionClosed = true;
    }

	/*
	**	Generates a random ID of the desired length.
	*/
    private static function generateId ($length)
    {
		$result = '';

		$n = Text::length (Session::$charset);

        while ($length-- > 0)
            $result .= Session::$charset[Math::rand() % $n];

        return $result;
    }

	/*
	**	Loads session data from the database. Fields sessionId and sessionName must already be set.
	*/
    public static function dbSessionLoad ()
    {
		$conn = Resources::getInstance()->Database;
		$create = false;

		// VIOLET Requires to use new parameter-based SQL execution.
		// VIOLET Requires: Filter
        Session::$sessionId = Regex::_extract('/^['.Session::$charset.']+$/', Session::$sessionId);
        if (!Session::$sessionId || Text::length(Session::$sessionId) != 32)
        {
            Session::$data = new Map();
			$create = true;

            do { Session::$sessionId = Session::generateId(32); }
            while (null != $conn->execAssoc('SELECT session_id FROM ##sessions WHERE session_id='.Filter::filter('escape', Session::$sessionId)));
        }
        else
        {
            Session::$data = $conn->execAssoc('SELECT * FROM ##sessions WHERE session_id='.Filter::filter('escape', Session::$sessionId));
            if (!Session::$data)
            {
                Session::$data = new Map();
                $create = true;
            }
            else
            {
                try
                {
					Session::$data = Filter::fromSerialized (Filter::fromDeflate (Session::$data->data));
                    if (!Session::$data || typeOf(Session::$data) != 'Rose\\Map')
                        Session::$data = new Map();
                }
                catch (\Exception $e)
                {
                    Session::$data = new Map();
                    trace('Session: ' + $e->getMessage());
                }
            }
		}

		// VIOLET Replace NOW() With a DateTime from local objects.
        if ($create == true)
            $conn->execQuery('INSERT INTO ##sessions SET last_activity=NOW(), session_id='.Filter::filter('escape', Session::$sessionId).', data=\'\'');

        Cookies::getInstance()->setCookie (Session::$sessionName, Session::$sessionId, Configuration::getInstance()->Session->expires);
    }

	/*
	**	Saves the session data to the database. Fields sessionId and sessionName must already be set.
	*/
    public static function dbSessionSave ()
    {
        $user_id = Session::$data->has('currentUser') && Session::$data->currentUser->user_id ? Filter::filter('escape', Session::$data->currentUser->user_id) : 'NULL';
		$data = Filter::filter('xescape', Filter::toDeflate(Filter::toSerialized(Session::$data)));

        Resources::getInstance()->Database->execQuery(
			'UPDATE ##sessions SET last_activity=NOW(), user_id='.$user_id.', data='.$data.' WHERE session_id='.Filter::filter('escape', Session::$sessionId)
		);
    }
};

/*
	**NOTE** : utf8mb4_bin collation is required to make lowercase and uppercase distinction.

	DROP TABLE IF EXISTS sessions;
	CREATE TABLE sessions
	(
		session_id char(32) primary key unique not null,
		last_activity datetime default null,

		user_id int unsigned default null,
		constraint foreign key (user_id) references users (user_id) on delete cascade,

		data varbinary(4096) default null
	)
	ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

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
