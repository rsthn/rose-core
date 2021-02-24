<?php
/*
**	Rose\Extensions
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

use Rose\IO\Directory;
use Rose\IO\Path;
use Rose\Map;
use Rose\Arry;

/*
**	Provides functionality to load and query status of extensions.
*/

class Extensions
{
	/*
	**	Contains a list of currently loaded extensions.
	*/
	private static $loaded = null;

	/*
	**	Directories to look for extensions. Treated as relative paths unless it starts with '/'.
	*/
	public static $sources;

	/*
	**	Load all available extensions from the native, installed and user extension directories.
	*/
	public static function init()
	{
		self::$loaded = new Map();

		foreach (self::$sources->__nativeArray as $path)
		{
			$path = Text::startsWith('/', $path) ? $path : Path::append(Path::dirname(__FILE__), $path);
			if (!Path::exists($path)) continue;

			Directory::readDirs($path)->dirs->forEach(function($i) use(&$path) { self::load($i->name, $path); });
		}
	}

	/*
	**	Loads an extension given its identifier and the source path.
	*/
    public static function load ($identifier, $path)
    {
		if (self::isLoaded($identifier))
			return;

		require_once(Path::append($path, $identifier, $identifier.'.php'));

		self::$loaded->set($identifier, true);
	}

	/*
	**	Returns boolean indicating if the given extension is installed.
	*/
    public static function isInstalled ($identifier)
    {
		$identifier = Path::append($identifier, $identifier.'.php');

		foreach (self::$sources->__nativeArray as $path)
		{
			$path = Text::startsWith('/', $path) ? $path : Path::append(Path::dirname(__FILE__), $path);
			$path = Path::append($path, $identifier);
			if (Path::exists($path)) return true;
		}
	}

	/*
	**	Returns boolean indicating if the given extension is loaded.
	*/
    public static function isLoaded ($identifier)
    {
		return self::$loaded->has($identifier);
	}
};

/*
**	Set source directories.
*/
Extensions::$sources = new Arry([
	'Ext',						// Native extensions.
	'../../extensions',			// Extensions installed with composer.
	'../../../../extensions'	// User extensions.
]);
