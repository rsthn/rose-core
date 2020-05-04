<?php
/*
**	Rose\Ext\Wind
**
**	Copyright (c) 2019-2020, RedStar Technologies, All rights reserved.
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

use Rose\IO\Path;
use Rose\IO\File;

use Rose\Gateway;
use Rose\Regex;
use Rose\Text;
use Rose\DateTime;
use Rose\Expr;
use Rose\Map;

use Rose\Resources;
use Rose\Session;
use Rose\Strings;
use Rose\Configuration;

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
	private static $data;

	private static $contentFlushed;
	private static $contentType;

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
		Gateway::registerService ('api', new WindProxy());

		Wind::$base = 'resources/api';
		Wind::$data = new Map();

		Wind::$contentFlushed = false;
		Wind::$contentType = null;
	}

	public static function reply ($response)
	{
		if (Wind::$contentFlushed)
			Gateway::exit();

		if (is_array($response))
			$response = new Map ($response);

		if (\Rose\typeOf($response) == 'Rose\\Map' || \Rose\typeOf($response) == 'Rose\Arry')
		{
			if (Wind::$contentType == null)
				Wind::$contentType = 'Content-Type: application/json; charset=utf-8';
		}
		else if (is_string($response) && strlen($response) != 0)
		{
			if (Wind::$contentType == null)
				Wind::$contentType = 'Content-Type: text/plain; charset=utf-8';
		}
		else
		{
			$response = $response ? (string)$response : null;
		}

		if ($response != null)
		{
			Gateway::header(Wind::$contentType);
			echo (string)$response;
		}

		Gateway::exit();
	}

	public static function process ($path)
	{
		$path = Path::append(Wind::$base, Text::replace('.', '/', $path));

		if (Path::exists($path))
		{
			Expr::eval(File::getContents($path), Wind::$data, 'obj');
		}
	}

	public static function main ()
	{
		$gateway = Gateway::getInstance();
		$params = $gateway->requestParams;

		$f = Regex::_extract ('/[#A-Za-z0-9.,_:|-]+/', $params->f);
		if (!$f) Wind::reply ([ 'response' => Wind::R_FUNCTION_NOT_FOUND ]);

		Wind::process($f);
	}

	public static function header ($args, $parts, $data)
	{
		Gateway::header($args->get(1));
		return null;
	}

	public static function contentType ($args, $parts, $data)
	{
		Wind::$contentType = 'Content-Type: ' . $args->get(1);
		return null;
	}

	public static function return ($args, $parts, $data)
	{
		Wind::reply ($args->get(1));
	}

	public static function stop ($args, $parts, $data)
	{
		Gateway::exit();
	}

	public static function loadTable ($args, $parts, $data)
	{
		return $data->{'data'} = Resources::getInstance()->Database->execQuery($args->get(1));
	}

	public static function loadData ($args, $parts, $data)
	{
		return $data->{'data'} = Resources::getInstance()->Database->execQuery($args->get(1))->rows;
	}

	public static function execQuery ($args, $parts, $data)
	{
		Resources::getInstance()->Database->execQuery($args->get(1));
		return null;
	}

	public static function _echo ($parts, $data)
	{
		if (!Wind::$contentFlushed)
		{
			Gateway::header(Wind::$contentType ? Wind::$contentType : 'Content-Type: text/plain; charset=utf-8');
			Wind::$contentFlushed = true;
		}

		for ($i = 1; $i < $parts->length(); $i++)
			echo (Expr::expand($parts->get($i), $data, 'arg') . ' ');

		echo "\n";

		return null;
	}
};

Expr::register('Session', function ($args) { return Session::$data; });
Expr::register('Configuration', function ($args) { return Configuration::getInstance(); });
Expr::register('Strings', function ($args) { return Strings::getInstance(); });
Expr::register('Resources', function ($args) { return Resources::getInstance(); });

Expr::register('Now', function ($args) { return new DateTime(); });

Expr::register('header', function(...$args) { return Wind::header(...$args); });
Expr::register('contentType', function(...$args) { return Wind::contentType(...$args); });
Expr::register('return', function(...$args) { return Wind::return(...$args); });
Expr::register('stop', function(...$args) { return Wind::stop(...$args); });
Expr::register('loadTable', function(...$args) { return Wind::loadTable(...$args); });
Expr::register('loadData', function(...$args) { return Wind::loadData(...$args); });
Expr::register('execQuery', function(...$args) { return Wind::execQuery(...$args); });
Expr::register('return', function(...$args) { return Wind::return(...$args); });
Expr::register('_echo', function(...$args) { return Wind::_echo(...$args); });

Wind::init();
