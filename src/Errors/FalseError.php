<?php

namespace Rose\Errors;
use Rose\Errors\Error;

/*
**	Special error that doesn't describe an execution error, but rather a quick way to get out of some dog-watch block.
*/

class FalseError extends Error {
    public function __construct (string $message='') {
        parent::__construct ($message);
    }

    public function getType() {
        return 'FalseError';
    }
};
