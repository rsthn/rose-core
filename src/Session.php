<?php

namespace Rose;

use Rose\Errors\Error;
use Rose\Errors\FalseError;

use Rose\Data\Connection;

use Rose\Configuration;
use Rose\Resources;
use Rose\Text;
use Rose\Map;
use Rose\Math;
use Rose\DateTime;
use Rose\Gateway;
use Rose\Cookies;
use Rose\Regex;

/**
 * Stores and retrieves persistent session information. Data is stored on the database or on the session, controlled by Configuration.Session.database.
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
    **	Indicates if the active session ID is a valid session.
    */
    public static $validSessionId;
    
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
        Session::$validSessionId = false;

        Session::$sessionName = Configuration::getInstance()->Session;
        if (Session::$sessionName) Session::$sessionName = Session::$sessionName->name;

        Session::$sessionId = '';

        // Verify if m_<SessionName> was provided over POST or GET to override session id.
        if (Session::$sessionName)
        {
            if (Gateway::getInstance()->request->has('m_'.Session::$sessionName))
                Session::$sessionId = Gateway::getInstance()->request->get('m_'.Session::$sessionName);
            else
                Session::$sessionId = Cookies::get(Session::$sessionName);

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

        if (!Session::$validSessionId) {
            Session::open(false);
            if (!Session::$validSessionId)
                return;
        }

        if (Configuration::getInstance()->Session && Configuration::getInstance()->Session->database === 'true') {
            Session::dbSessionDelete();
        }

        Cookies::remove(Session::$sessionName);

        Session::$sessionOpen = false;
        Session::$validSessionId = false;
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

        $conf = Configuration::getInstance()->Session;

        // Load session data from the database if specified in the configuration field 'Session.database'.
        if ($conf && $conf->database === 'true')
        {
            try {
                if (!Session::dbSessionLoad($createSession))
                    return false;
            }
            catch (\Throwable $e) {
                throw new Error ('Fatal: Unable to connect to database for session initialization.');
            }
        }
        // Load session data from regular PHP session storage.
        else
        {
            if (session_name() !== Session::$sessionName)
                session_name(Session::$sessionName);

            if (Session::$sessionId)
                session_id(Session::$sessionId);

            session_cache_limiter(null);

            try {
                if (!Session::$sessionId)
                    throw new FalseError();

                session_start(['use_cookies' => 0, 'use_only_cookies' => 0]);
            }
            catch (FalseError $e)
            {
                if (!$createSession)
                    return false;

                session_id(Session::generateId(48));
                session_start(['use_cookies' => 0, 'use_only_cookies' => 0]);
            }

            if (!isset($_SESSION['session']))
            {
                if (!$createSession) {
                    Cookies::remove(Session::$sessionName);
                    return false;
                }

                Session::$data = new Map();
            }
            else
                Session::$data = unserialize($_SESSION['session']) ?? new Map();

            Session::$sessionId = session_id();
        }

        Cookies::set(Session::$sessionName, Session::$sessionId, $conf ? $conf->expires : 0);

        $expires = $conf ? $conf->expires : 0;
        if ($expires > 0) {
            if (Session::$data->last_activity != null) {
                if ((new DateTime())->sub(Session::$data->last_activity) > $expires)
                    Session::clear();
            }
        }

        Session::$data->last_activity = (string)(new DateTime());
        Session::$sessionOpen = true;
        Session::$validSessionId = true;
        return true;
    }

    /**
     * Closes the session and flushes the data to storage. If shallow is true, only internal fields of the session will be saved.
     */
    public static function close ($shallow=false)
    {
        if (Session::$sessionOpen == false)
            return;

        if (Configuration::getInstance()->Session->database === 'true') {
            Session::dbSessionSave($shallow);
        }
        else {
            $_SESSION['session'] = serialize(Session::$data);
            session_write_close();
        }

        Session::$sessionOpen = false;
    }

    /**
     * Writes the session data to persistent storage, does not require the session to be open but the session ID must be valid.
     */
    public static function write ($shallow=false)
    {
        if (Configuration::getInstance()->Session->database !== 'true')
            throw new Error ('Immediate session write is available only with database storage');

        if (!Session::$sessionId)
            throw new Error ('Session ID is not set');

        Session::dbSessionSave($shallow);
    }

    /*
    **	Generates a random session ID of the specified length.
    */
    private static function generateId ($m)
    {
        $n = (int)(($m*5+7) / 8) + 1;

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

            Session::$sessionId = Session::generateId(48);
            Session::$data = new Map();
            $create = true;
        }
        else
        {
            Session::$data = $conn->execAssoc("SELECT * FROM ##sessions WHERE session_id=".Connection::escape(Session::$sessionId));
            if (!Session::$data) {
                Session::$data = new Map();
                $create = true;
                if (!$createSession)
                    return false;
            }
            else {
                try {
                    Session::$data = Map::fromNativeArray(json_decode(base64_decode (Session::$data->data), true));
                    if (!Session::$data || \Rose\typeOf(Session::$data) !== 'Rose\\Map')
                        Session::$data = new Map();
                }
                catch (\Throwable $e) {
                    Session::$data = new Map();
                    \Rose\trace ('(Error: Session): ' . $e->getMessage());
                }
            }
        }

        if ($create == true)
            $conn->execQuery("INSERT INTO ##sessions (created_at, session_id, data) VALUES ('".(string)(new DateTime())."', ".Connection::escape(Session::$sessionId).", '')");

        return true;
    }

    /*
    **	Saves the session data to the database. Fields sessionId and sessionName must already be set. When 'shallow' is true, only internal session fields will be saved.
    */
    public static function dbSessionSave ($shallow)
    {
        $user_id = Session::$data->has('user') && Session::$data->user->user_id ? Connection::escape(Session::$data->user->user_id) : 'NULL';
        $device_id = Session::$data->has('device_id') ? Connection::escape(Session::$data->device_id) : 'NULL';
        $last_activity = Session::$data->has('last_activity') ? Connection::escape(Session::$data->last_activity) : 'NULL';

        if (!$shallow) {
            $data = Connection::escape(base64_encode((string)(Session::$data)));
            Resources::getInstance()->Database->execQuery(
                "UPDATE ##sessions SET last_activity=".$last_activity.", user_id=".$user_id.", device_id=".$device_id.", data=".$data." WHERE session_id=".Connection::escape(Session::$sessionId)
            );
        }
        else {
            Resources::getInstance()->Database->execQuery(
                "UPDATE ##sessions SET last_activity=".$last_activity." WHERE session_id=".Connection::escape(Session::$sessionId)
            );
        }
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
    CREATE TABLE sessions
    (
        session_id VARCHAR(48) PRIMARY KEY NOT NULL,

        created_at DATETIME DEFAULT NULL,
        last_activity DATETIME DEFAULT NULL,

        device_id INT DEFAULT NULL,
        INDEX (device_id),

        user_id INT DEFAULT NULL FOREIGN KEY REFERENCES users (user_id),
        data VARCHAR(8192) DEFAULT NULL
    )
    ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE EVENT evt_session_cleanup
    ON SCHEDULE EVERY 3600 SECOND
    STARTS CONCAT(CURDATE(), ' 00:00:00')
    DO  DELETE FROM sessions
        WHERE TIMESTAMPDIFF(SECOND, last_activity, NOW()) >= 604800;
*/
