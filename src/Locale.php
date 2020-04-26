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
    public function format ($formatType, $value)
    {
		$config = Configuration::getInstance();

        switch ($formatType = Text::toUpperCase($formatType))
        {
            case 'NUMBER':
				return number_format((double)$value, $config->Locale->numeric[1], $config->Locale->numeric[0], $config->Locale->numeric[2]);

            case 'INTEGER':
				return number_format((double)$value, 0, 0, $config->Locale->numeric[2]);

			case 'TIME':
				return strftime($config->Locale->time, is_object($value) ? $value->getTimestamp() : (int)$value);

            case 'DATE':
				return strftime($config->Locale->date, is_object($value) ? $value->getTimestamp() : (int)$value);

            case 'DATETIME':
				return strftime($config->Locale->datetime, is_object($value) ? $value->getTimestamp() : (int)$value);

            case 'GMT':
				return date('D, d M Y H:i:s ', is_object($value) ? $value->getTimestamp() : (int)$value) . 'GMT';

            case 'UTC':
				return date('Y-m-d\TH:i:s\Z', is_object($value) ? $value->getTimestamp() : (int)$value);

            case 'ISO_DATE':
				return strftime('%Y-%m-%d', is_object($value) ? $value->getTimestamp() : (int)$value);

            case 'ISO_TIME':
				return strftime('%H:%M:%S', is_object($value) ? $value->getTimestamp() : (int)$value);

            case 'ISO_DATETIME':
				return strftime('%Y-%m-%d %H:%M:%S', is_object($value) ? $value->getTimestamp() : (int)$value);

            default:
				if (Text::substring ($formatType, 0, 3) == 'DT_')
					return strftime ($config->Locale->get($formatType), is_object($value) ? $value->getTimestamp() : (int)$value);

				if ((Text::substring($formatType,0,7) == 'NUMERIC'))
				{
					$tmp = $config->Locale->get($formatType);
					return number_format ((double)$value, $tmp[1], $tmp[0], $tmp[2]);
				}

				return Text::format($config->Locale->get($formatType), $value);
        }
    }
}
