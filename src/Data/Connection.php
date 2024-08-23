<?php

namespace Rose\Data;

use Rose\Errors\Error;

use Rose\Data\Driver;
use Rose\Data\Reader;

use Rose\Map;
use Rose\Arry;
use Rose\Text;

/**
**	Provides an interface between a client and a database server.
*/

class Connection
{
    // The connection resource used by this class.
    public $conn = null;

    // Driver of the connection.
    private $driver;

    // Prefix for all tables in the database, all instances of the symbol ## in a query will be replaced with this prefix.
    private $dbPrefix = '';

    // Indicates if queries made using this connection should be traced.
    private $dbTracing = false;

    // Database host.
    private $dbHost;

    // Database port.
    private $dbPort;

    // Username for the database.
    private $dbUser;

    // Password for the database user.
    private $dbPassword;

    // Name of the database to use.
    private $dbName;

    // Available database drivers, registered using the `Connection::registerDriver` method.
    private static $drivers = null;

    /**
     * Registers a database driver.
     * @param string $name Name of the driver.
     * @param Driver $driver Driver instance.
     */
    public static function registerDriver ($name, $driver)
    {
        if (Connection::$drivers == null)
            Connection::$drivers = new Map();

        if (!($driver instanceof Driver))
            throw new Error ('Connection (registerDriver): Provided `driver` parameter is not an instance of Rose\\Data\\Driver.');

        Connection::$drivers->set($name, $driver);
    }

    /**
     * Constructs a database connection using the given configuration.
     * @param string $host Database host.
     * @param string $port Database port (NULL for default).
     * @param string $user Database user.
     * @param string $password Database password.
     * @param string $database Database name.
     * @param boolean $tracing Indicates if queries made using this connection should be traced.
     * @param string $prefix Prefix for all tables in the database, all instances of the symbol ## in a query will be replaced with this prefix.
     * @param string $driver Name of the driver to use.
     */
    public function __construct ($host=null, $port=null, $user=null, $password=null, $database=null, $tracing=false, $prefix=null, $driver=null)
    {
        $this->dbHost = $host;
        $this->dbPort = $port ? (int)$port : null;
        $this->dbUser = $user;
        $this->dbPassword = $password;
        $this->dbName = $database;

        if (Connection::$drivers == null)
            throw new Error("Connection: There are no database drivers registered.");

        $this->driver = Connection::$drivers->get(Text::toLowerCase($driver));
        if ($this->driver == null)
            throw new Error("Connection: Driver `".$driver."` was not found.");

        if ($tracing == true) $this->tracing(true);
        if ($prefix) $this->prefix($prefix);
    }

    /**
     * Called upon destruction of the instance, if the connection is active it will be closed.
     */
    public function __destruct () {
        $this->close();
    }

    /**
     * Returns or sets the tracing state of the connection.
     * @param boolean $state New tracing state.
     * @return boolean Tracing state.
     */
    public function tracing ($state=null) {
        if ($state !== null) $this->dbTracing = $state;
        return $this->dbTracing;
    }

    /**
     * Returns or sets the database prefix.
     * @param string $value New database prefix.
     * @return string Database prefix.
     */
    public function prefix ($value=null) {
        if ($value !== null) $this->dbPrefix = $value;
        return $this->dbPrefix;
    }

    /**
     * Builds a connection using the given configuration, should contain fields: server, user, password, database, driver and optionally trace.
     * @param Map $config Configuration to use.
     * @param boolean $autoConnect Indicates if the connection should be opened automatically.
     * @return Connection Connection instance.
     */
    public static function fromConfig ($config, $autoConnect=true) {
        $conn = new Connection ($config->server, $config->port, $config->user, $config->password, $config->database, $config->trace == 'true', $config->prefix, $config->driver);
        if ($autoConnect) $conn->open();
        return $conn;
    }

    /**
     * Opens the connection to the database server. Throws an error if the operation failed.
     */
    public function open()
    {
        if ($this->conn != null) return;

        try {
            $this->conn = $this->driver->open ($this->dbHost, $this->dbPort, $this->dbUser, $this->dbPassword, $this->dbName);
        }
        catch (\Throwable $e) {
            throw new Error ('Unable to open connection to '.$this->dbUser.'@'.$this->dbHost.': '.$e->getMessage());
        }

        if ($this->conn == null)
            throw new Error ('Unable to open connection to '.$this->dbUser.'@'.$this->dbHost.': '.$this->driver->getLastError(null));
    }

    /**
     * Closes the connection to the database server.
     */
    public function close() {
        if ($this->conn === null) return;
        $this->driver->close($this->conn);
        $this->conn = null;
    }

    /**
     * Returns the last error from the database driver.
     * @return string Last error.
     */
    public function getLastError() {
        return $this->driver->getLastError($this->conn);
    }

    /**
     * Returns the last id generated on the last insertion statement.
     * @return int ID of the last inserted row.
     */
    public function getLastInsertId() {
        return $this->driver->getLastInsertId($this->conn);
    }

    /**
     * Returns the number of affected rows on the last executed query.
     * @return int Number of affected rows.
     */
    public function getAffectedRows() {
        return $this->driver->getAffectedRows($this->conn);
    }

    /**
     * Filters an input query string.
     * @param string $queryString Query string to filter.
     * @return string Filtered query string.
     */
    private function filterQuery ($queryString) {
        return Text::replace('##', $this->dbPrefix, $queryString);
    }

    /**
     * Escapes the given string to be used in a query.
     */
    public static function escape ($value, $start="'", $end="'", $search1="'", $replace1="''", $search2=null, $replace2=null)
    {
        if ($search1 != null)
            $value = Text::replace ($search1, $replace1, $value);

        if ($search2 != null)
            $value = Text::replace ($search2, $replace2, $value);

        return $start . addcslashes ($value, "#\\\t\n\v\f\r") . $end;
    }

    /**
     * Escapes an identifier using the driver's `escapeName` method.
     * @param string $value Identifier to escape.
     * @return string Escaped identifier.
     */
    public function escapeName ($value) {
        return $this->driver->escapeName($value);
    }

    /**
     * Escapes a value using the driver's `escapeValue` method.
     * @param string $value Value to escape.
     * @return string Escaped value.
     */
    public function escapeValue ($value)
    {
        return $this->driver->escapeValue($value);
    }

    /**
     * Escapes the given value to be used in a query. Uses the type of the value to determine the appropriate format and if escape is required. Also
     * uses the driver's `escapeName` method to escape column names.
     * @param mixed $value Value to escape.
     * @return string Escaped value.
     */
    public function escapeExt ($value)
    {
        switch (\Rose\typeof($value, true))
        {
            case 'Rose\\Arry':
                $s = new Arry();
                $value->forEach(function($i) use(&$s) { $s->push($this->escapeExt($i)); });
                $value = $s;
                break;
    
            case 'Rose\\Map':
                $s = new Arry();
                $value->forEach(function($i, $k) use(&$s) { $s->push($this->escapeName($k) . '=' . $this->escapeExt($i)); });
                $value = $s;
                break;
    
            case 'null':
                $value = 'NULL';
                break;
    
            case 'bool':
                $value = $value ? '1' : '0';
                break;
    
            case 'int':
            case 'number':
                break;
    
            case 'string':
                $value = $this->escapeValue($value);
                break;

            default:
                $value = $this->escapeValue((string)$value);
                break;
        }
    
        return $value;
    }

    /**
     * Executes a query (or an statement) and returns an array (for queries) or a `boolean` (for statements).
     * @param string $queryString Query or statement to execute.
     * @param array $params Parameters to use in the query.
     * @return array|boolean Result of the query or `true` if the statement was executed successfully.
     */
    public function execQuery ($queryString, $params=null)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive($this->conn))
            $this->open();

        $rs = null; $err = null;
        try { $rs = $this->driver->query($queryString, $this->conn, $params); }
        catch (\Throwable $e) { $err = $e->getMessage(); }

        if ($rs === false || $rs === null || $err !== null)
            throw new Error ($err ? $err : $this->driver->getLastError($this->conn));
        if ($rs === true)
            return $rs;

        $dt = new Arry();
        $i = null;

        while (true) {
            $i = $this->driver->fetchAssoc($rs, $this->conn);
            if ($i === null) break;
            $dt->push(Map::fromNativeArray($i, false));
        }

        $this->driver->freeResult($rs, $this->conn);
        return $dt;
    }

    /**
     * Executes a query (or an statement) and returns a Reader.
     * @param string $queryString Query or statement to execute.
     * @param array $params Parameters to use in the query.
     * @return Reader Reader instance.
     */
    public function execReader ($queryString, $params=null)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive($this->conn))
            $this->open();

        $rs = null; $err = null;
        try { $rs = $this->driver->reader($queryString, $this->conn, $params); }
        catch (\Throwable $e) { $err = $e->getMessage(); }

        if ($rs === false || $rs === null || $err !== null)
            throw new Error ($err ? $err : $this->driver->getLastError($this->conn));
        if ($rs === true)
            throw new Error ('Result is not a data set.');

        return new Reader ($this->driver, $this->conn, $rs);
    }

    /**
     * Executes a query and returns the header, that is the field names and the number of rows the query would produce.
     * @param string $queryString Query to execute.
     * @param array $params Parameters to use in the query.
     * @return Map Header information.
     */
    public function execHeader ($queryString, $params=null)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive($this->conn))
            $this->open();

        $rs = null; $err = null;
        try { $rs = $this->driver->reader($queryString, $this->conn, $params); }
        catch (\Throwable $e) { $err = $e->getMessage(); }

        if ($rs === false || $rs === null || $err !== null)
            throw new Error ($err ? $err : $this->driver->getLastError($this->conn));
        if ($rs === true)
            throw new Error ('Result is not a data set.');

        $info = new Map();

        $info->count = $this->driver->getNumRows ($rs, $this->conn);
        $info->fields = new Arry();

        $n = $this->driver->getNumFields($rs, $this->conn);
        for ($i = 0; $i < $n; $i++)
            $info->fields->push($this->driver->getFieldName ($rs, $i, $this->conn));

        $this->driver->freeResult($rs, $this->conn);
        return $info;
    }

    /**
     * Executes a query and returns an scalar value. That is, the first column of the first row of the result set.
     * @param string $queryString Query to execute.
     * @param array $params Parameters to use in the query.
     * @return mixed Scalar value.
     */
    public function execScalar ($queryString, $params=null)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive($this->conn))
            $this->open();

        $rs = null; $err = null;
        try { $rs = $this->driver->query($queryString, $this->conn, $params); }
        catch (\Throwable $e) { $err = $e->getMessage(); }

        if ($rs === false || $rs === null || $err !== null)
            throw new Error ($err ? $err : $this->driver->getLastError($this->conn));
        if ($rs === true)
            throw new Error ('Result is not a data set.');

        $array = $this->driver->fetchRow($rs, $this->conn);
        $this->driver->freeResult($rs, $this->conn);

        return $array ? $array[0] : null;
    }

    /**
     * Executes a query and returns an array with the row values.
     * @param string $queryString Query to execute.
     * @param array $params Parameters to use in the query.
     * @return Arry Array with the row values.
     */
    public function execArray ($queryString, $params=null)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive($this->conn))
            $this->open();

        $rs = null; $err = null;
        try { $rs = $this->driver->query($queryString, $this->conn, $params); }
        catch (\Throwable $e) { $err = $e->getMessage(); }

        if ($rs === false || $rs === null || $err !== null)
            throw new Error ($err ? $err : $this->driver->getLastError($this->conn));
        if ($rs === true)
            throw new Error ('Result is not a data set.');

        $dt = new Arry();
        $i = null;

        while (true) {
            $i = $this->driver->fetchRow($rs, $this->conn);
            if ($i == null) break;
            $dt->push(new Arry($i));
        }

        $this->driver->freeResult($rs, $this->conn);
        return $dt;
    }

    /**
     * Executes a query and returns a Map with the first row of the result set.
     * @param string $queryString Query to execute.
     * @param array $params Parameters to use in the query.
     * @return Map Result.
     */
    public function execAssoc ($queryString, $params=null)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive($this->conn))
            $this->open();

        $rs = null; $err = null;
        try { $rs = $this->driver->query($queryString, $this->conn, $params); }
        catch (\Throwable $e) { $err = $e->getMessage(); }

        if ($rs === false || $rs === null || $err !== null)
            throw new Error ($err ? $err : $this->driver->getLastError($this->conn));
        if ($rs === true)
            throw new Error ('Result is not a data set.');

        $data = $this->driver->fetchAssoc($rs, $this->conn);
        $this->driver->freeResult($rs, $this->conn);

        return $data === null ? null : Map::fromNativeArray($data, false);
    }

    /**
     * Returns the string representation of the reader.
     */
    public function __toString()
    {
        return '[Rose\Data\Connection]';
    }
};

/* ****************** */
use Rose\Data\Drivers\MySQLi;
use Rose\Data\Drivers\PostgreSQL;
use Rose\Data\Drivers\MSSQL;
use Rose\Data\Drivers\SQLServer;
use Rose\Data\Drivers\ODBC;

MySQLi::register();
PostgreSQL::register();
MSSQL::register();
SQLServer::register();
ODBC::register();

/*
    [Database]
    server=
    port=
    database=
    user=
    password=
    prefix=
    trace=
    driver=mysqli
*/
