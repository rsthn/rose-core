<?php
/*
**	Rose\Directory
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

namespace Rose\IO;

use Rose\IO\Path;

use Rose\Arry;
use Rose\Map;
use Rose\Regex;
use Rose\Error;
use Rose\Text;

/*
**	Provides methods to work with directories.
*/

class Directory
{
	/*
	**	Flag constants for read() method.
	*/
	public const READ_FILES = 1;
	public const READ_DIRS = 2;
	public const READ_SIZE = 4;
	public const KEEP_STRUCTURE = 8;

	/*
	**	Returns an array with the contents of the directory.
	**
	**	NOTE: Output parameter is used internally.
	*/
    public static function read (string $path, bool $recursive=true, string $pattern='/.+/', int $offset=0, int $flags=Directory::READ_FILES | Directory::READ_DIRS, $output=null)
    {
        $result = null;
        $hdl = null;
		$item = null;

		$path = Path::normalize($path);

		if (!Path::is_dir($path))
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

        if (Path::is_dir($path) && ($hdl = opendir($path)) != null)
        {
            while (($item = readdir($hdl)) !== false)
            {
				$fpath = $path . $item;

                if ($item == '.' || $item == '..')
					continue;

                if (Path::is_dir($fpath))
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
                    if (!($flags & Directory::READ_FILES) || !Regex::_matches($pattern, $fpath))
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

	/*
	**	Returns an array only with file entries in the directory.
	*/
    public static function readFiles (string $path, bool $recursive=false, string $pattern='/.+/', int $offset=0, int $flags=0)
    {
		return Directory::read ($path, $recursive, $pattern, $offset, $flags | Directory::READ_FILES);
    }

	/*
	**	Returns an array only with directory entries in the directory.
	*/
    public static function readDirs (string $path, bool $recursive=false, string $pattern='/.+/', int $offset=0, int $flags=0)
    {
        return Directory::read ($path, $recursive, $pattern, $offset, $flags | Directory::READ_DIRS);
    }

	/*
	**	Removes a directory (recursively if needed), returns true if success.
	*/
    public static function remove (string $path, bool $recursive=false)
    {
		$path = Path::normalize($path);

		if (!Path::exists($path))
			return true;

        if (!Path::is_dir($path))
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
			rmdir ($path);
        }
        catch (Error $e) {
            return false;
		}

		return true;
    }

	/*
	**	Creates a directory using the given path, returns true if the directory was created, or false if an error occurred.
	*/
    public static function create (string $path, bool $recursive=false)
    {
		if (Path::exists($path) && Path::is_dir($path))
			return true;

        try {
            return mkdir ($path, 0755, $recursive);
        }
        catch (Error $e) {
            return false;
        }
    }

	/*
	**	Copies all files (and directories if recursive is set) from the source to the destination, if 'overwrite' is true the destination
	**	files will be overwritten.
	*/
    public static function copy (string $source, string $dest, bool $recursive=true, bool $overwrite=false, string $pattern=null)
    {
        if ($dest == '' || $source == '')
            return false;

		$source = Path::normalize($source);
		$dest = Path::normalize($dest);

		if (!Path::is_dir($source))
		{
			if (Path::exists($dest) && !$overwrite)
				return true;

			return File::copy ($source, $dest);
		}

		if (!Path::exists($dest))
			Directory::create ($dest, true);

		foreach (scandir($source) as $entry)
		{
			if ($entry == '.' || $entry == '..')
				continue;

			if ($pattern != null && !Regex::_matches($pattern, $entry))
				continue;

			if (!$recursive && Path::is_dir($source.'/'.$entry))
				continue;

			if (!Directory::copy ($source.'/'.$entry, $dest.'/'.$entry))
				return false;
		}

        return true;
    }
};
