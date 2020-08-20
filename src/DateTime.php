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
**	Provides an interface to manipulate date and time, stored always in UTC.
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
	**	Constructs a DateTime object, by default will contain the current date and time.
	*/
	public function __construct ($datetime='')
    {
		if (is_numeric($datetime))
			$datetime = strftime('%Y-%m-%d %H:%M:%S', $datetime);

		$tmp = new \DateTime ($datetime ? $datetime : 'now', Locale::getInstance()->timezone ? new \DateTimeZone (Locale::getInstance()->timezone) : null);

        $this->year = $tmp->format('Y');
        $this->month = $tmp->format('m');
		$this->day = $tmp->format('d');

        $this->hour = $tmp->format('H');
        $this->minute = $tmp->format('i');
		$this->second = $tmp->format('s');

		$this->timestamp = mktime ($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }

	/*
	**	Returns the DateTime in UNIX timestamp format.
	*/
	public function getTimestamp ()
	{
		return $this->timestamp;
	}

	/*
	**	Sets the DateTime from the specified UNIX timestamp.
	*/
	public function setTimestamp ($timestamp)
	{
		$this->timestamp = $timestamp;

		$this->year = strftime('%Y', $timestamp);
		$this->month = strftime('%m', $timestamp);
		$this->day = strftime('%d', $timestamp);

		$this->hour = strftime('%H', $timestamp);
		$this->minute = strftime('%M', $timestamp);
		$this->second = strftime('%S', $timestamp);

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

		if ($value instanceof DateTime)
			return $value->timestamp;

		return (new \DateTime ($value))->getTimestamp();
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
		return Math::round (($this->timestamp - DateTime::getUnixTimestamp($datetime)) / DateTime::getUnit($unit));
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
				return strftime("%a, %d %b %Y %H:%M:%S GMT", $this->getTimestamp());

            case 'TIME':
				return sprintf("%02d:%02d:%02d", $this->hour, $this->minute, $this->second);

            case 'DATE':
				return sprintf("%4d-%02d-%02d", $this->year, $this->month, $this->day);
        }
    }

	/*
	**	Returns the string representation of the DateTime object (YYYY-MM-DD HH:II:SS);
	*/
    public function __toString ()
    {
        return $this->format('DATETIME');
    }
};
