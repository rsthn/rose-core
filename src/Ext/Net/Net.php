<?php
/*
**	Rose\Ext\Net
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

use Rose\Errors\Error;
use Rose\IO\Path;
use Rose\IO\Directory;
use Rose\IO\File;
use Rose\Expr;
use Rose\Arry;
use Rose\Map;
use Rose\Text;

/*
**	Small utility class to execute HTTP requests.
*/

class Http
{
	private static $curl_last_info = null;
	private static $curl_last_data = null;

	/**
	 * 	Indicates if request data should be output to the log file.
	 */
	public static $debug = false;

	/**
	 * 	HTTP request headers.
	 */
	private static $headers = null;

	/**
	 * 	Current request method for `fetch` function.
	 */
	public static $method = 'GET';

	/**
	**	Initializes the Http class properties.
	*/
	public static function init ()
	{
		self::$headers = new Map();

		self::header ('Accept: text/html,application/json,application/xhtml+xml,application/xml, */*');
	}

	/**
	**	Sets an HTTP header for subsequent requests.
	**
	**	@param $headerLine string
	*/
	public static function header ($headerLine)
	{
		self::$headers->set(Text::split(':', $headerLine)->get(0), $headerLine);
	}

	/*
	**	Returns the HTTP code of the last request.
	*/
	public static function getCode ()
	{
		return self::$curl_last_info['http_code'];
	}

	/*
	**	Returns the content-type of the last request.
	*/
	public static function getContentType ()
	{
		return self::$curl_last_info['content_type'];
	}

	/*
	**	Returns the data returned by the last request.
	*/
	public static function getData ()
	{
		return self::$curl_last_data;
	}

	/**
	**	Sets the HTTP Authorization header.
	**
	**	@param $method string|bool Either `Bearer` or `Basic`, or a `false` value to remove the authorization header.
	**	@param $username string The username when using `Basic` method, or the access token when using `Bearer`.
	**	@param $password string The password when using `Basic` method, or null when using `Bearer`.
	*/
	public static function auth ($method, $username=null, $password=null)
	{
		if (!$method)
		{
			self::$headers->remove('Authorization');
			return;
		}

		switch (Text::toLowerCase($method))
		{
			case 'basic':
				self::$headers->set('Authorization', 'Authorization: Basic ' . base64_encode($username . ':' . $password));
				break;
	
			case 'bearer':
				self::$headers->set('Authorization', 'Authorization: Bearer ' . $username);
				break;
		}

		return null;
	}

	/**
	**	Returns the result of executing a GET request.
	**
	**	@param $url string
	**	@param $fields Rose\Map
	**	@param $requestHeaders Rose\Map
	**	@param $method string
	*/
	public static function get ($url, $fields, $requestHeaders=null, $method='GET')
	{
		$c = curl_init();

		$method = Text::toUpperCase($method);

		$headers = new Map();
		$headers->merge(self::$headers, true);
		if ($requestHeaders) $headers->merge($requestHeaders, true);

		if (Text::indexOf($url, '?') === false)
			$url .= '?';

		$list = new Arry();

		$fields->forEach(function(&$value, $name) use(&$list)
		{
			if (\Rose\typeOf($value) != 'primitive')
				return;

			$list->push(urlencode($name) . '=' . urlencode($value));
		});

		$ch = Text::substring($url, -1);
		if ($ch != '?' && $ch != '&') $url .= '&';

		$url .= $list->join('&');

		curl_setopt ($c, CURLOPT_URL, $url);
		curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($c, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt ($c, CURLOPT_HTTPHEADER, $headers->values()->__nativeArray);

		if (self::$debug)
		{
			\Rose\trace($method.' ' . $url);
			\Rose\trace('HEADERS ' . $headers->values());
		}

		$data = curl_exec($c);

		self::$curl_last_info = curl_getinfo($c);
		self::$curl_last_data = $data;

		curl_close($c);
		return $data;
	}

	/**
	**	Returns the result of executing a POST request. The data parameter can be a Map or a string.
	**
	**	-	When a map is specified the data will be posted as "application/x-www-form-urlencoded", however note that if the map
	**		contains objects of the form { name, path } or { name, path, data } the content type will be set to "multipart/form-data"
	**		and files will be uploaded.
	**
	**	- When data is a string, it will be posted as a data stream as either "application/json" or "text/xml".
	**
	**	@param $url string
	**	@param $data Rose\Map or string
	**	@param $requestHeaders Rose\Map
	**	@param $method string
	*/
	public static function post ($url, $data, $requestHeaders=null, $method='POST')
	{
		$c = curl_init();

		$method = Text::toUpperCase($method);

		$headers = new Map();
		$headers->merge(self::$headers, true);
		if ($requestHeaders) $headers->merge($requestHeaders, true);

		$fields = new Map();
		$tempFiles = new Arry();

		if (\Rose\typeOf($data) == 'Rose\Map')
		{
			$data->forEach(function($value, $name) use(&$fields, &$tempFiles)
			{
				if (\Rose\typeOf($value) == 'Rose\Map')
				{
					if ($value->has('path'))
					{
						$path = Path::resolve($value->get('path'));

						if (!Path::exists($path))
							throw new Error ('File for field \''.$name.'\' not found.');

						if ($value->has('name'))
							$value = curl_file_create ($path, '', $value->get('name'));
						else
							$value = curl_file_create ($path, '', Path::basename($path));
					}
					else if ($value->has('name') && $value->has('data'))
					{
						$path = './tmp/uploads/'.Expr::call('utils::uuid', null).Path::extname($value->get('name'));
						File::setContents($path, $value->get('data'));

						$value = curl_file_create (Path::resolve($path), '', $value->get('name'));
						$tempFiles->push($path);
					}
					else
						return;
				}

				$fields->set($name, $value);
			});

			if ($tempFiles->length == 0 && (!$headers->has('content-type') || $headers->get('content-type') == 'content-type: application/x-www-form-urlencoded'))
			{
				$temp = $fields->map(function ($value, $name) {
					return urlencode($name) . '=' . urlencode($value);
				});

				$temp = $temp->values()->join('&');

				if (Text::length($temp) > 2048)
				{
					$headers->set('content-type', 'content-type: multipart/form-data');
					$fields = $fields->__nativeArray;
				}
				else
				{
					$headers->set('content-type', 'content-type: application/x-www-form-urlencoded');
					$fields = $temp;
				}
			}
			else
			{
				$headers->set('content-type', 'content-type: multipart/form-data');
				$fields = $fields->__nativeArray;
			}
		}
		else
		{
			if (!$headers->has('content-type'))
				$headers->set('content-type', 'content-type: ' . ($data[0] == '<' ? 'text/xml' : ($data[0] == '[' || $data[0] == '{' ? 'application/json' : 'application/octet-stream')));

			$fields = $data;
		}

		curl_setopt ($c, CURLOPT_URL, $url);
		curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($c, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt ($c, CURLOPT_HTTPHEADER, $headers->values()->__nativeArray);
		curl_setopt ($c, CURLOPT_POSTFIELDS, $fields);

		if (self::$debug)
		{
			\Rose\trace($method . ' ' . $url);
			\Rose\trace('HEADERS ' . $headers->values());
			\Rose\trace('FIELDS ' . (is_array($fields) ? new Map($fields) : $fields));
		}

		$data = curl_exec($c);

		$tempFiles->forEach(function($path) { File::remove($path); });

		self::$curl_last_info = curl_getinfo($c);
		self::$curl_last_data = $data;

		curl_close($c);

		return $data;
	}

	/**
	**	Forwards the parameters to fetchGet or fetchPost (based on current method), parses the JSON result and returns a Map or Arry.
	*/
	public static function fetch ($url, $fields)
	{
		$method = self::$method;
		self::$method = 'GET';

		return ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') ? self::fetchPost($url, $fields, $method) : self::fetchGet($url, $fields, $method);
	}

	/**
	**	Forwards the parameters to Http::get(), parses the JSON result and returns a Map or Arry.
	*/
	public static function fetchGet ($url, $fields, $method='GET')
	{
		return Expr::call('utils::json::parse', new Arry ([null, self::get($url, $fields, new Map([ 'Accept' => 'Accept: application/json' ]), $method)]));
	}

	/**
	**	Forwards the parameters to Http::post(), parses the JSON result and returns a Map or Arry.
	*/
	public static function fetchPost ($url, $data, $method='POST')
	{
		return Expr::call('utils::json::parse', new Arry ([null, self::post($url, $data, new Map([ 'Accept' => 'Accept: application/json' ]), $method)]));
	}

};

Http::init();

/* ****************** */
/* http::get <url> [<fields>*] */

Expr::register('http::get', function ($args)
{
	$fields = new Map();

	for ($i = 2; $i < $args->length; $i++)
	{
		$data = $args->get($i);

		if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
			continue;

		$fields->merge($data, true);
	}

	return Http::get($args->get(1), $fields, null);
});

/* ****************** */
/* http::post <url> [<fields>*] */

Expr::register('http::post', function ($args)
{
	$fields = new Map();

	for ($i = 2; $i < $args->length; $i++)
	{
		$data = $args->get($i);

		if (\Rose\typeOf($data, true) == 'string')
		{
			$fields = $data;
			break;
		}

		if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
			continue;

		$fields->merge($data, true);
	}

	return Http::post($args->get(1), $fields);
});

/* ****************** */
/* http::put <url> [<fields>*] */

Expr::register('http::put', function ($args)
{
	$fields = new Map();

	for ($i = 2; $i < $args->length; $i++)
	{
		$data = $args->get($i);

		if (\Rose\typeOf($data, true) == 'string')
		{
			$fields = $data;
			break;
		}

		if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
			continue;

		$fields->merge($data, true);
	}

	return Http::post($args->get(1), $fields, null, 'PUT');
});


/* ****************** */
/* http::fetch [<method>] <url> [<fields>] */

Expr::register('http::fetch', function ($args)
{
	$fields = new Map();

	$j = 1;

	switch (Text::toUpperCase($args->get($j)))
	{
		case 'GET': case 'PUT': case 'POST': case 'DELETE':
			Http::$method = Text::toUpperCase($args->get($j++));
			break;
	}

	for ($i = $j+1; $i < $args->length; $i++)
	{
		$data = $args->get($i);

		if (\Rose\typeOf($data, true) == 'string')
		{
			$fields = $data;
			break;
		}

		if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
			continue;

		$fields->merge($data, true);
	}

	return Http::fetch($args->get($j), $fields);
});

/* ****************** */
/* http::header header-line */

Expr::register('http::header', function ($args)
{
	Http::header($args->get(1));
	return null;
});

/* ****************** */
/* http::method method */

Expr::register('http::method', function ($args)
{
	Http::$method = Text::toUpperCase($args->get(1));
	return null;
});


/* ****************** */
/* http::debug value */

Expr::register('http::debug', function ($args)
{
	Http::$debug = \Rose\bool($args->get(1));
	return null;
});


/* ****************** */
/* http::auth basic <username> <password> */
/* http::auth basic <username> */
/* http::auth bearer <token> */
/* http::auth false */

Expr::register('http::auth', function ($args)
{
	if ($args->length == 2 && (!$args->get(1) || $args->get(1) == 'false'))
	{
		Http::auth (false);
		return null;
	}

	Http::auth ($args->get(1), $args->get(2), $args->{3});
	return null;
});

/* ****************** */
Expr::register('http::code', function ($args)
{
	return Http::getCode();
});

Expr::register('http::content-type', function ($args)
{
	return Http::getContentType();
});

Expr::register('http::data', function ($args)
{
	return Http::getData();
});
