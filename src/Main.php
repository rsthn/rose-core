<?php
/*
**	Rose Framework Initializer
**
**	Copyright (c) 2010-2020, RedStar Technologies, All rights reserved.
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

use Rose\Session;
use Rose\Configuration;
use Rose\Map;
use Rose\Gateway;

/*
**	Prints a tracing message into the log file.
*/
function trace ($string, $out=null)
{
	if (!$out) $out = Configuration::getInstance()->Gateway->logFile;
	if (!$out) $out = 'resources/system.log';

	$fp = @fopen ($out, 'a+t');
	if (!$fp) return;

	fwrite ($fp, $string);
	fwrite ($fp, "\n");

	fclose ($fp);
}

/*
**	Returns the class name of the given object.
*/
function typeOf ($object)
{
	if (is_object ($object))
		return get_class($object);

	return 'PrimitiveType';
}

/*
**	Returns true if the object is an string.
*/
function isString ($object)
{
	return is_string ($object);
}

/*
**	Raises a warning.
*/
function raiseWarning ($message)
{
	trigger_error ($message, E_USER_WARNING);
}

/*
**	Raises an error.
*/
function raiseError ($message)
{
	trigger_error ($message, E_USER_ERROR);
}

/*
**	Returns the relative root (same as Gateway.Root)
*/
function gateway_root ()
{
	return substr($_SERVER['SCRIPT_NAME'], 0, -9);
}

/*
**	Returns true if the object is an instance of the given class.
*/
function isSubTypeOf ($object, $className)
{
	return $object instanceof $className;
}

/*
**	Returns the current time in milliseconds.
*/
function mstime ()
{
	$t = explode (' ', microtime ());
	return (int) (($t[0] + $t[1]) * 1000);
}

/*
**	Global error handler/
*/
function error_handler ($errno, $error, $file, $line)
{
	throw new Error ($error . sprintf (' (%s %u)', $file, $line));
}

/*
**	Framework entry point.
*/
class Main
{
	/*
	**	Initializes the primary framework classes and passes control to the Gateway. If $callback is not null, it will be executed
	**	after Gateway's main().
	*/
	static function initialize($callback=null)
	{
		// Configure PHP environment.
		gc_disable();
		ignore_user_abort (false);
		umask(0);
		error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED);
		mt_srand (((double)microtime ()) * 10000);

		// Activate strip slashes if magic_quotes_gpc is enabled, but issue a warning since that is not required in this framework.
		if (get_magic_quotes_gpc ())
		{
			trace ('WARNING: magic_quotes_gpc is enabled, please disable or performance will not be optimal.');

			function stripslashes_gpc(&$value) {
				$value = stripslashes($value);
			}

			array_walk_recursive ($_GET, 'stripslashes_gpc');
			array_walk_recursive ($_POST, 'stripslashes_gpc');
			array_walk_recursive ($_COOKIE, 'stripslashes_gpc');
			array_walk_recursive ($_REQUEST, 'stripslashes_gpc');
		}

		// Load specially encoded request if "req64" parameter is set.
		if (isset($_REQUEST['req64']))
		{
			parse_str (base64_decode ($_REQUEST['req64']), $tmp);
			$_REQUEST = array_merge($_REQUEST, $tmp);
		}

		
		// Set global error handler.
		set_error_handler ('Rose\\error_handler', E_STRICT | E_WARNING | E_USER_ERROR | E_USER_WARNING);

		$ms_start = mstime ();

		Gateway::getInstance();
		Session::getInstance();

		try
		{
			Gateway::getInstance()->main();

			if ($callback != null)
				$callback();
		}
		catch (FalseError $e0)
		{
		}
		catch (\Exception $e1)
		{
			ob_end_clean();
			trace ($e1 = 'ERROR: ' . $e1->getMessage());
			echo $e1;
		}

		Session::getInstance()->close();
		Gateway::getInstance()->close();

		$ms_end = mstime();

		// trace (sprintf ("%.4f MB,%s,%.5f", memory_get_peak_usage () / 1048576, $_SERVER['REQUEST_URI'], ($ms_end - $ms_start)/1000), "timing.csv");
	}
};
