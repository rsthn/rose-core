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

/* ****************** */
class Net {
	static $curl_last_info = null;
	static $curl_last_data = null;

	static $headers = null;
};

Net::$headers = new Map();


/* ****************** */
/* http::get <url> [<fields>] [<fields>...] */

Expr::register('http::get', function ($args)
{
	$c = curl_init();

	$url = $args->get(1);

	if (Text::indexOf($url, '?') === false)
		$url .= '?';

	for ($i = 2; $i < $args->length; $i++)
	{
		$data = $args->get($i);

		if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
			continue;

		$list = new Arry();

		$data->forEach(function(&$value, $name) use(&$list)
		{
			if (\Rose\typeOf($value) == 'Rose\\Map')
				return;

			$list->push(urlencode($name) . '=' . urlencode($value));
		});

		$ch = Text::substring($url, -1);
		if ($ch != '?' && $ch != '&') $url .= '&';

		$url .= $list->join('&');
	}

	curl_setopt ($c, CURLOPT_URL, $url);
	curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($c, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt ($c, CURLOPT_HTTPHEADER, Net::$headers->values()->__nativeArray);

	$data = curl_exec($c);

	Net::$curl_last_info = curl_getinfo($c);
	Net::$curl_last_data = $data;

	curl_close($c);

	return $data;
});

/* ****************** */
/* http::post <url> [<fields>] */

Expr::register('http::post', function ($args)
{
	$c = curl_init();

	$url = $args->get(1);

	$fields = new Map();
	$files = new Arry();
	$raw_data = null;

	for ($i = 2; $i < $args->length; $i++)
	{
		$data = $args->get($i);

		if (\Rose\typeOf($data, true) == 'string')
		{
			$raw_data = $data;
			break;
		}

		if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
			continue;

		$data->forEach(function($value, $name) use(&$fields, &$files)
		{
			if (\Rose\typeOf($value) == 'Rose\\Map')
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
					Directory::create('./tmp/uploads');

					$path = './tmp/uploads/'.Expr::call('utils::uuid', null).Path::extname($value->get('name'));
					File::setContents($path, $value->get('data'));

					$value = curl_file_create (Path::resolve($path), '', $value->get('name'));
					$files->push($path);
				}
				else
					return;
			}

			$fields->set($name, $value);
		});
	}

	$headers = Net::$headers->values()->__nativeArray;

	if ($raw_data && !Net::$headers->has('Content-Type'))
		$headers[] = 'Content-Type: ' . ($raw_data[0] == '<' ? 'text/xml' : ($raw_data[0] == '[' || $raw_data[0] == '{' ? 'application/json' : ''));

	curl_setopt ($c, CURLOPT_URL, $url);
	curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($c, CURLOPT_POSTFIELDS, $raw_data ? $raw_data : $fields->__nativeArray);
	curl_setopt ($c, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt ($c, CURLOPT_HTTPHEADER, $headers);

	$data = curl_exec($c);

	$files->forEach(function($path) { File::remove($path); });

	Net::$curl_last_info = curl_getinfo($c);
	Net::$curl_last_data = $data;

	curl_close($c);

	return $data;
});


/* ****************** */
/* http::fetch <url> [<fields>] */

Expr::register('http::fetch', function ($args)
{
	$args = new Arry ([null, Expr::call('http::get', $args)]);
	return Expr::call('utils::json:parse', $args);
});


/* ****************** */
/* http::fetch:post <url> [<fields>] */

Expr::register('http::fetch:post', function ($args)
{
	$args = new Arry ([null, Expr::call('http::post', $args)]);
	return Expr::call('utils::json:parse', $args);
});


/* ****************** */
/* http::header header-line */

Expr::register('http::header', function ($args)
{
	$line = $args->get(1);
	Net::$headers->set(explode(':', $line)[0], $line);

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
		Net::$headers->remove('Authorization');
		return null;
	}

	switch (strtolower($args->get(1)))
	{
		case 'basic':
			Net::$headers->set('Authorization', 'Authorization: BASIC ' . base64_encode($args->get(2) . ':' . ($args->has(3) ? $args->get(3) : '')));
			break;

		case 'bearer':
			Net::$headers->set('Authorization', 'Authorization: BEARER ' . $args->get(2));
			break;
	}

	return null;
});

/* ****************** */
Expr::register('http::code', function ($args)
{
	return Net::$curl_last_info['http_code'];
});

Expr::register('http::content-type', function ($args)
{
	return Net::$curl_last_info['content-type'];
});

Expr::register('http::data', function ($args)
{
	return Net::$curl_last_data;
});
