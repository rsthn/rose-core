<?php
/*
**	Rose\Ext\Misc
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

use Rose\Resources;
use Rose\Strings;
use Rose\Configuration;
use Rose\Gateway;

use Rose\Text;
use Rose\Math;
use Rose\Regex;
use Rose\Expr;
use Rose\Map;
use Rose\Arry;

Expr::register('configuration', function ($args) { return Configuration::getInstance(); });
Expr::register('config', function ($args) { return Configuration::getInstance(); });
Expr::register('c', function ($args) { return Configuration::getInstance(); });

Expr::register('strings', function ($args)
{
	if ($args->length == 1)
		return Strings::getInstance();

	return Strings::get($args->get(1));
});

Expr::register('s', function ($args)
{
	if ($args->length == 1)
		return Strings::getInstance();

	return Strings::get($args->get(1));
});

Expr::register('s::lang', function ($args)
{
	if ($args->length == 1)
		return Strings::getInstance()->lang;

	Strings::getInstance()->setLang($args->get(1));
	return null;
});

Expr::register('resources', function ($args) { return Resources::getInstance(); });

Expr::register('gateway', function ($args) { return Gateway::getInstance(); });
Expr::register('gateway::redirect', function ($args) { return Gateway::redirect($args->get(1)); });
Expr::register('gateway::flush', function ($args) { return Gateway::flush(); });

Expr::register('utils::rand', function() { return Math::rand(); });
Expr::register('utils::randstr', function($args) { return bin2hex(random_bytes((int)$args->get(1))); });
Expr::register('utils::randstr:base64', function($args) { return base64_encode(random_bytes((int)$args->get(1))); });
Expr::register('utils::uuid', function() {
	$data = random_bytes(16);

	$tmp = explode(' ', microtime());
	$tmp[0] = (int)($tmp[0] * 0xFFFF);
	$tmp[1] = (int)$tmp[1];

	$data[15] = chr(($tmp[0] & 0xFF00) >> 8);
	$data[14] = chr(($tmp[1] & 0xFF000000) >> 24);
	$data[13] = chr(($tmp[1] & 0x00FF0000) >> 16);
	$data[12] = chr(($tmp[0] & 0x00FF) >> 0);
	$data[11] = chr(($tmp[1] & 0x000000FF) >> 0);
	$data[10] = chr(($tmp[1] & 0x0000FF00) >> 8);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
});

Expr::register('utils::unique', function($args) {

	$tmp = explode(' ', microtime());
	$tmp[0] = ((int)($tmp[0] * 0x1000000)) & 0xFFFFFF;
	$tmp[1] = ((int)$tmp[1]) & 0xFFFFFFFF;

	$data = [
		(($tmp[1] >> 24) & 0x3F),
		(($tmp[1] >> 0) & 0x3F),
		(($tmp[1] >> 12) & 0x3F),
		(($tmp[1] >> 18) & 0x3F),
		(($tmp[1] >> 6) & 0x3F),
		(($tmp[0] >> 6) & 0x3F),
		(((($tmp[1] >> 30) & 0x03) << 4) | (($tmp[0] >> 12) & 0x0F)),
		(($tmp[0] >> 0) & 0x3F),
	];

	$n = $args->length > 1 ? (int)$args->get(1) : 0;

	while ($n-- > 8)
		$data[] = ord(random_bytes(1)) & 0x3F;

	$tmp = '';
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789._';

	for ($i = 0; $i < count($data); $i++)
		$tmp .= $chars[ $data[$i] ^ (ord(random_bytes(1)) & 0x3F) ];

	return $tmp;
});

Expr::register('utils::sleep', function($args) { sleep($args->get(1)); return null; });

Expr::register('utils::base64:encode', function($args) { return base64_encode ($args->get(1)); });
Expr::register('utils::base64:decode', function($args) { return base64_decode ($args->get(1)); });

Expr::register('utils::hex:encode', function($args) { return bin2hex ($args->get(1)); });
Expr::register('utils::hex:decode', function($args) { return hex2bin ($args->get(1)); });

Expr::register('utils::url:encode', function($args) { return urlencode ($args->get(1)); });
Expr::register('utils::url:decode', function($args) { return urldecode ($args->get(1)); });

Expr::register('utils::html:encode', function($args) { return htmlspecialchars ($args->get(1)); });
Expr::register('utils::html:decode', function($args) { return htmlspecialchars_decode ($args->get(1)); });

Expr::register('utils::json:stringify', function($args)
{
	$value = $args->get(1);

	if (\Rose\typeOf($value) == 'Rose\\Arry' || \Rose\typeOf($value) == 'Rose\\Map')
		return (string)$value;

	return json_encode($value);
});

Expr::register('utils::json:prettify', function($args)
{
	$value = $args->get(1);

	if (\Rose\typeOf($value) == 'Rose\\Arry' || \Rose\typeOf($value) == 'Rose\\Map')
		$value = (string)$value;

	if (\Rose\typeOf($value) == 'primitive')
		$value = json_decode($value, true);

	return json_encode($value, JSON_PRETTY_PRINT);
});

Expr::register('utils::json:parse', function($args)
{
	$value = $args->get(1);
	return $value[0] == '[' ? Arry::fromNativeArray(json_decode($value, true)) : ($value[0] == '{' ? Map::fromNativeArray(json_decode($value, true)) : json_decode($value, true));
});

Expr::register('utils::html', function($args)
{
	$data = $args->get(1);

	if (\Rose\typeOf($data) == 'Rose\Data\Reader')
	{
		$s = '';

		foreach ($data->fields->__nativeArray as $name)
		{
			$s .= "<th>$name</th>";
		}

		$s = "<tr>$s</tr>";

		$data->forEach(function ($row) use (&$s)
		{
			$i = '';

			foreach ($row->__nativeArray as $col)
				$i .= "<td>$col</td>";

			$s .= '<tr>'.nl2br($i).'</tr>';
		});

		return "<table style='font-family: monospace;' border='1'>$s</table>";
	}

	if (\Rose\typeOf($data) == 'Rose\Arry')
	{
		if (!$data->length) return '';

		$s = '';

		foreach ($data->get(0)->keys()->__nativeArray as $name)
		{
			$s .= "<th>$name</th>";
		}

		$s = "<tr>$s</tr>";

		$data->forEach(function ($row) use (&$s)
		{
			$i = '';

			foreach ($row->__nativeArray as $col)
				$i .= "<td>$col</td>";

			$s .= '<tr>'.nl2br($i).'</tr>';
		});

		return "<table style='font-family: monospace;' border='1'>$s</table>";
	}

	if (\Rose\typeOf($data) == 'Rose\Map')
	{
		$s = '';

		$data->forEach(function ($value, $key) use (&$s)
		{
			$s .= '<tr>'.nl2br("<th>$key</th>td>$value</td>").'</tr>';
		});

		return "<table style='font-family: monospace;' border='1'>$s</table>";
	}

	return $data;
});

Expr::register('utils::shell', function($args)
{
	return shell_exec ($args->get(1));
});

/* ************ */
Expr::register('array::new', function($args)
{
	$array = new Arry();

	for ($i = 1; $i < $args->length; $i++)
		$array->push($args->get($i));

	return $array;
});

Expr::register('array::sort:asc', function($args)
{
	$array = $args->get(1);
	$array->sort('ASC');
	return null;
});

Expr::register('array::sort:desc', function($args)
{
	$array = $args->get(1);
	$array->sort('DESC');
	return null;
});

Expr::register('array::sortl:asc', function($args)
{
	$array = $args->get(1);
	$array->sortl('ASC');
	return null;
});

Expr::register('array::sortl:desc', function($args)
{
	$array = $args->get(1);
	$array->sortl('DESC');
	return null;
});

Expr::register('array::push', function($args)
{
	$array = $args->get(1);

	for ($i = 2; $i < $args->length; $i++)
		$array->push($args->get($i));

	return null;
});

Expr::register('array::unshift', function($args)
{
	$array = $args->get(1);

	for ($i = 2; $i < $args->length; $i++)
		$array->unshift($args->get($i));

	return null;
});

Expr::register('array::pop', function($args)
{
	return $args->get(1)->pop();
});

Expr::register('array::shift', function($args)
{
	return $args->get(1)->shift();
});

Expr::register('array::first', function($args)
{
	return $args->get(1)->first();
});

Expr::register('array::last', function($args)
{
	return $args->get(1)->last();
});

Expr::register('array::remove', function($args)
{
	return $args->get(1)->remove((int)$args->get(2));
});

Expr::register('array::indexof', function($args)
{
	return $args->get(1)->indexOf($args->get(2));
});

Expr::register('array::length', function($args)
{
	return $args->get(1)->length();
});

Expr::register('array::append', function($args)
{
	return $args->get(1)->append($args->get(2));
});

Expr::register('array::unique', function($args)
{
	return $args->get(1)->unique();
});

Expr::register('array::reverse', function($args)
{
	return $args->get(1)->reverse();
});

Expr::register('array::clear', function($args)
{
	return $args->get(1)->clear();
});

/* ************ */
Expr::register('map::new', function($args)
{
	$map = new Map();

	for ($i = 1; $i+1 < $args->length; $i += 2)
		$map->set($args->get($i), $args->get($i+1));

	return $map;
});

Expr::register('map::sort:asc', function($args)
{
	$map = $args->get(1);
	$map->sort('ASC');
	return null;
});

Expr::register('map::sort:desc', function($args)
{
	$map = $args->get(1);
	$map->sort('DESC');
	return null;
});

Expr::register('map::sortk:asc', function($args)
{
	$map = $args->get(1);
	$map->sortk('ASC');
	return null;
});

Expr::register('map::sortk:desc', function($args)
{
	$map = $args->get(1);
	$map->sortk('DESC');
	return null;
});

Expr::register('map::keys', function($args)
{
	$map = $args->get(1);
	return $map->keys();
});

Expr::register('map::values', function($args)
{
	$map = $args->get(1);
	return $map->values();
});

Expr::register('map::set', function($args)
{
	$map = $args->get(1);

	for ($i = 2; $i+1 < $args->length; $i+=2)
		$map->set($args->get($i), $args->get($i+1));

	return null;
});

Expr::register('map::get', function($args)
{
	$map = $args->get(1);
	return $map->{$args->get(2)};
});

Expr::register('map::remove', function($args)
{
	return $args->get(1)->remove((string)$args->get(2));
});

Expr::register('map::keyof', function($args)
{
	return $args->get(1)->keyOf($args->get(2));
});

Expr::register('map::length', function($args)
{
	return $args->get(1)->length();
});

Expr::register('map::merge', function($args)
{
	return $args->get(1)->merge($args->get(2), true);
});

Expr::register('map::clear', function($args)
{
	return $args->get(1)->clear();
});

/* ************ */
Expr::register('re::matches', function($args)
{
	return Regex::_matches($args->get(1), $args->get(2));
});

Expr::register('re::matchFirst', function($args)
{
	return Regex::_matchFirst($args->get(1), $args->get(2));
});

Expr::register('re::matchAll', function($args)
{
	return Regex::_matchAll($args->get(1), $args->get(2));
});

Expr::register('re::split', function($args)
{
	return Regex::_split($args->get(1), $args->get(2));
});

Expr::register('re::replace', function($args)
{
	return Regex::_split($args->get(1), $args->get(2), $args->get(3));
});

Expr::register('re::extract', function($args)
{
	return Regex::_extract($args->get(1), $args->get(2));
});
