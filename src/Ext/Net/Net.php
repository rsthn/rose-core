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

use Rose\Expr;
use Rose\Arry;
use Rose\Map;
use Rose\Text;

/* ****************** */
$curl_last_info = null;
$curl_last_data = null;


/* ****************** */
/* http::get <url> [<fields>] */

Expr::register('http::get', function ($args)
{
	$c = curl_init();

	$url = $args->get(1);
	$data = $args->length > 2 ? $args->get(2) : null;

	if (Text::indexOf($url, '?') === false)
		$url .= '?';

	if ($data && \Rose\typeOf($data) == 'Rose\\Map')
	{
		$data->map(function(&$value, $name) {
			return urlencode($name) . '=' . urlencode($value);
		});

		$url .= $data->values()->join('&');
	}

	curl_setopt ($c, CURLOPT_URL, $url);
	curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($c, CURLOPT_CUSTOMREQUEST, "GET");

	$data = curl_exec($c);

	global $curl_last_info, $curl_last_data;
	
	$curl_last_info = curl_getinfo($c);
	$curl_last_data = $data;

	curl_close($c);

	return $data;
});


/* ****************** */
/* http::get:json <url> [<fields>] */

Expr::register('http::get:json', function ($args)
{
	$args = new Arry ([null, Expr::call('http::get', $args)]);
	return Expr::call('utils::json:parse', $args);
});

Expr::register('http::fetch', function ($args)
{
	$args = new Arry ([null, Expr::call('http::get', $args)]);
	return Expr::call('utils::json:parse', $args);
});


/* ****************** */

Expr::register('http::code', function ($args)
{
	global $curl_last_info;
	return $curl_last_info['http_code'];
});

Expr::register('http::content-type', function ($args)
{
	global $curl_last_info;
	return $curl_last_info['content-type'];
});

Expr::register('http::data', function ($args)
{
	global $curl_last_data;
	return $curl_last_data;
});
