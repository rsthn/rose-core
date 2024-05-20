<?php

namespace Rose\IO;

use Rose\Errors\Error;
use Rose\IO\Path;
use Rose\IO\Directory;
use Rose\IO\DataStream;
use Rose\IO\StreamDescriptor;
use Rose\DateTime;
use Rose\Expr;

// @title File

class File
{
	/*
	**	Returns the size of the file.
	*/
    public static function size (string $filepath)
    {
        return file_exists($filepath) ? filesize($filepath) : null;
    }

	/*
	**	Dumps the file contents to the standard output.
	*/
    public static function dump (string $filepath)
    {
        readfile ($filepath);
    }

	/*
	**	Returns the modification time of the file as an ISO DateTime string (LTZ) or as a unix timestamp (UTC) if $timestamp is `true`.
	*/
    public static function mtime (string $filepath, bool $timestamp=false)
    {
		clearstatcache();
        return $timestamp ? filemtime($filepath) : (string)new DateTime(filemtime($filepath));
    }

	/*
	**	Returns the last access time of the file as an ISO DateTime string (LTZ) or as a unix timestamp (UTC) if $timestamp is `true`.
	*/
    public static function atime (string $filepath, bool $timestamp=false)
    {
        return $timestamp ? fileatime($filepath) : (string)new DateTime(fileatime($filepath));
    }

	/*
	**	Sets the last modified time of a file (UTC).
	*/
    public static function touch (string $filepath, $time=null)
    {
        return \touch ($filepath, $time === null ? DateTime::getUnixTimestamp(true) : (\Rose\isObject($time) ? $time->getTimestamp() : (\Rose\isInteger($time) ? (int)$time : DateTime::getUnixTimestamp($time))));
    }

	/*
	**	Reads and returns the contents of the file.
	*/
    public static function getContents (string $filepath, $context=null)
    {
        return file_get_contents ($filepath, false, $context);
    }

	/*
	**	Sets the contents of a file.
	*/
    public static function setContents (string $filepath, ?string $contents, $context=null)
    {
		if (!Path::exists(Path::dirname($filepath)))
			Directory::create(Path::dirname($filepath), true);

		file_put_contents ($filepath, $contents, 0, $context);
    }

	/*
	**	Appends the given contents to the file.
	*/
    public static function appendContents (string $filepath, ?string $contents)
    {
		if (!Path::exists(Path::dirname($filepath)))
			Directory::create(Path::dirname($filepath), true);

        $fp = fopen ($filepath, 'a+b');
        if (!$fp) return;

        fwrite ($fp, $contents);
		fclose ($fp);

		\touch ($filepath, DateTime::getUnixTimestamp(true));
    }

	/*
	**	Opens the file and returns a data stream.
	*/
    //VIOLET: Remove this if not used anymore
    public static function open (string $filepath, string $openMode='wb', $context=null)
    {
		$_handle = null;

        if ($context == null)
            $_handle = fopen ($filepath, $openMode, false);
        else
			$_handle = fopen ($filepath, $openMode, false, $context);

        if ($_handle == null)
            throw new Error ('File (open): Unable to open: ' . $filepath);

		//VIOLET Requires: DataStream
		//VIOLET Requires: StreamDescriptor
        return new DataStream (new StreamDescriptor ($_handle));
    }

	/*
	**	Removes a file, returns true if success.
	*/
    public static function remove (string $filepath)
    {
		if (!Path::exists($filepath))
			return true;

		try {
			\chmod ($filepath, 0755);
		}
		catch (\Throwable $e) {
		}
	
        try {
			\unlink ($filepath);
            return true;
        }
        catch (\Throwable $e) {
            return false;
        }
    }

	/*
	**	Removes a file. Does not check anything.
	*/
    public static function unlink (string $filepath)
    {
        try {
            \unlink ($filepath);
            return true;
        }
        catch (\Throwable $e) {
            return false;
        }
    }

	/*
	**	Copies a file (overwrites if exists) to the given target, which can be directory or file.
	*/
    public static function copy (string $source, string $target)
    {
		if (Path::isDir($target))
			return \copy ($source, Path::append($target, Path::basename($source))) ? true : false;
		else
        	return \copy ($source, $target) ? true : false;
    }

	/*
	**	Creates a file, returns true if the file was created, or false if an error occurred.
	*/
    public static function create (string $filepath)
    {
        $_desc = fopen ($filepath, 'wb');
        if (!$_desc) return false;

        fclose ($_desc);
        return true;
    }
};

/**
 * Returns the size of the file or `null` if the file does not exist.
 * @code (`file:size` <path>)
 * @example
 * (file:size "/var/www/image.jpg")
 * ; 1024
 */
Expr::register('file:size', function ($args) {
    return File::size($args->get(1));
});

/**
 * Dumps the file contents to the standard output.
 * @code (`file:dump` <path>)
 * @example
 * (file:dump "/var/www/image.jpg")
 * ; Image data
 */
Expr::register('file:dump', function ($args) {
    File::dump($args->get(1));
    return null;
});

/**
 * Returns the modification time of the file as a datetime string (LTZ).
 * @code (`file:mtime` <path>)
 * @example
 * (file:mtime "/var/www/image.jpg")
 * ; 2024-03-24 03:36:18
 */
Expr::register('file:mtime', function ($args) {
    return File::mtime($args->get(1));
});

/**
 * Returns the last access time of the file as a datetime string (LTZ).
 * @code (`file:atime` <path>)
 * @example
 * (file:atime "/var/www/image.jpg")
 * ; 2024-03-24 03:36:18
 */
Expr::register('file:atime', function ($args) {
    return File::atime($args->get(1));
});

/**
 * Sets the last modified time of a file (UTC). Datetime can be a datetime object, string or a unix timestamp.
 * @code (`file:touch` <path> [datetime])
 * @example
 * (file:touch "/var/www/image.jpg" "2024-03-24 03:36:18")
 * ; true
 */
Expr::register('file:touch', function ($args) {
    return File::touch($args->get(1), $args->has(2) ? $args->get(2) : null);
});

/**
 * Reads and returns the contents of the file.
 * @code (`file:read` <path>)
 * @example
 * (file:read "/var/www/test.txt")
 * ; "Hello, World!"
 */
Expr::register('file:read', function ($args) {
    return Path::exists($args->get(1)) ? File::getContents($args->get(1)) : null;
});

/**
 * Writes the contents to the file.
 * @code (`file:write` <path> <contents>)
 * @example
 * (file:write "/var/www/test.txt" "Hello, World!")
 * ; true
 */
Expr::register('file:write', function ($args) {
    File::setContents($args->get(1), $args->get(2));
    return true;
});

/**
 * Appends the given contents to the file.
 * @code (`file:append` <path> <contents>)
 * @example
 * (file:append "/var/www/test.txt" "Hello, World!")
 * ; true
 */
Expr::register('file:append', function ($args) {
    File::appendContents($args->get(1), $args->get(2));
    return true;
});

/**
 * Deletes a file, returns `true` if success.
 * @code (`file:remove` <path>)
 * @example
 * (file:remove "/var/www/test.txt")
 * ; true
 */
Expr::register('file:remove', function ($args) {
    return File::remove($args->get(1));
});

/**
 * Deletes a file. Does not check anything.
 * @code (`file:unlink` <path>)
 * @example
 * (file:unlink "/var/www/test.txt")
 * ; true
 */
Expr::register('file:unlink', function ($args) {
    return File::unlink($args->get(1));
});

/**
 * Copies a file (overwrites if exists) to the given target, which can be directory or file. Use the `stream` flag
 * to copy the file using manual streaming.
 * @code (`file:copy` <source> <target> [stream=false])
 * @example
 * (file:copy "/var/www/image.jpg" "/var/www/images")
 * ; true
 */
Expr::register('file:copy', function ($args)
{
    if ($args->{3} === true)
    {
        $source = fopen($args->get(1), 'rb');
        $target = fopen($args->get(2), 'wb');

        if (!($source && $target))
            return false;

        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);
        return true;
    }

    return File::copy($args->get(1), $args->get(2));
});

/**
 * Creates a file, returns `true` if the file was created, or `false` if an error occurred.
 * @code (`file:create` <path>)
 * @example
 * (file:create "/var/www/test.txt")
 * ; true
 */
Expr::register('file:create', function ($args) {
    return File::create($args->get(1));
});

/**
 * Opens a file for reading, writing or appending, and returns a data stream.
 * @code (`stream:open` <path> [mode='r'])
 * @example
 * (stream:open "test.txt" "w")
 * ; (data-stream)
 */
Expr::register('stream:open', function ($args) {
    return fopen($args->get(1), $args->{2} ?? 'r');
});

/**
 * Close a file data stream.
 * @code (`stream:close` <data-stream>)
 * @example
 * (stream:close (stream:open "test.txt" "w"))
 * ; true
 */
Expr::register('stream:close', function ($args) {
    return fclose($args->get(1));
});

/**
 * Writes data to a file data stream.
 * @code (`stream:write` <data-stream> <data>)
 * @example
 * (stream:write (fh) "Hello, World!")
 * ; true
 */
Expr::register('stream:write', function ($args) {
    return fwrite($args->get(1), $args->get(2)) !== false;
});

/**
 * Reads and returns up to length bytes from the file data stream. Returns empty string at EOF or `null` on error.
 * @code (`stream:read` <data-stream> <length>)
 * @example
 * (stream:read (fh) 1024)
 * ; "Hello, World!"
 */
Expr::register('stream:read', function ($args) {
    $data = fread($args->get(1), $args->get(2));
    return $data !== false ? $data : null;
});
