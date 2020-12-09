<?php

	require('vendor/autoload.php');

	Rose\Main::cli();

	\Rose\DateTime::setTimezone('GMT-6');

	echo (\Rose\Expr::eval("
		(set x (array::new))
		(set y asc)
		(array::push (x) 'AAA' 'BB' 'CCCCC')
		(array::unshift (x) 'X' 'Y' 'Z')
		(array::push (x) (array::shift (x)))
		(array::unshift (x) (array::pop (x)))
		(array::sortl:(y) (x))
		(array::remove (x) 0)
		(yield (array::length (x)))
	", null, 'arg'));

	echo "\n";
