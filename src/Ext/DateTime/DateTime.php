<?php

namespace Rose\Ext;

use Rose\DateTime;
use Rose\Expr;
use Rose\Text;
use Rose\Math;

// @title DateTime

/**
 * Returns the current date and time.
 * @code (`datetime:now` [targetTimezone])
 * @example
 * (datetime:now)
 * ; 2024-03-23 02:19:49
 *
 * (datetime:now "America/New_York")
 * ; 2024-03-23 04:20:05
 *
 * (datetime:now "GMT-7.5")
 * ; 2024-03-23 01:20:39
 */
Expr::register('datetime:now', function ($args) {
    return new DateTime('now', $args->length == 2 ? $args->get(1) : null);
});

/**
 * Returns the current date and time as a UNIX timestamp in UTC.
 * @code (`datetime:now-int`)
 * @example
 * (datetime:now-int)
 * ; 1711182138
 */
Expr::register('datetime:now-int', function ($args) {
    return (new DateTime('now', $args->length == 2 ? $args->get(1) : null))->getTimestamp();
});

/**
 * Returns the current datetime as a UNIX timestamp in milliseconds.
 * @code (`datetime:millis`)
 * @example
 * (datetime:millis)
 * ; 1711182672943
 */
Expr::register('datetime:millis', function ($args) {
    return \Rose\mstime();
});

/**
 * Parses a date and time string. Assumes source is in local timezone (LTZ) if no `sourceTimezone` specified. Note that the
 * default `targetTimezone` is the one configured in the `timezone` setting of the `Locale` configuration section.
 * @code (`datetime:parse` <input> [targetTimezone] [sourceTimezone])
 * @example
 * (datetime:parse "2024-03-23 02:19:49")
 * ; 2024-03-23 02:19:49
 *
 * (datetime:parse "2024-03-23 02:19:49" "America/New_York")
 * ; 2024-03-23 04:19:49
 */
Expr::register('datetime:parse', function ($args) {
    return new DateTime($args->get(1), $args->length >= 3 ? $args->get(2) : null, $args->length >= 4 ? $args->get(3) : null);
});

/**
 * Parses a date and time string and returns a UNIX timestamp.
 * @code (`datetime:int` <input>)
 * @example
 * (datetime:int "2024-03-23 02:19:49")
 * ; 1711181989
 */
Expr::register('datetime:int', function ($args) {
    return DateTime::getUnixTimestamp($args->get(1));
});

/**
 * Returns the subtraction of two datetime in a given unit (`A` minus `B`). Defaults to seconds.
 * Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
 * @code (`datetime:sub` <value-A> <value-B> [unit])
 * @example
 * (datetime:sub "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
 * ; -3600
 */
Expr::register('datetime:sub', function ($args) {
    $a = new DateTime ($args->get(1));
    $b = new DateTime ($args->get(2));
    $unit = $args->length == 4 ? $args->get(3) : 'SECOND';
    return $a->sub($b, $unit);
});

/**
 * Returns the absolute difference between two datetime in a given unit (defaults to seconds).
 * Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
 * @code (`datetime:diff` <value-A> <value-B> [unit])
 * @example
 * (datetime:diff "2024-03-23 02:19:49" "2024-03-23 03:19:49" "SECOND")
 * ; 3600
 */
Expr::register('datetime:diff', function ($args) {
    $a = new DateTime ($args->get(1));
    $b = new DateTime ($args->get(2));
    $unit = $args->length == 4 ? $args->get(3) : 'SECOND';
    return Math::abs($a->sub($b, $unit));
});

/**
 * Returns the addition of a given delta value in a given unit (defaults to seconds) to a datetime.
 * Valid units are: `YEAR`, `MONTH`, `DAY`, `HOUR`, `MINUTE`, `SECOND`.
 * @code (`datetime:add` <input> <delta> [unit])
 * @example
 * (datetime:add "2024-03-23 02:19:49" 3600 "SECOND")
 * ; 2024-03-23 03:19:49
 */
Expr::register('datetime:add', function ($args) {
    $a = new DateTime ($args->get(1));
    $b = $args->get(2);

    $unit = $args->length == 4 ? $args->get(3) : 'SECOND';
    return $a->add($b, $unit);
});

/**
 * Returns the date part of a datetime.
 * @code (`datetime:date` <input>)
 * @example
 * (datetime:date "2024-03-23 02:19:49")
 * ; 2024-03-23
 */
Expr::register('datetime:date', function ($args) {
    $a = (string)(new DateTime ($args->get(1)));
    return Text::substring($a, 0, 10);
});

/**
 * Returns the time part of a datetime (only hours and minutes).
 * @code (`datetime:time` <input>)
 * @example
 * (datetime:time "2024-03-23 02:19:49")
 * ; 02:19
 */
Expr::register('datetime:time', function ($args) {
    $a = (string)(new DateTime ($args->get(1)));
    return Text::substring($a, 11, 5);
});

/**
 * Formats a date and time string.
 * @code (`datetime:format` <input> <format>)
 * @example
 * (datetime:format "2024-03-23 02:19:49" "Year: %Y, Month: %m, Day: %d & Time: %H:%M:%S")
 * ; Year: 2024, Month: 03, Day: 23 & Time: 02:19:49
 */
Expr::register('datetime:format', function ($args) {
    $value = $args->get(1);
    $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
    return $value ? DateTime::strftime($args->get(2), $value + DateTime::$offset) : null;
});
