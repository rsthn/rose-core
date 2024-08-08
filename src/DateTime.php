<?php

namespace Rose;

use Rose\Math;
use Rose\Locale;
use Rose\Text;

/**
 * Provides an interface to manipulate date and time. Stored always in UTC (int). Shown always in LTZ (local timezone).
 * Which means "string" will always be considered to be in LTZ, and "int" in UTC.
 */

class DateTime
{
    /**
     * Periods of time.
     */
    public const SECOND = 'second';
    public const MINUTE = 'minute';
    public const HOUR = 'hour';
    public const DAY = 'day';
    public const WEEK = 'week';
    public const MONTH = 'month';
    public const YEAR = 'year';

    /**
     * Date components.
     */
    public $year, $month, $day;

    /**
     * Time components
     */
    public $hour, $minute, $second;

    /**
     * Descriptive components.
     */
    public $week, $weekday;

    /**
     * UNIX Timestamp.
     */
    private $timestamp;

    /**
     * Target timezone, used to appropriately maintain a correct timestamp value when using setTimestamp().
     */
    public $targetTimezone;

    /**
     * Default timezone and timezone offset in seconds.
     */
    public static $timezone;
    public static $offset;
    public static $utc;

    /**
     * Initializes static constants of this class.
     */
    public static function init()
    {
        date_default_timezone_set('UTC');

        self::$utc = new \DateTimeZone('UTC');
        self::$timezone = Locale::getInstance()->timezone;

        if (!self::$timezone)
            self::$timezone = 'UTC';

        self::$offset = self::timezoneOffset(self::$timezone);
    }

    /**
     * Sets the global timezone.
     * @param string $name
     */
    public static function setTimezone ($name)
    {
        self::$timezone = $name;
        self::$offset = self::timezoneOffset($name);
    }

    /**
     * Returns the offset from UTC to the given timezone.
     * @param string $timezone
     * @return int
     */
    public static function timezoneOffset ($timezone)
    {
        if (Text::toUpperCase($timezone) == 'LTZ' || !$timezone)
            $timezone = self::$timezone;

        $timezone = new \DateTimeZone ($timezone);
        return $timezone->getOffset(new \DateTime('now', $timezone));
    }

    /**
     * Constructs a DateTime object. Set to the current date and time by default.
     * @param mixed $datetime
     * @param string $targetTimezone
     * @param string $fromTimezone
     * @return DateTime
     */
    public function __construct ($datetime=null, $targetTimezone=null, $fromTimezone=null)
    {
        if (Text::toUpperCase($targetTimezone) === 'LTZ')
            $targetTimezone = self::$timezone;

        if (\Rose\isNumeric($datetime)) {
            $datetime = DateTime::strftime('%Y-%m-%d %H:%M:%S', (int)$datetime);
        }
        else if ($datetime instanceOf DateTime) {
            if (!$targetTimezone) $targetTimezone = $datetime->targetTimezone;
            $datetime = DateTime::strftime('%Y-%m-%d %H:%M:%S', $datetime->getTimestamp());
        }
        else
        {
            if ($datetime && $datetime !== 'now') {
                $targetTimezone = $targetTimezone ? $targetTimezone : self::$timezone;
                $fromTimezone = $fromTimezone ? $fromTimezone : self::$timezone;
            }
            else {
                $datetime = 'now';
                $targetTimezone = $targetTimezone ? $targetTimezone : self::$timezone;
                $fromTimezone = $fromTimezone ? $fromTimezone : 'UTC';
            }
        }

        $tmp = new \DateTime ($datetime, self::$utc);
        $tmp = mktime ($tmp->format('H'), $tmp->format('i'), $tmp->format('s'), $tmp->format('m'), $tmp->format('d'), $tmp->format('Y'));

        $this->targetTimezone = $targetTimezone;
        $this->setTimestamp($tmp, $targetTimezone, $fromTimezone);
    }

    /**
     * Returns the DateTime in UNIX timestamp format (UTC).
     * @return int
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Sets the DateTime from the specified UNIX timestamp (UTC).
     * @param int $timestamp
     * @param string $targetTimezone - Defaults to the object's target timezone.
     * @param string $fromTimezone - Defaults to UTC.
     * @return DateTime
     */
    public function setTimestamp ($timestamp, $targetTimezone='', $fromTimezone='')
    {
        if (!$targetTimezone) $targetTimezone = $this->targetTimezone;
        if (!$fromTimezone) $fromTimezone = 'UTC';

        $this->timestamp = ($timestamp -= self::timezoneOffset($fromTimezone));
        $timestamp += self::timezoneOffset($targetTimezone);

        $this->year = (int)DateTime::strftime('%Y', $timestamp);
        $this->month = (int)DateTime::strftime('%m', $timestamp);
        $this->day = (int)DateTime::strftime('%d', $timestamp);
        $this->week = (int)DateTime::strftime('%W', $timestamp);
        $this->weekday = (int)DateTime::strftime('%w', $timestamp);

        $this->hour = (int)DateTime::strftime('%H', $timestamp);
        $this->minute = (int)DateTime::strftime('%M', $timestamp);
        $this->second = (int)DateTime::strftime('%S', $timestamp);

        return $this;
    }

    /**
     * Returns the UNIX timestamp of the specified argument, can be a string date, a DateTime or an integer. If null
     * is specified, null will be returned. And if true is specified the current time will be returned.
     *
     * @param mixed $value
     * @return int
     */
    public static function getUnixTimestamp ($value=true)
    {
        if ($value === null)
            return null;

        if ($value === true)
            return time();

        if (\Rose\isNumeric($value))
            return (int)$value;

        if ($value instanceof \DateTime || $value instanceof DateTime)
            return $value->timestamp;

        return (new DateTime ($value))->getTimestamp();
    }

    /**
     * Returns the time span expressed in seconds given a time period name (i.e. SECOND, MINUTE, etc). If the
     * name cannot be converted to seconds, the string representation of the name will be returned.
     * @param string $name
     * @return string
     */
    public static function getUnit (string $name)
    {
        switch (Text::toUpperCase($name))
        {
            case 'SECOND': return 1;
            case 'MINUTE': return 60;
            case 'HOUR': return 3600;
            case 'DAY': return 86400;
            case 'WEEK': return 604800;
            case 'MONTH': return DateTime::MONTH;
            case 'YEAR': return DateTime::YEAR;
        }

        return null;
    }

    /**
     * Returns boolean if the specified year is a leap year.
     */
    public static function isLeapYear ($year)
    {
        return ($year % 4 == 0 && ($year % 100 != 0 || $year % 400 == 0));
    }

    /**
     * Returns the last day of the specified month (1..12).
     */
    public static function monthLastDay ($month, $year)
    {
        if ($month == 2)
            return self::isLeapYear($year) ? 29 : 28;

        if ($month == 4 || $month == 6 || $month == 9 || $month == 11)
            return 30;

        return 31;
    }

    /**
     * Updates the DateTime object to reflect the separate date and time components.
     * @return DateTime
     */
    private function update()
    {
        // Process out of range seconds.
        while ($this->second >= 60) {
            $this->second -= 60;
            $this->minute++;
        }

        while ($this->second < 0) {
            $this->second += 60;
            $this->minute--;
        }

        // Process out of range minutes.
        while ($this->minute >= 60) {
            $this->minute -= 60;
            $this->hour++;
        }

        while ($this->minute < 0) {
            $this->minute += 60;
            $this->hour--;
        }

        // Process out of range hours.
        while ($this->hour >= 24) {
            $this->hour -= 24;
            $this->day++;
        }

        while ($this->hour < 0) {
            $this->hour += 24;
            $this->day--;
        }

        // Process out of range month.
        while ($this->month > 12) {
            $this->month -= 12;
            $this->year++;
        }

        while ($this->month < 1) {
            $this->month += 12;
            $this->year--;
        }

        // Process out of range day.
        while ($this->day > ($lastDay = self::monthLastDay($this->month, $this->year)))
        {
            $this->day -= $lastDay;
            $this->month++;

            if ($this->month > 12) {
                $this->month -= 12;
                $this->year++;
            }
        }

        while ($this->day < 1)
        {
            $this->month--;
            if ($this->month < 1) {
                $this->month += 12;
                $this->year--;
            }

            $this->day += self::monthLastDay($this->month, $this->year);
        }

        return $this;
    }

    /**
     * Returns the difference in the specified unit between the current DateTime and the given one.
     * @param mixed $datetime
     * @param string $unit
     */
    public function sub ($datetime, $unit=DateTime::SECOND)
    {
        $code = DateTime::getUnit($unit);
        if ($code === null)
            throw new \Exception ("Invalid unit specified: " . $unit);

        if ($code === DateTime::MONTH) {
            $datetime = new DateTime($datetime);
            return ($this->year*12 + $this->month) - ($datetime->year*12 + $datetime->month);
        }

        if ($code === DateTime::YEAR) {
            $datetime = new DateTime($datetime);
            return $this->year - $datetime->year;
        }

        return (int)floor(($this->timestamp - DateTime::getUnixTimestamp($datetime)) / $code);
    }

    /**
     * Adds the specified time span to the DateTime, negative values are allowed.
     * @param int $span
     * @param string $unit
     */
    public function add ($span, $unit=DateTime::SECOND)
    {
        $val = DateTime::getUnit($unit);
        if ($val === null)
            throw new \Exception ("Invalid unit specified: " . $unit);

        if (\Rose\isInteger($val))
            return $this->setTimestamp($this->timestamp + $val * $span);

        if ($val === DateTime::MONTH)
            $this->month += $span;
        else if ($val === DateTime::YEAR)
            $this->year += $span;

        return $this->update();
    }

    /**
     * Formats the date time and returns a string, all in ISO DateTime format.
     */
    public function format ($format)
    {
        switch (Text::toUpperCase($format))
        {
            case 'DATETIME':
                return sprintf("%4d-%02d-%02d %02d:%02d:%02d", $this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second);

            case 'UTC':
                return DateTime::strftime("%a, %d %b %Y %H:%M:%S GMT", $this->getTimestamp());

            case 'ISO':
                return DateTime::strftime("%4d-%02d-%02dT%02d:%02d:%02dZ", $this->getTimestamp());

            case 'TIME':
                return sprintf("%02d:%02d:%02d", $this->hour, $this->minute, $this->second);

            case 'DATE':
                return sprintf("%4d-%02d-%02d", $this->year, $this->month, $this->day);
        }
    }

    /**
     * Formats the date time and returns a string using strftime-like modifiers.
     */
    public static function strftime ($format, $timestamp)
    {
        $n = Text::length($format);
        $str = '';

        for ($i = 0; $i < $n; $i++)
        {
            if ($format[$i] != '%')
            {
                $str .= $format[$i];
                continue;
            }

            switch($format[++$i])
            {
                case 'a': $str .= date('D', $timestamp); break; // A textual representation of a day, three letters - Mon through Sun.
                case 'A': $str .= date('l', $timestamp); break; // A full textual representation of the day of the week - Sunday through Saturday.
                case 'd': $str .= date('d', $timestamp); break; // Day of the month, 2 digits with leading zeros - 01 to 31.
                case 'e': $str .= date('j', $timestamp); break; // Day of the month without leading zeros - 1 to 31.
                case 'u': $str .= date('N', $timestamp); break; // ISO 8601 numeric representation of the day of the week - 1 (for Monday) through 7 (for Sunday).
                case 'w': $str .= date('w', $timestamp); break; // Numeric representation of the day of the week - 0 (for Sunday) through 6 (for Saturday).
                case 'W': $str .= date('W', $timestamp); break; // ISO 8601 week number of year, weeks starting on Monday - Example: 42 (the 42nd week in the year).
                
                case 'b': $str .= date('M', $timestamp); break; // A short textual representation of a month, three letters - Jan through Dec.
                case 'B': $str .= date('F', $timestamp); break; // A full textual representation of a month, such as January or March - January through December.
                case 'h': $str .= date('M', $timestamp); break; // A short textual representation of a month, three letters - Jan through Dec.
                case 'm': $str .= date('m', $timestamp); break; // Numeric representation of a month, with leading zeros - 01 through 12.
                case 'n': $str .= date('n', $timestamp); break; // Numeric representation of a month, without leading zeros - 1 through 12.

                case 'y': $str .= date('y', $timestamp); break; // A two digit representation of a year - Examples: 99 or 03.
                case 'Y': $str .= date('Y', $timestamp); break; // A full numeric representation of a year, at least 4 digits, with - for years BCE. Examples: -0055, 0787, 1999, 2003.

                case 'H': $str .= date('H', $timestamp); break; // 24-hour format of an hour with leading zeros - 00 through 23.
                case 'k': $str .= date('G', $timestamp); break; // 24-hour format of an hour without leading zeros - 0 through 23.
                case 'I': $str .= date('h', $timestamp); break; // 12-hour format of an hour with leading zeros - 01 through 12.
                case 'l': $str .= date('g', $timestamp); break; // 12-hour format of an hour without leading zeros - 1 through 12.
                case 'M': $str .= date('i', $timestamp); break; // Minutes with leading zeros - 00 to 59.
                case 'S': $str .= date('s', $timestamp); break; // Seconds with leading zeros - 00 through 59.
                case 's': $str .= date('S', $timestamp); break; // English ordinal suffix for the day of the month, 2 characters - st, nd, rd or th. Works well with j.
                case 'p': $str .= date('A', $timestamp); break; // Uppercase Ante meridiem and Post meridiem - AM or PM.
                case 'P': $str .= date('a', $timestamp); break; // Lowercase Ante meridiem and Post meridiem - am or pm.
            }
        }

        return $str;
    }

    /**
     * Returns the string representation of the DateTime object (YYYY-MM-DD HH:II:SS);
     */
    public function __toString () {
        return $this->format('DATETIME');
    }
};

/**
 * Initialize DateTime class.
 */
DateTime::init();
