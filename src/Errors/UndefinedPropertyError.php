<?php

namespace Rose\Errors;
use Rose\Errors\Error;

/*
**	Describes an error that occurred when a property is undefined.
*/

class UndefinedPropertyError extends Error
{
    public function __construct ($message)
    {
        parent::__construct($message);
    }
};
