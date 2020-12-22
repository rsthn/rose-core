<?php

	require('vendor/autoload.php');

	Rose\Main::cli();

	\Rose\DateTime::setTimezone('GMT-6');

	echo (\Rose\Expr::eval("
	
A = (json (& name: 'A' last: 'B'))
B = (json (& name 'A' last 'B'))
C = (json (& :name 'A' :last 'B'))

	", null, 'arg'));

	echo "\n";
