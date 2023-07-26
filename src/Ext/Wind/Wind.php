<?php
/*
**	Rose\Ext\Wind
**
**	Copyright (c) 2019-2021, RedStar Technologies, All rights reserved.
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

namespace Rose\Ext;

use Rose\Errors\FalseError;
use Rose\Errors\Error;
use Rose\Errors\MetaError;

use Rose\IO\Directory;
use Rose\IO\Path;
use Rose\IO\File;

use Rose\Gateway;
use Rose\Regex;
use Rose\Text;
use Rose\DateTime;
use Rose\Expr;
use Rose\Arry;
use Rose\Map;
use Rose\Math;

use Rose\Resources;
use Rose\Session;
use Rose\Strings;
use Rose\Configuration;

use Rose\Ext\Wind\SubReturn;
use Rose\Ext\Wind\WindError;

use Rose\Main;

/*
**	Wind extension.
*/

class WindProxy
{
	public function main() {
		Wind::main();
	}
};

class Wind
{
	private static $base;
	private static $cache;
	public static $data;

	public static $multiResponseMode;
	public static $contentFlushed;
	public static $contentType;
	public static $response;

	public static $callStack;

	public const R_OK 						= 200;
	public const R_FUNCTION_NOT_FOUND 		= 400;
	public const R_DATABASE_ERROR			= 401;
	public const R_FORBIDDEN 				= 402;
	public const R_PRIVILEGE_REQUIRED		= 403;
	public const R_NOT_FOUND				= 404;

	public const R_VALIDATION_ERROR			= 407;
	public const R_NOT_AUTHENTICATED		= 408;
	public const R_CUSTOM_ERROR				= 409;
	public const R_INVALID_DATA				= 410;

	public static function init()
	{
		Gateway::registerService ('wind', new WindProxy());

		self::$base = Main::$CORE_DIR.'/wind';
		self::$cache = 'volatile/wind';

		self::$callStack = new Arry();
	
		self::$contentFlushed = false;
		self::$contentType = null;
		self::$multiResponseMode = 0;
	}

	public static function prepare ($response)
	{
		if (\Rose\isArray($response))
			$response = new Map ($response);

		if (\Rose\typeOf($response) == 'Rose\\Map' || \Rose\typeOf($response) == 'Rose\\Arry')
		{
			if (\Rose\typeOf($response) == 'Rose\\Arry')
			{
				$response = new Map([ 'response' => Wind::R_OK, 'data' => $response ], false);
			}
			else
			{
				if (!$response->has('response'))
				{
					$tmp = new Map([ 'response' => Wind::R_OK ]);
					$tmp->merge ($response, true);
					$response = $tmp;
				}
			}
		}
		else if (\Rose\isString($response))
		{
		}
		else
		{
			$response = $response ? (string)$response : null;
		}

		return $response;
	}

	public static function reply ($response)
	{
		if (self::$data->internal_call != 0)
		{
			self::$response = $response;
			throw new SubReturn();
		}

		if (self::$contentFlushed)
			Gateway::exit();

		$response = self::prepare($response);

		if (\Rose\typeOf($response) == 'Rose\Map' || \Rose\typeOf($response) == 'Rose\Arry')
		{
			if (self::$contentType == null)
				self::$contentType = 'Content-Type: application/json; charset=utf-8';
		}
		else if (\Rose\isString($response) && strlen($response) != 0)
		{
			if (self::$contentType == null)
				self::$contentType = 'Content-Type: text/plain; charset=utf-8';
		}

		self::$response = $response;

		if (self::$multiResponseMode)
			throw new FalseError();

		if ($response != null)
		{
			Gateway::header(self::$contentType);
			echo (string)$response;
		}

		if (self::$data->internal_call != 0)
			throw new SubReturn();

		Gateway::exit();
	}

	public static function resetContext ()
	{
		self::$data = new Map();
		self::$data->internal_call = 0;
	}

	public static function process ($path)
	{
		if ($path[0] == '@')
			$path = self::$callStack->get(self::$callStack->length-1)[0].$path;

		$path1 = Path::append(self::$base, Text::replace('.', '/', $path) . '.fn');
		$path2 = Path::append(self::$cache, $path.'.fn');

		self::$response = null;

		if (Path::exists($path2) && Path::exists($path1) && File::mtime($path2, true) == File::mtime($path1, true))
		{
			$expr = unserialize(File::getContents($path2));
		}
		else if (Path::exists($path1))
		{
			$expr = Expr::clean(Expr::parse(File::getContents($path1)));

			File::setContents($path2, serialize($expr));
			File::touch($path2, File::mtime($path1, true));
		}
		else
			throw new WindError ([ 'response' => self::R_FUNCTION_NOT_FOUND, 'error' => Strings::get('@messages.function_not_found') . ': ' . $path ]);

		$tmp = Text::split('.', $path);
		$tmp->pop();
		$tmp = $tmp->join('.').'.';

		self::$callStack->push([ $tmp, $path, $path1 ]);

		$response = Expr::expand($expr, self::$data, 'last');

		self::$callStack->pop();

		if ($response != null)
			self::reply ($response);
	}

	/**
	 * Runs a violet expression script in CLI-mode.
	 */
	public static function run ($path, $data=null)
	{
		self::$data = $data ? $data : new Map();
		self::$data->internal_call = 1;

		self::$response = null;

		if (Path::exists($path))
			$expr = Expr::clean(Expr::parse(File::getContents($path)));
		else
			throw new Error (Strings::get('@messages.file_not_found') . ': ' . $path);

		try {
			$response = Expr::expand($expr, self::$data, 'last');

			if ($response != null)
				self::reply ($response);
		}
		catch (SubReturn $e)
		{
			if (!self::$contentFlushed)
				echo self::$response;
		}
		catch (FalseError $e) {
		}
	}

	private static function requiresJsonReply()
	{
		return strstr($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
	}

	public static function main ()
	{
		$gateway = Gateway::getInstance();
		$params = $gateway->requestParams;

		// Handle OPTIONS request.
		if ($gateway->method === 'OPTIONS')
			return;

		// Handle regular requests.
		if ($params->rpkg != null || $params->mreq != null)
		{
			$requests = Text::split (';', $params->rpkg != null ? $params->rpkg : $params->mreq);

			self::$multiResponseMode = 1;

			$r = new Map ();
			$n = 0;

			$originalParams = $params;

			foreach ($requests->__nativeArray as $i)
			{
				$i = Text::trim($i);
				if (!$i) continue;

				$i = Text::split (',', $i);
				if ($i->length != 2) continue;

				try {
					$gateway->requestParams->clear()->merge($originalParams, true);
					parse_str(base64_decode($i->get(1)), $requestParams);
					$gateway->requestParams->__nativeArray = $gateway->requestParams->__nativeArray + $requestParams;
				}
				catch (\Throwable $e) {
					\Rose\trace('Error: '.$e->getMessage());
					continue;
				}

				if (++$n > 64) break;

				try
				{
					self::resetContext();

					$f = Regex::_extract ('/[#A-Za-z0-9.,_-]+/', $gateway->requestParams->f);
					if (!$f) {
						if (!$gateway->requestParams->has('f'))
							throw new WindError ([ 'response' => self::R_OK, 'message' => Strings::get('@messages.operational') ]);
						else
							throw new WindError ([ 'response' => self::R_FUNCTION_NOT_FOUND, 'message' => Strings::get('@messages.function_not_found') . ': ' . $gateway->requestParams->f ]);
					}

					self::process($f);
				}
				catch (FalseError $e) {
				}
				catch (WindError $e) {
					self::$response = self::prepare($e->getResponse());
				}
				catch (MetaError $e)
				{
					switch ($e->code)
					{
						case 'EXPR_YIELD':
							self::$response = self::prepare($e->value);
							break;

						case 'FN_RET':
							self::$response = self::prepare($e->value);
							break;
		
						default:
							throw $e;
					}
				}
				catch (\Throwable $e) {
					self::$response = new Map([ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ]);
				}

				$r->set($i->get(0), self::$response);
			}

			self::$multiResponseMode = 0;
			self::reply($r);
		}

		if ($gateway->relativePath)
			$params->f = Text::replace('/', '.', Text::trim($gateway->relativePath, '/'));

		try
		{
			self::resetContext();

			$f = Regex::_extract ('/[#A-Za-z0-9.,_-]+/', $params->f);
			if (!$f) {
				if (!$params->has('f'))
					throw new WindError ([ 'response' => self::R_OK, 'message' => Strings::get('@messages.operational') ]);
				else
					throw new WindError ([ 'response' => self::R_FUNCTION_NOT_FOUND, 'message' => Strings::get('@messages.function_not_found') . ': ' . $params->f ]);
			}

			self::process($f);
		}
		catch (FalseError $e) {
		}
		catch (WindError $e) {
			self::reply ($e->getResponse());
		}
		catch (MetaError $e)
		{
			switch ($e->code)
			{
				case 'EXPR_YIELD':
				case 'FN_RET':
					self::reply ($e->value);
					break;

				default:
					throw $e;
			}
		}
		catch (\Throwable $e)
		{
			if (self::requiresJsonReply())
				self::reply ([ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ]);
			else
				throw $e;
		}
	}

	/**
	**	header <header-line>
	*/
	public static function header ($args, $parts, $data)
	{
		if (Text::toUpperCase(Text::substring($args->get(1), 0, 12)) == 'CONTENT-TYPE')
			self::$contentType = $args->get(1);

		Gateway::header($args->get(1));
		return null;
	}

	/**
	**	contentType <mime>
	*/
	public static function contentType ($args, $parts, $data)
	{
		self::$contentType = 'Content-Type: ' . $args->get(1);
		return null;
	}

	/**
	**	return [<data>]
	*/
	public static function _return ($args, $parts, $data)
	{
		self::reply ($args->length > 1 ? $args->get(1) : new Map());
	}

	/**
	**	stop [<object-expr>]
	*/
	public static function stop ($args, $parts, $data)
	{
		self::$data->internal_call = 0;

		if ($args->length > 1)
			self::reply ($args->get(1));

		Gateway::exit();
	}

	/**
	**	echo <message> [<message>...]
	*/
	public static function _echo ($parts, $data, $addNewLine)
	{
		$s = '';

		for ($i = 1; $i < $parts->length(); $i++)
			$s .= Expr::expand($parts->get($i), $data, 'arg');

		if ($addNewLine)
			$s .= "\n";

		if (!self::$contentFlushed)
		{
			try {
				Gateway::header(self::$contentType ? self::$contentType : 'Content-Type: text/plain; charset=utf-8'); }
			catch (\Throwable $e) {
			}

			self::$contentFlushed = true;
		}

		echo $s;

		return null;
	}

	/**
	**	trace <message> [<message>...]
	*/
	public static function _trace ($parts, $data)
	{
		$s = '';

		for ($i = 1; $i < $parts->length(); $i++)
			$s .= ' ' . Expr::expand($parts->get($i), $data, 'arg');

		if ($s != '')
			\Rose\trace(Text::substring($s, 1));

		return null;
	}

	/**
	**	trace::alt <output> <message> [<message>...]
	*/
	public static function _trace_alt ($parts, $data)
	{
		$s = '';

		for ($i = 2; $i < $parts->length(); $i++)
			$s .= ' ' . Expr::expand($parts->get($i), $data, 'arg');

		if ($s != '')
			\Rose\trace(Text::substring($s, 1), '@'.Expr::expand($parts->get(1), $data, 'arg').'.log');

		return null;
	}

	/**
	**	call <fnname> [:<name> <expr>...]
	*/
	public static function _call ($parts, $data)
	{
		self::$data->internal_call = 1 + self::$data->internal_call;
		$response = null;
		$n_args = null;
		$p_args = null;

		try {
			$n_args = Expr::getNamedValues($parts, $data, 2);

			$p_args = self::$data->args;
			self::$data->args = $n_args;

			self::process($name = Expr::expand($parts->get(1), $data));
		}
		catch (SubReturn $e) {
			$response = self::$response;
		}
		catch (FalseError $e) {
			self::$data->args = $p_args;
			throw $e;
		}
		catch (WindError $e) {
			self::$data->internal_call = self::$data->internal_call - 1;
			self::$data->args = $p_args;
			throw $e;
		}
		catch (MetaError $e)
		{
			switch ($e->code)
			{
				case 'EXPR_YIELD':
				case 'FN_RET':
					$response = $e->value;
					break;

				default:
					throw $e;
			}
		}
		catch (\Throwable $e)
		{
			self::$data->internal_call = self::$data->internal_call - 1;
			self::$data->args = $p_args;

			if (self::requiresJsonReply())
				throw new WindError ([ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ]);
			else
				throw $e;
		}

		self::$data->internal_call = self::$data->internal_call - 1;
		self::$data->args = $p_args;

		return $response;
	}

	/**
	**	icall <fnname> [:<name> <expr>...]
	*/
	public static function _icall ($parts, $data)
	{
		self::$data->internal_call = 1 + self::$data->internal_call;

		$response = null;
		$p_data = self::$data;
		self::$data = new Map();

		try {
			self::$data->internal_call = $p_data->internal_call;
			self::$data->args = Expr::getNamedValues($parts, $data, 2);
			self::process($name = Expr::expand($parts->get(1), $data));
		}
		catch (SubReturn $e) {
			$response = self::$response;
		}
		catch (FalseError $e) {
			self::$data = $p_data;
			throw $e;
		}
		catch (WindError $e) {
			self::$data = $p_data;
			self::$data->internal_call = self::$data->internal_call - 1;
			throw $e;
		}
		catch (MetaError $e)
		{
			switch ($e->code)
			{
				case 'EXPR_YIELD':
				case 'FN_RET':
					$response = $e->value;
					break;

				default:
					throw $e;
			}
		}
		catch (\Throwable $e)
		{
			self::$data = $p_data;
			self::$data->internal_call = self::$data->internal_call - 1;

			if (self::requiresJsonReply())
				throw new WindError ([ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ]);
			else
				throw $e;
		}

		self::$data = $p_data;
		self::$data->internal_call = self::$data->internal_call - 1;

		return $response;
	}

	private static $eventsEnabled = false;
	private static $lastSent = null;

	/**
	 * evt::init
	 */
	public static function enableEvents ()
	{
		if (self::$eventsEnabled)
			return;

		self::$eventsEnabled = true;
		self::$lastSent = time();

		self::$contentType = 'text/event-stream; charset=utf-8';

		Gateway::header("Content-Type: text/event-stream; charset=utf-8");
		Gateway::header("Transfer-Encoding: identity");
		Gateway::header("Content-Encoding: identity");
		Gateway::header("Cache-Control: no-store");
		Gateway::header("X-Accel-Buffering: no");

		Gateway::persistent();
		Gateway::flush();
	}

	/**
	 * evt::send [<event-name>] <data>
	 */
	public static function sendEvent ($args, $parts, $data)
	{
		if (!self::$eventsEnabled)
			return false;

		$s = '';
		$i = 2;

		if ($args->length > 2)
			$s = "event: ".$args->get(1)."\n";
		else
			$i = 1;

		$i = $args->get($i);

		if (\Rose\typeOf($i) == 'Rose\\Arry' || \Rose\typeOf($i) == 'Rose\\Map')
			$i = (string)$i;

		$s .= "data: " . $i . "\n\n";
		echo $s;

		self::$lastSent = time();
		return true;
	}

	/**
	 * evt::alive
	 */
	public static function eventsAlive ()
	{
		if (!self::$eventsEnabled)
			return false;

		$now = time();
		if ($now - self::$lastSent >= 30) {
			echo ": alive\n\n";
			self::$lastSent = time();
		}

		return Gateway::connected();
	}
};

/* ****************************************************************************** */
Expr::register('header', function(...$args) { return Wind::header(...$args); });
Expr::register('content-type', function(...$args) { return Wind::contentType(...$args); });

Expr::register('evt::init', function(...$args) { return Wind::enableEvents(); });
Expr::register('evt::send', function(...$args) { return Wind::sendEvent(...$args); });
Expr::register('evt::alive', function(...$args) { return Wind::eventsAlive(); });

Expr::register('stop', function(...$args) { return Wind::stop(...$args); });
Expr::register('return', function(...$args) { return Wind::_return(...$args); });
Expr::register('_echo', function($parts, $data) { return Wind::_echo($parts, $data, true); });
Expr::register('_print', function($parts, $data) { return Wind::_echo($parts, $data, false); });
Expr::register('_trace', function(...$args) { return Wind::_trace(...$args); });
Expr::register('_trace::alt', function(...$args) { return Wind::_trace_alt(...$args); });
Expr::register('_call', function(...$args) { return Wind::_call(...$args); });
Expr::register('_icall', function(...$args) { return Wind::_icall(...$args); });

/* ****************************************************************************** */
Wind::init();
