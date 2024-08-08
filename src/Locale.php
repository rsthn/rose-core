<?php

namespace Rose;

use Rose\Configuration;
use Rose\Text;
use Rose\Expr;
use Rose\DateTime;

// @title Locale
// @desc Provides locale-related information and formatting functions.

class Locale
{
    /**
     * Primary and only instance of this class.
     */
    private static $objectInstance = null;

    /**
     * Loaded timezone string, can be either a named timezone or a UTC offset
     */
    public $timezone;

    /**
     * Initializes the instance of the class. Similar to calling getInstance().
     */
    public static function init() {
        self::getInstance();
    }

    /**
     * Returns the instance of this class.
     */
    public static function getInstance ()
    {
        if (Locale::$objectInstance == null) {
            Locale::$objectInstance = new Locale();
            Locale::$objectInstance->initialize();
        }

        return Locale::$objectInstance;
    }

    /**
     * Private constructor to prevent instantiation. Use getInstance() to get the singleton instance.
     */
    private function __construct() {
    }

    /**
     * Initializes environment specific elements using the configuration of the locale object.
     */
    private function initialize()
    {
        $config = Configuration::getInstance()->Locale;

        $this->timezone = $config ? $config->timezone : null;
        if (!$this->timezone) return;

        try {
            new \DateTimeZone ($this->timezone);
        }
        catch (\Throwable $e) {
            trace ('Locale: Invalid or unknown timezone: ' . $this->timezone);
            $this->timezone = '+00:00';
        }
    }

    /**
     * Formats an object using the specified format type, which can be: NUMBER, INTEGER, TIME, DATE, DATETIME, GMT, UTC, SDATE, and SDATETIME.
     */
    public function format ($formatType, $value, $format=null)
    {
        $config = Configuration::getInstance()->Locale;

        switch (Text::toUpperCase($formatType))
        {
            case 'NUMBER':
                if (!$format) $format = 'numeric';
                if ($config && $config->has($format)) $format = $config->get($format);
                if ($format === 'numeric') $format = '.2,';
                return number_format((double)$value, $format[1], $format[0], $format[2]);

            case 'INTEGER':
                if (!$format) $format = 'numeric';
                if ($config && $config->has($format)) $format = $config->get($format);
                if ($format === 'numeric') $format = '.2,';
                return number_format((double)$value, 0, 0, $format[2]);

            case 'TIME':
                if (!$format) $format = 'time';
                if ($config && $config->has($format)) $format = $config->get($format);

                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return $value ? DateTime::strftime($format, $value + DateTime::$offset) : null;

            case 'DATE':
                if (!$format) $format = 'date';
                if ($config && $config->has($format)) $format = $config->get($format);

                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return $value ? DateTime::strftime($format, $value + DateTime::$offset) : null;

            case 'DATETIME':
                if (!$format) $format = 'datetime';
                if ($config && $config->has($format)) $format = $config->get($format);

                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return $value ? DateTime::strftime($format, $value + DateTime::$offset) : null;

            case 'GMT':
                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return date('D, d M Y H:i:s ', $value) . 'GMT';

            case 'UTC':
                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return date('Y-m-d\TH:i:s\Z', $value);

            case 'ISO_DATE':
                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return $value ? DateTime::strftime('%Y-%m-%d', $value + DateTime::$offset) : null;

            case 'ISO_TIME':
                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return $value ? DateTime::strftime('%H:%M:%S', $value + DateTime::$offset) : null;

            case 'ISO_DATETIME':
                $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                return $value ? DateTime::strftime('%Y-%m-%d %H:%M:%S', $value + DateTime::$offset) : null;

            default:
                if (Text::toUpperCase(Text::substring ($formatType, 0, 3)) == 'DT_')
                {
                    $value = $value === null ? null : (\Rose\isInteger($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
                    return $value ? DateTime::strftime($config->get($formatType), $value + DateTime::$offset) : null;
                }

                if (Text::toUpperCase(Text::substring($formatType,0,7)) == 'NUMERIC')
                {
                    $tmp = $config->get($formatType);
                    return number_format ((double)$value, $tmp[1], $tmp[0], $tmp[2]);
                }

                return Text::format($config->get($formatType), $value);
        }
    }
};

/**
 * Formats a value as a number with the specified format or uses [Locale.numeric] if no format is specified.
 * @code (`locale:number` <format> <value>)
 * @code (`locale:number` <value>)
 * @example
 * (locale:number 1234567.89)
 * ; 1,234,567.89
 *
 * (locale:number ",3'" 1234567.89)
 * ; 1'234'567,890
 */
Expr::register('locale:number', function ($args) {
    return Locale::getInstance()->format('NUMBER', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null);
});

/**
 * Formats a value as an integer with the specified format or uses [Locale.numeric] if no format is specified.
 * @code (`locale:integer` <format> <value>)
 * @code (`locale:integer` <value>)
 * @example
 * (locale:integer 1234567.89)
 * ; 1,234,568
 * 
 * (locale:integer ",3'" 1234567.89)
 * ; 1'234'568
 */
Expr::register('locale:integer', function ($args) {
    return Locale::getInstance()->format('INTEGER', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null);
});

/**
 * Formats a value as a time with the specified format or uses [Locale.time] if no format is specified.
 * @code (`locale:time` <format> <value>)
 * @code (`locale:time` <value>)
 * @example
 * (locale:time "2024-12-31 23:15:37")
 * ; 11:15 PM
 * 
 * (locale:time "%H:%M:%S" "2024-12-31 23:15:37")
 * ; 23:15:37
 */
Expr::register('locale:time', function ($args) {
    return Locale::getInstance()->format('TIME', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null);
});

/**
 * Formats a value as a date with the specified format or uses [Locale.date] if no format is specified.
 * @code (`locale:date` <format> <value>)
 * @code (`locale:date` <value>)
 * @example
 * (locale:date "2024-12-31 23:15:37")
 * ; 31/12/2024
 * 
 * (locale:date "%Y-%m-%d" "2024-12-31 23:15:37")
 * ; 2024-12-31
 */
Expr::register('locale:date', function ($args) {
    return Locale::getInstance()->format('DATE', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null);
});

/**
 * Formats a value as a date and time with the specified format or uses [Locale.datetime] if no format is specified.
 * @code (`locale:datetime` <format> <value>)
 * @code (`locale:datetime` <value>)
 * @example
 * (locale:datetime "2024-12-31 23:15:37")
 * ; 31/12/2024 23:15
 * 
 * (locale:datetime "%Y-%m-%d %H:%M:%S" "2024-12-31 23:15:37")
 * ; 2024-12-31 23:15:37
 */
Expr::register('locale:datetime', function ($args) {
    return Locale::getInstance()->format('DATETIME', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null);
});

/**
 * Formats a value as a GMT date and time.
 * @code (`locale:gmt` <value>)
 * @example
 * (locale:gmt "2024-12-31 23:15:37")
 * ; Tue, 31 Dec 2024 23:15:37 GMT
 */
Expr::register('locale:gmt', function ($args) {
    return Locale::getInstance()->format('GMT', $args->get(1));
});

/**
 * Formats a value as a UTC date and time.
 * @code (`locale:utc` <value>)
 * @example
 * (locale:utc "2024-12-31 23:15:37")
 * ; 2024-12-31T23:15:37Z
 */
Expr::register('locale:utc', function ($args) {
    return Locale::getInstance()->format('UTC', $args->get(1));
});

/**
 * Formats a value as an ISO date.
 * @code (`locale:iso-date` <value>)
 * @example
 * (locale:iso-date "2024-12-31 23:15:37")
 * ; 2024-12-31
 */
Expr::register('locale:iso-date', function ($args) {
    return Locale::getInstance()->format('ISO_DATE', $args->get(1));
});

/**
 * Formats a value as an ISO time.
 * @code (`locale:iso-time` <value>)
 * @example
 * (locale:iso-time "2024-12-31 23:15:37")
 * ; 23:15:37
 */
Expr::register('locale:iso-time', function ($args) {
    return Locale::getInstance()->format('ISO_TIME', $args->get(1));
});

/**
 * Formats a value as an ISO date and time.
 * @code (`locale:iso-datetime` <value>)
 * @example
 * (locale:iso-datetime "2024-12-31 23:15:37")
 * ; 2024-12-31 23:15:37
 */
Expr::register('locale:iso-datetime', function ($args) {
    return Locale::getInstance()->format('ISO_DATETIME', $args->get(1));
});

/**
 * Formats a value using the specified format type, which can be: NUMBER, INTEGER, TIME, DATE, DATETIME, GMT, UTC, SDATE, and SDATETIME.
 * @code (`locale:format` <format-type> <value> [format])
 * @example
 * (locale:format "NUMBER" 1234567.89)
 * ; 1,234,567.89
 * 
 * (locale:format "TIME" "2024-12-31 23:15:37")
 * ; 11:15 PM
 * 
 * (locale:format "INTEGER" ",3'" 1234567.89)
 * ; 1'234'568
 */
Expr::register('locale:format', function ($args) {
    return Locale::getInstance()->format($args->get(1), $args->length == 4 ? $args->get(3) : $args->get(2), $args->{2});
});
