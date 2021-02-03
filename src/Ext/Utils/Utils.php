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

Expr::register('utils::rand', function() { return Math::rand(); });
Expr::register('utils::randstr', function($args) { return bin2hex(random_bytes((int)$args->get(1))); });
Expr::register('utils::randstr:base64', function($args) { return base64_encode(random_bytes((int)$args->get(1))); });
Expr::register('utils::uuid', function() {
	$data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
});

Expr::register('utils::sleep', function($args) { sleep($args->get(1)); return null; });

Expr::register('utils::base64:encode', function($args) { return base64_encode ($args->get(1)); });
Expr::register('utils::base64:decode', function($args) { return base64_decode ($args->get(1)); });

Expr::register('utils::hex:encode', function($args) { return bin2hex ($args->get(1)); });
Expr::register('utils::hex:decode', function($args) { return hex2bin ($args->get(1)); });

Expr::register('utils::url:encode', function($args) { return urlencode ($args->get(1)); });
Expr::register('utils::url:decode', function($args) { return urldecode ($args->get(1)); });

Expr::register('utils::json:stringify', function($args)
{
	$value = $args->get(1);

	if (\Rose\typeOf($value) == 'Rose\\Arry' || \Rose\typeOf($value) == 'Rose\\Map')
		return (string)$value;

	return json_encode($value);
});

Expr::register('utils::json:parse', function($args)
{
	$value = $args->get(1);
	return $value[0] == '[' ? Arry::fromNativeArray(json_decode($value, true)) : ($value[0] == '{' ? Map::fromNativeArray(json_decode($value, true)) : json_decode($value, true));
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
