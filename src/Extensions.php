<?php

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

		try {
			require_once(Path::append($path, $identifier, $identifier.'.php'));
		}
		catch (\Throwable $e) {
			trace('[ERROR] Unable to load: ' . $identifier);
			trace($e);
		}

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
