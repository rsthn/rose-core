<?php

namespace Rose\Ext;

use Rose\Data\Connection;
use Rose\Resources;
use Rose\Configuration;
use Rose\Expr;

$mainConn = null;

/**
 * Create global resource 'Database' which maps to a Connection created using the `Database` configuration section.
 */
Resources::getInstance()->registerConstructor ('Database', function() {
	return Connection::fromConfig (Configuration::getInstance()->Database);
});

/**
 * Sets the current conection. If `null` is specified the default connection will be used.
 * @code (db::conn <connection|null>)
 */
Expr::register('db::conn', function ($args)
{
	global $mainConn;

	if ($args->length == 1)
		return Resources::getInstance()->Database;

	if ($args->get(1) === null) {
		Resources::getInstance()->Database = $mainConn;
		return null;
	}

	if (!$mainConn)
		$mainConn = Resources::getInstance()->exists('Database', true) ? Resources::getInstance()->Database : null;

	Resources::getInstance()->Database = $args->get(1);
	return null;
});

/**
 * Escapes a value to be used in SQL queries, uses the driver escape when necessary.
 * @code (db::escape <value>)
 * @example (db::escape "Jack O'Neill")
 */
Expr::register('db::escape', function ($args) {
	return Resources::getInstance()->Database->escapeExt ($args->get(1));
});

/**
 * Uses the driver to escape the given value considering it to be a column/table name.
 * @code (db::escape-name <value>)
 * @example (db::escape-name "First Name")
 */
Expr::register('db::escape-name', function ($args) {
	return Resources::getInstance()->Database->escapeName($args->get(1));
});

/**
 * Executes a query and returns a scalar value, that is, first column of first row or `null` if no rows are returned.
 * @code (db::scalar <query> [params])
 * @example (db::scalar `SELECT COUNT(*) FROM users WHERE name LIKE ?` (# "Jack%" ))
 */
Expr::register('db::scalar', function ($args) {
	return Resources::getInstance()->Database->execScalar($args->get(1), $args->{2});
});

/**
 * Executes a query and returns an array with scalars value (all rows, first column).
 * @code (db::scalars <query> [params])
 * @example (db::scalars `SELECT name, last_name FROM users WHERE age > ?` (# 18))
 */
Expr::register('db::scalars', function ($args) {
	return Resources::getInstance()->Database->execQuery($args->get(1), $args->{2})->map(function($i) { return $i->values()->get(0); });
});

/**
 * Executes a query and returns a map with the first row.
 * @code (db::row <query> [params])
 * @example (db::row `SELECT name, last_name FROM users WHERE age >= ?` (# 21))
 */
Expr::register('db::row', function ($args) {
	return Resources::getInstance()->Database->execAssoc($args->get(1), $args->{2});
});

/**
 * Executes a query and returns an array with the first row, values only.
 * @code (db::row:array <query> [params])
 * @example (db::row:array `SELECT name, last_name FROM users WHERE age >= ?` (# 21))
 */
Expr::register('db::row-values', function ($args) {
	return Resources::getInstance()->Database->execArray($args->get(1), $args->{2});
});

/**
 * Executes a query and returns an array with the result rows.
 * @code (db::table <query> [params])
 * @example (db::table `SELECT name, last_name FROM users WHERE age >= ?` (# 18))
 */
Expr::register('db::table', function ($args) {
	return Resources::getInstance()->Database->execQuery($args->get(1), $args->{2});
});

/**
 * Executes a query and returns the header, that is the field names and the number of rows the query would produce.
 * @code (db::header <query> [params])
 * @example (db::header `SELECT name, last_name FROM clients WHERE status=?` (# 'active'))
 */
Expr::register('db::header', function ($args) {
	return Resources::getInstance()->Database->execHeader($args->get(1), $args->{2});
});

/**
 * Executes a query and returns an array with row values.
 * @code (db::table-values <query> [params])
 * @example (db::table-values `SELECT name, last_name FROM clients WHERE status=?` (# 'active'))
 */
Expr::register('db::table-values', function ($args) {
	return Resources::getInstance()->Database->execArray($args->get(1), $args->{2});
});

/**
 * Executes a query and returns the reader object from which rows can be read manually incrementally or all at once.
 * @code (db::reader <query> [params])
 * @example (db::reader `SELECT name, last_name FROM clients WHERE status=?` (# 'active'))
 */
Expr::register('db::reader', function ($args) {
	return Resources::getInstance()->Database->execReader($args->get(1), $args->{2});
});

/**
 * Executes a query and returns a boolean.
 * @code (db::exec <query> [params])
 * @example (db::exec `DELETE FROM clients WHERE status=?` (# 'inactive'))
 */
Expr::register('db::exec', function ($args)
{
	$query = trim($args->get(1));
	if (!$query) return true;
	return Resources::getInstance()->Database->execQuery($query, $args->{2}) === true ? true : false;
});

/**
 * Executes a row update operation.
 * @code (db::update <table-name> <condition|fields> <fields>)
 * @example (db::update `users` `id=1` (& name "Jack" last_name "O'Neill"))
 */
Expr::register('db::update', function ($args)
{
	$conn = Resources::getInstance()->Database;
	$table = $args->get(1);
	$condition = $args->get(2);
	$data = $args->get(3);
	if ($data->length == 0) return true;

	if (\Rose\typeOf($condition, true) !== 'string') {
		$cond = $condition->map(function($value, $name) use(&$conn) { return $conn->escapeName($name).'='.$conn->escapeValue($value); })->values()->join(' AND ');
		if ($cond) $cond = ' WHERE ' . $cond;
	}
	else
		$cond = $condition ? ' WHERE ' . $condition : '';

	$s = 'UPDATE ' . $table . ' SET ' . $conn->escapeExt($data)->join(', ') . $cond;
	return $conn->execQuery($s);
});

/**
 * Executes a row insert operation and returns the ID of the inserted row when applicable.
 * @code (db::insert <table-name> <fields>)
 * @example (db::insert `users` (& name "Daniel" last_name "Jackson"))
 */
Expr::register('db::insert', function ($args)
{
	$conn = Resources::getInstance()->Database;
	$table = $args->get(1);
	$data = $args->get(2);

	$s = 'INSERT INTO ' . $table . ' ('. $data->keys()->map(function($i) use(&$conn) { return $conn->escapeName($i); })->join(', ') .')';
	$s .= ' VALUES (' . $conn->escapeExt ($data->values())->join(', ') . ')';

    return $conn->execQuery($s) === true ? $conn->getLastInsertId() : null;
});

/**
 * Returns a single row matching the specified fields.
 * @code (db::get <table-name> <string|fields>)
 * @example (db::get `users` `id=1`)
 */
Expr::register('db::get', function ($args)
{
	$conn = Resources::getInstance()->Database;

	$table = $args->get(1);
	$condition = $args->get(2);

	if (\Rose\typeOf($condition, true) !== 'string')
	{
		$cond = $condition->map(function($value, $name) use(&$conn) { return $conn->escapeName($name).'='.$conn->escapeValue($value); })->values()->join(' AND ');
		if ($cond) $cond = ' WHERE ' . $cond;
	}
	else
		$cond = $condition ? ' WHERE ' . $condition : '';

	$s = 'SELECT * FROM ' . $table . $cond;
	return $conn->execAssoc($s);
});

/**
 * Deletes one or more rows from a table.
 * @code (db::delete <table-name> <string|fields>)
 * @example (db::delete `users` `id=1`)
 */
Expr::register('db::delete', function ($args)
{
	$conn = Resources::getInstance()->Database;
	$table = $args->get(1);
	$condition = $args->get(2);

	if (\Rose\typeOf($condition, true) !== 'string') {
		$cond = $condition->map(function($value, $name) use(&$conn) { return $conn->escapeName($name).'='.$conn->escapeValue($value); })->values()->join(' AND ');
		if ($cond) $cond = ' WHERE ' . $cond;
	}
	else
		$cond = $condition ? ' WHERE ' . $condition : '';

	$s = 'DELETE FROM ' . $table . $cond;
	return $conn->execQuery($s);
});

/**
 * Returns the ID of the row created by the last insert operation.
 * @code (db::lastInsertId)
 */
Expr::register('db::lastInsertId', function ($args) {
	return Resources::getInstance()->Database->getLastInsertId();
});

/**
 * Returns the number of affected rows by the last update operation.
 * @code (db::affectedRows)
 */
Expr::register('db::affectedRows', function ($args) {
	return Resources::getInstance()->Database->getAffectedRows();
});

//Expr::register('db::fields:update', function ($args) {
//	return Resources::getInstance()->Database->escapeExt ($args->get(1))->join(', ');
//});
//Expr::register('db::fields:insert', function ($args) {
//	return Resources::getInstance()->Database->escapeExt ($args->get(1)->values())->join(', ');
//});

/**
 * Opens a new connection and returns the database handle.
 * @code (db::open <config>)
 * @example (db::open (& server "localhost" user "usrname" password "mypwd" database "test" driver "mysql" trace false ))
 */
Expr::register('db::open', function ($args) {
	return Connection::fromConfig ($args->get(1));
});

/**
 * Closes the specified connection, if it is the currently active connection then the default one will be set again.
 * @code (db::close <connection>)
 */
Expr::register('db::close', function ($args)
{
	global $mainConn;

	if ((Resources::getInstance()->exists('Database', true) ? Resources::getInstance()->Database : null) === $args->get(1))
		Resources::getInstance()->Database = $mainConn;

	$args->get(1)->close();
	return null;
});
