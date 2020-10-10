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

Expr::register('strings', function ($args) { return Strings::getInstance(); });
Expr::register('s', function ($args) { return Strings::getInstance(); });

Expr::register('resources', function ($args) { return Resources::getInstance(); });
Expr::register('gateway', function ($args) { return Gateway::getInstance(); });

Expr::register('request', function ($args) { return Gateway::getInstance()->requestParams; });

Expr::register('math::rand', function() { return Math::rand(); });
Expr::register('math::randstr', function($args) { return bin2hex(random_bytes((int)$args->get(1))); });
Expr::register('math::uuid', function() {
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
Expr::register('utils::json:stringify', function($args) { return (string)($args->get(1)); });
Expr::register('utils::json:parse', function($args) { return Text::substring($args->get(1), 0, 1) == '[' ? Arry::fromNativeArray(json_decode($args->get(1), true)) : Map::fromNativeArray(json_decode($args->get(1), true)); });
