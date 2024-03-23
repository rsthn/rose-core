<?php

namespace Rose\Ext\Wind;

use Rose\Errors\Error;
use Rose\Map;

class WindError extends Error
{
    private $response;

    public function __construct ($response)
    {
        parent::__construct ('WindError');
        $this->response = $response;
    }

    public function getResponse ()
    {
        return $this->response;
    }
};
