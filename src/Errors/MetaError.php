<?php
/*
**	Rose\MetaError
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

namespace Rose\Errors;

use Rose\Errors\Error;
use Rose\Math;

/*
**	Special exception that doesn't describe an error, but rather a way to quickly return a value to a catch block.
*/

class MetaError extends Error
{
	public static $baseLevel = 0;

	public $code;
	public $level;
	public $value;
	public $levelSensitive;

	/*
	**	Increase the base nesting-level desired for meta errors.
	*/
	public static function incBaseLevel()
	{
		self::$baseLevel++;
	}

	/*
	**	Decreases the base nesting-level desired for meta errors.
	*/
	public static function decBaseLevel()
	{
		self::$baseLevel--;
	}

	/*
	**	Creates the MetaError with the specified code (string) and value. Note that the level parameter controls to which
	**	nesting-level this exception is intended. A value of 0 is for the inner most level, a value of 1 is for the next
	**	level and so on.
	**
	**	When this exception is catched, the isForMe() method should be used to determine if it is for the caller.
	*/
    public function __construct ($code=null, $value=null, $level=0)
    {
		parent::__construct ('MetaError: ' . $code);

		$this->code = $code;
		$this->value = $value;

		$this->levelSensitive = $level != 0;
		$this->level = Math::max(0, self::$baseLevel - $level);
	}

	/*
	**	Returns true if this exception if meant for the caller (level == baseLevel). If it is not, caller should rethrow it.
	*/
	public function isForMe($baseLevelDelta=0)
	{
		return !$this->levelSensitive || $this->level == ($baseLevelDelta + self::$baseLevel);
	}
};
