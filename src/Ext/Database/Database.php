<?php
/*
**	Rose\Ext\Database
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

use Rose\Data\Connection;

use Rose\Resources;
use Rose\Configuration;
use Rose\Expr;

Resources::getInstance()->registerConstructor ('Database', function() {
	return Connection::fromConfig (Configuration::getInstance()->Database);
});

Expr::register('escape', function ($args)
{
	return Connection::escape($args->get(1));
});

Expr::register('db::scalar', function ($args)
{
	return Resources::getInstance()->Database->execScalar ($args->get(1));
});

Expr::register('db::scalars', function ($args)
{
	return Resources::getInstance()->Database->execQuery ($args->get(1))->rows->map(function($i) { return $i->values()->get(0); });
});

Expr::register('db::record', function ($args)
{
	return Resources::getInstance()->Database->execAssoc ($args->get(1));
});

Expr::register('db::record:array', function ($args)
{
	return Resources::getInstance()->Database->execAssoc ($args->get(1))->values();
});

Expr::register('db::table', function ($args)
{
	return Resources::getInstance()->Database->execQuery ($args->get(1))->rows;
});

Expr::register('db::table:array', function ($args)
{
	return Resources::getInstance()->Database->execQuery ($args->get(1))->rows->map(function($i) { return $i->values(); });
});

Expr::register('db::exec', function ($args)
{
	return Resources::getInstance()->Database->execQuery ($args->get(1)) === true ? true : false;
});
