<?php
/*
**	Rose Framework Initializer
**
**	Copyright (c) 2010-2025, RedStar Technologies, All rights reserved.
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
use Rose\Errors\FalseError;

use Rose\Configuration;
use Rose\Map;
use Rose\Expr;
use Rose\DateTime;
use Rose\Gateway;
use Rose\Text;
use Rose\IO\Directory;
use Rose\IO\Path;
use Rose\Ext\Wind;

/**
 * Prints a tracing message to the log file.
 */
function trace ($string, $out='@system.log')
{
    static $paths = null;

    if ($out[0] == '@')
        $out = Main::$CWD . '/' . (Main::$CORE_DIR !== '.' ? Path::append(Main::$CORE_DIR, '../logs/'.Text::substring($out, 1)) : ('./logs/' . Text::substring($out, 1)));

    if (!$paths)
        $paths = new Map();

    $path = Path::dirname($out);
    if (!$paths->has($path)) {
        if (!Path::exists($path))
            Directory::create($path, true);
        $paths->set($path, true);
    }

    try {
        $fp = @fopen ($out, 'a+t');
        if (!$fp) return;

        fwrite ($fp, $string);
        fwrite ($fp, "\n");

        fclose ($fp);
    }
    catch (\Exception $e) {
        if (!Text::endsWith($out, 'errors.log'))
            \Rose\trace($e->getMessage(), '@errors.log');
    }
}

/**
 * Returns the class name of the given object.
 */
function typeOf ($object, $detailed=false)
{
    if (is_object ($object)) {
        if ($object instanceof \Closure)
            return 'function';
        return get_class($object);
    }

    if ($detailed)
    {
        if (is_null($object))
            return 'null';

        if (is_string($object))
            return 'string';

        if (is_bool($object))
            return 'bool';

        if (is_array($object))
            return 'array';

        if (is_int($object))
            return 'int';

        if (is_numeric($object))
            return 'number';

        if (is_callable($object))
            return 'function';
    }

    return 'primitive';
}

/**
 * Returns `true` if the value is an array.
 */
function isArray ($value) {
    return is_array ($value);
}

/**
 * Returns `true` if the value is an object.
 */
function isObject ($value) {
    return is_object ($value);
}

/**
 * Returns `true` if the value is a string.
 */
function isString ($value) {
    return is_string ($value);
}

/*
**	Returns true if the value is a numeric value.
*/
function isNumeric ($value) {
    return is_numeric ($value);
}

/**
 * Returns `true` if the value is an integer value.
 */
function isInteger ($value) {
    return is_int ($value);
}

/**
 * Returns `true` if the value represents a number, ensure to use a `float` or `double` cast when actually using
 * the value, since it may contain extra characters after the number.
 */
function isNumber ($value) {
    return is_double ($value);
}

/**
 * Returns `true` if the value is a boolean value.
 */
function isBool ($value) {
    return is_bool ($value);
}

/**
 * Returns the boolean value of the given argument.
 */
function bool ($value) {
    if ($value === true || $value === false)
        return $value;

    return $value === 'true' || ($value !== 'false' && !!$value);
}

/**
 * Raises a warning.
 */
function raiseWarning ($message) {
    trigger_error ($message, E_USER_WARNING);
}

/**
 * Raises an error.
 */
function raiseError ($message) {
    trigger_error ($message, E_USER_ERROR);
}

/**
 * Returns true if the object is an instance of the given class.
 */
function isSubTypeOf ($object, $className) {
    return $object instanceof $className;
}

/**
 * Returns the current time in milliseconds.
 */
function mstime() {
    $t = explode (' ', microtime ());
    return (int) (($t[0] + $t[1]) * 1000);
}

/**
 * Global error handler.
 */
function error_handler ($errno, $error, $file, $line) {
    throw new Error ($error . sprintf (' (%s %u)', $file, $line));
}

/**
 * 	Calls ini_set silently such that any errors will be inhibited.
 */
function silent_ini_set ($name, $value)
{
    try {
        ini_set($name, $value);
    }
    catch (\Throwable $e) {
    }
}

/*
**	Fatal error handler.
*/
function fatal_handler()
{
    global $lastException;

    $error = error_get_last();
    $stackTrace = null;

    if ($error != null && ($error['type'] == E_NOTICE || $error['type'] == E_WARNING))
        $error = null;

    if ($error == null && $lastException == null)
        return;

    ob_end_clean();

    $err_id = (string)time();
    $err_id = substr($err_id, 0, 4) . chr(rand(65,90)) . substr($err_id, 3, 3) . chr(rand(65,90)) . substr($err_id, 6);
    $s = '[' . date('Y-m-d h:i') . '] ' . $err_id . ': ';
    $tab = '  ';
    $msg = '';

    if ($error != null)
    {
        $s .= 'Fatal Error ' . $error['type'] . ' in ' . basename($error['file']) . ':' . $error['line'] . "\n";

        $msg = trim(Regex::_getString('/.*?:(.+) in/', $error['message'], 1));
        if ($msg) {
        }
        else {
            $msg = $error['message'];
            $s .= $tab . '*** ' . $msg . " ***\n";
        }
    }

    if ($lastException != null)
    {
        $msg = $lastException->getMessage();
        $s .= typeOf($lastException) . ' in ' . basename($lastException->getFile()) . ':' . $lastException->getLine() . "\n";
        $s .= $tab . '*** ' . $msg . " ***\n";
        $stackTrace = $lastException->getTrace();
    }

    if (Wind::$callStack->length > 0)
        $s .= $tab . 'Wind: ' . Wind::$callStack->map(function($i) { return $i[2]; })->join(", ") . "\n";

    if ($stackTrace != null)
    {
        foreach ($stackTrace as $err)
        {
            if (isset($err['class']))
                $s .= $tab . $err['class'] . ' :: ' . $err['function'];
            else
                $s .= $tab . $err['function'];

            $s .= ' (';
            if (isset($err['args']))
            {
                for ($i = 0; $i < count($err['args']); $i++)
                {
                    $s .= typeOf($err['args'][$i], true);

                    if ($i != count($err['args']) - 1)
                        $s .= ', ';
                }
            }
            $s .= ')';

            if (isset($err['file']))
                $s .= ' ' . basename($err['file']) . ':' . $err['line'];

            $s .= "\n";
        }
    }

    \Rose\trace($s, '@errors.log');

    if (!headers_sent())
        header('content-type: application/json');

    if (Configuration::getInstance()?->Gateway?->display_errors !== 'false')
        echo (new Map([ 'response' => 409, 'error' => $msg ]));
    else
        echo (new Map([ 'response' => 409, 'error' => 'Unhandled error occurred: '.$err_id ]));

    exit;
}

/**
 * Framework entry point.
 */
class Main
{
    /**
     * Returns the name of the framework.
     */
    static function name() {
        return 'rsthn/rose-core';
    }

    /**
     * Returns the version of the framework.
     */
    static function version() {
        return '5.0.15'; //@version
    }

    /**
     * Project's core directory.
     */
    public static $CORE_DIR = null;

    /**
     * Original current working directory.
     */
    public static $CWD = null;

    /**
     * Constant that indicates if the framework has been loaded and initialized. Used as a dummy var to force autoload of the Main class by using `Main::$loaded`.
     */
    public static $loaded = true;

    /**
     * Sets the global definitions and PHP configuration. Called by `cli` or `initialize`.
     */
    static function defs ($cliMode=false)
    {
        // Configure PHP environment.
        gc_disable();
        ignore_user_abort(false);
        umask(0);
        error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        mt_srand ((int)(((double)microtime ()) * 10000));
        set_time_limit ($cliMode ? 0 : 300);

        // Set global error handlers and disable PHP error output.
        if (!$cliMode) {
            set_error_handler ('Rose\\error_handler', E_STRICT | E_USER_ERROR | E_WARNING | E_USER_WARNING);
            register_shutdown_function ('Rose\\fatal_handler');
        }

        silent_ini_set ('display_errors', '0');

        // Set global project core directory (use 'resources' for legacy systems, 'rcore' for Rose 3.1+ and '.' for 4.1+).
        if (self::$CORE_DIR == null)
        {
            if (file_exists('resources/'))
                self::$CORE_DIR = 'resources';
            else if (file_exists('rcore/'))
                self::$CORE_DIR = 'rcore';
            else
                self::$CORE_DIR = '.';
        }

        self::$CWD = str_replace('\\', '/', getcwd());
    }

    /**
     * Initializes the primary framework classes for CLI operation.
     */
    static function cli ($fsroot, $keepSafes=false)
    {
        // Load certain required classes that are not auto-loaded in CLI mode.
        require_once 'IO/File.php';

        Main::defs(!$keepSafes);
        Expr::$cachePath = null;

        ignore_user_abort(true);
        set_time_limit(0);

        try {
            Gateway::getInstance()->init(true, $fsroot);
        }
        catch (\Throwable $e) {
            $lastException = $e;
        }

        register_shutdown_function (function() {
            Gateway::getInstance()->close();
        });
    }

    /**
     * Initializes the primary framework classes and passes control to the Gateway. If `$callback` is not null,
     * it will be executed after the Gateway's main().
     */
    static function initialize ($fsroot, $callback)
    {
        Main::defs();

        // Load specially encoded request if "req64" parameter is set.
        if (isset($_REQUEST['req64'])) {
            parse_str (base64_decode ($_REQUEST['req64']), $tmp);
            $_REQUEST = array_merge($_REQUEST, $tmp);
        }

        $ms_start = mstime();

        global $lastException;
        $lastException = null;

        try
        {
            Gateway::getInstance()->init(false, $fsroot);

            try {
                Gateway::getInstance()->main();
            }
            catch (FalseError $e) {
            }

            if ($callback != null)
                $callback();

            Gateway::getInstance()->close();
        }
        catch (\Throwable $e) {
            $lastException = $e;
        }

        $ms_end = mstime();

        if (Configuration::getInstance()?->Gateway?->access_log == 'true')
            trace (sprintf ('%s   %7.2f MB   %6d ms   %s   %s', (string)(new DateTime()), memory_get_peak_usage()/1048576, $ms_end-$ms_start, $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI']), '@access.log');
    }
};
