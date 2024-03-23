<?php

namespace Rose\Ext\Wind;

use Rose\Errors\Error;

class SubReturn extends Error
{
    public function __construct ($message=null)
    {
        parent::__construct ($message);
    }
};
