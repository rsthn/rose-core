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

$defConnection = null;

/*
**	Create global resource 'Database' which maps to a Connection created using the `Database` configuration section.
*/
Resources::getInstance()->registerConstructor ('Database', function() {
	return Connection::fromConfig (Configuration::getInstance()->Database);
});

/*
**	Escapes a value to be used in SQL queries. Does not take driver into account.
*/
Expr::register('escape', function ($args)
{
	return Connection::escape($args->get(1));
});

/**
 * Sets the current conection. If no parameter specified the default connection will be used.
 */
Expr::register('db::conn', function ($args)
{
	global $defConnection;

	if ($args->length == 1) {
		Resources::getInstance()->Database = $defConnection;
		return null;
	}

	if (!$defConnection)
		$defConnection = Resources::getInstance()->exists('Database', true) ? Resources::getInstance()->Database : null;

	Resources::getInstance()->Database = $args->get(1);
	return null;
});

/*
**	Escapes a value to be used in SQL queries, uses the driver escape when necessary.
*/
Expr::register('db::escape', function ($args)
{
	return Resources::getInstance()->Database->escapeExt ($args->get(1));
});

/*
**	Uses the driver's escapeName method to escape the given value.
*/
Expr::register('db::escape:name', function ($args)
{
	return Resources::getInstance()->Database->escapeName ($args->get(1));
});

/*
**	Executes a query and returns a scalar value (first row, first column).
*/
Expr::register('db::scalar', function ($args)
{
	return Resources::getInstance()->Database->execScalar ($args->get(1));
});

/*
**	Executes a query and returns an array with scalars value (all rows, first column).
*/
Expr::register('db::scalars', function ($args)
{
	return Resources::getInstance()->Database->execQuery ($args->get(1))->map(function($i) { return $i->values()->get(0); });
});

/*
**	Executes a query and returns a map with the first row.
*/
Expr::register('db::row', function ($args)
{
	return Resources::getInstance()->Database->execAssoc ($args->get(1));
});

/*
**	Executes a query and returns an array with the first row values.
*/
Expr::register('db::row:array', function ($args)
{
	return Resources::getInstance()->Database->execAssoc ($args->get(1))->values();
});

/*
**	Executes a query and returns an array with rows as Map.
*/
Expr::register('db::table', function ($args)
{
	return Resources::getInstance()->Database->execQuery ($args->get(1));
});

/**
 * Executes a query and returns the header, that is the number of rows the query would produce and the field names.
 */
Expr::register('db::header', function ($args)
{
	return Resources::getInstance()->Database->execHeader ($args->get(1));
});

/*
**	Executes a query and returns an array with rows as Arry.
*/
Expr::register('db::table:array', function ($args)
{
	return Resources::getInstance()->Database->execArray ($args->get(1));
});

/*
**	Executes a query and returns the reader object, from which rows can be read manually and/or incrementally.
*/
Expr::register('db::reader', function ($args)
{
	return Resources::getInstance()->Database->execReader ($args->get(1));
});

/*
**	Executes a query and returns a boolean.
*/
Expr::register('db::exec', function ($args)
{
	$query = trim($args->get(1));
	if (!$query) return true;

	return Resources::getInstance()->Database->execQuery ($query) === true ? true : false;
});

/*
**	Executes a row update operation.
**
**	db::update <table-name> <condition> <fields>
*/
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

/*
**	Executes a row insert operation.
**
**	db::insert <table-name> <fields>
*/
Expr::register('db::insert', function ($args)
{
	$conn = Resources::getInstance()->Database;

	$table = $args->get(1);
	$data = $args->get(2);

	$s = 'INSERT INTO ' . $table . ' ('. $data->keys()->map(function($i) use(&$conn) { return $conn->escapeName($i); })->join(', ') .')';
	$s .= ' VALUES (' . $conn->escapeExt ($data->values())->join(', ') . ')';

	return $conn->execQuery($s);
});

/*
**	Returns the ID of the row created by the last insert operation.
*/
Expr::register('db::lastInsertId', function ($args)
{
	return Resources::getInstance()->Database->getLastInsertId();
});

/*
**	Returns the number of affected rows by the last update operation.
*/
Expr::register('db::affectedRows', function ($args)
{
	return Resources::getInstance()->Database->getAffectedRows();
});

Expr::register('db::fields:update', function ($args)
{
	return Resources::getInstance()->Database->escapeExt ($args->get(1))->join(', ');
});

Expr::register('db::fields:insert', function ($args)
{
	return Resources::getInstance()->Database->escapeExt ($args->get(1)->values())->join(', ');
});

/**
 * Opens a new connection and returns the database handle.
 */
Expr::register('db::open', function ($args)
{
	return Connection::fromConfig ($args->get(1));
});

/**
 * Closes the specified connection, if it is the active connection then the default one will be set again.
 */
Expr::register('db::close', function ($args)
{
	global $defConnection;

	if ((Resources::getInstance()->exists('Database', true) ? Resources::getInstance()->Database : null) === $args->get(1))
		Resources::getInstance()->Database = $defConnection;

	$args->get(1)->close();
	return null;
});
