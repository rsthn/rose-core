<?php

namespace Rose\IO;

use Rose\Gateway;
use Rose\Text;
use Rose\Regex;
use Rose\Expr;

// @title Path

class Path
{
	/*
	**	Path delimiter.
	*/
	public const SEPARATOR = DIRECTORY_SEPARATOR;

	/*
	**	Returns the base name in the path (includes extension).
	*/
    public static function basename ($path)
    {
		return \basename($path);
    }

	/*
	**	Returns the extension name.
	*/
    public static function extname ($path)
    {
		return Regex::_getString("/\.([^.]*)$/", basename($path));
    }

	/*
	**	Returns the base name in the path without extension, note that anything after the first dot (.) will be considered extension.
	*/
    public static function name ($path)
    {
		return Regex::_getString("/^(.+?)\.([^.]*)$/", basename($path), 1);
    }

	/*
	**	Normalizes the separator in the given path.
	*/
    public static function normalize ($path)
    {
		$path = Path::SEPARATOR != '/' ? Text::replace(Path::SEPARATOR, '/', $path) : $path;

		while (Text::substring($path, -1) == '/')
			$path = Text::substring($path, 0, -1);

		return $path;
	}

	/*
	**	Returns the directory name.
	*/
    public static function dirname ($path)
    {
        return Path::normalize(\dirname($path));
    }

	/*
	**	Returns the fully resolved path.
	*/
    public static function resolve ($path)
    {
		return $path ? Path::normalize(\realpath($path)) : null;
    }

	/*
	**	Appends the given items to the specified path.
	*/
    public static function append ($path, ...$items)
    {
		$path = Path::normalize($path);

		foreach ($items as $i)
			$path .= ($path ? '/' : '') . Path::normalize($i);

		return $path;
    }

	/*
	**	Returns true if the path points to a file.
	*/
    public static function isFile ($path)
    {
        return \is_file ($path);
    }

	/*
	**	Returns true if the path points to a directory.
	*/
    public static function isDir ($path)
    {
        return \is_dir ($path);
    }

	/*
	**	Returns true if the path points to a symbolic link.
	*/
    public static function isLink ($path)
    {
        return \is_link ($path);
    }

	/*
	**	Returns true if the path exists.
	*/
    public static function exists ($path)
    {
		if (!$path) return false;

		if ($path === 'php://input')
			return true;

        return file_exists ($path);
	}

	/*
	**	Changes the permissions of the given path.
	*/
	public static function chmod ($path, $mode, $recursive=false)
    {
        return \chmod ($path, $mode) ? true : false;
	}

	/*
	**	Changes the current directory.
	*/
	public static function chdir ($path)
    {
        return \chdir ($path);
	}

	/*
	**	Renames a path.
	*/
    public static function rename ($source, $target)
    {
        return \rename ($source, $target) ? true : false;
    }

	/*
	**	Creates a symbolic link.
	*/
    public static function symlink ($link, $target)
    {
        return \symlink (Path::resolve($target), $link) ? true : false;
    }

	/*
	**	Creates a hard link.
	*/
    public static function link ($link, $target)
    {
        return \link (Path::resolve($target), $link) ? true : false;
    }

	/*
	**	Returns the current working directory.
	*/
    public static function cwd ()
    {
        $value = \getcwd();

        while (Text::endsWith($value, '/'))
			$value = Text::substring($value, 0, -1);

		return $value;
    }

	/*
	**	Returns the fsroot (where the script is executed from).
	*/
    public static function fsroot ()
    {
		return Gateway::getInstance()->fsroot;
    }

	/*
	**	Generates and returns a temporal file path with the specified prefix.
	*/
    public static function tempnam (string $prefix='')
    {
        return \tempnam(sys_get_temp_dir(), $prefix);
    }

	/*
	**	Returns the path to the system's temporal folder.
	*/
    public static function temp ()
    {
        return sys_get_temp_dir();
    }
};

/**
 * Returns the fsroot, the path from which the script is executed.
 * @code (`path:fsroot`)
 * @example
 * (path:fsroot)
 * ; /var/www/html
 */
Expr::register('path:fsroot', function ($args) {
    return Path::fsroot();
});

/**
 * Returns the current working directory.
 * @code (`path:cwd`)
 * @example
 * (path:cwd)
 * ; /var/www/html
 */
Expr::register('path:cwd', function ($args) {
    return Path::cwd();
});

/**
 * Returns the base name in the path (includes extension).
 * @code (`path:basename` <path>)
 * @example
 * (path:basename '/var/www/html/index.html')
 * ; index.html
 */
Expr::register('path:basename', function ($args) {
    return Path::basename ($args->get(1));
});

/**
 * Returns the extension name.
 * @code (`path:extname` <path>)
 * @example
 * (path:extname '/var/www/html/index.html')
 * ; .html
 */
Expr::register('path:extname', function ($args) {
    return Path::extname ($args->get(1));
});

/**
 * Returns the base name in the path without extension, note that anything after the first dot (.) will be considered extension.
 * @code (`path:name` <path>)
 * @example
 * (path:name '/var/www/html/index.html')
 * ; index
 */
Expr::register('path:name', function ($args) {
    return Path::name ($args->get(1));
});

/**
 * Normalizes the separator in the given path.
 * @code (`path:normalize` <path>)
 * @example
 * (path:normalize '/var\\www/html\\index.html')
 * ; /var/www/html/index.html
 */
Expr::register('path:normalize', function ($args) {
    return Path::normalize ($args->get(1));
});

/**
 * Returns the directory name.
 * @code (`path:dirname` <path>)
 * @example
 * (path:dirname '/var/www/html/index.html')
 * ; /var/www/html
 */
Expr::register('path:dirname', function ($args) {
    return Path::dirname ($args->get(1));
});

/**
 * Returns the fully resolved path.
 * @code (`path:resolve` <path>)
 * @example
 * (path:resolve './index.html')
 * ; /var/www/html/index.html
 */
Expr::register('path:resolve', function ($args) {
    return Path::resolve ($args->get(1));
});

/**
 * Appends the given items to the specified path.
 * @code (`path:append` <path> <items...>)
 * @example
 * (path:append '/var/www/html' 'index.html')
 * ; /var/www/html/index.html
 */
Expr::register('path:append', function ($args) {
    $args->shift();
    return Path::append (...$args->__nativeArray);
});

/**
 * Returns `true` if the path points to a file.
 * @code (`path:is-file` <path>)
 * @example
 * (path:is-file '/var/www/html/index.html')
 * ; true
 */
Expr::register('path:is-file', function ($args) {
    return Path::isFile ($args->get(1));
});

/**
 * Returns `true` if the path points to a directory.
 * @code (`path:is-dir` <path>)
 * @example
 * (path:is-dir '/var/www/html')
 * ; true
 */
Expr::register('path:is-dir', function ($args) {
    return Path::isDir ($args->get(1));
});

/**
 * Returns `true` if the path points to a symbolic link.
 * @code (`path:is-link` <path>)
 * @example
 * (path:is-link '/var/www/html/index.html')
 * ; false
 */
Expr::register('path:is-link', function ($args) {
    return Path::isLink ($args->get(1));
});

/**
 * Returns `true` if the path exists.
 * @code (`path:exists` <path>)
 * @example
 * (path:exists '/var/www/html/index.html')
 * ; true
 */
Expr::register('path:exists', function ($args) {
    return Path::exists ($args->get(1));
});

/**
 * Changes the permissions of the given path. Value is assumed to be in octal.
 * @code (`path:chmod` <path> <mode>)
 * @example
 * (path:chmod '/var/www/html/index.html' 777)
 * ; true
 */
Expr::register('path:chmod', function ($args) {
    return Path::chmod ($args->get(1), octdec($args->get(2)));
});

/**
 * Changes the current directory.
 * @code (`path:chdir` <path>)
 * @example
 * (path:chdir '/var/www/html')
 * ; true
 */
Expr::register('path:chdir', function ($args) {
    return Path::chdir ($args->get(1));
});

/**
 * Renames a file or directory.
 * @code (`path:rename` <source> <target>)
 * @example
 * (path:rename '/var/www/html/index.html' '/var/www/html/index2.html')
 * ; true
 */
Expr::register('path:rename', function ($args) {
    return Path::rename ($args->get(1), $args->get(2));
});

/**
 * Creates a symbolic link. Not all systems support this, be careful when using this function.
 * @code (`path:symlink` <link> <target>)
 * @example
 * (path:symlink '/var/www/html/index.html' '/var/www/html/index2.html')
 * ; true
 */
Expr::register('path:symlink', function ($args) {
    return Path::symlink ($args->get(1), $args->get(2));
});

/**
 * Creates a hard link. Not all systems support this, be careful when using this function.
 * @code (`path:link` <link> <target>)
 * @example
 * (path:link '/var/www/html/index.html' '/var/www/html/index2.html')
 * ; true
 */
Expr::register('path:link', function ($args) {
    return Path::link ($args->get(1), $args->get(2));
});

/**
 * Generates and returns a temporal file path with the specified prefix.
 * @code (`path:tempnam` <prefix>)
 * @example
 * (path:tempnam 'prefix')
 * ; /tmp/prefix_5f3e2e7b7b7e4
 */
Expr::register('path:tempnam', function ($args) {
    return Path::tempnam($args->has(1) ? $args->get(1) : '');
});

/**
 * Returns the path to the system's temporal folder.
 * @code (`path:temp`)
 * @example
 * (path:temp)
 * ; /tmp
 */
Expr::register('path:temp', function ($args) {
    return Path::temp();
});
