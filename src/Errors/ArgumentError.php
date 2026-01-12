<?php

namespace Rose\Errors;
use Rose\Errors\Error;

/*
**	Describes an error that occurred when an illegal argument if found.
*/

class ArgumentError extends Error
{
    public function __construct ($message) {
        parent::__construct($message);
    }

    public function getType() {
        return 'ArgumentError';
    }
};
