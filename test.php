<?php

	require('vendor/autoload.php');

	Rose\Main::cli();

	\Rose\DateTime::setTimezone('GMT-6');

	echo \Rose\Expr::eval("
		(join ' ' (map i (for i from 1 count 20 (@  (if (eq 4 (i)) (break) else (echo (i))) ) ) (* 2 (i))))
	")."\n";
