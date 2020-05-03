<?php
/*
**	Rose\Expr
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

require_once('Main.php');

use Rose\Errors\Error;
use Rose\Regex;
use Rose\Arry;
use Rose\Map;

/**
**	Expression module, based on the Rin's templating module. The formats available are shown below.
**
**	HTML Escaped Output:			(data.value)					Escapes HTML characters from the output.
**	Raw Output:						(!data.value)					Does not escape HTML characters from the output (used to output direct HTML).
**	Double-Quoted Escaped Output:	($data.value)					Escapes HTML characters and surrounds with double quotes.
**	Immediate Reparse:				[<....] [@....] "..." '...'		Reparses the contents as if parseTemplate() was called again.
**	Immediate Output:				(:...)							Takes the contents and outputs exactly as-is without format and optionally enclosed by ()
**																	when the first character is not '<', ( or space.
**	Filtered Output:				(filterName ... <expr> ...)		Runs a filter call, 'expr' can be any of the allowed formats shown here (nested if desired),
**																	filterName should map to one of the available filter functions in the Rin.Expr::$filters map,
**																	each of which have their own parameters.
*/

class Expr
{
	/*
	**	Unescapes the back-slash escape sequences.
	*/
	static private function unescape ($value)
	{
		if (typeOf($value) == 'Rose\\Arry')
		{
			$value->forEach(function($value) { Expr::unescape($value); });
			return $value;
		}

		if (typeOf($value) == 'Rose\\Map')
		{
			$value->data = Expr::unescape($value->data);
			return $value;
		}

		for ($i = 0; $i < strlen($value); $i++)
		{
			if ($value[$i] == '\\')
			{
				$r = $value[$i+1];
				$value = substr($value, 0, $i) . $r . substr($value, $i+2);
			}
		}

		return $value;
	}

	/**
	**	Parses a template and returns the compiled 'parts' structure to be used by the 'expand' method.
	**
	**	>> array parseTemplate (string template, char sym_open, char sym_close, bool is_tpl=false);
	*/
	static public function parseTemplate ($template, $sym_open, $sym_close, $is_tpl=false, $root=1)
	{
		$nflush = 'string'; $flush = null; $state = 0; $count = 0;
		$str = ''; $parts = new Arry(); $mparts = $parts; $nparts = false;

		if ($is_tpl === true)
		{
			$template = trim($template);
			$nflush = 'identifier';
			$state = 10;

			$mparts->push($parts = new Arry());
		}

		$template .= "\0";

		$emit = function ($type, $data) use(&$parts, &$nparts, &$mparts, $sym_open, $sym_close)
		{
			if ($type == 'template')
			{
				$data = Expr::parseTemplate ($data, $sym_open, $sym_close, true, 0);
			}
			else if ($type == 'parse-string')
			{
				$data = Expr::parseTemplate ($data, $sym_open, $sym_close, false, 0);
				$type = 'base-string';

				if (typeOf($data) == 'Rose\\Arry')
				{
					$type = $data->get(0)->type;
					$data = $data->get(0)->data;
				}
			}
			else if ($type == 'parse-string-and-merge')
			{
				$data = Expr::parseTemplate ($data, $sym_open, $sym_close, false, 0);
			}

			if ($type == 'parse-string-and-merge')
			{
				$data->forEach(function($i) use(&$parts) {
					$parts->push($i);
				});
			}
			else
				$parts->push(Map::fromNativeArray([ 'type' => $type, 'data' => $data ], false));

			if ($nparts)
			{
				$mparts->push($parts = new Arry());
				$nparts = false;
			}
		};

		for ($i = 0; $i < strlen($template); $i++)
		{
			if ($template[$i] == "\\")
			{
				$str .= "\\";
				$str .= $template[++$i];
				continue;
			}

			switch ($state)
			{
				case 0:
					if ($template[$i] == "\0")
					{
						$flush = 'string';
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == '<')
					{
						$state = 1; $count = 1;
						$flush = 'string';
						$nflush = 'parse-string-and-merge';
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == '@')
					{
						$state = 1; $count = 1;
						$flush = 'string';
						$nflush = 'parse-string-and-merge';
						$i++;
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == ':')
					{
						$state = 12; $count = 1;
						$flush = 'string';
						$nflush = 'string';
						$i++;
					}
					else if ($template[$i] == $sym_open)
					{
						$state = 1; $count = 1;
						$flush = 'string';
						$nflush = 'template';
					}
					else
					{
						$str .= $template[$i];
					}

					break;
	
				case 1:
					if ($template[$i] == "\0")
					{
						throw new Error ("Parse error: Unexpected end of template");
					}

					if ($template[$i] == $sym_close)
					{
						$count--;
	
						if ($count < 0)
							throw new Error ("Parse error: Unmatched " + $sym_close);

						if ($count == 0)
						{
							$state = 0;
							$flush = $nflush;
							break;
						}
					}
					else if ($template[$i] == $sym_open)
					{
						$count++;
					}
	
					$str .= $template[$i];
					break;

				case 10:
					if ($template[$i] == "\0")
					{
						$flush = $nflush;
						break;
					}
					else if ($template[$i] == '.')
					{
						$emit ($nflush, $str);
						$emit ('access', '.');

						$nflush = 'identifier';
						$str = '';
						break;
					}
					else if (Regex::_matches('/[\t\n\r\f\v ]/', $template[$i]))
					{
						$flush = $nflush;
						$nflush = 'identifier';
						$nparts = true;

						while (Regex::_matches('/[\t\n\r\f\v ]/', $template[$i])) $i++;
						$i--;

						break;
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == '<')
					{
						if ($str) $flush = $nflush;
						$state = 11; $count = 1; $nflush = 'parse-string-and-merge';
						break;
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == '@')
					{
						if ($str) $flush = $nflush;
						$state = 11; $count = 1; $nflush = 'parse-string-and-merge';
						$i++;
						break;
					}
					else if ($template[$i] == '"')
					{
						if ($str) $flush = $nflush;
						$state = 14; $count = 1; $nflush = 'parse-string-and-merge';
						break;
					}
					else if ($template[$i] == '\'')
					{
						if ($str) $flush = $nflush;
						$state = 15; $count = 1; $nflush = 'parse-string-and-merge';
						break;
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == ':')
					{
						if ($str) $flush = $nflush;
						$state = 13; $count = 1; $nflush = 'string';
						$i++;
						break;
					}
					else if ($template[$i] == $sym_open)
					{
						if ($str) $emit ($nflush, $str);
						$state = 11; $count = 1; $str = ''; $nflush = 'parse-string';
						$str .= $template[$i];
						break;
					}
	
					if ($nflush != 'identifier')
					{
						$emit ($nflush, $str);
						$str = '';
						$nflush = 'identifier';
					}

					$str .= $template[$i];
					break;

					break;
	
				case 11:
					if ($template[$i] == "\0")
						throw new Error ("Parse error: Unexpected end of template");
	
					if ($template[$i] == $sym_close)
					{
						$count--;
	
						if ($count < 0)
							throw new Error ("Parse error: Unmatched " + $sym_close);

						if ($count == 0)
						{
							$state = 10;
	
							if ($nflush == 'parse-string-and-merge')
								break;
						}
					}
					else if ($template[$i] == $sym_open)
					{
						$count++;
					}
	
					$str .= $template[$i];
					break;

				case 12:
					if ($template[$i] == "\0")
						throw new Error ("Parse error: Unexpected end of template");
	
					if ($template[$i] == $sym_close)
					{
						$count--;
	
						if ($count < 0)
							throw new Error ("Parse error: Unmatched " + $sym_close);

						if ($count == 0)
						{
							if (strlen($str) != 0)
							{
								if (!($str[0] == '<' || $str[0] == '[' || $str[0] == ' '))
									$str = $sym_open . $str . $sym_close;
							}

							$state = 0;
							$flush = $nflush;
							break;
						}
					}
					else if ($template[$i] == $sym_open)
					{
						$count++;
					}
	
					$str .= $template[$i];
					break;

				case 13:
					if ($template[$i] == "\0")
						throw new Error ("Parse error: Unexpected end of template");

					if ($template[$i] == $sym_close)
					{
						$count--;
	
						if ($count < 0)
							throw new Error ("Parse error: Unmatched " + $sym_close);

						if ($count == 0)
						{
							if (!($str[0] == '<' || $str[0] == '[' || $str[0] == ' '))
								$str = $sym_open . $str . $sym_close;

							$state = 10;
							break;
						}
					}
					else if ($template[$i] == $sym_open)
					{
						$count++;
					}
	
					$str .= $template[$i];
					break;

				case 14:
					if ($template[$i] == "\0")
					{
						throw new Error ("Parse error: Unexpected end of template");
					}
	
					if ($template[$i] == '"')
					{
						$count--;
	
						if ($count < 0)
							throw new Error ("Parse error: Unmatched " + '"');

						if ($count == 0)
						{
							$state = 10;
	
							if ($nflush == 'parse-string-and-merge')
								break;
						}
					}

					$str .= $template[$i];
					break;

				case 15:
					if ($template[$i] == "\0")
					{
						throw new Error ("Parse error: Unexpected end of template");
					}
	
					if ($template[$i] == '\'')
					{
						$count--;
	
						if ($count < 0)
							throw new Error ("Parse error: Unmatched " + '\'');

						if ($count == 0)
						{
							$state = 10;
	
							if ($nflush == 'parse-string-and-merge')
								break;
						}
					}

					$str .= $template[$i];
					break;
			}

			if ($flush)
			{
				$emit ($flush, $str);
				$flush = $str = '';
			}
		}

		if (!$is_tpl)
		{
			$i = 0;
			while ($i < $mparts->length())
			{
				if ($mparts->get($i)->type == 'string' && $mparts->get($i)->data == '')
					$mparts->remove($i);
				else
					break;
			}

			$i = $mparts->length()-1;
			while ($i > 0)
			{
				if ($mparts->get($i)->type == 'string' && $mparts->get($i)->data == '')
					$mparts->remove($i--);
				else
					break;
			}

			if ($mparts->length() == 0)
				$mparts->push(Map::fromNativeArray([ 'type' => 'string', 'data' => '' ], false));
		}

		if ($root)
			Expr::unescape($mparts);

		return $mparts;
	}

	/**
	**	Parses a template and returns the compiled 'parts' structure to be used by the 'expand' method. This
	**	version assumes the sym_open and sym_close chars are ( and ) respectively.
	**
	**	>> array parse (string template);
	*/
	static public function parse ($template)
	{
		return Expr::parseTemplate(trim($template), '(', ')', false);
	}

	/**
	**	Expands a template using the given data object, ret can be set to 'text' or 'obj' allowing to expand the template as
	**	a string (text) or an array of objects (obj) respectively. If none provided it will be expanded as text.
	**
	**	>> string/array expand (array parts, object data, string ret='text', string mode='base-string');
	*/
	static public function expand ($parts, $data, $ret='text', $mode='base-string')
	{
		$s = new Arry();

		// Expand variable parts.
		if ($mode == 'var')
		{
			$escape = true;
			$quote = false;

			$root = $data;
			$last = null;
			$str = '';

			for ($i = 0; $i < $parts->length() && $data != null; $i++)
			{
				switch ($parts->get($i)->type)
				{
					case 'identifier':
					case 'string':
						$str .= $parts->get($i)->data;
						$last = null;
						break;

					case 'template':
						$last = Expr::expand($parts->get($i)->data, $root, 'arg', 'template');
						$str .= $last;
						break;

					case 'base-string':
						$str += Expr::expand($parts->get($i)->data, $root, 'arg', 'base-string');
						$last = null;
						break;

					case 'access':
						if (!$last || (typeOf($last) != 'Rose\\Arry' && typeOf($last) != 'Rose\\Map'))
						{
							while (true)
							{
								if ($str[0] == '!')
								{
									$str = substr($str, 1);
									$escape = false;
								}
								else if ($str[0] == '$')
								{
									$str = substr($str, 1);
									$quote = true;
								}
								else
									break;
							}

							if ($str != 'this')
								$data = $data != null ? ($data->has($str) ? $data->get($str) : null) : null;
						}
						else
							$data = $last;

						$str = '';
						break;
				}
			}

			while (true)
			{
				if ($str[0] == '!')
				{
					$str = substr($str, 1);
					$escape = false;
				}
				else if ($str[0] == '$')
				{
					$str = substr($str, 1);
					$quote = true;
				}
				else
					break;
			}

			if ($str != 'this')
				$data = $data != null ? ($data->has($str) ? $data->get($str) : null) : null;

			if (is_string($data))
			{
				if ($escape)
					$data = str_replace('&', '&amp;', str_replace('<', '&lt;', str_replace('>', '&gt;', $data)));

				if ($quote)
					$data = '"' . $data . '"';
			}

			$s->push($data);
		}

		// Expand function parts.
		if ($mode == 'fn')
		{
			$args = new Arry();

			$args->push(Expr::expand($parts->get(0), $data, 'text', 'base-string'));

			if (Expr::$filters->has('_'.$args->get(0)))
				$args->set(0, '_'.$args->get(0));

			if (!(Expr::$filters->has($args->get(0))))
				return '(Unknown: '.$args->get(0).')';

			if ($args->get(0)[0] == '_')
				return Expr::$filters->get($args->get(0)) ($parts, $data);

			for ($i = 1; $i < $parts->length(); $i++)
				$args->push(Expr::expand($parts->get($i), $data, 'arg', 'base-string'));

			$s->push(Expr::$filters->get($args->get(0)) ($args, $parts, $data));
		}

		// Template mode.
		if ($mode == 'template')
		{
			if ($parts->length() == 1)
			{
				if ($parts->get(0)->length() == 1 && $parts->get(0)->get(0)->type == 'string')
					return $parts->get(0)->get(0)->data;

				return Expr::expand($parts->get(0), $data, $ret, 'var');
			}

			return Expr::expand($parts, $data, $ret, 'fn');
		}

		// Expand parts.
		if ($mode == 'base-string')
		{
			$parts->forEach(function($i) use(&$s, &$data, &$ret)
			{
				switch ($i->type)
				{
					case 'template':
						$s->push(Expr::expand($i->data, $data, $ret, 'template'));
						break;

					case 'string': case 'identifier':
						$s->push($i->data);
						break;

					case 'base-string':
						$s->push(Expr::expand($i->data, $data, $ret, 'base-string'));
						break;
				}
			});
		}

		// Return as argument ('object' if only one, or string if more than one), that is, the first item in the result.
		if ($ret == 'arg')
		{
			if (typeOf($s) == 'Rose\\Arry')
			{
				if ($s->length() != 1)
					return $s->join('');

				return $s->get(0);
			}

			return $s;
		}

		if ($ret != 'obj' && typeOf($s) == 'Rose\\Arry')
		{
			$f = function($e) use(&$f) {
				return $e != null && typeOf($e) == 'Rose\\Arry' ? $e->map($f)->join('') : (string)$e;
			};

			$s = $s->map($f)->join('');
		}

		return $s;
	}

	/**
	**	Parses the given template and returns a function that when called with an object will expand the template.
	**
	**	>> object compile (string template);
	*/
	public static function compile ($template)
	{
		$template = Expr::parse($template);

		return function ($data, $mode) use (&$template) {
			return Expr::expand($template, $data, $mode);
		};
	}

	/**
	**	Template filters, functions that are used to format data. Each function takes three parameters (args, parts and data). By default the filter arguments
	**	are expanded and passed via 'args' for convenience, however if the filter name starts with '_' the 'args' parameter will be skipped and only (parts, data)
	**	will be available, each 'part' must be expanded manually by calling Expr::expand.
	*/
	public static $filters;
};

Expr::$filters = new Map();

/**
**	Expression filters.
*/
Expr::$filters->set('not', function($args) { return !$args->get(1); });
Expr::$filters->set('notnull', function($args) { return !!$args->get(1); });
Expr::$filters->set('null', function($args) { return !$args->get(1); });
Expr::$filters->set('int', function($args) { return (int)$args->get(1); });
Expr::$filters->set('eq', function($args) { return $args->get(1) == $args->get(2); });
Expr::$filters->set('ne', function($args) { return $args->get(1) != $args->get(2); });
Expr::$filters->set('lt', function($args) { return $args->get(1) < $args->get(2); });
Expr::$filters->set('le', function($args) { return $args->get(1) <= $args->get(2); });
Expr::$filters->set('gt', function($args) { return $args->get(1) > $args->get(2); });
Expr::$filters->set('ge', function($args) { return $args->get(1) >= $args->get(2); });
Expr::$filters->set('and', function($args) { for ($i = 1; $i < $args->length(); $i++) if (!$args->get($i)) return false; return true; });
Expr::$filters->set('or', function($args) { for ($i = 1; $i < $args->length(); $i++) if (~~$args->get($i)) return true; return false; });
Expr::$filters->set('char', function($args) { return chr($args->get(1)); });
Expr::$filters->set('len', function($args) { return strlen((string)$args->get(1)); });

Expr::$filters->set('neg', function($args) { return -$args->get(1); });
Expr::$filters->set('*', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x *= $args->get($i); return $x; });
Expr::$filters->set('mul', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x *= $args->get($i); return $x; });
Expr::$filters->set('/', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x /= $args->get($i); return $x; });
Expr::$filters->set('div', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x /= $args->get($i); return $x; });
Expr::$filters->set('+', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x -= -$args->get($i); return $x; });
Expr::$filters->set('sum', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x -= -$args->get($i); return $x; });
Expr::$filters->set('-', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x -= $args->get($i); return $x; });
Expr::$filters->set('sub', function($args) { $x = $args->get(1); for ($i = 2; $i < $args->length(); $i++) $x -= $args[$i]; return $x; });

/**
**	Returns the JSON representation of the expression.
**
**	json <expr>
*/
Expr::$filters->set('json', function ($args)
{
	return (string)$args->get(1);
});

/**
**	Sets a variable in the data context.
**
**	set <var-name> <expr>
*/
Expr::$filters->set('set', function ($args, $parts, $data)
{
	$data->set($args->get(1), $args->get(2));
	return '';
});

/**
**	Returns the expression without white-space on the left or right. The expression can be a string or an array.
**
**	trim <expr>
*/
Expr::$filters->set('trim', function ($args)
{
	return $args->get(1) ? (typeOf($args->get(1)) == 'Rose\\Arry' ? $args->get(1)->map(function($e) { return trim($e); }) : trim($args->get(1))) : '';
});

/**
**	Returns the expression in uppercase. The expression can be a string or an array.
**
**	upper <expr>
*/
Expr::$filters->set('upper', function ($args)
{
	return $args->get(1) ? (typeOf($args->get(1)) == 'Rose\\Arry' ? $args->get(1)->map(function($e) { return strtoupper($e); }) : strtoupper($args->get(1))) : '';
});

/**
**	Returns the expression in lower. The expression can be a string or an array.
**
**	lower <expr>
*/
Expr::$filters->set('lower', function ($args)
{
	return $args->get(1) ? (typeOf($args->get(1)) == 'Rose\\Arry' ? $args->get(1)->map(function($e) { return strtolower($e); }) : strtolower($args->get(1))) : '';
});

/**
**	Converts all new-line chars in the expression to <br/>, the expression can be a string or an array.
**
**	nl2br <expr>
*/
Expr::$filters->set('nl2br', function ($args)
{
	return $args->get(1) ? (typeOf($args->get(1)) == 'Rose\\Arry' ? $args->get(1)->map(function($e) { return str_replace("\n", '<br/>', $e); }) : str_replace("\n", '<br/>', $args->get(1))) : '';
});

/**
**	Returns the expression inside an XML tag named 'tag-name', the expression can be a string or an array.
**
**	% <tag-name> <expr>
*/
Expr::$filters->set('%', function ($args)
{
	$args->shift();
	$name = $args->shift();

	$s = '';

	for ($i = 0; $i < $args->length(); $i++)
	{
		if (typeOf($args->get($i)) == 'Rose\\Arry')
		{
			$s .= '<'.$name.'>';
			
			for ($j = 0; $j < $args->get($i)->length(); $j++)
				$s .= $args->get($i)->get($j);

			$s .= '</'.$name.'>';
		}
		else
			$s .= '<'.$name.'>'.$args->get($i).'</'.$name.'>';
	}

	return $s;
});

/**
**	Returns the expression inside an XML tag named 'tag-name', attributes are supported.
**
**	%% <tag-name> [<attr> <value>]* [<content>]
*/
Expr::$filters->set('%%', function ($args)
{
	$args->shift();
	$name = $args->shift();

	$attr = '';
	$text = '';

	for ($i = 0; $i < $args->length(); $i += 2)
	{
		if ($i+1 < $args->length())
			$attr .= ' '.$args->get($i).'='.$args->get($i+1);
		else
			$text = $args->get($i);
	}

	return $text ? '<'.$name.$attr.'>'.$text.'</'.$name.'>' : '<'.$name.$attr.'/>';
});


/**
**	Joins the given array expression into a string. The provided string-expr will be used as separator.
**
**	join <string-expr> <array-expr>
*/
Expr::$filters->set('join', function ($args)
{
	if ($args->get(2) && typeOf($args->get(2)) == 'Rose\\Arry')
		return $args->get(2)->join($args->get(1));

	return '';
});

/**
**	Splits the given expression by the specified string. Returns an array.
**
**	split <string-expr> <expr>
*/
Expr::$filters->set('split', function ($args)
{
	if ($args->get(2) && is_string($args->get(2)))
		return $args->get(2)->split($args->get(1));

	return new Arry();
});

/**
**	Returns an array with the keys of the given object-expr.
**
**	keys <object-expr>
*/
Expr::$filters->set('keys', function ($args)
{
	if ($args->get(1) && typeOf($args->get(1)) == 'Rose\\Map')
		return $args->get(1)->keys();

	return new Arry();
});

/**
**	Returns an array with the values of the given object-expr.
**
**	values <object-expr>
*/
Expr::$filters->set('values', function ($args)
{
	if ($args->get(1) && typeOf($args->get(1)) == 'Rose\\Map')
		return $args->get(1)->values();

	return [];
});

/**
**	Constructs an array obtained by expanding the given template for each of the items in the list-expr, the optional varname
**	parameter (defaults to 'i') indicates the name of the variable that will contain the data of each item as the list-expr is
**	traversed. The default variables i# and i## (suffix '#' and '##') are introduced to denote the index/key and numeric index
**	of the current item respectively, note that the later will always have a numeric value.
**
**	each <list-expr> [<varname:i>] <template>
*/
Expr::$filters->set('_each', function ($parts, $data)
{
	$var_name = 'i';
	$list = Expr::expand($parts->get(1), $data, 'arg');

	$k = 2;

	try {
		$tmp = Expr::expand($parts->get($k), $data, 'arg');

		if ($tmp && $parts->get($k)->get(0)->type == 'identifier' && Regex::_matches('/^[A-Za-z0-9_-]+$/', $tmp)) {
			$var_name = $tmp;
			$k++;
		}
	}
	catch(\Exception $e) {
	}

	$s = new Arry();
	$j = 0;

	$list->forEach(function($item, $key) use(&$var_name, &$s, &$j, &$k, &$parts, &$data)
	{
		$data->set($var_name, $item);
		$data->set($var_name . '##', $j++);
		$data->set($var_name . '#', $key);

		for ($k0 = $k; $k0 < $parts->length(); $k0++)
			$s->push(Expr::expand($parts->get($k0), $data, 'text'));
	});

	$data->remove($var_name);
	$data->remove($var_name . '#');

	return $s;
});

/**
**	Returns the valueA if the expression is true otherwise valueB, this is a short version of the 'if' filter with the
**	difference that the result is 'obj' instead of text.
**
**	? <expr> <valueA> [<valueB>]
*/
Expr::$filters->set('_?', function ($parts, $data)
{
	if (Expr::expand($parts->get(1), $data, 'arg'))
		return Expr::expand($parts->get(2), $data, 'arg');

	if ($parts->length() > 3)
		return Expr::expand($parts->get(3), $data, 'arg');

	return '';
});

/**
**	Returns the value if the expression is true, supports 'elif' and 'else' as well. The result of this filter is always text.
**
**	if <expr> <value> [elif <expr> <value>] [else <value>]
*/
Expr::$filters->set('_if', function ($parts, $data)
{
	for ($i = 0; $i < $parts->length(); $i += 3)
	{
		if (Expr::expand($parts->get($i), $data, 'arg') == 'else')
			return Expr::expand($parts->get($i+1), $data, 'text');

		if (Expr::expand($parts->get($i+1), $data, 'arg'))
			return Expr::expand($parts->get($i+2), $data, 'text');
	}

	return '';
});

/**
**	Loads the expression value and attempts to match one case.
**
**	switch <expr> <case1> <value1> ... <caseN> <valueN> default <defvalue> 
*/
Expr::$filters->set('_switch', function ($parts, $data)
{
	$value = Expr::expand($parts->get(1), $data, 'arg');

	for ($i = 2; $i < $parts->length(); $i += 2)
	{
		$case_value = Expr::expand($parts->get($i), $data, 'arg');
		if ($case_value == $value || $case_value == 'default')
			return Expr::expand($parts->get($i+1), $data, 'text');
	}

	return '';
});

/**
**	Repeats the specified template for a number of times.
**
**	repeat [<from>] <count> [<varname:i>] <template>
*/
Expr::$filters->set('repeat', function ($args, $parts, $data)
{
	$var_name = 'i';
	$count = (int)$args->get(1);
	$from = 0;

	$k = 2;

	if ($args->get($k) && Regex::_matches('/^[0-9]+$/', $args->get($k)))
	{
		$from = $count;
		$count = $from + (int)$args->get($k++);
	}

	if ($args->get($k) && $parts->get($k)->get(0)->type == 'identifier' && Regex::_matches('/^[A-Za-z0-9_-]+$/', $args->get($k)))
		$var_name = $args->get($k++);

	$s = new Arry();

	for ($i = $from; $i < $count; $i++)
	{
		$data->set($var_name, $i);

		for ($j = $k; $j < $parts->length(); $j++)
			$s->push(Expr::expand($parts->get($j), $data, 'text'));
	}

	$data->remove($var_name);
	return $s;
});

/**
**	Constructs a list from the given arguments and returns it.
**
**	list <expr> [<expr>...]
*/
Expr::$filters->set('_list', function ($parts, $data)
{
	$s = new Arry();

	for ($i = 1; $i < $parts->length(); $i++)
		$s->push(Expr::expand($parts->get($i), $data, 'arg'));

	return $s;
});

/**
**	Writes the specified arguments to the console.
**
**	echo <expr> [<expr>...]
*/
Expr::$filters->set('_echo', function ($parts, $data)
{
	for ($i = 1; $i < $parts->length(); $i++)
		echo(Expr::expand($parts->get($i), $data, 'arg')."\n");

	return '';
});

/**
**	Constructs an associative array (dictionary) and returns it.
**
**	dict <name>: <expr> [<name>: <expr>...]
*/
Expr::$filters->set('_dict', function ($parts, $data)
{
	$s = new Map();
	$key = null;

	for ($i = 1; $i < $parts->length(); $i++)
	{
		$tmp = Expr::expand($parts->get($i), $data, 'arg');
		if (substr($tmp, -1) == ':')
		{
			$key = substr($tmp, 0, strlen($tmp)-1);
			continue;
		}

		if (!$key) continue;

		$s->set($key, $tmp);
		$key = null;
	}

	return $s;
});
