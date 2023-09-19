<?php

namespace Rose\Ext;

use Rose\DateTime;
use Rose\Expr;
use Rose\Text;

/**
 * Returns the current date and time.
 * @code (datetime::now [targetTimezone])
 */
Expr::register('datetime::now', function ($args) {
	return new DateTime('now', $args->length == 2 ? $args->get(1) : null);
});

/**
 * Returns the current date and time as a Unix timestamp.
 * @code (datetime::now-int [targetTimezone])
 */
Expr::register('datetime::now-int', function ($args) {
	return (new DateTime('now', $args->length == 2 ? $args->get(1) : null))->getTimestamp();
});

/**
 * Parses a date and time string.
 * @code (datetime::parse <string> [targetTimezone] [sourceTimezone])
 */
Expr::register('datetime::parse', function ($args) {
	return new DateTime($args->get(1), $args->length >= 3 ? $args->get(2) : null, $args->length >= 4 ? $args->get(3) : null);
});

/**
 * Parses a date and time string as a Unix timestamp.
 * @code (datetime::int <datetime>)
 */
Expr::register('datetime::int', function ($args) {
	return DateTime::getUnixTimestamp($args->get(1));
});

/**
 * Returns the subtraction of two dates in a given unit.
 * @code (datetime::sub <datetime> <datetime> [SECOND|MINUTE|HOUR|DAY|WEEK|YEAR])
 */
Expr::register('datetime::sub', function ($args) {
	$a = new DateTime ($args->get(1));
	$b = new DateTime ($args->get(2));
	$unit = $args->length == 4 ? $args->get(3) : 'SECOND';
	return $a->sub($b, $unit);
});

/**
 * Returns the differente between two dates in a given unit.
 * @code (datetime::diff <datetime> <datetime> [SECOND|MINUTE|HOUR|DAY|WEEK|YEAR])
 */
Expr::register('datetime::diff', function ($args) {
	$a = new DateTime ($args->get(1));
	$b = new DateTime ($args->get(2));
	$unit = $args->length == 4 ? $args->get(3) : 'SECOND';
	return $a->sub($b, $unit);
});

/**
 * Returns the addition of a date and a delta value in a given unit.
 * @code (datetime::add <datetime> <value> [SECOND|MINUTE|HOUR|DAY|WEEK|YEAR])
 */
Expr::register('datetime::add', function ($args) {
	$a = new DateTime ($args->get(1));
	$b = $args->get(2);

	$unit = $args->length == 4 ? $args->get(3) : 'SECOND';
	return $a->add($b, $unit);
});

/**
 * Returns the date part of a date and time string.
 * @code (datetime::date <datetime>)
 */
Expr::register('datetime::date', function ($args) {
	$a = (string)(new DateTime ($args->get(1)));
	return Text::substring($a, 0, 10);
});

/**
 * Returns the time part of a date and time string.
 * @code (datetime::time <datetime>)
 */
Expr::register('datetime::time', function ($args) {
	$a = (string)(new DateTime ($args->get(1)));
	return Text::substring($a, 11, 5);
});

/**
 * Formats a date and time string.
 * @code (datetime::format <datetime> <string>)
 */
Expr::register('datetime::format', function ($args) {
	$value = $args->get(1);
	$value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
	return $value ? DateTime::strftime($args->get(2), $value + DateTime::$offset) : null;
});
