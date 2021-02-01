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

use Rose\Errors\Error;
use Rose\Errors\MetaError;
use Rose\Regex;
use Rose\Arry;
use Rose\Map;
use Rose\IO\Path;
use Rose\IO\File;
use Rose\Math;

/**
**	Expression module, based on the Rin's templating module. The formats available are shown below.
**
**	HTML Escaped Output:			(data.value)					Escapes HTML characters from the output.
**	Raw Output:						(!data.value)					Does not escape HTML characters from the output (used to output direct HTML).
**	Double-Quoted Escaped Output:	($data.value)					Escapes HTML characters and surrounds with double quotes.
**	Immediate Reparse:				[<....] [@....] "..." '...'		Reparses the contents as if parseTemplate() was called again.
**	Immediate Output:				(:...)							Takes the contents and outputs exactly as-is without format and optionally enclosed by ()
**																	when the first character is not '<', ( or space.
**	Filtered Output:				(functionName ... <expr> ...)	Runs a function call, 'expr' can be any of the allowed formats shown here (nested if desired),
**																	functionName should map to one of the available expression functions registered in
**																	the Rin.Expr::$functions map, each of which have their own parameters.
*/

class Expr
{
	/*
	**	Strict mode flag. When set, any undefined expression function will trigger an error.
	*/
	static public $strict = true;

	/*
	**	Imported sources.
	*/
	static public $imported = null;

	/*
	**	Base path for imported sources.
	*/
	static public $importPath;

	/*
	**	Current source path.
	*/
	static public $currentPath;

	/*
	**	Current namespace.
	*/
	static public $namespace = '';

	/*
	**	Cache path.
	*/
	static public $cachePath = 'volatile/expr';

	/*
	**	Post-processes a parsed expression. Unescapes the backslash escape sequences.
	*/
	static private function postprocess ($value)
	{
		if (typeOf($value) == 'Rose\\Arry')
		{
			$value->forEach(function($value) { Expr::postprocess($value); });
			return $value;
		}

		if (typeOf($value) == 'Rose\\Map')
		{
			switch ($value->type)
			{
				case 'identifier':
					break;

				case 'integer':
					break;

				case 'number':
					break;

				default:
					$value->data = Expr::postprocess($value->data);
					break;
			}

			return $value;
		}

		for ($i = 0; $i < strlen($value); $i++)
		{
			if ($value[$i] == '\\')
			{
				$r = $value[$i+1];

				switch ($r)
				{
					case 'n': $r = "\n"; break;
					case 'r': $r = "\r"; break;
					case 'f': $r = "\f"; break;
					case 'v': $r = "\v"; break;
					case 't': $r = "\t"; break;
					case 's': $r = "\s"; break;
					case '"': $r = "\""; break;
					case "'": $r = "\'"; break;
				}

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
			else if ($type == 'parse')
			{
				$data = Expr::parseTemplate ($data, $sym_open, $sym_close, false, 0);
				$type = 'base-string';

				if (typeOf($data) == 'Rose\\Arry')
				{
					$type = $data->get(0)->type;
					$data = $data->get(0)->data;
				}
			}
			else if ($type == 'parse-trim-merge')
			{
				$data = Expr::parseTemplate (Text::split ("\n", trim($data))->map(function($i) { return Text::trim($i); })->join("\n"), $sym_open, $sym_close, false, 0);
			}
			else if ($type == 'parse-merge')
			{
				$data = Expr::parseTemplate ($data, $sym_open, $sym_close, false, 0);
			}
			else if ($type == 'parse-merge-alt')
			{
				$data = Expr::parseTemplate ($data, '{', '}', false, 0);
			}
			else if ($type == 'integer')
			{
				$data = (int)$data;
			}
			else if ($type == 'number')
			{
				$data = (float)$data;
			}

			if ($type == 'parse-merge' || $type == 'parse-merge-alt' || $type == 'parse-trim-merge')
			{
				$data->forEach(function($i) use(&$parts) {
					$parts->push($i);
				});
			}
			else
				$parts->push(new Map([ 'type' => $type, 'data' => $data ], false));

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
						$nflush = 'parse-merge';
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == '@')
					{
						$state = 1; $count = 1;
						$flush = 'string';
						$nflush = 'parse-trim-merge';
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
					else if (Regex::_matches('/^([-+][0-9]|[0-9])/', $template[$i].$template[$i+1]) && $str == '')
					{
						if ($flush && $str)
						{
							$emit ($flush, $str);
							$flush = $str = '';
						}

						$str = $template[$i];
						$nflush = 'integer';
						$state = 17;
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
						$state = 11; $count = 1; $nflush = 'parse-merge';
						break;
					}
					else if ($template[$i] == $sym_open && $template[$i+1] == '@')
					{
						if ($str) $flush = $nflush;
						$state = 11; $count = 1; $nflush = 'parse-trim-merge';
						$i++;
						break;
					}
					else if ($template[$i] == '"')
					{
						if ($str) $flush = $nflush;
						$state = 14; $count = 1; $nflush = 'parse-merge';
						break;
					}
					else if ($template[$i] == '\'')
					{
						if ($str) $flush = $nflush;
						$state = 15; $count = 1; $nflush = 'parse-merge';
						break;
					}
					else if ($template[$i] == '`')
					{
						if ($str) $flush = $nflush;
						$state = 16; $count = 1; $nflush = 'parse-merge-alt';
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
						$state = 11; $count = 1; $str = ''; $nflush = 'parse';
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
	
							if ($nflush == 'parse-merge' || $nflush == 'parse-merge-alt' || $nflush == 'parse-trim-merge')
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
	
							if ($nflush == 'parse-merge' || $nflush == 'parse-merge-alt' || $nflush == 'parse-trim-merge')
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
	
							if ($nflush == 'parse-merge' || $nflush == 'parse-merge-alt' || $nflush == 'parse-trim-merge')
								break;
						}
					}

					$str .= $template[$i];
					break;

				case 16:
					if ($template[$i] == "\0")
					{
						throw new Error ("Parse error: Unexpected end of template");
					}
	
					if ($template[$i] == '`')
					{
						$count--;
	
						if ($count < 0)
							throw new Error ("Parse error: Unmatched " + '`');

						if ($count == 0)
						{
							$state = 10;
	
							if ($nflush == 'parse-merge' || $nflush == 'parse-merge-alt' || $nflush == 'parse-trim-merge')
								break;
						}
					}

					$str .= $template[$i];
					break;

				case 17:
					if ($template[$i] == '.')
					{
						$nflush = 'number';
						$state = 18;
					}
					else if (!Regex::_matches('/[0-9]/', $template[$i]))
					{
						$state = 10;
						$i--;
						break;
					}

					$str .= $template[$i];
					break;

				case 18:
					if (!Regex::_matches('/[0-9]/', $template[$i]))
					{
						$state = 10;
						$i--;
						break;
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
				$mparts->push(new Map([ 'type' => 'string', 'data' => '' ], false));
		}

		if ($root)
		{
			Expr::postprocess($mparts);

			if (false) {
				echo "<pre>";
				$s = (string)$mparts;
				$s = json_decode($s);
				echo json_encode($s, JSON_PRETTY_PRINT);
				echo "</pre>";
				exit;
			}
		}

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
	**	Removes all static parts from a parsed template.
	**
	**	>> array clean (array parts);
	*/
	static public function clean ($parts)
	{
		for ($i = 0; $i < $parts->length; $i++)
		{
			if ($parts->get($i)->type != 'template')
			{
				$parts->remove($i);
				$i--;
			}
		}

		return $parts;
	}

	/**
	**	Expands a template using the given data object, ret can be set to 'text' or 'obj' allowing to expand the template as
	**	a string (text) or an array of objects (obj) respectively. If none provided it will be expanded as text.
	**
	**	>> string/array expand (array parts, object data, string ret='text', string mode='base-string', bool throwMeta=false);
	*/
	static public function expand ($parts, $data, $ret='text', $mode='base-string', $throwMeta=false)
	{
		$s = new Arry();

		// Expand variable parts.
		if ($mode == 'var')
		{
			$escape = true;
			$quote = false;

			$root = $data;
			$last = null;
			$first = true;
			$str = '';

			for ($i = 0; $i < $parts->length() && $data != null; $i++)
			{
				switch ($parts->get($i)->type)
				{
					case 'identifier':
					case 'string':
					case 'integer':
					case 'number':
						$str .= $parts->get($i)->data;
						$last = null;
						break;

					case 'template':
						$last = Expr::expand($parts->get($i)->data, $root, 'arg', 'template');
						$str .= typeOf($last) == 'primitive' ? $last : '';

						if (\Rose\typeOf($last) == 'function')
						{
							$data = $last (new Arry(['']), null, $root);
							$last = null;
						}

						break;

					case 'base-string':
						$str .= Expr::expand($parts->get($i)->data, $root, 'arg', 'base-string');
						$last = null;
						break;

					case 'access':
						if (!$last || typeOf($last) == 'primitive')
						{
							if (!$str) $str = 'this';

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

							if ($str != 'this' && $data != null)
							{
								$tmp = $data;
								$data = $data->{$str};

								if ($data === null && $first)
								{
									// VIOLET: Possibly no longer required.
									if (Expr::$functions->has($str))
										$data = Expr::$functions->get($str) (null, null, $tmp);
								}

								$first = false;
							}
						}
						else
							$data = $last;

						$str = '';
						break;
				}
			}

			while ($str != '')
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

			if ($str && $str != 'this')
			{
				$failed = false;

				if ($data != null)
				{
					if (typeOf($data) == 'Rose\\Arry' || typeOf($data) == 'Rose\\Map')
					{
						if (!$data->has($str))
						{
							$failed = true;
							$data = null;
						}
						else
							$data = $data->{$str};
					}
					else
						$data = $data->{$str};
				}
				else
					$failed = true;

				if ($failed && $parts->length == 1)
				{
					if (Expr::$strict == true)
						throw new Error ('Expression function `'.$str.'` not found.');
				}
			}

			/*if (is_string($data))
			{
				//if ($escape)
				//	$data = str_replace('&', '&amp;', str_replace('<', '&lt;', str_replace('>', '&gt;', $data)));

				//if ($quote)
				//	$data = '"' . $data . '"';
			}*/

			$s->push($data);
		}

		// Expand variable parts and returns a reference to it.
		if ($ret == 'varref')
		{
			$root = $data;
			$last = null;
			$first = true;
			$str = '';

			for ($i = 0; $i < $parts->length() && $data != null; $i++)
			{
				switch ($parts->get($i)->type)
				{
					case 'identifier':
					case 'string':
					case 'integer':
					case 'number':
						$str .= (string)$parts->get($i)->data;
						$last = null;
						break;

					case 'template':
						$last = Expr::expand($parts->get($i)->data, $root, 'arg', 'template');
						$str .= typeOf($last) == 'primitive' ? $last : '';
						break;

					case 'base-string':
						$str .= Expr::expand($parts->get($i)->data, $root, 'arg', 'base-string');
						$last = null;
						break;

					case 'access':
						if (!$last || typeOf($last) == 'primitive')
						{
							if (!$str) $str = 'this';

							while (true)
							{
								if ($str[0] == '!')
								{
									$str = substr($str, 1);
								}
								else if ($str[0] == '$')
								{
									$str = substr($str, 1);
								}
								else
									break;
							}

							if ($str != 'this' && $data != null)
							{
								$tmp = $data;
								$data = $data->{$str};

								if ($data === null && $first)
								{
									if (Expr::$functions->has($str))
										$data = Expr::$functions->get($str) (null, null, $tmp);
								}

								$first = false;
							}
						}
						else
							$data = $last;

						$str = '';
						break;
				}
			}

			while ($str != '')
			{
				if ($str[0] == '!')
				{
					$str = substr($str, 1);
				}
				else if ($str[0] == '$')
				{
					$str = substr($str, 1);
				}
				else
					break;
			}

			return $str != 'this' ? [$data, $str] : null;
		}

		// Expand function parts.
		if ($mode == 'fn')
		{
			$args = new Arry();
			$args->push(Expr::expand($parts->get(0), $data, 'arg', 'base-string'));

			switch (\Rose\typeOf($args->get(0)))
			{
				case 'function':
					for ($i = 1; $i < $parts->length(); $i++)
						$args->push(Expr::expand($parts->get($i), $data, 'arg', 'base-string'));

					return $args->get(0) ($args, $parts, $data);

				default:
					$args->set(0, (string)$args->get(0));
					break;
			}

			if (Expr::$functions->has('_'.$args->get(0)))
				$args->set(0, '_'.$args->get(0));

			if (!(Expr::$functions->has($args->get(0))))
			{
				if (Expr::$strict == true)
					throw new Error ('Expression function `'.$args->get(0).'` not found.');

				return '(Unknown: '.$args->get(0).')';
			}

			if ($args->get(0)[0] == '_')
				return Expr::$functions->get($args->get(0)) ($parts, $data);

			for ($i = 1; $i < $parts->length(); $i++)
				$args->push(Expr::expand($parts->get($i), $data, 'arg', 'base-string'));

			$s->push(Expr::$functions->get($args->get(0)) ($args, $parts, $data));
		}

		// Template mode.
		if ($mode == 'template')
		{
			if ($parts->length() == 1)
			{
				if ($parts->get(0)->length() == 1)
				{
					switch ($parts->get(0)->get(0)->type)
					{
						case 'string':
						case 'integer':
						case 'number':
							return $parts->get(0)->get(0)->data;

						case 'identifier':
							$name = $parts->get(0)->get(0)->data;

							if (Expr::$functions->has($name) || Expr::$functions->has('_'.$name))
								return Expr::expand($parts, $data, $ret, 'fn');

							break;
					}
				}

				// case 'template':
				return Expr::expand($parts->get(0), $data, $ret, 'var');
			}

			return Expr::expand($parts, $data, $ret, 'fn');
		}

		// Expand parts.
		if ($mode == 'base-string')
		{
			try
			{
				$parts->forEach(function($i, $index, $list) use (&$s, &$data, &$ret, &$throwMeta)
				{
					try
					{
						switch ($i->type)
						{
							case 'template':
								$tmp = Expr::expand($i->data, $data, $ret, 'template');
								break;

							
							case 'identifier':
							case 'access':
							case 'string':
							case 'integer':
							case 'number':
								$tmp = $i->data;
								break;

							case 'base-string':
								$tmp = Expr::expand($i->data, $data, $ret, 'base-string');
								break;
						}

						if ($ret == 'void')
							return;

						if ($ret == 'last' && $index != $list->length-1)
							return;

						$s->push($tmp);
					}
					catch (MetaError $e)
					{
						if ($throwMeta) throw $e;

						switch ($e->code)
						{
							case 'EXPR_YIELD':
								$s->clear();
								$s->push($e->value);
								throw new MetaError('EXPR_END');

							default:
								throw $e;
						}
					}
				});
			}
			catch (MetaError $e)
			{
				if ($throwMeta) throw $e;

				switch ($e->code)
				{
					case 'EXPR_END':
						break;

					default:
						throw $e;
				}
			}
		}

		// Return types for direct objects.
		if ($ret == 'obj') return $s;

		// Returns only the last obtained value.
		if ($ret == 'last')
		{
			if (typeOf($s) == 'Rose\\Arry')
				$s = $s->{0};

			return $s;
		}

		// When the output is not really needed.
		if ($ret == 'void') return null;

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

		if ($ret == 'text' && typeOf($s) == 'Rose\\Arry')
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

		return function ($data=null, $mode='text') use (&$template) {
			return Expr::expand($template, $data ? $data : new Map(), $mode);
		};
	}

	/**
	**	Parses and expands the given template immediately.
	**
	**	>> object eval (string template, object data, string mode='text');
	*/
	public static function eval ($template, $data=null, $mode='text')
	{
		$template = Expr::parse($template);
		return Expr::expand($template, $data ? $data : new Map(), $mode);
	}

	/**
	**	Expands the template as 'arg' and returns the result.
	**
	**	>> object value (string parts, object data);
	*/
	public static function value ($parts, $data=null)
	{
		return typeOf($parts) != 'Rose\\Arry' ? $parts : Expr::expand($parts, $data ? $data : new Map(), 'arg');
	}

	/**
	**	Template functions, functions that are used to format data. Each function takes three parameters (args, parts and data). By default the function arguments
	**	are expanded and passed via 'args' for convenience, however if the function name starts with '_' the 'args' parameter will be skipped and only (parts, data)
	**	will be available, each 'part' must be expanded manually by calling Expr::expand.
	*/
	public static $functions;

	/**
	**	Registers an expression function.
	**
	**	>> object register (string name, function fn);
	*/
	public static function register ($name, $fn)
	{
		Expr::$functions->set ($name, $fn);
	}

	/**
	**	Calls an expression function.
	**
	**	>> object call (string name, object args, object data);
	*/
	public static function call ($name, $args, $data=null)
	{
		if (Expr::$functions->has($name))
			return Expr::$functions->get($name) ($args, null, $data);

		return null;
	}

	/*
	**	Returns a map given a 'parts' array having values of the form "name: value" or ":name value".
	**
	**	>> object getNamedValues (array parts, object data, int i=1, bool expanded=true);
	*/
	public static function getNamedValues ($parts, $data, $i=1, $expanded=true)
	{
		$s = new Map();
		$mode = 0;
	
		for (; $i < $parts->length(); $i += 2)
		{
			$key = Expr::expand($parts->get($i), $data, 'arg');

			if (!$mode) {
				if ($key[0] == ':') $mode = 1; else $mode = substr($key, -1) == ':' ? 2 : 3;
			}

			if ($mode == 1)
				$key = substr($key, 1);
			else if ($mode == 2)
				$key = substr($key, 0, strlen($key)-1);

			if ($expanded)
				$s->set($key, Expr::expand($parts->get($i+1), $data, 'arg'));
			else
				$s->set($key, $parts->get($i+1));
		}

		return $s;
	}

	/*
	**	Performs a reduction operation by evaluating the specified function for each item in the list, passing two arguments (accum, value) and
	**	saving the returned value as `accum`.
	**
	**	>> object reduce (object value, array list, int startIndex, function fn);
	*/
	public static function reduce ($value, $list, $startIndex, $fn)
	{
		for ($i = $startIndex; $i < $list->length(); $i++)
			$value = $fn ($value, $list->get($i), $i-$startIndex);

		return $value;
	}

	/*
	**	Applies a function to a given input.
	**
	**	>> object apply (array list, function fn);
	*/
	public static function apply ($list, $fn)
	{
		if ($list->length == 1)
		{
			$list = $list->get(0);

			if (\Rose\typeOf($list) == 'Rose\Arry')
			{
				$output = new Arry();
	
				for ($i = 0; $i < $list->length(); $i++)
					$output->push( $fn ($list->get($i)) );
			}
			else if (\Rose\typeOf($list) == 'Rose\Map')
			{
				$output = new Map();
	
				foreach ($list->__nativeArray as $name => $value)
					$output->set($name, $fn ($value));
			}
			else
				$output = $fn ($list);
		}
		else
		{
			$_output = new Arry();

			$list->forEach(function($list) use (&$_output, &$fn)
			{
				if (\Rose\typeOf($list) == 'Rose\Arry')
				{
					$output = new Arry();
		
					for ($i = 0; $i < $list->length(); $i++)
						$output->push( $fn ($list->get($i)) );
				}
				else if (\Rose\typeOf($list) == 'Rose\Map')
				{
					$output = new Map();
		
					foreach ($list->__nativeArray as $name => $value)
						$output->set($name, $fn ($value));
				}
				else
					$output = $fn ($list);

				$_output->push($output);
			});

			$output = $_output;
		}

		return $output;
	}

	/*
	**	Evaluates a block of expressions, returns the last value.
	**
	**	>> object value (array parts, object data);
	*/
	public static function blockValue ($parts, $data)
	{
		$value = null;

		try
		{
			for ($i = 0; $i < $parts->length; $i++)
				$value = Expr::expand($parts->get($i), $data, 'arg', 'base-string', true);
		}
		catch (MetaError $e)
		{
			switch ($e->code)
			{
				case 'EXPR_YIELD':
					return $e->value;

				default:
					throw $e;
			}
		}

		return $value;
	}
};


/*
**	Initialize class constants.
*/

Expr::$functions = new Map();
Expr::$imported = new Map();

Expr::$currentPath = Path::resolve(Main::$CORE_DIR);
Expr::$importPath = Path::resolve(Main::$CORE_DIR);


/**
**	Expression functions.
*/
Expr::register('null', function($args) { return null; });
Expr::register('true', function($args) { return true; });
Expr::register('false', function($args) { return false; });

Expr::register('len', function($args) { $s = $args->get(1); return \Rose\typeOf($s) == 'primitive' ? strlen((string)$s) : $s->length; });
Expr::register('int', function($args) { return (int)$args->get(1); });
Expr::register('str', function($args) { $s = ''; for ($i = 1; $i < $args->length; $i++) $s .= (string)$args->get($i); return $s; });
Expr::register('float', function($args) { return (float)$args->get(1); });
Expr::register('chr', function($args) { return chr($args->get(1)); });
Expr::register('ord', function($args) { return ord($args->get(1)); });

Expr::register('not', function($args) { return !$args->get(1); });
Expr::register('neg', function($args) { return -$args->get(1); });
Expr::register('abs', function($args) { return abs($args->get(1)); });

Expr::register('_and', function($parts, $data) { for ($i = 1; $i < $parts->length(); $i++) { $v = Expr::value($parts->get($i), $data); if (!$v) return null; } return $v; });
Expr::register('_or', function($parts, $data) { for ($i = 1; $i < $parts->length(); $i++) { $v = Expr::value($parts->get($i), $data); if (!!$v) return $v; } return null; });

Expr::register('eq', function($args) { return $args->get(1) == $args->get(2); });
Expr::register('ne', function($args) { return $args->get(1) != $args->get(2); });
Expr::register('lt', function($args) { return $args->get(1) < $args->get(2); });
Expr::register('le', function($args) { return $args->get(1) <= $args->get(2); });
Expr::register('gt', function($args) { return $args->get(1) > $args->get(2); });
Expr::register('ge', function($args) { return $args->get(1) >= $args->get(2); });
Expr::register('isnotnull', function($args) { return $args->get(1) !== null; });
Expr::register('isnull', function($args) { return $args->get(1) === null; });
Expr::register('isnotempty', function($args) { return !!$args->get(1); });
Expr::register('isempty', function($args) { return !$args->get(1); });
Expr::register('iszero', function($args) { return (float)$args->get(1) == 0; });

Expr::register('eq?', function($args) { return $args->get(1) == $args->get(2); });
Expr::register('ne?', function($args) { return $args->get(1) != $args->get(2); });
Expr::register('lt?', function($args) { return $args->get(1) < $args->get(2); });
Expr::register('le?', function($args) { return $args->get(1) <= $args->get(2); });
Expr::register('gt?', function($args) { return $args->get(1) > $args->get(2); });
Expr::register('ge?', function($args) { return $args->get(1) >= $args->get(2); });
Expr::register('notnull?', function($args) { return $args->get(1) !== null; });
Expr::register('null?', function($args) { return $args->get(1) === null; });
Expr::register('notempty?', function($args) { return !!$args->get(1); });
Expr::register('empty?', function($args) { return !$args->get(1); });
Expr::register('zero?', function($args) { return (float)$args->get(1) == 0; });

Expr::register('typeof', function($args)
{
	$type = typeOf($args->get(1), true);

	if ($type == 'Rose\\Arry') $type = 'array';
	if ($type == 'Rose\\Map') $type = 'object';

	if (substr($type, 0, 5) == 'Rose\\')
		$type = strtolower(substr($type, 5));

	return $type;
});

Expr::register('*', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum*$value; }); });
Expr::register('/', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum/$value; }); });
Expr::register('+', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum+$value; }); });
Expr::register('-', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum-$value; }); });
Expr::register('mul', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum*$value; }); });
Expr::register('div', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum/$value; }); });
Expr::register('sum', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum+$value; }); });
Expr::register('sub', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum-$value; }); });
Expr::register('mod', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum%$value; }); });
Expr::register('pow', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return pow($accum, $value); }); });

Expr::register('min', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return Math::min($accum, $value); }); });
Expr::register('max', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return Math::max($accum, $value); }); });

/**
**	Returns the JSON representation of the expression.
**
**	json <expr>
*/
Expr::register('json', function ($args)
{
	$value = $args->get(1);

	if (typeOf($value) == 'Rose\\Arry' || typeOf($value) == 'Rose\\Map')
		return (string)$value;

	return json_encode($value);
});

/**
**	Sets one or more variables in the data context.
**
**	set <var-name> <expr> [<var-name> <expr>]*
*/
Expr::register('_set', function ($parts, $data)
{
	for ($i = 1; $i+1 < $parts->length; $i += 2)
	{
		$value = Expr::value($parts->get($i+1), $data);

		if ($parts->get($i)->length > 1)
		{
			$ref = Expr::expand($parts->get($i), $data, 'varref');
			if ($ref != null)
			{
				if (!$ref[0])
					throw new Error('Unable to set: ' . $parts->get($i)->map(function($i) { return $i->data; })->join('')  );

				$ref[0]->{$ref[1]} = $value;
			}
		}
		else
			$data->set(Expr::value($parts->get($i), $data), $value);
	}

	return null;
});

/**
**	Removes one or more variables from the data context.
**
**	unset <var-name> [<var-name>]*
*/
Expr::register('_unset', function ($parts, $data)
{
	for ($i = 1; $i < $parts->length; $i++)
	{
		if ($parts->get($i)->length > 1)
		{
			$ref = Expr::expand($parts->get($i), $data, 'varref');
			if ($ref != null) $ref[0]->remove ($ref[1]);
		}
		else
			$data->remove(Expr::value($parts->get($i), $data));
	}

	return null;
});

/**
**	Returns the expression without white-space on the left or right. The expression can be a string or an array.
**
**	trim <args>
*/
Expr::register('trim', function ($args)
{
	return Expr::apply($args->slice(1), function($value) { return trim($value); });
});

/**
**	Returns the expression in uppercase. The expression can be a string or an array.
**
**	upper <args>
*/
Expr::register('upper', function ($args)
{
	return Expr::apply($args->slice(1), function($value) { return strtoupper($value); });
});

/**
**	Returns the expression in lower. The expression can be a string or an array.
**
**	lower <args>
*/
Expr::register('lower', function ($args)
{
	return Expr::apply($args->slice(1), function($value) { return strtolower($value); });
});

/**
**	Returns a sub-string of the given string.
**
**	substr <start> <count> <string>
**	substr <start> <string>
*/
Expr::register('substr', function ($args)
{
	$s = (string)$args->get($args->length-1);

	$start = 0;
	$count = null;

	if ($args->length == 4)
	{
		$start = (int)($args->get(1));
		$count = (int)($args->get(2));
	}
	else
	{
		$start = (int)($args->get(1));
		$count = null;
	}

	if ($start < 0) $start += Text::length($s);
	if ($count < 0) $count += Text::length($s);

	if ($count === null)
		$count = Text::length($s) - $start;

	return Text::substring ($s, $start, $count);
});

/**
**	Replaces a matching string with the given replacement string in a given text.
**
**	replace <search> <replacement> <args>
*/
Expr::register('replace', function ($args)
{
	$search = $args->get(1);
	$replacement = $args->get(2);

	return Expr::apply($args->slice(3), function($value) use(&$search, &$replacement) {
		return Text::replace($search, $replacement, $value);
	});
});

/**
**	Converts all new-line chars in the expression to <br/>, the expression can be a string or an array.
**
**	nl2br <args>
*/
Expr::register('nl2br', function ($args)
{
	return Expr::apply($args->slice(1), function($value) { return str_replace("\n", '<br/>', $value); });
});

/**
**	Returns the expression inside an XML tag named 'tag-name', the expression can be a string or an array.
**
**	% <tag-name> <arg>
*/
Expr::register('%', function ($args)
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
**	%% <tag-name> [<attr> <value>]* [<arg>]
*/
Expr::register('%%', function ($args)
{
	$args->shift();
	$name = $args->shift();

	$attr = '';
	$text = '';

	for ($i = 0; $i < $args->length(); $i += 2)
	{
		if ($i+1 < $args->length())
			$attr .= ' '.$args->get($i).'="'.$args->get($i+1).'"';
		else
			$text = $args->get($i);
	}

	return $text ? '<'.$name.$attr.'>'.$text.'</'.$name.'>' : '<'.$name.$attr.'/>';
});


/**
**	Joins the given list expression into a string. The provided glue will be used as separator.
**
**	join <glue> <list-expr>
*/
Expr::register('join', function ($args)
{
	if ($args->get(2) && typeOf($args->get(2)) == 'Rose\\Arry')
		return $args->get(2)->join($args->get(1));

	return '';
});

/**
**	Splits the given expression by the specified delimiter. Returns an array.
**
**	split <delimiter> <str-expr>
*/
Expr::register('split', function ($args)
{
	if ($args->get(2) && is_string($args->get(2)))
		return Text::split($args->get(1), $args->get(2));

	return new Arry();
});

/**
**	Returns an array with the keys of the given object-expr.
**
**	keys <object-expr>
*/
Expr::register('keys', function ($args)
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
Expr::register('values', function ($args)
{
	if ($args->get(1) && typeOf($args->get(1)) == 'Rose\\Map')
		return $args->get(1)->values();

	return [];
});

/**
**	Constructs an array obtained by expanding the given block for each of the items in the list-expr, the mandatory varname
**	parameter (namely 'i') indicates the name of the variable that will contain the data of each item as the list-expr is
**	traversed. Extra variables i# and i## (suffix '#' and '##') are introduced to denote the index/key and numeric index
**	of the current item respectively, note that the later will always have a numeric value.
**
**	each <varname> <list-expr> <block>
*/
Expr::register('_each', function ($parts, $data)
{
	$var_name = Expr::value($parts->get(1), $data);
	$list = Expr::value($parts->get(2), $data);

	$s = new Arry();
	$j = 0;

	if (!$list) return $s;

	$block = $parts->slice(3);

	$list->forEach(function($item, $key) use(&$var_name, &$s, &$j, &$k, &$data, &$block)
	{
		$data->set($var_name, $item);
		$data->set($var_name . '##', $j++);
		$data->set($var_name . '#', $key);

		$s->push(Expr::blockValue($block, $data));
	});

	$data->remove($var_name);
	$data->remove($var_name . '##');
	$data->remove($var_name . '#');

	return $s;
});

/**
**	Expands the given block for each of the items in the list-expr, the mandatory varname parameter (namely 'i') indicates the name of the variable
**	that will contain the data of each item as the list-expr is traversed. Extra variables i# and i## (suffix '#' and '##') are introduced to denote
**	the index/key and numeric index of the current item respectively, note that the later will always have a numeric value.
**
**	Returns the source list.
**
**	foreach <varname> <list-expr> <block>
*/
Expr::register('_foreach', function ($parts, $data)
{
	$var_name = Expr::value($parts->get(1), $data);
	$list = Expr::value($parts->get(2), $data);

	$j = 0;
	if (!$list) return $list;

	$block = $parts->slice(3);

	$list->forEach(function($item, $key) use(&$var_name, &$s, &$j, &$k, &$data, &$block)
	{
		$data->set($var_name, $item);
		$data->set($var_name . '##', $j++);
		$data->set($var_name . '#', $key);

		Expr::blockValue($block, $data);
	});

	$data->remove($var_name);
	$data->remove($var_name . '##');
	$data->remove($var_name . '#');

	return $list;
});

/**
**	Returns the valueA if the expression is true otherwise valueB, this is a short version of the 'if' function with the
**	difference that the result is 'obj' instead of text.
**
**	? <expr> <valueA> [<valueB>]
*/
Expr::register('_?', function ($parts, $data)
{
	if (Expr::expand($parts->get(1), $data, 'arg'))
		return Expr::expand($parts->get(2), $data, 'arg');

	if ($parts->length() > 3)
		return Expr::expand($parts->get(3), $data, 'arg');

	return '';
});

/**
**	Returns the valueA if it is not null (or empty or zero), otherwise returns valueB.
**
**	?? <valueA> <valueB>
*/
Expr::register('_??', function ($parts, $data)
{
	$value = Expr::expand($parts->get(1), $data, 'arg');
	if ($value) return $value;

	return Expr::expand($parts->get(2), $data, 'arg');
});

/**
**	Returns the value if the expression is true, supports 'elif' and 'else' as well.
**
**	if <expr> <value> [elif <expr> <value>] [else <value>]
*/
Expr::register('_if', function ($parts, $data)
{
	for ($i = 0; $i < $parts->length(); $i += 3)
	{
		if (Expr::expand($parts->get($i), $data, 'arg') == 'else')
			return Expr::expand($parts->get($i+1), $data, 'arg');

		if (Expr::expand($parts->get($i+1), $data, 'arg'))
			return Expr::expand($parts->get($i+2), $data, 'arg');
	}

	return '';
});

/**
**	Returns the value returned by the block if the expression is true.
**
**	when <expr> <block>
*/
Expr::register('_when', function ($parts, $data)
{
	if (Expr::expand($parts->get(1), $data, 'arg'))
		return Expr::blockValue($parts->slice(2), $data);

	return null;
});

/**
**	Loads the expression value and attempts to match one case.
**
**	case <expr> <case1> <value1> ... <caseN> <valueN> default <defvalue> 
*/
Expr::register('_switch', function ($parts, $data)
{
	$value = Expr::expand($parts->get(1), $data, 'arg');

	for ($i = 2; $i < $parts->length(); $i += 2)
	{
		$case_value = Expr::expand($parts->get($i), $data, 'arg');
		if ($case_value == $value || $case_value == 'default')
			return Expr::expand($parts->get($i+1), $data, 'arg');
	}

	return '';
});

Expr::register('_case', function ($parts, $data)
{
	$value = Expr::expand($parts->get(1), $data, 'arg');

	for ($i = 2; $i < $parts->length(); $i += 2)
	{
		$case_value = Expr::expand($parts->get($i), $data, 'arg');
		if ($case_value == $value || $case_value == 'default')
			return Expr::expand($parts->get($i+1), $data, 'arg');
	}

	return '';
});

/**
**	Exits the current inner most loop.
**
**	break
*/
Expr::register('_break', function ($parts, $data)
{
	throw new \Exception('EXC_BREAK');
});

/**
**	Skips execution and continues the next cycle of the current inner most loop.
**
**	continue
*/
Expr::register('_continue', function ($parts, $data)
{
	throw new \Exception('EXC_CONTINUE');
});

/**
**	Constructs an array with the results of repeating the specified template for a number of times.
**
**	repeat <varname:i> [from <number>] [to <number>] [count <number>] [step <number>] <template>
*/
Expr::register('_repeat', function ($parts, $data)
{
	if ($parts->length < 3 || ($parts->length & 1) != 1)
		return '(`repeat`: Wrong number of parameters)';

	$var_name = Expr::value($parts->get(1), $data);
	$count = null;
	$from = 0; $to = null;
	$step = null;

	for ($i = 2; $i < $parts->length-1; $i+=2)
	{
		$value = Expr::value($parts->get($i), $data);

		switch (Text::toLowerCase($value))
		{
			case 'from':
				$from = (float)Expr::value($parts->get($i+1), $data);
				break;

			case 'to':
				$to = (float)Expr::value($parts->get($i+1), $data);
				break;

			case 'count':
				$count = (float)Expr::value($parts->get($i+1), $data);
				break;

			case 'step':
				$step = (float)Expr::value($parts->get($i+1), $data);
				break;
		}
	}

	$tpl = $parts->get($parts->length-1);
	$arr = new Arry();

	if ($to !== null)
	{
		if ($step === null)
			$step = $from > $to ? -1 : 1;

		if ($step < 0)
		{
			for ($i = $from; $i >= $to; $i += $step)
			{
				try {
					$data->set($var_name, $i);
					$arr->push(Expr::value($tpl, $data));
				}
				catch (\Exception $e) {
					$name = $e->getMessage();
					if ($name == 'EXC_BREAK') break;
					if ($name == 'EXC_CONTINUE') continue;
					throw $e;
				}
			}
		}
		else
		{
			for ($i = $from; $i <= $to; $i += $step)
			{
				try {
					$data->set($var_name, $i);
					$arr->push(Expr::value($tpl, $data));
				}
				catch (\Exception $e) {
					$name = $e->getMessage();
					if ($name == 'EXC_BREAK') break;
					if ($name == 'EXC_CONTINUE') continue;
					throw $e;
				}
			}
		}
	}
	else if ($count !== null)
	{
		if ($step === null)
			$step = 1;

		for ($i = $from; $count > 0; $count--, $i += $step)
		{
			try {
				$data->set($var_name, $i);
				$arr->push(Expr::value($tpl, $data));
			}
			catch (\Exception $e) {
				$name = $e->getMessage();
				if ($name == 'EXC_BREAK') break;
				if ($name == 'EXC_CONTINUE') continue;
				throw $e;
			}
		}
	}
	else
	{
		if ($step === null)
			$step = 1;

		for ($i = $from; ; $i += $step)
		{
			try {
				$data->set($var_name, $i);
				$arr->push(Expr::value($tpl, $data));
			}
			catch (\Exception $e) {
				$name = $e->getMessage();
				if ($name == 'EXC_BREAK') break;
				if ($name == 'EXC_CONTINUE') continue;
				throw $e;
			}
		}
	}

	$data->remove($var_name);
	return $arr;
});

/**
**	Repeats the specified template for a number of times.
**
**	for <varname:i> [from <number>] [to <number>] [count <number>] [step <number>] <template>
*/
Expr::register('_for', function ($parts, $data)
{
	if ($parts->length < 3 || ($parts->length & 1) != 1)
		return '(`for`: Wrong number of parameters)';

	$var_name = Expr::value($parts->get(1), $data);
	$count = null;
	$from = 0; $to = null;
	$step = null;

	for ($i = 2; $i < $parts->length-1; $i+=2)
	{
		$value = Expr::value($parts->get($i), $data);

		switch (Text::toLowerCase($value))
		{
			case 'from':
				$from = (float)Expr::value($parts->get($i+1), $data);
				break;

			case 'to':
				$to = (float)Expr::value($parts->get($i+1), $data);
				break;

			case 'count':
				$count = (float)Expr::value($parts->get($i+1), $data);
				break;

			case 'step':
				$step = (float)Expr::value($parts->get($i+1), $data);
				break;
		}
	}

	$tpl = $parts->get($parts->length-1);

	if ($to !== null)
	{
		if ($step === null)
			$step = $from > $to ? -1 : 1;

		if ($step < 0)
		{
			for ($i = $from; $i >= $to; $i += $step)
			{
				try {
					$data->set($var_name, $i);
					Expr::value($tpl, $data);
				}
				catch (\Exception $e) {
					$name = $e->getMessage();
					if ($name == 'EXC_BREAK') break;
					if ($name == 'EXC_CONTINUE') continue;
					throw $e;
				}
			}
		}
		else
		{
			for ($i = $from; $i <= $to; $i += $step)
			{
				try {
					$data->set($var_name, $i);
					Expr::value($tpl, $data);
				}
				catch (\Exception $e) {
					$name = $e->getMessage();
					if ($name == 'EXC_BREAK') break;
					if ($name == 'EXC_CONTINUE') continue;
					throw $e;
				}
			}
		}
	}
	else if ($count !== null)
	{
		if ($step === null)
			$step = 1;

		for ($i = $from; $count > 0; $count--, $i += $step)
		{
			try {
				$data->set($var_name, $i);
				Expr::value($tpl, $data);
			}
			catch (\Exception $e) {
				$name = $e->getMessage();
				if ($name == 'EXC_BREAK') break;
				if ($name == 'EXC_CONTINUE') continue;
				throw $e;
			}
		}
	}
	else
	{
		if ($step === null)
			$step = 1;

		for ($i = $from; ; $i += $step)
		{
			try {
				$data->set($var_name, $i);
				Expr::value($tpl, $data);
			}
			catch (\Exception $e) {
				$name = $e->getMessage();
				if ($name == 'EXC_BREAK') break;
				if ($name == 'EXC_CONTINUE') continue;
				throw $e;
			}
		}
	}

	$data->remove($var_name);
	return null;
});

/**
**	Repeats the specified block infinitely until a "break" is found.
**
**	loop <block>
*/
Expr::register('_loop', function ($parts, $data)
{
	if ($parts->length < 2)
		return '(`loop`: Wrong number of parameters)';

	$block = $parts->slice(1);

	while (true)
	{
		try {
			Expr::blockValue($block, $data);
		}
		catch (\Exception $e) {
			$name = $e->getMessage();
			if ($name == 'EXC_BREAK') break;
			if ($name == 'EXC_CONTINUE') continue;
			throw $e;
		}
	}

	return null;
});

/**
**	Writes the raw data to the output.
**
**	expr_debug <expr>
*/
Expr::register('_expr_debug', function ($parts, $data)
{
	echo $parts->get(1);
	return null;
});

/**
**	Writes the specified arguments to the console.
**
**	echo <expr> [<expr>...]
*/
Expr::register('_echo', function ($parts, $data)
{
	for ($i = 1; $i < $parts->length(); $i++)
		echo(Expr::expand($parts->get($i), $data, 'arg')."\n");

	return '';
});

/**
**	Constructs a list from the given arguments and returns it.
**
**	# <expr> [<expr>...]
*/
Expr::register('_#', function ($parts, $data)
{
	$s = new Arry();

	for ($i = 1; $i < $parts->length(); $i++)
		$s->push(Expr::expand($parts->get($i), $data, 'arg'));

	return $s;
});

/**
**	Constructs a non-expanded list from the given arguments and returns it.
**
**	## <expr> [<expr>...]
*/
Expr::register('_##', function ($parts, $data)
{
	$s = new Arry();

	for ($i = 1; $i < $parts->length(); $i++)
		$s->push($parts->get($i));

	return $s;
});

/**
**	Constructs an associative array (dictionary) and returns it.
**
**	& <name>: <expr> [<name>: <expr>...]
**	& :<name> <expr> [:<name> <expr>...]
*/
Expr::register('_&', function ($parts, $data)
{
	return Expr::getNamedValues ($parts, $data, 1, true);
});

/**
 **	Constructs a non-expanded associative array (dictionary) and returns it.
 **
 **	&& <name>: <expr> [<name>: <expr>...]
 **	&& :<name> <expr> [:<name> <expr>...]
 */
Expr::register('_&&', function ($parts, $data)
{
	return Expr::getNamedValues ($parts, $data, 1, false);
});

/**
**	Returns true if the specified map contains all the specified keys. If it fails the global variable `err` will contain an error message.
**
**	contains <expr> <name> [<name>...]
*/
Expr::register('contains', function ($args, $parts, $data)
{
	$value = $args->get(1);

	if (typeOf($value) != 'Rose\\Map')
	{
		$data->err = 'Argument is not a Map';
		return false;
	}

	$s = '';

	for ($i = 2; $i < $args->length; $i++)
	{
		if (!$value->has($args->get($i)))
			$s .= ', '.$args->get($i);
	}

	if ($s != '')
	{
		$data->err = Text::substring($s, 1);
		return false;
	}

	return true;
});

/**
**	Returns true if a map has some key, or if a list has some value. Returns boolean.
**
**	has <name> <map-expr>
**	has <value> <list-expr>
*/
Expr::register('has', function ($args, $parts, $data)
{
	$value = $args->get(2);

	if (typeOf($value) == 'Rose\\Map')
		return $value->has($args->get(1));

	if (typeOf($value) == 'Rose\\Arry')
		return $value->indexOf($args->get(1)) !== null;

	return false;
});

/**
**	Returns a new array/map contaning the transformed values of the array/map (evaluating the block). And just as in 'each', the i# and i## variables be available.
**
**	map <varname> <list-expr> <block>
*/
Expr::register('_map', function ($parts, $data)
{
	$var_name = Expr::expand($parts->get(1), $data, 'arg');

	$list = Expr::expand($parts->get(2), $data, 'arg');
	if (!$list) return $list;

	$arrayMode = typeOf($list) == 'Rose\\Arry' ? true : false;
	$output = $arrayMode ? new Arry() : new Map();
	$j = 0;

	$block = $parts->slice(3);

	$list->forEach(function($item, $key) use(&$var_name, &$output, &$j, &$arrayMode, &$data, &$block)
	{
		$data->set($var_name, $item);
		$data->set($var_name . '##', $j++);
		$data->set($var_name . '#', $key);

		if ($arrayMode)
			$output->push(Expr::blockValue($block, $data));
		else
			$output->set($key, Expr::blockValue($block, $data));
	});

	$data->remove($var_name);
	$data->remove($var_name . '##');
	$data->remove($var_name . '#');

	return $output;
});

/**
**	Returns a new array/map contaning the elements where the block evaluates to non-zero. Just as in 'each', the i# and i## variables be available.
**
**	filter <varname> <list-expr> <block>
*/
Expr::register('_filter', function ($parts, $data)
{
	$var_name = Expr::expand($parts->get(1), $data, 'arg');

	$list = Expr::expand($parts->get(2), $data, 'arg');
	if (!$list) return $list;

	$arrayMode = typeOf($list) == 'Rose\\Arry' ? true : false;
	$output = $arrayMode ? new Arry() : new Map();
	$j = 0;

	$block = $parts->slice(3);

	$list->forEach(function($item, $key) use(&$var_name, &$output, &$j, &$arrayMode, &$data, &$block)
	{
		$data->set($var_name, $item);
		$data->set($var_name . '##', $j++);
		$data->set($var_name . '#', $key);

		if (!!Expr::blockValue($block, $data))
		{
			if ($arrayMode)
				$output->push($item);
			else
				$output->set($key, $item);
		}
	});

	$data->remove($var_name);
	$data->remove($var_name . '##');
	$data->remove($var_name . '#');

	return $output;
});


/**
**	Expands the specified template string (or already parsed template [array]) with the given data. The sym_open and sym_close will be '{' and '}' respectively.
**	If no data is provided, current data parameter will be used.
**
**	expand <template> <data>
*/
Expr::register('expand', function ($args, $parts, $data)
{
	if (typeOf($args->get(1)) == 'Rose\\Arry')
		return Expr::expand ($args->get(1), $args->length == 3 ? $args->get(2) : $data);
	else
		return Expr::expand (Expr::parseTemplate ($args->get(1), '{', '}'), $args->length == 3 ? $args->get(2) : $data);
});

/**
**	Calls a function described by the given parameter.
**
**	call <function> <args...>
*/
/*Expr::register('_call', function ($parts, $data)
{
	$ref = Expr::expand($parts->get(1), $data, 'varref');
	if (!$ref || typeOf($ref[0]->{$ref[1]}) != 'function')
	{
		echo $ref[0];
		exit;
		throw new Error ('Expression is not a function: ' . Expr::expand($parts->get(1), $data, 'obj')->map(function($i) { return $i == null ? '.' : $i; })->join(''));
	}

	$args = [];

	for ($i = 2; $i < $parts->length; $i++)
		$args[] = Expr::value($parts->get($i), $data);

	return call_user_func_array ($ref[0]->{$ref[1]}, $args);
});*/

/**
**	Try-catch block support.
**
**	try <block> [catch <block>] [finally <block>]
*/
Expr::register('_try', function ($parts, $data)
{
	$i = 2;

	$_catch = 0;
	$_finally = 0;

	if ($parts->length > 2)
	{
		switch (Expr::value($parts->get(2), $data))
		{
			case 'catch':
				$_catch = 3;

				if ($parts->length > 4 && Expr::value($parts->get(4), $data) == 'finally')
					$_finally = 5;

				break;

			case 'finally':
				$_finally = 3;
				break;
		}
	}

	try {
		Expr::value($parts->get(1), $data);
	}
	catch (\Exception $e) {
		$data->err = $e->getMessage();

		if ($_catch)
			Expr::value($parts->get($_catch), $data);
	}

	if ($_finally) Expr::value($parts->get($_finally), $data);

});

/**
**	Throws an error exception. If no parameter is specified, the internal variable 'err' will be used as message.
**
**	throw <expr>
**	throw
*/
Expr::register('throw', function ($args, $parts, $data)
{
	if ($args->length > 1)
		throw new \Exception ($args->get(1));

	throw new \Exception ($data->get('err'));
});

/**
**	Throws an error if the specified condition is not true.
**
**	assert <condition> <message>
*/
Expr::register('_assert', function ($parts, $data)
{
	if (Expr::expand($parts->get(1), $data, 'arg'))
		return null;

	throw new \Exception (Expr::expand($parts->get(2), $data));
});

/**
**	Yields a value to the inner-most expression evaluation loop to force result to be the specified value.
**
**	yield <value>
*/
Expr::register('yield', function($args) {
	throw new MetaError('EXPR_YIELD', $args->get(1));
});

/**
**	Introduces a new temporal variable with the specified value. Returns the value returned by the block.
**
**	with <varname> <value> <block>
*/
Expr::register('_with', function($parts, $data)
{
	$var_name = Expr::expand($parts->get(1), $data, 'arg');

	$old_value_present = false;
	$old_value = null;

	if ($data->has($var_name))
	{
		$old_value_present = true;
		$old_value = $data->{$var_name};
	}

	$data->{$var_name} = Expr::expand($parts->get(2), $data, 'arg');

	$value = Expr::blockValue($parts->slice(3), $data);

	if ($old_value_present)
		$data->{$var_name} = $old_value;
	else
		$data->remove($var_name);

	return $value;
});

/**
**	Returns from a function with the specified value.
**
**	ret [<value>]
*/
Expr::register('ret', function($args) {
	throw new MetaError('FN_RET', $args->has(1) ? $args->get(1) : null);
});

/*
**	Creates a function and returns it.
**
**	fn <param-name>* <block>
*/
Expr::register('_fn', function($parts, $data)
{
	$params = new Arry();

	for ($i = 1; $i < $parts->length; $i++)
	{
		if ($parts->get($i)->get(0)->type != 'identifier')
		{
			$block = $parts->slice($i);

			return function ($args, $parts, $data) use (&$params, &$block)
			{
				if ($args->length-1 < $params->length)
					throw new Error ("Invalid number of parameters, expected ".($params->length)." got ".($args->length-1).".");

				$new = new Map();
				$new->set('global', $data);

				$params->forEach(function ($param, $index) use (&$new, &$args)
				{
					$new->set($param, $args->get($index+1));
				});

				$value = null;

				try {
					$value = Expr::blockValue($block, $new);
				}
				catch (MetaError $e)
				{
					switch ($e->code)
					{
						case 'FN_RET':
							$value = $e->value;
							break;

						default:
							throw $e;
					}
				}

				return $value;
			};
		}

		$params->push(Expr::value($parts->get($i), $data));
	}

	return null;
});

/*
**	Defines a global function. Note that functions are isolated and do not have access to the global scope, however this
**	action defines a local variable named `global` which can be used to access it.
**
**	def-fn <fn-name> <param-name>* <block>
*/
Expr::register('_def-fn', function($parts, $data)
{
	$name = Expr::value($parts->get(1), $data);
	$params = new Arry();

	for ($i = 2; $i < $parts->length; $i++)
	{
		if ($parts->get($i)->get(0)->type != 'identifier')
		{
			$block = $parts->slice($i);

			$fn = function ($args, $parts, $data) use (&$params, &$block, &$name)
			{
				if ($args->length-1 < $params->length)
					throw new Error ("Invalid number of parameters in call of `".$name."`, expected ".($params->length)." got ".($args->length-1).".");

				$new = new Map();
				$new->set('global', $data);

				$params->forEach(function ($param, $index) use (&$new, &$args)
				{
					$new->set($param, $args->get($index+1));
				});

				$value = null;

				try {
					$value = Expr::blockValue($block, $new);
				}
				catch (MetaError $e)
				{
					switch ($e->code)
					{
						case 'FN_RET':
							$value = $e->value;
							break;

						default:
							throw $e;
					}
				}

				return $value;
			};

			break;
		}

		$params->push(Expr::value($parts->get($i), $data));
	}

	if (Text::startsWith($name, '::'))
		Expr::register(Text::substring($name, 2), $fn);
	else if (Text::indexOf($name, '::'))
		Expr::register($name, $fn);
	else
		Expr::register((Expr::$namespace ? (Expr::$namespace . '::') : '') . $name, $fn);

	return null;
});


/*
**	Defines an alias for a function.
**
**	def-alias <fn-name> <fn-expr>
*/
Expr::register('_def-alias', function($parts, $data)
{
	$name = Expr::value($parts->get(1), $data);
	$fn = Expr::value($parts->get(2), $data);

	if (\Rose\typeOf($fn) != 'function')
	{
		$tmp = Expr::$functions->get((string)$fn);
		if (!$tmp)
			throw new Error ('Value for an alias must be a function, undefined: ' . (string)$fn);
		else
			$fn = $tmp;
	}

	if (Text::startsWith($name, '::'))
		Expr::register(Text::substring($name, 2), $fn);
	else if (Text::indexOf($name, '::'))
		Expr::register($name, $fn);
	else
		Expr::register((Expr::$namespace ? (Expr::$namespace . '::') : '') . $name, $fn);

	return null;
});

/*
**	Sets the namespace for any def-* statements.
**
**	ns <str-expr>
*/
Expr::register('ns', function($args, $parts, $data)
{
	Expr::$namespace = $args->get(1);
	return null;
});


/*
**	Imports a file and evaluates it. Any file is imported just once.
**
**	require <str-expr>+
*/
Expr::register('require', function($args, $parts, $data)
{
	for ($i = 1; $i < $args->length; $i++)
	{
		$currentPath = Expr::$currentPath;
		$namespace = Expr::$namespace;

		$path = $args->get($i);

		if (Text::startsWith($path, './'))
			$path = Path::append(Expr::$currentPath, Text::substring($path, 2));
		else if (!Text::startsWith($path, '/'))
			$path = Path::append(Expr::$importPath, $path);

		if (!Text::endsWith($path, '.fn'))
			$path .= '.fn';

		$path = Path::resolve($path);
		$path_cache = null;

		if (Text::startsWith($path, Expr::$importPath))
			$path_cache = Path::append(Expr::$cachePath, Text::replace('/', '-', Text::substring($path, 1+Text::length(Expr::$importPath))));

		if (!Path::exists($path))
			throw new Error ("Source does not exist: " . $path);

		if (Expr::$imported->get($path) == File::mtime($path, true))
			continue;

		Expr::$imported->set($path, File::mtime($path, true));

		if ($path_cache && Path::exists($path_cache) && File::mtime($path_cache, true) == File::mtime($path, true))
		{
			$expr = unserialize(File::getContents($path_cache));
		}
		else
		{
			$expr = Expr::parse(Regex::_replace ('|/\*(.*?)\*/|s', File::getContents($path), ''));

			for ($i = 0; $i < $expr->length; $i++)
			{
				if ($expr->get($i)->type != 'template')
				{
					$expr->remove($i);
					$i--;
				}
			}

			if ($path_cache) {
				File::setContents($path_cache, serialize($expr));
				File::touch($path_cache, File::mtime($path, true));
			}
		}

		Expr::$currentPath = Path::dirname($path);
		Expr::$namespace = '';

		try
		{
			$value = Expr::expand ($expr, $data, 'void');
		}
		catch (MetaError $e)
		{
			switch ($e->code)
			{
				case 'FN_RET':
					$value = $e->value;
					break;

				default:
					throw $e;
			}
		}

		Expr::$currentPath = $currentPath;
		Expr::$namespace = $namespace;
	}

	return $value;
});

/*
**	Creates a new map by zipping the respective keys and values together.
**
**	zipmap key-name+ <arr-expr>
**	zipmap <arr-expr> <arr-expr>
*/
Expr::register('zipmap', function($args, $parts, $data)
{
	$map = new Map();

	if (\Rose\typeOf($args->get(1)) == 'Rose\Arry')
	{
		$keys = $args->get(1);
		$values = $args->get(2);

		$n = Math::min($keys->length, $values->length);

		for ($i = 0; $i < $n; $i++)
			$map->set($keys->get($i), $values->get($i));
	}
	else
	{
		$values = $args->last();

		$n = Math::min($args->length-2, $values->length);

		for ($i = 0; $i < $n; $i++)
			$map->set($args->get($i+1), $values->get($i));
	}

	return $map;
});
