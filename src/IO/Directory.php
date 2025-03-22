<?php

namespace Rose\IO;

use Rose\IO\Path;

use Rose\Arry;
use Rose\Map;
use Rose\Regex;
use Rose\Error;
use Rose\Text;
use Rose\Expr;

// @title Directory

class Directory
{
    /**
     * Flag constants for the read() method.
     */
    public const READ_FILES = 1;
    public const READ_DIRS = 2;
    public const READ_SIZE = 4;
    public const KEEP_STRUCTURE = 8;

    /*
    **	Returns an array with the contents of the directory.
    **	NOTE: Output parameter is used internally.
    */
    public static function read (string $path, bool $recursive=true, string $pattern='/./', int $offset=0, int $flags=Directory::READ_FILES | Directory::READ_DIRS, $output=null)
    {
        $result = null;
        $hdl = null;
        $item = null;

        $path = Path::normalize($path);

        if (!Path::isDir($path))
            return null;

        $path .= '/';

        if (!$output)
        {
            $result = new Map();
            
            $result->name = Path::basename ($path);
            $result->path = Text::substring ($path, $offset);
            $result->files = new Arry();
            $result->dirs = new Arry();
        }
        else
            $result = $output;

        if (Path::isDir($path) && ($hdl = opendir($path)) != null)
        {
            while (($item = readdir($hdl)) !== false)
            {
                $fpath = $path . $item;

                if ($item == '.' || $item == '..')
                    continue;

                if (Path::isDir($fpath))
                {
                    if ($recursive)
                    {
                        if (!($flags & Directory::READ_DIRS))
                        {
                            Directory::read ($fpath, true, $pattern, $offset, $flags, $result);
                        }
                        else
                        {
                            if (!($flags & Directory::KEEP_STRUCTURE))
                            {
                                $result->dirs->push(Map::fromNativeArray(array('name' => $item, 'path' => Text::substring($fpath, $offset)), false));
                                Directory::read ($fpath, true, $pattern, $offset, $flags, $result);
                            }
                            else
                                $result->dirs->push(Directory::read ($fpath, true, $pattern, $offset, $flags));
                        }
                    }
                    else
                    {
                        if (!($flags & Directory::READ_DIRS))
                            continue;

                        $result->dirs->push(Map::fromNativeArray(array('name' => $item, 'path' => Text::substring($fpath, $offset).'/'), false));
                    }
                }
                else
                {
                    if (!($flags & Directory::READ_FILES) || !Regex::_matches($pattern, $item))
                        continue;

                    if ($flags & Directory::READ_SIZE)
                        $result->files->push(Map::fromNativeArray(array('name' => $item, 'path' => Text::substring($fpath, $offset), 'size' => File::size($fpath)), false));
                    else
                        $result->files->push(Map::fromNativeArray(array('name' => $item, 'path' => Text::substring($fpath, $offset)), false));
                }
            };

            closedir($hdl);
        }

        return $result;
    }

    /**
     * Returns an array only with file entries in the directory.
     */
    public static function readFiles (string $path, bool $recursive=false, string $pattern='/./', int $offset=0, int $flags=0)
    {
        return Directory::read ($path, $recursive, $pattern, $offset, $flags | Directory::READ_FILES);
    }

    /**
     * Returns an array only with directory entries in the directory.
     */
    public static function readDirs (string $path, bool $recursive=false, string $pattern='/./', int $offset=0, int $flags=0)
    {
        return Directory::read ($path, $recursive, $pattern, $offset, $flags | Directory::READ_DIRS);
    }

    /**
     * Removes a directory (recursively if needed), returns true if success.
     */
    public static function remove (string $path, bool $recursive=false)
    {
        $path = Path::normalize($path);

        if (!Path::exists($path))
        {
            try { \rmdir ($path); }
            catch (\Throwable $e) { return false; }
            return true;
        }

        if (!Path::isDir($path) || Path::isLink($path))
            return File::remove($path);

        if ($recursive)
        {
            foreach (scandir($path) as $entry)
            {
                if ($entry == '.' || $entry == '..')
                    continue;

                $entry = $path.'/'.$entry;
                Directory::remove ($entry, true);
            }
        }

        try {
            \rmdir ($path);
        }
        catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Removes a directory, does not check anything.
     */
    public static function rmdir (string $path)
    {
        $path = Path::normalize($path);

        try {
            \rmdir ($path);
        }
        catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Creates a directory using the given path, returns true if the directory was created, or false if an error occurred.
     */
    public static function create (string $path, bool $recursive=false)
    {
        if (Path::exists($path) && Path::isDir($path))
            return true;

        try {
            return mkdir ($path, 0755, $recursive);
        }
        catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Copies all files (and directories if recursive is set) from the source to the destination, if 'overwrite' is true the destination
     * files will be overwritten.
     */
    public static function copy (string $source, string $dest, bool $recursive=true, bool $overwrite=false, string $pattern=null)
    {
        if ($dest == '' || $source == '')
            return false;

        $source = Path::normalize($source);
        $dest = Path::normalize($dest);

        if (!Path::isDir($source))
        {
            if (Path::exists($dest) && !$overwrite)
                return true;

            return File::copy($source, $dest);
        }

        if (!Path::exists($dest))
            Directory::create ($dest, true);

        foreach (scandir($source) as $entry)
        {
            if ($entry == '.' || $entry == '..')
                continue;

            if ($pattern != null && !Regex::_matches($pattern, $entry))
                continue;

            if (!$recursive && Path::isDir($source.'/'.$entry))
                continue;

            if (!Directory::copy ($source.'/'.$entry, $dest.'/'.$entry, $recursive, $overwrite, $pattern))
                return false;
        }

        return true;
    }
};

/**
 * Creates a directory and all its parent directories (if needed). Returns boolean.
 * @code (`dir:create` <path>)
 * @example
 * (dir:create "/tmp/test")
 * ; true
 */
Expr::register('dir:create', function ($args) {
    return Directory::create($args->get(1), true);
});

/**
 * Returns an array with file entries in the directory. Each entry is a map with keys `name` and `path`.
 * @code (`dir:files` <path> [regex-pattern])
 * @example
 * (dir:files "/home")
 * ; [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}]
 */
Expr::register('dir:files', function ($args) {
    return Directory::readFiles ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')?->files ?? new Arry();
});

/**
 * Returns an array with directory entries in the directory. Each entry is a map with keys `name` and `path`.
 * @code (`dir:dirs` <path> [regex-pattern])
 * @example
 * (dir:dirs "/home")
 * ; [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
 */
Expr::register('dir:dirs', function ($args) {
    return Directory::readDirs ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/')?->dirs ?? new Arry();
});

/**
 * Returns an object with keys `name`, `path`, `files` and `dirs`. The `files` and `dirs` keys are arrays with the file
 * and directory entries each of which is a map with keys `name` and `path`.
 * @code (`dir:entries` <path> [regex-pattern])
 * @example
 * (dir:entries "/home")
 * ; {
 * ;    name: "home", path: "/home", 
 * ;    files: [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}],
 * ;    dirs: [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
 * ; }
 */
Expr::register('dir:entries', function ($args) {
    return Directory::read ($args->get(1), false, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

/**
 * Returns an array with file entries in the directory (recursively). Each entry is a map with keys `name` and `path`.
 * @code (`dir:files-recursive` <path> [regex-pattern])
 * @example
 * (dir:files-recursive "/home")
 * ; [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}]
 */
Expr::register('dir:files-recursive', function ($args) {
    return Directory::readFiles ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')?->files ?? new Arry();
});

/**
 * Returns an array with directory entries in the directory (recursively). Each entry is a map with keys `name` and `path`.
 * @code (`dir:dirs-recursive` <path> [regex-pattern])
 * @example
 * (dir:dirs-recursive "/home")
 * ; [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
 */
Expr::register('dir:dirs-recursive', function ($args) {
    return Directory::readDirs ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/')?->dirs ?? new Arry();
});

/**
 * Returns an object with keys `name`, `path`, `files` and `dirs`. The `files` and `dirs` keys are arrays with the file
 * and directory entries in the folder and all its subfolders. Each entry is a map with keys `name` and `path`.
 * @code (`dir:entries-recursive` <path> [regex-pattern])
 * @example
 * (dir:entries-recursive "/home")
 * ; {
 * ;    name: "home", path: "/home",
 * ;    files: [{name: "file1.txt", path: "/home/file1.txt"}, {name: "file2.txt", path: "/home/file2.txt"}],
 * ;    dirs: [{name: "dir1", path: "/home/dir1/"}, {name: "dir2", path: "/home/dir2/"}]
 * ; }
 */
Expr::register('dir:entries-recursive', function ($args) {
    return Directory::read ($args->get(1), true, $args->{2} ? $args->{2} : '/.+/', 0, Directory::READ_FILES | Directory::READ_DIRS);
});

/**
 * Removes a directory (must be empty) returns `true` if success.
 * @code (`dir:remove` <path>)
 * @example
 * (dir:remove "/tmp/test")
 * ; true
 */
Expr::register('dir:remove', function ($args) {
    Directory::remove($args->get(1), false);
    return null;
});

/**
 * Removes a directory recursively and returns `true` if success.
 * @code (`dir:remove-recursive` <path>)
 * @example
 * (dir:remove-recursive "/tmp/test")
 * ; true
 */
Expr::register('dir:remove-recursive', function ($args) {
    Directory::remove($args->get(1), true);
    return null;
});

/**
 * Removes a directory (must be empty) without any checks. Returns `true` if success.
 * @code (`dir:rmdir` <path>)
 * @example
 * (dir:rmdir "/tmp/test")
 * ; true
 */
Expr::register('dir:rmdir', function ($args) {
    Directory::rmdir($args->get(1));
    return null;
});

/**
 * Copies all files (and directories if `recursive` is set) from the `source` to the `destination` directories. If
 * `overwrite` is true the destination files will be overwritten.
 * @code (`dir:copy` <source> <destination> [recursive=true] [overwrite=true])
 * @example
 * (dir:copy "/tmp/test" "/tmp/test2")
 * ; true
 */
Expr::register('dir:copy', function ($args) {
    return Directory::copy ($args->get(1), $args->get(2), $args->has(3) ? $args->get(3) : true, $args->has(4) ? $args->get(4) : true);
});
