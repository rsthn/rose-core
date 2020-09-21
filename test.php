<?php

	require('vendor/autoload.php');

	Rose\Main::cli();

	echo \Rose\Expr::eval('(datetime::sub (datetime::add (datetime::now) 4 MINUTE) (datetime::now) )');
