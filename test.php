<?php

	require('vendor/autoload.php');

	Rose\Main::cli();

	echo \Rose\Expr::eval('(datetime::parse (str (datetime::now)))');
