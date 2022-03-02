<?php
/*
**	Rose\DateTime
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose;

use Rose\Math;
use Rose\Locale;
use Rose\Text;

/*
**	Provides an interface to manipulate date and time. Stored always in UTC. Shown always in LTZ (local timezone).
*/

class DateTime
{
	/*
	**	Periods of time expressed in seconds.
	*/
	public const SECOND = 1;
	public const MINUTE = 60*DateTime::SECOND;
	public const HOUR = 60*DateTime::MINUTE;
	public const DAY = 24*DateTime::HOUR;
	public const WEEK = 7*DateTime::DAY;
	public const YEAR = 365*DateTime::DAY;

	/*
	**	Date components.
	*/
    private $year, $month, $day;

	/*
	**	Time components
	*/
	private $hour, $minute, $second;

	/*
	**	UNIX Timestamp.
	*/
    private $timestamp;

	/*
	**	Target timezone, used to appropriately maintain a correct timestamp value when using setTimestamp().
	*/
	public $targetTimezone;

	/*
	**	Default timezone and timezone offset in seconds.
	*/
	public static $timezone;
	public static $offset;
	public static $utc;

	/*
	**	Initialices static constants of this class.
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

	/*
	**	Sets the global timezone.
	*/
	public static function setTimezone ($name)
	{
		self::$timezone = $name;
		self::$offset = self::timezoneOffset($name);
	}

	/*
	**	Returns the offset from UTC to the given timezone.
	*/
	public static function timezoneOffset ($timezone)
	{
		if (Text::toUpperCase($timezone) == 'LTZ' || !$timezone)
			$timezone = self::$timezone;

		$timezone = new \DateTimeZone ($timezone);
		return $timezone->getOffset(new \DateTime('now', $timezone));
	}

	/*
	**	Constructs a DateTime object, by default will contain the current date and time.
	*/
	public function __construct ($datetime=null, $targetTimezone=null, $fromTimezone=null)
    {
		if (Text::toUpperCase($targetTimezone) === 'LTZ')
			$targetTimezone = self::$timezone;

		if (is_numeric($datetime))
		{
			$datetime = DateTime::strftime('%Y-%m-%d %H:%M:%S', $datetime);
		}
		else if ($datetime instanceOf DateTime)
		{
			if (!$targetTimezone) $targetTimezone = $datetime->targetTimezone;
			$datetime = DateTime::strftime('%Y-%m-%d %H:%M:%S', $datetime->getTimestamp());
		}
		else
		{
			if ($datetime && $datetime !== 'now')
			{
				$targetTimezone = $targetTimezone ? $targetTimezone : self::$timezone;
				$fromTimezone = $fromTimezone ? $fromTimezone : self::$timezone;
			}
			else
			{
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

	/*
	**	Returns the DateTime in UNIX timestamp format (UTC).
	*/
	public function getTimestamp ()
	{
		return $this->timestamp;
	}

	/*
	**	Sets the DateTime from the specified UNIX timestamp (UTC).
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

		$this->hour = (int)DateTime::strftime('%H', $timestamp);
		$this->minute = (int)DateTime::strftime('%M', $timestamp);
		$this->second = (int)DateTime::strftime('%S', $timestamp);

		return $this;
	}

	/*
	**	Returns the UNIX timestamp of the specified argument, can be a string date, a DateTime or an integer. If null is specified, null will be returned. And
	**	if true is specified the current time will be returned.
	*/
	public static function getUnixTimestamp ($value=true)
	{
		if ($value === null)
			return null;

		if ($value === true)
			return time();

		if (is_numeric($value))
			return $value;

		if ($value instanceof \DateTime || $value instanceof DateTime)
			return $value->timestamp;

		return (new DateTime ($value))->getTimestamp();
	}

	/*
	**	Returns the time span expressed in seconds given a time period name (i.e. SECOND, MINUTE, etc).
	*/
	public static function getUnit ($name)
	{
		if (is_numeric($name))
			return $name;

		switch (Text::toUpperCase($name))
		{
			case 'SECOND': return DateTime::SECOND;
			case 'MINUTE': return DateTime::MINUTE;
			case 'HOUR': return DateTime::HOUR;
			case 'DAY': return DateTime::DAY;
			case 'WEEK': return DateTime::WEEK;
			case 'YEAR': return DateTime::YEAR;
		}

		return 1;
	}

	/*
	**	Returns the difference in the specified unit between the current DateTime and the given one.
	*/
	public function sub ($datetime, $unit=DateTime::SECOND)
	{
		return (int)floor(($this->timestamp - DateTime::getUnixTimestamp($datetime)) / DateTime::getUnit($unit));
	}

	/*
	**	Adds the specified time span to the DateTime, negative values are allowed.
	*/
	public function add ($span, $unit=DateTime::SECOND)
	{
		return $this->setTimestamp ($this->timestamp + $span * DateTime::getUnit($unit));
	}

	/*
	**	Formats the date time and returns a string, all in ISO DateTime format.
	*/
    public function format ($format)
    {
        switch (Text::toUpperCase($format))
        {
            case 'DATETIME':
				return sprintf("%4d-%02d-%02d %02d:%02d:%02d", $this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second);

			case 'UTC':
				return DateTime::strftime("%a, %d %b %Y %H:%M:%S GMT", $this->getTimestamp());

            case 'TIME':
				return sprintf("%02d:%02d:%02d", $this->hour, $this->minute, $this->second);

            case 'DATE':
				return sprintf("%4d-%02d-%02d", $this->year, $this->month, $this->day);
        }
    }

	/*
	**	Formats the date time and returns a string using strftime-like modifiers.
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
				case 'a': $str .= date('D', $timestamp); break;
				case 'A': $str .= date('l', $timestamp); break;
				case 'd': $str .= date('d', $timestamp); break;
				case 'e': $str .= date('j', $timestamp); break;
				case 'u': $str .= date('N', $timestamp); break;
				case 'w': $str .= date('w', $timestamp); break;
				case 'W': $str .= date('W', $timestamp); break;
				
				case 'b': $str .= date('M', $timestamp); break;
				case 'B': $str .= date('F', $timestamp); break;
				case 'h': $str .= date('M', $timestamp); break;
				case 'm': $str .= date('m', $timestamp); break;
				case 'n': $str .= date('n', $timestamp); break;

				case 'y': $str .= date('y', $timestamp); break;
				case 'Y': $str .= date('Y', $timestamp); break;

				case 'H': $str .= date('H', $timestamp); break;
				case 'k': $str .= date('G', $timestamp); break;
				case 'I': $str .= date('h', $timestamp); break;
				case 'l': $str .= date('g', $timestamp); break;
				case 'M': $str .= date('i', $timestamp); break;
				case 'S': $str .= date('s', $timestamp); break;
				case 'p': $str .= date('A', $timestamp); break;
				case 'P': $str .= date('a', $timestamp); break;
			}
		}

		return $str;
    }

	/*
	**	Returns a property of the DateTime.
	*/
    public function __get ($name)
    {
		return $this->{$name};
    }

	/*
	**	Returns the string representation of the DateTime object (YYYY-MM-DD HH:II:SS);
	*/
    public function __toString ()
    {
        return $this->format('DATETIME');
	}
};

/*
**	Initialize DateTime class.
*/	
DateTime::init();
