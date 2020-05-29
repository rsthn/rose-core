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

Expr::register('db::escape', function ($args)
{
	return Resources::getInstance()->Database->escapeExt ($args->get(1));
});

Expr::register('db::escape:name', function ($args)
{
	return Resources::getInstance()->Database->escapeName ($args->get(1));
});

Expr::register('db::scalar', function ($args)
{
	return Resources::getInstance()->Database->execScalar ($args->get(1));
});

Expr::register('db::scalars', function ($args)
{
	return Resources::getInstance()->Database->execQuery ($args->get(1))->rows->map(function($i) { return $i->values()->get(0); });
});

Expr::register('db::row', function ($args)
{
	return Resources::getInstance()->Database->execAssoc ($args->get(1));
});

Expr::register('db::row:array', function ($args)
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

Expr::register('db::fields:update', function ($args)
{
	return Resources::getInstance()->Database->escapeExt ($args->get(1))->join(', ');
});

Expr::register('db::fields:insert', function ($args)
{
	return Resources::getInstance()->Database->escapeExt ($args->get(1)->values())->join(', ');
});

Expr::register('db::update', function ($args)
{
	$conn = Resources::getInstance()->Database;

	$table = $args->get(1);
	$condition = $args->get(2);
	$data = $args->get(3);

	if ($data->length == 0)
		return true;

	$s = 'UPDATE ' . $table . ' SET ';
	$s .= $conn->escapeExt($data)->join(', ');
	$s .= ' WHERE ' . $condition;

	return $conn->execQuery($s);
});

Expr::register('db::insert', function ($args)
{
	$conn = Resources::getInstance()->Database;

	$table = $args->get(1);
	$data = $args->get(2);

	$s = 'INSERT INTO ' . $table . ' ('. $data->keys()->map(function($i) use(&$conn) { return $conn->escapeName($i); })->join(', ') .')';
	$s .= ' VALUES (' . $conn->escapeExt ($data->values())->join(', ') . ')';

	return $conn->execQuery($s);
});

Expr::register('db::lastInsertId', function ($args)
{
	return Resources::getInstance()->Database->getLastInsertId();
});
