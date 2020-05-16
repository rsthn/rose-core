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
use Rose\Map;

/*
**	Provides an interface to automatically load and manipulate string resources stored in .conf and .plain files.
*/

class Strings
{
	/*
	**	Primary and only instance of this class.
	*/
	private static $objectInstance = null;

	/*
	**	List of already loaded strings files.
	*/
	private $loadedMaps;

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
	**	Constructs the Strings object, this is a private constructor as this class can have only one instance.
	*/
    private function __construct ()
    {
		$this->loadedMaps = new Map ();

        $this->altLangBase = $this->altBase = $this->langBase = $this->base = 'resources/strings/';
		$this->debug = null;

		$res = Resources::getInstance();
		$gateway = Gateway::getInstance();

		// Set indicator that language code is used from input request parameters.
		$lang = Text::substring ($gateway->requestParams->lang, 0, 2);
		$res->LangSrc = 'REQUEST';

		// Attempt to load language code from cookie.
        if (!$lang && Cookies::getInstance()->has('lang'))
        {
            $gateway->requestParams->lang = $lang = Cookies::getInstance()->lang;
            $res->LangSrc = 'SYSPARAM';
		}

		// Attempt to load language code from current user data.
        if (!$lang && Session::$data->currentUser != null && Session::$data->currentUser->lang != null)
        {
			if (Path::exists($this->base.Session::$data->currentUser->lang))
			{
				$gateway->requestParams->lang = $lang = Session::$data->currentUser->lang;
				$res->LangSrc = 'USER';
			}
		}

		// Use default language code from configuration.
        if (!$lang && Configuration::getInstance()->Locale->lang != null)
        {
			$gateway->requestParams->lang = $lang = Text::format(Configuration::getInstance()->Locale->lang);
            $res->LangSrc = 'CONFIG';
		}

		// Force language code override if "override_lang" input request parameter is specified.
        if ($gateway->requestParams->override_lang != null)
        {
			$gateway->requestParams->lang = $lang = $gateway->requestParams->override_lang;
            $res->LangSrc = 'OVERRIDE';
		}

		// Load debug configuration string.
        $this->debug = Configuration::getInstance()->Strings->debug;

		// Ensure that if no language is selected all strings will be loaded from the base strings directory.
        if (!$lang) $lang = '.';

		// Ensure language code is only two characters long.
        if (Text::length($lang) > 2)
            $lang = '.';

		// Redirect to error page if path to strings is not available.
        if (!Path::exists($this->langBase = $this->altLangBase = $this->base.$lang))
        {
            if (Configuration::getInstance()->Strings->lang_error_url)
                Gateway::redirect (Configuration::getInstance()->Strings->lang_error_url);
            else
                throw new Error ('Specified language code is not supported: ' . $lang);
        }
    }

	/*
	**	Sets the strings language code. The language directory resources/strings/XX for code XX should exist.
	*/
    public function setLang ($lang)
    {
        $this->altLangBase = $this->altBase.$lang;
        $this->langBase = $this->base.$lang;
    }

	/*
	**	Sets the strings base directory. If $base is null, the default will be set.
	*/
    public function setBase ($base)
    {
		if (!$base) $base = 'resources/strings/';

        if (Text::substring($base, -1) != '/')
            $base .= '/';

        $this->langBase = $base . Text::substring($this->langBase, Text::length($this->base)) . '/';
        $this->base = $base;
    }

	/*
	**	Sets the strings alternative base directory. If $base is null, the default will be set.
	*/
    public function setAltBase ($base)
    {
		if (!$base) $base = 'resources/strings/';

        if (Text::substring($base, -1) != '/')
            $base .= '/';

		$this->altLangBase = $base . Text::substring($this->altLangBase, Text::length($this->altBase)) . '/';
        $this->altBase = $base;
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
