<?php

namespace Rose\Ext;

use Rose\Data\Connection;
use Rose\Resources;
use Rose\Configuration;
use Rose\Expr;
use Rose\Errors\ArgumentError;

$mainConn = null;

// @title Database

/**
 * Create global resource 'Database' which maps to a Connection created using the `Database` configuration section.
 */
Resources::getInstance()->registerConstructor ('Database', function() {
    return Connection::fromConfig (Configuration::getInstance()->Database);
});

/**
 * Escapes a value to be used in SQL queries. Uses the driver escape function when necessary.
 * @code (`db:escape` <value>)
 * @example
 * (db:escape "Jack O'Neill")
 * ; 'Jack O''Neill'
 */
Expr::register('db:escape', function ($args) {
    return Resources::getInstance()->Database->escapeExt ($args->get(1));
});

/**
 * Uses the driver to escape the given value considering it to be a column/table name.
 * @code (`db:escape-name` <value>)
 * @example
 * (db:escape-name "First Name")
 * ; `First Name`
 */
Expr::register('db:escape-name', function ($args) {
    return Resources::getInstance()->Database->escapeName($args->get(1));
});

/**
 * Executes a query and returns a scalar value, that is, first column of first row or `null` if no rows are returned.
 * @code (`db:scalar` <query> [...params])
 * @example
 * (db:scalar `SELECT COUNT(*) FROM users WHERE name LIKE ?` "Jack%"))
 * ; 3
 */
Expr::register('db:scalar', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:scalar) expected scalar value');
    return Resources::getInstance()->Database->execScalar($args->get(1), $args->length() > 2 ? $args->slice(2) : null);
});

/**
 * Executes a query and returns an array with scalars value (all rows, first column).
 * @code (`db:scalars` <query> [...params])
 * @example
 * (db:scalars `SELECT name, last_name FROM users WHERE age > ?` 18)
 * ; ["Jack", "Daniel", "Samantha"]
 */
Expr::register('db:scalars', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:scalars) expected scalar value');
    return Resources::getInstance()->Database->execQuery($args->get(1), $args->length() > 2 ? $args->slice(2) : null)
        ->map(function($i) { return $i->values()->get(0); });
});

/**
 * Executes a query and returns a map with the first row.
 * @code (`db:row` <query> [...params])
 * @example
 * (db:row `SELECT name, last_name FROM users WHERE age >= ?` 21)
 * ; {"name": "Jack", "last_name": "O'Neill"}
 */
Expr::register('db:row', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:row) expected scalar value');
    return Resources::getInstance()->Database->execAssoc($args->get(1), $args->length() > 2 ? $args->slice(2) : null);
});

/**
 * Executes a query and returns an array with the first row, values only.
 * @code (`db:row-values` <query> [...params])
 * @example
 * (db:row-values `SELECT name, last_name FROM users WHERE age >= ?` 21)
 * ; ["Jack", "O'Neill"]
 */
Expr::register('db:row-values', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:row-values) expected scalar value');
    return Resources::getInstance()->Database->execArray($args->get(1), $args->length() > 2 ? $args->slice(2) : null);
});

/**
 * Executes a query and returns an array with all the resulting rows.
 * @code (`db:table` <query> [...params])
 * @example
 * (db:table `SELECT name FROM super_users WHERE age >= ?` 18)
 * ; [{"name": "Jack"}, {"name": "Daniel"}, {"name": "Samantha"}]
 */
Expr::register('db:table', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:table) expected scalar value');
    return Resources::getInstance()->Database->execQuery($args->get(1), $args->length() > 2 ? $args->slice(2) : null);
});

/**
 * Executes a query and returns an array with row values.
 * @code (`db:table-values` <query> [...params])
 * @example
 * (db:table-values `SELECT name, last_name FROM super_users WHERE status=?` "active")
 * ; [["Jack", "O'Neill"], ["Daniel", "Jackson"], ["Samantha", "Carter"]]
 */
Expr::register('db:table-values', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:table-values) expected scalar value');
    return Resources::getInstance()->Database->execArray($args->get(1), $args->length() > 2 ? $args->slice(2) : null);
});

/**
 * Executes a query and returns the header, that is, the field names and the number of rows the query would produce.
 * @code (`db:header` <query> [...params])
 * @example
 * (db:header `SELECT name, last_name FROM users WHERE status=?` "active")
 * ; {"count": 3, "fields":["name", "last_name"]}
 */
Expr::register('db:header', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:header) expected scalar value');
    return Resources::getInstance()->Database->execHeader($args->get(1), $args->length() > 2 ? $args->slice(2) : null);
});

/**
 * Executes a query and returns a reader object from which rows can be read incrementally or all at once.
 * @code (`db:reader` <query> [...params])
 * @example
 * (set reader (db:reader `SELECT name FROM super_users WHERE status=?` "active"))
 * (echo (reader.fields))
 * ; ["name"]
 *
 * (while (reader.fetch)
 *     (echo "row #" (+ 1 (reader.index)) ": " (reader.data))
 * )
 * ; row #1: {"name": "Jack"}
 * ; row #2: {"name": "Daniel"}
 * ; row #3: {"name": "Samantha"}
 *
 * (reader.close)
 */
Expr::register('db:reader', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:reader) expected scalar value');
    return Resources::getInstance()->Database->execReader($args->get(1), $args->length() > 2 ? $args->slice(2) : null);
});

/**
 * Executes a query and returns a boolean indicating success or failure.
 * @code (`db:exec` <query> [...params])
 * @example
 * (db:exec `DELETE FROM users WHERE status=?` "inactive"))
 * ; true
 */
Expr::register('db:exec', function ($args) {
    if ($args->length > 2 && \Rose\typeOf($args->get(2)) === 'Rose\\Arry')
        throw new ArgumentError('(db:exec) expected scalar value');
    $query = trim($args->get(1));
    if (!$query) return true;
    return Resources::getInstance()->Database->execQuery($query, $args->length() > 2 ? $args->slice(2) : null) === true ? true : false;
});

/**
 * Executes a row update operation and returns boolean indicating success or failure.
 * @code (`db:update` <table-name> <condition> <fields>)
 * @example
 * (db:update "users" "id=1" (& name "Jack" last_name "O'Neill"))
 * ; true
 *
 * (db:update "users" (& id 1) (& name "Jack"))
 * ; true
 */
Expr::register('db:update', function ($args)
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
 * Executes a row insert operation and returns the ID of the newly inserted row or `null` if the operation failed.
 * @code (`db:insert` <table-name> <fields>)
 * @example
 * (db:insert `users` (& name "Daniel" last_name "Jackson"))
 * ; 3
 */
Expr::register('db:insert', function ($args)
{
    $conn = Resources::getInstance()->Database;
    $table = $args->get(1);
    $data = $args->get(2);

    $s = 'INSERT INTO ' . $table . ' ('. $data->keys()->map(function($i) use(&$conn) { return $conn->escapeName($i); })->join(', ') .')';
    $s .= ' VALUES (' . $conn->escapeExt ($data->values())->join(', ') . ')';

    return $conn->execQuery($s) === true ? $conn->getLastInsertId() : null;
});

/**
 * Returns a single row matching the specified condition.
 * @code (`db:get` <table-name> <condition>)
 * @example
 * (db:get "users" "id=1")
 * ; {"id": 1, "name": "Jack", "last_name": "O'Neill"}
 *
 * (db:get "users" (& id 3))
 * ; {"id": 2, "name": "Samantha", "last_name": "Carter"}
 */
Expr::register('db:get', function ($args)
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
 * Deletes one or more rows from a table and returns a boolean indicating success or failure.
 * @code (`db:delete` <table-name> <condition>)
 * @example
 * (db:delete "users" "user_id=1")
 * ; true
 *
 * (db:delete "users" (& user_id 3))
 * ; true
 */
Expr::register('db:delete', function ($args)
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
 * @code (`db:lastInsertId`)
 * @example
 * (db:lastInsertId)
 * ; 3
 */
Expr::register('db:lastInsertId', function ($args) {
    return Resources::getInstance()->Database->getLastInsertId();
});

/**
 * Returns the number of affected rows by the last update operation.
 * @code (`db:affectedRows`)
 * @example
 * (db:affectedRows)
 * ; 45
 */
Expr::register('db:affectedRows', function ($args) {
    return Resources::getInstance()->Database->getAffectedRows();
});

/**
 * Opens a new connection and returns the database handle, use it only when managing multiple connections to different
 * database servers because if only one is used (the default one) this is not necessary.
 * @code (`db:open` <config>)
 * @example
 * (db:open (& server "localhost" user "main" password "mypwd" database "test" driver "mysql" trace false ))
 * ; [Rose\Data\Connection]
 */
Expr::register('db:open', function ($args) {
    return Connection::fromConfig($args->get(1));
});

/**
 * Closes the specified connection. If the provided connection is the currently active one, it will be closed and the
 * default connection will be activated (if any).
 * @code (`db:close` <connection>)
 */
Expr::register('db:close', function ($args)
{
    global $mainConn;

    if ((Resources::getInstance()->exists('Database', true) ? Resources::getInstance()->Database : null) === $args->get(1))
        Resources::getInstance()->Database = $mainConn;

    $args->get(1)->close();
    return null;
});

/**
 * Sets or returns the active conection. Should be used only if you're managing multiple connections.
 * Pass `null` as parameter to use the default connection.
 * @code (`db:conn` <connection>)
 * @code (`db:conn`)
 */
Expr::register('db:conn', function ($args)
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
