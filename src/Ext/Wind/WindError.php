<?php

namespace Rose\Ext\Wind;

use Rose\Errors\Error;
use Rose\Map;

class WindError extends Error
{
    public $data;

    public function __construct ($name, $data=null) {
        if ($data === null) {
            $data = $name;
            $name = 'WindError';
        }
        parent::__construct($name);
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function __toString() {
        return json_encode($this->data);
    }
};
