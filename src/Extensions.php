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
	**	Load all installed extensions from the 'Ext' directory.
	*/
	public static function init()
	{
		self::$loaded = new Map();

		Directory::readDirs(Path::append(dirname(__FILE__), 'Ext'))->dirs->forEach(function($i) { self::load($i->name, true); });
		Directory::readDirs(Path::append(dirname(__FILE__), '../../extensions'))->dirs->forEach(function($i) { self::load($i->name); });
	}

	/*
	**	Loads an extension given its identifier.
	*/
    public static function load ($identifier, $isPrimary=false)
    {
		if (self::isLoaded($identifier))
			return;

		require_once(Path::append(dirname(__FILE__), ($isPrimary ? 'Ext' : '../../extensions'), $identifier, $identifier.'.php'));

		self::$loaded->set($identifier, true);
	}

	/*
	**	Returns boolean indicating if the given extension is installed.
	*/
    public static function isInstalled ($identifier)
    {
		return Path::exists(Path::append(dirname(__FILE__), '../../extensions', $identifier, $identifier.'.php'));
	}

	/*
	**	Returns boolean indicating if the given extension is loaded.
	*/
    public static function isLoaded ($identifier)
    {
		return self::$loaded->has($identifier);
	}
};
