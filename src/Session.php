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

require_once('Main.php');

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
	**	Primary and only instance of this class.
	*/
	private static $objectInstance = null;

	/*
	**	Indicates if session has been closed.
	*/
	public $sessionClosed;

	/*
	**	Contains the session data objects, the contents of this attribute will be filled by the framework initializer.
	*/
	public $data;

	/*
	**	Contains the session ID for the current session.
	*/
	public $sessionId;
	
	/*
	**	Contains the name of the session cookie.
	*/
	public $sessionName;
	
	/*
	**	Charset used by generateId() to generate session IDs.
	*/
    public $charset = '78JKLMFOPQRST0GHI6N12UV34WXYZ5MFOEBPQR9ACD';

	/*
	**	Returns the instance of this class.
	*/
    public static function getInstance ()
    {
		if (Session::$objectInstance == null)
			Session::$objectInstance = new Session();

        return Session::$objectInstance;
    }

	/*
	**	Constructs the Session object, this is a private constructor as this class can have only one instance.
	*/
	private function __construct()
	{
		$config = Configuration::getInstance();
		$gateway = Gateway::getInstance();

		$this->sessionClosed = true;
		$this->sessionName = '';
		$this->sessionId = '';

		// Load session name from the config unless parameter "sysnoss" (no session storage) is set to 1.
		if ($config->Session->name != null && !$gateway->requestParams->get('sysnoss'))
			$this->sessionName = $config->Session->name;

		// If session name was not specified use session-less method.
		if (!$this->sessionName)
		{
			$this->sessionClosed = true;
			$this->invalidate();
		}
		else
		{
			// Load session data from the database if configuration indicates so.
			if ($config->Session->database == 'true')
			{
				// Verify if m_<SessionName> was provided over POST or GET to override session id.
				if ($gateway->requestParams->has('m_'.$sess))
					$this->sessionId = $gateway->requestParams->get('m_'.$sess);
				else
					$this->sessionId = Cookies::getInstance()->get($sess);

				try {
					$this->dbSessionLoad();
				}
				catch (\Exception $e) {
					throw new Error ('Fatal: Unable to connect to database for session initialization.');
				}
			}
			// Load session data from regular PHP session storage.
			else
			{
				session_name ($this->sessionName);

				// Verify if m_<SessionName> was provided over POST or GET to override session id.
				if ($gateway->requestParams->has('m_'.$sess))
					$this->sessionId = $gateway->requestParams->get('m_'.$sess);

				try {
					session_set_cookie_params (0, gateway_root());
					session_start();
				}
				catch (\Exception $e) {
					session_regenerate_id();
					session_start();
				}

				if (isset($_SESSION['session']))
					$this->$data = unserialize ($_SESSION['session']);
				else
					$this->$data = new Map ();

				$this->sessionId = session_id();
			}
		}

        $expires = $config->Session->expires;
        if ($expires < 1) return;

        if ($this->$data->last_activity != null)
        {
			if ((new DateTime())->sub($this->$data->last_activity) > $expires)
                $this->invalidate();
        }

		$this->$data->last_activity = (string)(new DateTime());
    }

	/*
	**	Invalidates the session and destroys any information stored.
	*/
    public function invalidate()
    {
        $this->data = new Map();
    }

	/*
	**	Closes the session and flushes the data to storage.
	*/
    public function close()
    {
		if ($this->sessionClosed == true)
			return;

		if (Configuration::getInstance()->Session->database == 'true')
		{
			$this->dbSessionSave();
		}
		else
		{
			$_SESSION['session'] = serialize ($this->data);
			session_write_close();
		}
	
		$this->sessionClosed = true;
    }

	/*
	**	Generates a random ID of the desired length.
	*/
    private function generateId ($length)
    {
		$result = '';

		$n = Text::length ($this->charset);

        while ($length-- > 0)
            $result .= $this->charset[Math::rand() % $n];

        return $result;
    }

	/*
	**	Loads session data from the database. Fields sessionId and sessionName must already be set.
	*/
    public function dbSessionLoad ()
    {
		$conn = Resources::getInstance()->sqlConn;
		$create = false;

		// VIOLET Requires to use new parameter-based SQL execution.
		// VIOLET Requires: Filter
        $this->sessionId = Regex::_extract('/^['.$this->charset.']+$/', $this->sessionId);
        if (!$this->sessionId || Text::length($this->sessionId) != 32)
        {
            $this->data = new Map();
			$create = true;

            do { $this->sessionId = $this->generateId(32); }
            while (null != $conn->execAssoc('SELECT session_id FROM ##sessions WHERE session_id='.Filter::filter('escape', $this->sessionId)));
        }
        else
        {
            $this->data = $conn->execAssoc('SELECT * FROM ##sessions WHERE session_id='.Filter::filter('escape', $this->sessionId));
            if (!$this->data)
            {
                $this->data = new Map();
                $create = true;
            }
            else
            {
                try
                {
					$this->data = Filter::fromSerialized (Filter::fromDeflate ($this->data->data));
                    if (!$this->data || typeOf($this->data) != 'Rose\\Map')
                        $this->data = new Map();
                }
                catch (\Exception $e)
                {
                    $this->data = new Map();
                    trace('Session: ' + $e->getMessage());
                }
            }
		}

		// VIOLET Replace NOW() With a DateTime from local objects.
        if ($create == true)
            $conn->execQuery('INSERT INTO ##sessions SET last_activity=NOW(), session_id='.Filter::filter('escape', $this->sessionId).', data=\'\'');

        Cookies::getInstance()->setCookie ($this->sessionName, $this->sessionId, Configuration::getInstance()->Session->expires);
    }

	/*
	**	Saves the session data to the database. Fields sessionId and sessionName must already be set.
	*/
    public function dbSessionSave ()
    {
        $user_id = $this->data->has('CurrentUser') && $this->data->CurrentUser->user_id ? Filter::filter('escape', $this->data->CurrentUser->user_id) : 'NULL';
		$data = Filter::filter('xescape', Filter::toDeflate(Filter::toSerialized($this->data)));

        Resources::getInstance()->sqlConn->execQuery(
			'UPDATE ##sessions SET last_activity=NOW(), user_id='.$user_id.', data='.$data.' WHERE session_id='.Filter::filter('escape', $this->sessionId)
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
