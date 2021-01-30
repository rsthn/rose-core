<?php

require('vendor/autoload.php');

use Rose\Main;
use Rose\Ext\Wind;
use Rose\Arry;
use Rose\Map;

Main::cli();

$args = new Arry($argv);

if ($args->length < 2)
{
	echo "Use: cli <path> [arguments...]\n";
	return;
}

Rose\Ext\Wind::run($args->get(1), new Map ([ 'args' => $args->slice(2) ]));
