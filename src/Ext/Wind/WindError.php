<?php

namespace Rose\Ext\Wind;

use Rose\Errors\Error;
use Rose\Map;

class WindError extends Error
{
    public $data;
    public $_details;

    public function __construct ($name, $data=null) {
        if ($data === null) {
            $data = $name;
            $name = 'WindError';
        }
        parent::__construct($name);
        $this->data = $data;
        $this->_details = null;
    }

    public function getType() {
        return 'WindError';
    }

    public function getData() {
        return $this->data;
    }

    public function __toString() {
        return json_encode($this->data);
    }

    public function __get ($name) {
        if ($name === 'details') {
            if (!$this->_details)
                $this->_details = new Map($this->data);
            return $this->_details;
        }
        return parent::__get($name);
    }
};
