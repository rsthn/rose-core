<?php
/*
**	Rose\File
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

use Rose\Path;
use Rose\Error;
use Rose\DataStream;
use Rose\StreamDescriptor;

/*
**	Describes a file. The static methods of this class provide access to several file specific functions.
*/

class File
{
	/*
	**	Generates and returns a temporal file path with the specified prefix.
	*/
    public static function temp (string $prefix='')
    {
        return tempnam(sys_get_temp_dir(), $prefix);
    }

	/*
	**	Returns the size of the file.
	*/
    public static function size (string $filepath)
    {
        return filesize ($filepath);
    }

	/*
	**	Dumps the file contents to the standard output.
	*/
    public static function dump (string $filepath)
    {
        readfile ($filepath);
    }

	/*
	**	Returns the modification time of the file as an ISO DateTime or as a unix timestamp if $timestamp is `true`.
	*/
    public static function mtime (string $filepath, bool $timestamp=false)
    {
        return $timestamp ? filemtime($filepath) : strftime('%Y-%m-%d %H:%M:%S', filemtime($filepath));
    }

	/*
	**	Returns the last access time of the file as an ISO DateTime or as a unix timestamp if $timestamp is `true`.
	*/
    public static function atime (string $filepath, bool $timestamp=false)
    {
        return $timestamp ? fileatime($filepath) : strftime('%Y-%m-%d %H:%M:%S', fileatime($filepath));
    }

	/*
	**	Sets the last modified time of a file.
	*/
    public static function touch (string $filepath, int $time)
    {
        return \touch ($filepath, $time);
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
    public static function setContents (string $filepath, string $contents, $context=null)
    {
		file_put_contents ($filepath, $contents, 0, $context);
    }

	/*
	**	Appends the given contents to the file.
	*/
    public static function appendContents (string $filepath, string $contents)
    {
        $fp = fopen ($filepath, 'a+b');
        if (!$fp) return;

        fwrite ($fp, $contents);
        fclose ($fp);
    }

	/*
	**	Opens the file and returns a data stream.
	*/
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
            unlink ($filepath);
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

	/*
	**	Copies a file (overwrite behavior if exists) to the given target (which can be directory or file).
	*/
    public static function copy (string $source, string $target)
    {
		if (Path::is_dir($target))
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
