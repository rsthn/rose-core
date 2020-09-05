<?php

	require('vendor/autoload.php');

	use Rose\Expr;
	use Rose\Map;
	use Rose\Arry;

	Rose\Main::cli();

	$data = new Map([
		'list' => [1, 2, 3, 4, 5],
		'name' => 'test',
		'test' => function ($x, $list) {
			return 'List is: ' . $list->map( function($i) use(&$x) { return $i*$x; } )->join(', ') . '\n';
		}
	]);

	try {
		$template = Expr::parseTemplate('[call this.[name] 10 [map i [filter i [list] [gt [i] 3]] [** 2 [i]]]]', '[', ']', false);
		echo Expr::expand($template, $data, 'arg');
	}
	catch (\Rose\Errors\Error $e) {
		echo($e->getMessage());
	}
