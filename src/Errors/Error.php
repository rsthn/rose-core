<?php

namespace Rose\Errors;

/*
**	The error class contains information about errors that occurred in the system, usually the framework handler
**	will show a box with the error message for all the errors that were uncaught.
*/

class Error extends \Exception
{
	/*
	**	Creates an error object and uses the given parameter and the exception's error message.
	*/
    public function __construct (?string $message='', $code=0)
    {
        parent::__construct ($message ?? '', $code);
    }

	/*
	**	Converts the error to its string representation. The typeName parameter is ignored.
	*/
    public function __typeCast ($typeName)
    {
        return '('.typeOf($this).'): '.$this->getMessage();
    }
};
