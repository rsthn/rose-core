<?php
/*
**	Rose\Path
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

use Rose\Gateway;
use Rose\Text;
use Rose\Regex;

/*
**	Provides methods to work with paths.
*/

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
		return Regex::_getString("/\.([A-Za-z][A-Za-z0-9_]*)$/", basename($path));
    }

	/*
	**	Returns the base name in the path without extension, note that anything after the first dot (.) will be considered extension.
	*/
    public static function name ($path)
    {
		return Regex::_getString("/^(.+?)\.([A-Za-z][A-Za-z0-9_]*)$/", basename($path), 1);
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
	**	Returns the full resolved path.
	*/
    public static function resolve ($path)
    {
		return Path::normalize(\realpath($path));
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
    public static function is_file ($path)
    {
        return \is_file ($path);
    }

	/*
	**	Returns true if the path points to a directory.
	*/
    public static function is_dir ($path)
    {
        return \is_dir ($path);
    }

	/*
	**	Returns true if the path points to a symbolic link.
	*/
    public static function is_link ($path)
    {
        return \is_link ($path);
    }

	/*
	**	Returns true if the path exists.
	*/
    public static function exists ($path)
    {
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
};
