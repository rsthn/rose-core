<?php

namespace Rose\Errors;

/*
**	The error class contains information about errors that occurred in the system, usually the framework handler
**	will show a box with the error message for all the errors that were uncaught.
*/

class Error extends \Exception
{
    public function __construct (?string $message='', $code=0) {
        parent::__construct ($message ?? '', $code);
    }

    public function __typeCast ($typeName) {
        return '('.\Rose\typeOf($this).'): '.$this->getMessage();
    }

    public function getType() {
        return 'Error';
    }

    public function __get ($name) {
        if ($name === 'type')
            return $this->getType();
        if ($name === 'message')
            return $this->getMessage();
        return '';
    }
};
