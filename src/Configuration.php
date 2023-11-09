<?php

namespace Rose;

use Rose\Errors\Error;

use Rose\IO\Path;
use Rose\IO\File;

use Rose\Map;
use Rose\Regex;
use Rose\Text;

/*
**	Provides an interface to read system configuration parameters.
*/

class Configuration extends Map
{
	/**
     * Primary and only instance of this class.
     */
    private static $objectInstance;

    /**
     * Indicates what configuration environment was used.
     */
    public $env;

	/**
     * Initializes the instance of the Configuration class and loads the configuration files.
     */
    public function __construct()
    {
        parent::__construct();
        $this->reload();
    }

	/**
     * Returns the instance of this class.
     */
    public static function getInstance()
    {
        if ((Configuration::$objectInstance == null))
            Configuration::$objectInstance = new Configuration();

        return Configuration::$objectInstance;
    }

	/**
     * Reloads the configuration files.
     */
    public function reload()
    {
		$this->clear();

		// Load default configuration file...
		try {
			Configuration::loadFrom (Main::$CORE_DIR.'/system.conf', $this, true);
		}
		catch (\Throwable $e) { }

		// ... And an environment dependent file based on the 'rose-env' file contents.
        $this->env = 'def';
		if (Path::exists('rose-env'))
		{
			$this->env = Text::trim(File::getContents('rose-env'));
			if (Path::exists(Main::$CORE_DIR.'/'.$this->env.'.conf')) {
				try {
					Configuration::loadFrom (Main::$CORE_DIR.'/'.$this->env.'.conf', $this, true);
				}
				catch (\Throwable $e) {
				}
			}
		}
    }

	/**
     * Parses the given configuration file and stores it on the given map, if no map is provided a new one will be created. The map is returned.
     */
    public static function loadFrom ($source, $target=null, $merge=false)
    {
        if (Text::position($source, '//') !== false)
            throw new Error ('Blocked attempt to load configuration from a remote address: ' . $source);

        return Configuration::loadFromBuffer (Path::exists($source) ? File::getContents($source) : '', $target, $merge);
    }

	/*
	**	Parses the given configuration buffer and stores it on the given map, if no map is provided a new one will be created. The buffer data is just
	**	field-name pairs separated by equal-sign (i.e. Name=John), and sections enclosed in square brakets (i.e. [General]).
	**
	**	Note that you can use the equal-sign in the field value without any issues because the parser will look only for the first to delimit the name.
	**
	**	If a multiline value is desired, single back-ticks can be used (after the equal sign to start, and on a single line to end) to span multiple
	**	lines, each line will be trimmed first before concatenating it to the value, and new-line character is preserved.
	*/
    public static function loadFromBuffer ($source, $target=null, $merge=false)
    {
        $elem = null;
        $tmp1 = null;
		$tmp2 = null;
		$state = 0;

        if ($target == null)
            $target = new Map();

        foreach (Text::split("\n", $source)->__nativeArray as $line)
        {
			$line = Text::trim($line);

            if ($state == 1)
            {
                if ($line == '`')
                {
                    if ($elem != null)
                        $elem->set ($tmp2, $tmp1);
                    else
                        $target->set ($tmp2, $tmp1);

                    $state = 0;
                    continue;
				}

                if ($tmp1) $tmp1 .= "\n";

                $tmp1 .= $line;
                continue;
			}

            if ($line == '' || $line[0] == '#')
                continue;

            if ($line[0] == '[')
            {
				$line = Text::trim(Text::substring($line, 1, -1));
                if (!$target->has($line) || $merge==false)
                    $target->set($line, new Map());

                $elem = $target->get($line);
                continue;
			}

			$tmp = Text::position($line, '=');
            if ($tmp === false) continue;

            $name = Text::trim(Text::substring($line, 0, $tmp));
			$value = Text::trim(Text::substring($line, $tmp+1));
			
			if ($name == '')
                continue;

			if ($value == '`') {
				$tmp1 = '';
				$tmp2 = $name;
				$state = 1;
				continue;
			}

            if ($elem != null)
                $elem->set($name, $value);
            else
                $target->set($name, $value);
		}

        return $target;
    }

	/*
	**	Saves the specified configuration map to a buffer. If none specified the main Configuration instance will be used.
	*/
    public static function saveToBuffer ($conf=null)
    {
        if (!$conf)
            $conf = Configuration::$objectInstance;

        $buff = '';
		$section = null;

        foreach ($conf->__nativeArray as $key=>$value)
        {
            if (typeOf($value) == 'Rose\\Map')
                continue;

            if (Regex::_matches("/[\r\n\t]/", $value)) {
                $buff .= $key."=`\n";
                $buff .= $value;
                $buff .= "\n`\n";
            }
            else {
                $buff .= $key.'='.$value."\n";
            }
		}

        if (Text::substring($buff,-2) != "\n\n") {
            $buff .= "\n";
		}

        foreach ($conf->__nativeArray as $sec=>$data)
        {
            if (typeOf($data) != 'Rose\\Map')
                continue;

			$buff .= '['.$sec."]\n";

            foreach ($data->__nativeArray as $key=>$value)
            {
                if (typeOf($value) == 'Rose\\Map')
                    continue;

                if (Regex::_matches("/[\r\n\t]/", $value)) {
                    $buff .= $key."=`\n";
                    $buff .= $value;
                    $buff .= "\n`\n";
                }
                else {
                    $buff .= $key.'='.$value."\n";
                }
            }

            if (Text::substring($buff,-2) != "\n\n")
                $buff.="\n";
        }

        return $buff;
    }
};
