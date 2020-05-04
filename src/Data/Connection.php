<?php
/*
**	Rose\Data\Connection
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
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

namespace Rose\Data;

use Rose\Errors\Error;

use Rose\Data\Driver;
use Rose\Data\Table;
use Rose\Data\Reader;

use Rose\Map;
use Rose\Text;

/**
**	Provides an interface between a client and a database server.
*/

class Connection
{
	/*
	**	The connection resource used by this class.
	*/
	public $conn = null;

	/*
	**	Driver of the connection.
	*/
	private $driver;

	/*
	**	Prefix for all tables in the database, all instances of the symbol ## in a query will be replaced with this prefix.
	*/
	private $dbPrefix = '';

	/*
	**	Indicates if queries made using this connection should be traced.
	*/
	private $dbTracing = false;

	/*
	**	Connection server.
	*/
	private $dbServer;

	/*
	**	Username for the database.
	*/
	private $dbUser;

	/*
	**	Password for the database user.
	*/
	private $dbPassword;

	/*
	**	Name of the database to use.
	*/
	private $dbName;

	/*
	**	Available database drivers, registered using the `registerDriver` method.
	*/
	private static $drivers = null;

	/*
	**	Registers a database driver.
	*/
	public static function registerDriver ($name, $driver)
	{
		if (Connection::$drivers == null)
			Connection::$drivers = new Map();

		if (!($driver instanceof Driver))
			throw new Error ('Connection (registerDriver): Provided $driver parameter is not an instance of Rose\\Data\\Driver.');

		Connection::$drivers->set($name, $driver);
	}

	/*
	**	Constructs a database connection using the given configuration.
	*/
    public function __construct ($server=null, $user=null, $password=null, $database=null, $tracing=false, $prefix=null, $driver)
    {
        $this->dbServer = $server;
        $this->dbUser = $user;
        $this->dbPassword = $password;
		$this->dbName = $database;

		if (Connection::$drivers == null)
			throw new Error("Connection: There are no database drivers registered.");

		$this->driver = Connection::$drivers->get($driver);
		if ($this->driver == null)
			throw new Error("Connection: Driver `".$driver."` was not found.");

        if ($tracing == true)
            $this->tracing(true);

        if ($prefix)
            $this->prefix($prefix);
    }

	/*
	**	Called upon destruction of the instance, if the connection is active it will be closed.
	*/
    public function __destruct ()
    {
        $this->close();
    }

	/*
	**	Returns or sets the tracing state of the connection.
	*/
    public function tracing ($state=null)
    {
        if ($state !== null)
            $this->dbTracing = $state;

        return $this->dbTracing;
    }

	/*
	**	Returns or sets the database prefix.
	*/
    public function prefix ($value=null)
    {
        if (($value!==null))
        {
            $this->dbPrefix=$value;
        }
        return $this->dbPrefix;
    }

	/*
	**	Builds a connection using the given configuration, should contain fields: server, user, password, database, driver and optionally trace.
	*/	
    public static function fromConfig ($config, $autoConnect=true)
    {
		$conn = new Connection ($config->server, $config->user, $config->password, $config->database, $config->trace == 'true', $config->prefix, $config->driver);

        if ($autoConnect)
			$conn->open();

		return $conn;
    }

	/*
	**	Opens the connection to the database server. Throws an error if the operation failed.
	*/
    public function open ()
    {
		if ($this->conn != null)
			return;

        try {
            $this->conn = $this->driver->open ($this->dbServer, $this->dbUser, $this->dbPassword, $this->dbName);
        }
		catch (\Exception $e) {
			throw new Error ('Unable to open connection to '.$this->dbUser.'@'.$this->dbServer.': '.$e->getMessage());
		}

        if ($this->conn == null)
            throw new Error ('Unable to open connection to '.$this->dbUser.'@'.$this->dbServer.': '.$this->driver->getLastError(null));
    }

	/*
	**	Closes the connection to the database server.
	*/
    public function close ()
    {
        if ($this->conn != null)
        {
            $this->driver->close ($this->conn);
            $this->conn = null;
        }
    }

	/*
	**	Returns the last error from the database driver.
	*/
	public function getLastError()
	{
		return $this->driver->getLastError($this->conn);
	}

	/*
	**	Returns the last id generated on the last insertion statement.
	*/
    public function getLastInsertId ()
    {
        return $this->driver->getLastInsertId($this->conn);
    }

	/*
	**	Returns the number of affected rows on the last executed query.
	*/
    public function getAffectedRows ()
    {
        return $this->driver->getAffectedRows($this->conn);
    }

	/*
	**	Filters an input query string.
	*/
    private function filterQuery ($queryString)
    {
        return Text::replace('##', $this->dbPrefix, $queryString);
    }

	/*
	**	Escapes the given string to be used in a query.
	*/
	public static function escape ($value)
	{
		return "'" . addcslashes (Text::replace ("'", "''", $value), "#\\\t\n\v\f\r") . "'";
	}

	/*
	**	Executes a query (or an statement) and returns a `Table` (for queries) or a `boolean` (for statements).
	*/
    public function execQuery ($queryString)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive ($this->conn))
            $this->connect();

        try { $rs = $this->driver->query ($queryString, $this->conn); }
		catch (\Exception $e) { }

        if ($rs === false || $rs === null)
            throw new Error ($this->driver->getLastError($this->conn));

        if ($rs === true)
            return $rs;

		$dt = new Table();
		$i = null;

		while (true)
		{
			$i = $this->driver->fetchAssoc($rs, $this->conn);
			if ($i == null) break;

			$dt->rows->push(Map::fromNativeArray($i, false));
		}

		for ($i=$this->driver->getNumFields($rs, $this->conn)-1; $i >= 0; $i--)
			$dt->fields->unshift($this->driver->getFieldName($rs, $i, $this->conn));

		$this->driver->freeResult($rs, $this->conn);
		return $dt;
	}

	/*
	**	Executes a query (or an statement) and returns a Reader.
	*/
    public function execReader ($queryString)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive ($this->conn))
            $this->connect();

        try { $rs = $this->driver->query ($queryString, $this->conn); }
		catch (\Exception $e) { }

        if ($rs === false || $rs === null)
            throw new Error ($this->driver->getLastError($this->conn));

        if ($rs === true)
            throw new Error ('Result is not a data set.');

		return new Reader ($this->driver, $this->conn, $rs);
    }

	/*
	**	Executes a query and returns an scalar value. That is, the first column of the first row of the result set.
	*/
    public function execScalar ($queryString)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive ($this->conn))
            $this->connect();

        try { $rs = $this->driver->query ($queryString, $this->conn); }
		catch (\Exception $e) { }

        if ($rs === false || $rs === null)
			throw new Error ($this->driver->getLastError($this->conn));

        if ($rs === true)
            throw new Error ('Result is not a data set.');

        $array = $this->driver->fetchRow($rs, $this->conn);
		$this->driver->freeResult($rs, $this->conn);

        return $array[0];
    }

	/*
	**	Executes a query and returns an array with the rows.
	*/
	public function execArray ($queryString)
	{
		$data = $this->execQuery ($queryString);

		if ($data === true)
			throw new Error ('Result is not a data set.');

		return $data->rows;
	}

	/*
	**	Executes a query and returns a Map with the first row of the result set.
	*/
    public function execAssoc ($queryString)
    {
        if ($this->conn == null)
            throw new Error ('Connection: Database connection is not open.');

        $queryString = $this->filterQuery($queryString);
        if ($this->dbTracing) \Rose\trace($queryString);

        if (!$this->driver->isAlive ($this->conn))
			$this->connect();

        try { $rs = $this->driver->query ($queryString, $this->conn); }
        catch (\Exception $e) { }

        if ($rs === false || $rs === null)
			throw new Error ($this->driver->getLastError($this->conn));

		if ($rs === true)
            throw new Error ('Result is not a data set.');

		$data = $this->driver->fetchAssoc($rs, $this->conn);
		$this->driver->freeResult($rs, $this->conn);

        if ($data == null)
            return null;

        return Map::fromNativeArray($data, false);
    }
};

/* ****************** */

use Rose\Data\Drivers\MySQL;
use Rose\Data\Drivers\MySQLi;
use Rose\Data\Drivers\PostgreSQL;
use Rose\Data\Drivers\MSSQL;
use Rose\Data\Drivers\SQLServer;
use Rose\Data\Drivers\ODBC;

MySQL::register();
MySQLi::register();
PostgreSQL::register();
MSSQL::register();
SQLServer::register();
ODBC::register();
