<?php

namespace Rose\Data;

use Rose\Map;
use Rose\Arry;

/**
 * Provides an interface to read data progressively from a database result set.
 */

class Reader
{
    // Database driver.
    private $driver;

    // Connection resource.
    private $conn;

    // Result set resource.
    private $rs;

    // Index of the last row retrieved.
    private $index;

    // Data of the last row retrieved.
    private $data;

    // Names of the fields in the result.
    private $fields;

    // Rows of the result, populated only if the `rows` property is accessed directly.
    private $rows;

    /**
     * Constructs the Reader.
     */
    public function __construct ($driver, $conn, $rs)
    {
        $this->driver = $driver;
        $this->conn = $conn;
        $this->rs = $rs;

        $this->index = -1;
        $this->data = null;

        $this->fields = new Arry();
        $this->rows = null;

        for ($i = $driver->getNumFields($rs, $conn)-1; $i >= 0; $i--)
            $this->fields->unshift ($driver->getFieldName($rs, $i, $conn));
    }

    /**
     * Returns the index of the last row retrieved.
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * Returns the data of the last row retrieved.
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Returns boolean indicating if an item was loaded from the reader or null if no more items left.
     */
    public function fetch()
    {
        if ($this->rs === null)
            return false;

        $data = $this->driver->fetchAssoc($this->rs, $this->conn);
        if (!$data) {
            $this->close();
            return false;
        }

        $this->index++;
        $this->data = Map::fromNativeArray($data, false);
        return true;
    }

    /**
     * Closes the data reader.
     */
    public function close() {
        if ($this->rs === null) return;
        $this->driver->freeResult ($this->rs, $this->conn);
        $this->rs = null;
    }

    /**
     * Runs the specified function for each of the rows from the reader.
     */
    public function forEach ($function) {
        while ($this->fetch())
            $function ($this->data, $this->index);
        return $this;
    }

    /**
     * Allows to directly access several functions as properties.
     */
    public function __get ($name)
    {
        switch ($name)
        {
            case 'fields':
                return $this->fields;

            case 'rows':
                if ($this->rows == null)
                {
                    $this->rows = new Arry();
                    $this->forEach(function ($item) { $this->rows->push($item); });
                }

                return $this->rows;

            case 'index':
                return $this->getIndex();

            case 'data':
                return $this->getData();

            case 'fetch':
                return $this->fetch();

            case 'close':
                return $this->close();
        }
    }

    /**
     * Returns the string representation of the reader.
     */
    public function __toString()
    {
        return '[Rose\Data\Reader]';
    }
};
