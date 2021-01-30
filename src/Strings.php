<?php
/*
**	Rose\Strings
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

use Rose\IO\Path;
use Rose\IO\File;

use Rose\Errors\Error;

use Rose\Resources;
use Rose\Configuration;
use Rose\Gateway;
use Rose\Session;
use Rose\Cookies;
use Rose\Text;
use Rose\Regex;
use Rose\Map;

/*
**	Provides an interface to automatically load and manipulate string resources stored in .conf and .plain files.
*/

class Strings
{
	/*
	**	Location from which the 'lang' parameter can be loaded (in order of precedence).
	*/
	public const FROM_NOWHERE				= 0;
	public const FROM_CONFIG				= 1;
	public const FROM_COOKIE				= 2;
	public const FROM_CURRENT_USER			= 3;
	public const FROM_RELATIVE_PATH			= 4;
	public const FROM_REQUEST				= 5;

	/*
	**	Primary and only instance of this class.
	*/
	private static $objectInstance = null;

	/*
	**	List of already loaded strings files.
	*/
	private $loadedMaps;

	/*
	**	Current language code.
	*/
	public $lang;

	/*
	**	Indicates the location from which the 'lang' parameter was loaded.
	*/
    public $langFrom;

	/*
	**	Base directory for general strings.
	*/
	public $base;
	public $altBase;

	/*
	**	Base directories for language strings.
	*/
	public $langBase;
	public $altLangBase;

	/*
	**	Indicates if we're debugging string output, values are: null, 'blank', or 'code'.
	*/
	public $debug;

	/*
	**	Returns the instance of this class.
	*/
    public static function getInstance ()
    {
        if (Strings::$objectInstance == null)
            Strings::$objectInstance = new Strings ();

        return Strings::$objectInstance;
    }

	/*
	**	Initializes the instance of the class. Similar to calling getInstance().
	*/
	public static function init ()
	{
		self::getInstance();
	}

	/*
	**	Constructs the Strings object, this is a private constructor as this class can have only one instance.
	*/
    private function __construct ()
    {
		$this->loadedMaps = new Map ();
        $this->debug = Configuration::getInstance()->Strings->debug;

		$this->altLangBase = $this->altBase = $this->langBase = $this->base = Main::$CORE_DIR.'/strings/';

		// Use default internal language code.
		$this->langFrom = Strings::FROM_NOWHERE;
		$this->lang = '';

		$this->setLang();
    }

	/*
	**	Sets the strings language code to the specified value. The language directory CORE_DIR/strings/XX for code XX should exist, if the
	**	parameter is not specified, the lang code will be loaded from one of the supported locations.
	*/
    public function setLang ($lang=null)
    {
		if ($lang == null)
		{
			$gateway = Gateway::getInstance();

			// Attempt to load language code from configuration.
			if (Configuration::getInstance()->Locale->lang != null)
			{
				$lang = Configuration::getInstance()->Locale->lang;
				$this->langFrom = Strings::FROM_CONFIG;
			}

			// Attempt to load language code from cookie.
			if (!$lang && Cookies::getInstance()->has('lang'))
			{
				$lang = Cookies::getInstance()->lang;
				$this->langFrom = Strings::FROM_COOKIE;
			}

			// Attempt to load language code from current user.
			if (!$lang && Session::$data->user != null && Session::$data->user->lang != null && Path::exists($this->base.Session::$data->user->lang))
			{
				$lang = Session::$data->user->lang;
				$this->langFrom = Strings::FROM_CURRENT_USER;
			}

			// Attempt to load language code from relative path.
			if (Regex::_matches('/^\/[a-z]{2}\//', $gateway->relativePath))
			{
				$lang = Text::substring($gateway->relativePath, 1, 2);
				$gateway->relativePath = Text::substring($gateway->relativePath, 3);

				$this->langFrom = Strings::FROM_RELATIVE_PATH;
			}
			else
			// Attempt to load language code from request parameters.
			if ($gateway->requestParams->has('__lang'))
			{
				$lang = $gateway->requestParams->__lang;
				$this->langFrom = Strings::FROM_REQUEST;
			}

			// Ensure that if no language is selected (or is invalid) all strings will be loaded from the base strings directory.
			if (!$lang || Text::length($lang) > 2 || !Path::exists($this->base.$lang.'/'))
				$lang = '.';

			$gateway->requestParams->__lang = $lang;
		}

		$this->lang = $lang;

        $this->altLangBase = $this->altBase.$this->lang.'/';
		$this->langBase = $this->base.$this->lang.'/';
    }

	/*
	**	Sets the strings base directory. If $base is null, the default will be set.
	*/
    public function setBase ($base)
    {
		if (!$base) $base = Main::$CORE_DIR.'/strings/';

        if (Text::substring($base, -1) != '/')
            $base .= '/';

		$this->base = $base;
		$this->langBase = $this->base.$this->lang.'/';
    }

	/*
	**	Sets the strings alternative base directory. If $base is null, the default will be set.
	*/
    public function setAltBase ($base)
    {
		if (!$base) $base = Main::$CORE_DIR.'/strings/';

        if (Text::substring($base, -1) != '/')
            $base .= '/';

		$this->altBase = $base;
		$this->altLangBase = $this->altBase.$this->lang.'/';
    }

	/*
	**	Retrieves a strings map given its name. Will attempt to retrieve the strings map from the cache, however if not loaded it will load it
	**	from whichever file is found first (conf, or plain) using the base directory, if not found, will retry using the alternative base
	**	directory, and if still not found an error will be issues.
	**
	**	If $name starts with '//' it will be treated as an absolute path. If it starts with '@' it will be considered a language string
	**	and the `langBase` directory will be used, in other cases the `base` directory will be used.
	*/
    public function retrieve ($name)
    {
        if ($this->debug == 'blank')
            return null;

        if ($this->debug == 'code')
            return $name;

		if ($this->loadedMaps->has($name))
			return $this->loadedMaps->get($name);

		// Attempt to load conf or plain from base directory.
		$tmp = Text::substring($name,0,2) == '//' ? Text::substring($name,2) : ($name[0] == '@' ? $this->langBase.Text::substring($name,1) : $this->base.$name);

		if (Path::exists($tmp.'.conf'))
		{
			$this->loadedMaps->set ($name, $data = Configuration::loadFrom($tmp.'.conf'));
			return $data;
		}

		if (Path::exists($tmp.'.plain'))
		{
			$this->loadedMaps->set ($name, $data = File::getContents($tmp.'.plain'));
			return $data;
		}

		// Attempt to load conf or plain from alternative base directory.
		$tmp2 = Text::substring($name,0,2) == '//' ? Text::substring($name,2) : ($name[0] == '@' ? $this->altLangBase.Text::substring($name,1) : $this->altBase.$name);
		if ($tmp != $tmp2)
		{
			if (Path::exists($tmp2.'.conf'))
			{
				$this->loadedMaps->set ($name, $data = Configuration::loadFrom($tmp2.'.conf'));
				return $data;
			}

			if (Path::exists($tmp2.'.plain'))
			{
				$this->loadedMaps->set ($name, $data = File::getContents($tmp2.'.plain'));
				return $data;
			}
		}

		// Attempt to load conf or plain from base directory ignoring language specifier.
		$tmp2 = Text::substring($name,0,2) == '//' ? Text::substring($name,2) : ($name[0] == '@' ? $this->base.Text::substring($name,1) : $this->base.$name);
		if ($tmp != $tmp2)
		{
			if (Path::exists($tmp2.'.conf'))
			{
				$this->loadedMaps->set ($name, $data = Configuration::loadFrom($tmp2.'.conf'));
				return $data;
			}

			if (Path::exists($tmp2.'.plain'))
			{
				$this->loadedMaps->set ($name, $data = File::getContents($tmp2.'.plain'));
				return $data;
			}
		}

		throw new Error ('Undefined strings file: '.$tmp);
    }

	/*
	**	Loads a strings file from the specified path and registers under the given name.
	*/
    public function load ($name, $path, $merge=false)
    {
        if ($this->loadedMaps->has($name) && !$merge)
            $this->loadedMaps->remove($name);

		if (!Path::exists($path.'.conf'))
		{
			if (!Path::exists($path.'.plain'))
				throw new Error ('Undefined strings file: '.$path);

			$this->loadedMaps->set ($name, File::getContents($path.'.plain'));
		}
		else
		{
			if ($merge)
			{
				if (!$this->loadedMaps->has($name))
					$this->loadedMaps->set ($name, new Map ());

				$this->loadedMaps->get($name)->merge(Configuration::loadFrom($path.'.conf'), true);
			}
			else
				$this->loadedMaps->set ($name, Configuration::loadFrom($path.'.conf'));
		}
    }

	/*
	**	Retrieves a strings map.
	*/
    public function __get ($name)
    {
        return $this->retrieve($name);
	}

	/*
 	**	Utility function to return a string.
	*/
	public static function get ($path, $placeholder=true)
	{
		$args = Text::split('/', $path);
		$tmp = Strings::getInstance();

		foreach ($args->__nativeArray as $i)
		{
			$tmp = $tmp->{$i};

			if ($tmp == null)
				return $placeholder ? $path : null;
		}

		return $tmp;
	}
};
