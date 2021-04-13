<?php
/*
**	Rose\Locale
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

use Rose\Configuration;
use Rose\Text;
use Rose\Expr;

/*
**	Configures and provides locale-related information and formatting options.
*/

class Locale
{
	/*
	**	Primary and only instance of this class.
	*/
	private static $objectInstance = null;

	/*
	**	Loaded timezone string, can be either a named timezone or a UTC offset
	*/
    public $timezone;

	/*
	**	Initializes the instance of the class. Similar to calling getInstance().
	*/
    public static function init ()
    {
		self::getInstance();
	}

	/*
	**	Returns the instance of this class.
	*/
    public static function getInstance ()
    {
        if (Locale::$objectInstance == null)
        {
            Locale::$objectInstance = new Locale();
			Locale::$objectInstance->initialize();
		}

        return Locale::$objectInstance;
	}

	/*
	**	Private constructor to prevent instantiation. Use getInstance() to get the singleton instance.
	*/
    private function __construct ()
    {
    }

	/*
	**	Initializes environment specific elements using the configuration of the locale object.
	*/
    private function initialize ()
    {
        $this->timezone = Configuration::getInstance()->Locale->timezone;

		if (!$this->timezone)
			return;

		try {
			new \DateTimeZone ($this->timezone);
		}
		catch (\Exception $e) {
			trace ('Locale: Invalid or unknown timezone: ' . $this->timezone);
			$this->timezone = '+00:00';
		}
    }

	/*
	**	Formats an object using the specified format type, which can be: NUMBER, INTEGER, TIME, DATE, DATETIME, GMT, UTC, SDATE, and SDATETIME.
	*/
    public function format ($formatType, $value, $format=null)
    {
		$config = Configuration::getInstance();

        switch (Text::toUpperCase($formatType))
        {
            case 'NUMBER':
				if (!$format) $format = 'numeric';
				if ($config->Locale->has($format)) $format = $config->Locale->get($format);

				return number_format((double)$value, $format[1], $format[0], $format[2]);

            case 'INTEGER':
				if (!$format) $format = 'numeric';
				if ($config->Locale->has($format)) $format = $config->Locale->get($format);

				return number_format((double)$value, 0, 0, $format[2]);

			case 'TIME':
				if (!$format) $format = 'time';
				if ($config->Locale->has($format)) $format = $config->Locale->get($format);

				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return $value ? strftime($format, $value + DateTime::$offset) : null;

			case 'DATE':
				if (!$format) $format = 'date';
				if ($config->Locale->has($format)) $format = $config->Locale->get($format);

				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return $value ? strftime($format, $value + DateTime::$offset) : null;

			case 'DATETIME':
				if (!$format) $format = 'datetime';
				if ($config->Locale->has($format)) $format = $config->Locale->get($format);

				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return $value ? strftime($format, $value + DateTime::$offset) : null;

			case 'GMT':
				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return date('D, d M Y H:i:s ', $value) . 'GMT';

			case 'UTC':
				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return date('Y-m-d\TH:i:s\Z', $value);

			case 'ISO_DATE':
				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return $value ? strftime('%Y-%m-%d', $value + DateTime::$offset) : null;

			case 'ISO_TIME':
				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return $value ? strftime('%H:%M:%S', $value + DateTime::$offset) : null;

			case 'ISO_DATETIME':
				$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
				return $value ? strftime('%Y-%m-%d %H:%M:%S', $value + DateTime::$offset) : null;

			default:
				if (Text::toUpperCase(Text::substring ($formatType, 0, 3)) == 'DT_')
				{
					$value = $value === null ? null : (is_int($value) ? (int)$value : DateTime::getUnixTimestamp((string)$value));
					return $value ? strftime($config->Locale->get($formatType), $value + DateTime::$offset) : null;
				}

				if (Text::toUpperCase(Text::substring($formatType,0,7)) == 'NUMERIC')
				{
					$tmp = $config->Locale->get($formatType);
					return number_format ((double)$value, $tmp[1], $tmp[0], $tmp[2]);
				}

				return Text::format($config->Locale->get($formatType), $value);
        }
    }
};

/*
**	Register expression functions to access locale formatting functions.
*/

Expr::register('locale::number', function ($args) { return Locale::getInstance()->format('NUMBER', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null); });
Expr::register('locale::integer', function ($args) { return Locale::getInstance()->format('INTEGER', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null); });
Expr::register('locale::time', function ($args) { return Locale::getInstance()->format('TIME', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null); });
Expr::register('locale::date', function ($args) { return Locale::getInstance()->format('DATE', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null); });
Expr::register('locale::datetime', function ($args) { return Locale::getInstance()->format('DATETIME', $args->get($args->length == 3 ? 2 : 1), $args->length == 3 ? $args->get(1) : null); });
Expr::register('locale::gmt', function ($args) { return Locale::getInstance()->format('GMT', $args->get(1)); });
Expr::register('locale::utc', function ($args) { return Locale::getInstance()->format('UTC', $args->get(1)); });
Expr::register('locale::iso_date', function ($args) { return Locale::getInstance()->format('ISO_DATE', $args->get(1)); });
Expr::register('locale::iso_time', function ($args) { return Locale::getInstance()->format('ISO_TIME', $args->get(1)); });
Expr::register('locale::iso_datetime', function ($args) { return Locale::getInstance()->format('ISO_DATETIME', $args->get(1)); });
Expr::register('locale::format', function ($args) { return Locale::getInstance()->format($args->get(1), $args->get(2)); });
