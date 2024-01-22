<?php

namespace Rose;

use Rose\Errors\Error;
use Rose\Errors\MetaError;
use Rose\Data\Connection;
use Rose\Regex;
use Rose\Arry;
use Rose\Map;
use Rose\IO\Path;
use Rose\IO\File;
use Rose\Math;
use Rose\JSON;

/**
 * 	Description of a linked context.
 */
class LinkedContext
{
    public $context;
    public $ns;

    public function __construct ($context, $ns='') {
        $this->context = $context;
        $this->ns = $ns;
    }

    public function getBaseName ($name)
    {
        if (!$this->ns)
            return $name;

        if (Text::startsWith($name, $this->ns . '::'))
            return Text::substring($name, Text::length($this->ns)+2);

        return null;
    }
}

/**
 * 	Encapsulated context.
 */
class Context
{
    static private $contextArray = null;

    private $id;
    public $data;
    public $privateFunctions;
    public $publicFunctions;
    public $exportedFunctions;
    public $chain;

    public $currentNamespace;
    public $currentPath;
    public $currentScope;
    public $defaultValue;

    public static function reset() {
        self::$contextArray = new Arry();
    }

    public static function getContext ($contextId) {
        return self::$contextArray->get($contextId);
    }

    /**
     * 	Constructs a new context.
     */
    public function __construct()
    {
        if (self::$contextArray == null)
            self::reset();

        self::$contextArray->push($this);
        $this->id = self::$contextArray->length()-1;

        $this->data = new Map();
        $this->privateFunctions = new Map();
        $this->publicFunctions = new Map();
        $this->exportedFunctions = new Map();

        $this->chain = new Arry();

        if ($this->id != 0)
            $this->chain->push(new LinkedContext (self::getContext(0)));

        $this->currentNamespace = '';
        $this->currentScope = 'public';
        $this->defaultValue = null;
    }

    /**
     * 	Returns the ID of the context.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * 	Registers or overrides a function (ExprFn object).
     */
    public function registerFunction ($name, $fn, $isPrivate=false, $isExported=false)
    {
        if ($isExported) {
            $this->exportedFunctions->set ($name, $fn);
            return;
        }

        if ($isPrivate)
            $this->privateFunctions->set ($name, $fn);
        else
            $this->publicFunctions->set ($name, $fn);
    }

    /**
     * 	Removes a function from the context.
     */
    public function unregisterFunction ($name)
    {
        if ($this->exportedFunctions->has($name)) {
            $this->exportedFunctions->remove($name);
            return;
        }

        if ($this->privateFunctions->has($name)) {
            $this->privateFunctions->remove($name);
            return;
        }

        if ($this->publicFunctions->has($name)) {
            $this->publicFunctions->remove($name);
            return;
        }
    }

    /**
     * 	Links a context to this context chain.
     */
    public function linkContext ($context, $ns='')
    {
        $n = $this->chain->length();
        for ($i = 0; $i < $n; $i++)
        {
            $linked = $this->chain->get($i);
            if ($linked->ns == $ns && $linked->context === $context)
                return;
        }

        $this->chain->push (new LinkedContext($context, $ns));
    }

    /**
     * 	Returns a function from the context chain given its name, or null if not found.
     */
    public function getFunction ($name)
    {
        if (!isString($name))
            return null;

        $list = $this->publicFunctions;
        if ($list->has($name))
            return $list->get($name);

        $list = $this->privateFunctions;
        if ($list->has($name))
            return $list->get($name);

        // violet: check exported functions?
    
        $n = $this->chain->length();
        for ($i = 0; $i < $n; $i++)
        {
            $linked = $this->chain->get($i);

            $tmp = $linked->getBaseName($name);
            if (!$tmp) continue;

            $list = $linked->context->publicFunctions;
            if ($list->has($tmp))
                return $list->get($tmp);

            $list = $linked->context->exportedFunctions;
            if ($list->has($tmp))
                return $list->get($tmp);
        }

        return null;
    }

    /**
     * 	Returns boolean indicating whether the specified function exists in the context chain.
     */
    public function hasFunction ($name) {
        return $this->getFunction($name) !== null;
    }

    /**
     * 	Sets a function in the exported context.
     */
    public function setFunction ($name, $fn) {
        $this->exportedFunctions->set ($name, $fn);
    }

    /**
     * 	Removes a function from the exported context.
     */
    public function deleteFunction ($name, $fn) {
        $this->exportedFunctions->remove ($name);
    }
};


/**
 * 	Expression function.
 */
class ExprFn
{
    private $context;
    private $fn;
    public $name;
    public $isRoot;

    public function __construct ($name, $fn, $context, $isRoot=false)
    {
        $this->name = $name;
        $this->fn = $fn;
        $this->context = $context;
        $this->isRoot = $isRoot;
    }

    public function getContext() {
        return $this->context;
    }

    public function exec3 ($args, $parts, $data) {
        return ($this->fn) ($args, $parts, $data);
    }

    public function exec2 ($parts, $data) {
        return ($this->fn) ($parts, $data);
    }
};


/**
**	Expression compiler and evaluator. Adapted from (Rin)[https://github.com/rsthn/rin/] templating module.
*/

class Expr
{
    /**
     * 	Constant symbols.
     */
    public const SYM_UNDERSCORE = '_';

    /*
    **	Strict mode flag. When set, any undefined expression function will trigger an error.
    */
    static public $strict = true;

    /*
    **	File modification time and related context of imported files.
    */
    static public $importedTime = null;
    static public $importedContext = null;

    /*
    **	Base path for imported sources.
    */
    static public $importPath;

    /*
    **	Cache path.
    */
    static public $cachePath = 'volatile/expr';

    /**
     * 	Current context.
     */
    static public $context;

    /*
    **	Context stack.
    */
    static public $contextStack;

    /**
     * 	Returns true if the specified value is a special type.
     */
    static public function isSpecialType ($value)
    {
        return $value === null || $value === true || $value === false || $value === self::SYM_UNDERSCORE;
    }

    /**
     * 	Attempts to access an index/key of a given data.
     */
    static public function accessGet ($data, $index)
    {
        if (\Rose\isString($data))
            return $data[$index];

        return $data->{$index};
    }

    /**
     * 	Attempts to set a value in the specified data given the index/key.
     */
    static public function accessSet (&$data, $index, $value)
    {
        if (\Rose\isString($data))
            throw new Error('writing to strings directly using an index is not allowed');

        return $data->{$index} = $value;
    }

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

        for ($i = 0; $i < Text::length($value); $i++)
        {
            if ($value[$i] == '\\')
            {
                $r = $value[$i+1];
                $n = 2;

                switch ($r)
                {
                    case 'n': $r = "\n"; break;
                    case 'r': $r = "\r"; break;
                    case 'f': $r = "\f"; break;
                    case 'v': $r = "\v"; break;
                    case 't': $r = "\t"; break;
                    case 'e': $r = "\x1B"; break;
                    case '"': $r = "\""; break;
                    case "'": $r = "\'"; break;
                    case "(": $r = "("; break;
                    case ")": $r = ")"; break;
                    case "{": $r = "{"; break;
                    case "}": $r = "}"; break;
                    case "\\": $r = "\\"; break;
                    case 'x': $r = chr(hexdec(Text::substring($value, $i+2, 2))); $n = 4; break;
                    default: continue 2;
                }

                $value = Text::substring($value, 0, $i) . $r . Text::substring($value, $i+$n);
            }
        }

        return $value;
    }

    /**
    **	Parses a template and returns the compiled 'parts' structure to be used by the 'expand' method.
    **
    **	>> array parseTemplate (string template, char sym_open, char sym_close, bool is_tpl=false, bool remove_comments=true);
    */
    static public function parseTemplate ($template, $sym_open, $sym_close, $is_tpl=false, $root=1, $remove_comments=true)
    {
        $nflush = 'string'; $flush = null; $state = 0; $count = 0;
        $str = ''; $parts = new Arry(); $mparts = $parts; $nparts = false;

        if ($is_tpl === true)
        {
            $template = Text::trim($template);
            $nflush = 'identifier';
            $state = 10;

            $mparts->push($parts = new Arry());
        }

        $template .= "\0";

        $emit = function ($type, $data) use(&$parts, &$nparts, &$mparts, $sym_open, $sym_close, &$remove_comments)
        {
            if ($type == 'template')
            {
                $data = Expr::parseTemplate ($data, $sym_open, $sym_close, true, 0, $remove_comments);
            }
            else if ($type == 'parse')
            {
                $data = Expr::parseTemplate ($data, $sym_open, $sym_close, false, 0, $remove_comments);
                $type = 'base-string';

                if (typeOf($data) == 'Rose\\Arry')
                {
                    $type = $data->get(0)->type;
                    $data = $data->get(0)->data;
                }
            }
            else if ($type == 'parse-trim-merge')
            {
                $data = Expr::parseTemplate (Text::split ("\n", Text::trim($data))->map(function($i) { return Text::trim($i); })->join("\n"), $sym_open, $sym_close, false, 0, $remove_comments);
            }
            else if ($type == 'parse-merge')
            {
                $data = Expr::parseTemplate ($data, $sym_open, $sym_close, false, 0, $remove_comments);
            }
            else if ($type == 'parse-merge-alt')
            {
                $data = Expr::parseTemplate ($data, '{', '}', false, 0, $remove_comments);
            }
            else if ($type == 'integer')
            {
                $data = (int)$data;
            }
            else if ($type == 'number')
            {
                $data = (float)$data;
            }
            else if ($type == 'identifier')
            {
                switch ($data)
                {
                    case 'null': $data = null; break;
                    case 'true': $data = true; break;
                    case 'false': $data = false; break;
                    case '_': $data = self::SYM_UNDERSCORE; break;
                }
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

        for ($i = 0; $i < Text::length($template); $i++)
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
                    else if ($template[$i] == ';' && $remove_comments && Text::trim($str) == '')
                    {
                        $state = 20; $count = 1;
                        $flush = 'string';
                        $nflush = 'string';
                    }
                    else
                    {
                        $str .= $template[$i];
                    }

                    break;
    
                case 1:
                    if ($template[$i] == "\0")
                    {
                        throw new Error ("parse error: unexpected end of template");
                    }

                    if ($template[$i] == $sym_close)
                    {
                        $count--;
    
                        if ($count < 0)
                            throw new Error ("parse error: unmatched " + $sym_close);

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
                    else if ($template[$i] == '`')
                    {
                        $state = 19;
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
                    else if (Regex::_matches('/^(([-+][0-9])|([0-9]))/', $template[$i].$template[$i+1]) && $str == '')
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

                        $keep = true;
                        $n = Text::length($template)-1;

                        while ($keep && $i < $n)
                        {
                            while ($i < $n && Regex::_matches('/[\t\n\r\f\v ]/', $template[$i])) $i++;

                            if ($template[$i] == ';') {
                                while ($i < $n && $template[$i] != "\n") $i++;
                            }
                            else
                                $keep = false;
                        }

                        if ($template[$i] == "\0")
                        {
                            $nflush = '';
                            $nparts = false;
                        }

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
                    else if ($template[$i] == ';' && $remove_comments)
                    {
                        $state = 21;
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
                        throw new Error ("parse error: unexpected end of template");
    
                    if ($template[$i] == $sym_close)
                    {
                        $count--;
    
                        if ($count < 0)
                            throw new Error ("parse error: unmatched " + $sym_close);

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
                        throw new Error ("parse error: unexpected end of template");
    
                    if ($template[$i] == $sym_close)
                    {
                        $count--;
    
                        if ($count < 0)
                            throw new Error ("parse error: unmatched " + $sym_close);

                        if ($count == 0)
                        {
                            if (Text::length($str) != 0)
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
                        throw new Error ("parse error: unexpected end of template");

                    if ($template[$i] == $sym_close)
                    {
                        $count--;
    
                        if ($count < 0)
                            throw new Error ("parse error: unmatched " + $sym_close);

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
                        throw new Error ("parse error: unexpected end of template");
    
                    if ($template[$i] == '"')
                    {
                        $count--;
    
                        if ($count < 0)
                            throw new Error ("parse error: unmatched " + '"');

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
                        throw new Error ("parse error: unexpected end of template");

                    if ($template[$i] == '\'')
                    {
                        $count--;

                        if ($count < 0)
                            throw new Error ("parse error: unmatched " + '\'');

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
                        throw new Error ("parse error: unexpected end of template");
    
                    if ($template[$i] == '`')
                    {
                        $count--;
    
                        if ($count < 0)
                            throw new Error ("parse error: unmatched " + '`');

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

                case 19:
                    if ($template[$i] == "\0")
                        throw new Error ("parse error: unexpected end of template");
    
                    if ($template[$i] == '`')
                        $state = 1;

                    $str .= $template[$i];
                    break;

                case 20:
                    if ($template[$i] == "\0" || $template[$i] == "\n")
                    {
                        $str = ';' . $str;
                        $state = 0;
                        $flush = $nflush;
                    }
                    else
                        $str .= $template[$i];

                    break;

                case 21:
                    if ($template[$i] == "\0" || $template[$i] == "\n")
                    {
                        $state = 10; $i--;
                        break;
                    }

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
                $s = JSON::parse($s);
                echo JSON::prettify($s);
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
    static public function parse ($template, $remove_comments=true)
    {
        return Expr::parseTemplate(Expr::clean($template), '(', ')', false, $remove_comments);
    }

    /**
    **	Removes all static parts from a parsed template, or removes comments from a given string.
    **
    **	>> array clean (array parts);
    */
    static public function clean ($parts)
    {
        if (\Rose\typeOf($parts) == 'primitive')
        {
            $data = (string)$parts;
            $data = Regex::_replace ('|/\*(.*?)\*/|s', '', $data);
            return $data;
        }

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
    **	>> string/array expand (array parts, object data, string ret='text', string mode='base-string');
    */
    static public function expand ($parts, $data, $ret='text', $mode='base-string')
    {
        $s = new Arry();

        // Expand variable parts.
        if ($mode == 'var')
        {
            $modifier1 = false;

            $root = $data;
            $last = null;
            $first = true;
            $str = '';

            for ($i = 0; $i < $parts->length() && $data != null; $i++)
            {
                switch ($parts->get($i)->type)
                {
                    case 'identifier':
                        if (self::isSpecialType($parts->get($i)->data))
                        {
                            if ($parts->get($i)->data !== self::SYM_UNDERSCORE)
                                return $parts->get($i)->data;

                            $last = self::$context->defaultValue;
                            break;
                        }

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
                            if ($str == '') $str = 'this';

                            while (true)
                            {
                                if ($str[0] == '!')
                                {
                                    $str = Text::substring($str, 1);
                                    $modifier1 = true;
                                }
                                else
                                    break;
                            }

                            if ($str != 'this' && $data != null)
                            {
                                $tmp = $data;
                                $data = self::accessGet($data, $str);

                                if ($data === null && $first) {
                                    $fn = Expr::getFunction($str);
                                    if ($fn) $data = $fn->exec3 (new Arry([$str]), null, $tmp);
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
                    $str = Text::substring($str, 1);
                    $modifier1 = true;
                }
                else
                    break;
            }

            if ($str != '' && $str != 'this')
            {
                $failed = false;

                if ($data != null)
                {
                    if (typeOf($data) == 'Rose\\Arry' || typeOf($data) == 'Rose\\Map')
                    {
                        if (!$data->has($str)) {
                            $failed = true;
                            $data = null;
                        }
                        else {
                            $data = self::accessGet($data, $str);
                        }
                    }
                    else {
                        $data = self::accessGet($data, $str);
                    }
                }
                else
                    $failed = true;

                if ($failed && $parts->length == 1)
                {
                    $fn = Expr::getFunction($str);
                    if ($fn !== null) {
                        $data = $fn->exec3 (new Arry([$str]), null, $root);
                    }
                    else {
                        if (Expr::$strict == true)
                            throw new Error ('function `'.$str.'` not found');
                    }
                }
            }

            if ($modifier1)
                $data = Resources::getInstance()->Database->escapeExt($data);

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
                        if (self::isSpecialType($parts->get($i)->data))
                        {
                            if ($parts->get($i)->data === self::SYM_UNDERSCORE)
                            {
                                $last = self::$context->defaultValue;
                                break;
                            }
                        }

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
                            if ($str == '') $str = 'this';

                            while (true)
                            {
                                if ($str[0] == '!')
                                {
                                    $str = Text::substring($str, 1);
                                }
                                else
                                    break;
                            }

                            if ($str != 'this' && $data != null)
                            {
                                $tmp = $data;
                                $data = self::accessGet($data, $str);

                                if ($data === null && $first) {
                                    $fn = Expr::getFunction($str);
                                    if ($fn !== null) $data = $fn->exec3 (new Arry([$str]), null, $tmp);
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
                    $str = Text::substring($str, 1);
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

            if (Expr::hasFunction('_'.$args->get(0)))
                $args->set(0, '_'.$args->get(0));

            if (!(Expr::hasFunction($args->get(0))))
            {
                if (Expr::$strict == true)
                    throw new Error ('function `'.$args->get(0).'` not found');

                return '(Unknown: '.$args->get(0).')';
            }

            if ($args->get(0)[0] == '_')
                return Expr::getFunction($args->get(0))->exec2 ($parts, $data);

            for ($i = 1; $i < $parts->length(); $i++)
                $args->push (Expr::expand($parts->get($i), $data, 'arg', 'base-string'));

            $s->push (Expr::getFunction($args->get(0))->exec3 ($args, $parts, $data));
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
                        case 'number':
                        case 'integer':
                            return $parts->get(0)->get(0)->data;

                        case 'identifier':
                            $name = $parts->get(0)->get(0)->data;

                            if (self::isSpecialType($name))
                            {
                                if ($name === self::SYM_UNDERSCORE)
                                    return self::$context->defaultValue;
                            }

                            if (Expr::hasFunction($name) || Expr::hasFunction('_'.$name))
                                return Expr::expand($parts, $data, $ret, 'fn');

                            break;
                    }
                }

                return Expr::expand($parts->get(0), $data, $ret, 'var');
            }

            return Expr::expand($parts, $data, $ret, 'fn');
        }

        // Expand parts.
        if ($mode == 'base-string')
        {
            try
            {
                $parts->forEach(function($i, $index, $list) use (&$s, &$data, &$ret)
                {
                    try
                    {
                        switch ($i->type)
                        {
                            case 'template':
                                $tmp = Expr::expand($i->data, $data, $ret, 'template');
                                break;

                            case 'identifier':
                                if (self::isSpecialType($i->data))
                                {
                                    if ($i->data === self::SYM_UNDERSCORE)
                                    {
                                        $tmp = self::$context->defaultValue;
                                        break;
                                    }
                                }

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
                        if (!$e->isForMe()) throw $e;

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
                if (!$e->isForMe()) throw $e;

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

        // Return as argument ('object' if only one, or `string` if more than one), that is, the first item in the result.
        if ($ret === 'arg')
        {
            if (typeOf($s) == 'Rose\\Arry')
            {
                if ($s->length() != 1)
                {
                    $f = function($e) use(&$f) {
                        return $e != null && \Rose\typeOf($e) == 'Rose\\Arry' ? $e->map($f)->join('') : (string)$e;
                    };
        
                    return $s->map($f)->join('');
                }

                return $s->get(0);
            }

            return $s;
        }

        if ($ret === 'text' && typeOf($s) === 'Rose\\Arry' && $s->length == 1 && ($s->get(0) === false || $s->get(0) === true))
        {
            return $s->get(0) ? '1' : '0';
        }

        // Text mode causes the final slices to be joined in a single string.
        if ($ret === 'text' && typeOf($s) === 'Rose\\Arry')
        {
            $f = function($e) use(&$f) {
                return $e != null && \Rose\typeOf($e) == 'Rose\\Arry' ? $e->map($f)->join('') : (string)$e;
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
     * 	Expands the template as 'arg' and returns the result.
     */
    public static function value ($parts, $data=null)
    {
        return typeOf($parts) != 'Rose\\Arry' ? $parts : Expr::expand($parts, $data ? $data : new Map(), 'arg');
    }

    /**
     * 	Registers an expression function in the current context.
     */
    public static function register ($name, $fn)
    {
        self::$context->registerFunction ($name, new ExprFn ($name, $fn, self::$context, true));
    }

    /**
     * 	Returns true if the specified function exists in the current context.
     */
    public static function hasFunction ($name) {
        return self::$context->hasFunction($name);
    }

    /**
     * 	Returns an ExprFn from the current context or null if not found.
     */
    public static function getFunction ($name) {
        return self::$context->getFunction($name);
    }

    /**
    **	Calls an expression function.
    **
    **	>> object call (string name, object args, object data);
    */
    public static function call ($name, $args=null, $data=null)
    {
        if (!$args) $args = new Arry();

        $fn = Expr::getFunction($name);
        if ($fn !== null)
            return $fn->exec3 ($args, null, $data);

        return null;
    }

    /*
    **	Returns a map given a 'parts' array having values of the form "name value", "name: value" or ":name value".
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
                if ($key[0] == ':') $mode = 1; else $mode = Text::substring($key, -1) == ':' ? 2 : 3;
            }

            if ($mode == 1)
                $key = Text::substring($key, 1);
            else if ($mode == 2)
                $key = Text::substring($key, 0, Text::length($key)-1);

            if ($expanded)
                $s->set((string)$key, Expr::expand($parts->get($i+1), $data, 'arg'));
            else
                $s->set((string)$key, $parts->get($i+1));
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
    **	Applies a function to a given input. The input can be a single-value, a sequence, an array or a map.
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

        MetaError::incBaseLevel();

        try
        {
            for ($i = 0; $i < $parts->length; $i++)
                $value = Expr::expand($parts->get($i), $data, 'arg', 'base-string');
        }
        catch (MetaError $e)
        {
            if (!$e->isForMe(-1)) throw $e;

            switch ($e->code)
            {
                case 'EXPR_YIELD':
                    $value = $e->value;
                    break;

                default:
                    throw $e;
            }
        }
        finally
        {
            MetaError::decBaseLevel();
        }

        return $value;
    }

    /**
     * Attempts to get a pure "identifier" from parts[index], when valid it stores the value in `output` and increases the given index `i`.
     * @return boolean
     */
    public static function takeIdentifier ($parts, $data, &$index, &$output)
    {
        if ($parts->get($index)->length == 1 && $parts->get($index)->get(0)->type == 'identifier')
        {
            $value = $parts->get($index)->get(0)->data;

            if (self::isSpecialType($value))
                return false;

            $output = Expr::expand($parts->get($index), $data, 'arg');
            $index++;

            return true;
        }

        return false;
    }

    /**
     * Verifies if the value at parts[index] is an identifier with the given identifcal value.
     * @return boolean
     */
    public static function isIdentifier ($parts, $index, $value)
    {
        if ($parts->get($index)->length == 1 && $parts->get($index)->get(0)->type == 'identifier')
            return $parts->get($index)->get(0)->data === $value;

        return false;
    }

    /**
     * Returns the name of the iterator, key and index variables.
     */
    public static function getIteratorName ($parts, $data, &$index, &$var_name, &$key_name, &$index_name, $def_name='i')
    {
        $var_name = $def_name;
        Expr::takeIdentifier($parts, $data, $index, $var_name);

        if (Text::endsWith($var_name, ':')) {
            $key_name = Text::substring($var_name, 0, -1);
            if (!Expr::takeIdentifier($parts, $data, $index, $var_name))
                throw new Error ('identifier expected for iterator `val` variable');
        }
        else if (Text::indexOf($var_name, ':') !== false) {
            $key_name = Text::substring($var_name, 0, Text::indexOf($var_name, ':'));
            $var_name = Text::substring($var_name, Text::indexOf($var_name, ':')+1);
        }
        else {
            $key_name = $var_name.'#';
        }
        $index_name = $key_name.'#';
    }
};


/*
**	Initialize class constants.
*/

Expr::$contextStack = new Arry();

Expr::$context = new Context();
Expr::$context->currentPath = Path::resolve(Main::$CORE_DIR);

Expr::$importedTime = new Map();
Expr::$importedContext = new Map();
Expr::$importPath = Path::resolve(Main::$CORE_DIR);


/**
**	Expression functions.
*/
global $_glb_object;
$_glb_object = new Map();

Expr::register('global', function($args) { global $_glb_object; return $_glb_object; });

Expr::register('len', function($args) { $s = $args->get(1); return \Rose\typeOf($s) == 'primitive' ? Text::length((string)$s) : $s->length; });
Expr::register('strlen', function($args) { return Text::length((string)$args->get(1), 'utf8'); });
Expr::register('int', function($args) { return (int)$args->get(1); });
Expr::register('bool', function($args) { return \Rose\bool($args->get(1)); });
// TODO: 'str' should be just a converter, and concat the new 'str'
Expr::register('str', function($args) { $s = ''; for ($i = 1; $i < $args->length; $i++) $s .= (string)$args->get($i); return $s; });
Expr::register('concat', function($args) { $s = ''; for ($i = 1; $i < $args->length; $i++) $s .= (string)$args->get($i); return $s; });
Expr::register('float', function($args) { return (float)$args->get(1); });
Expr::register('chr', function($args) { return chr($args->get(1)); });
Expr::register('ord', function($args) { return ord($args->get(1)); });

Expr::register('bit-not', function($args) { return ~$args->get(1); });
Expr::register('bit-and', function($args) { return $args->get(1) & $args->get(2); });
Expr::register('bit-or', function($args) { return $args->get(1) | $args->get(2); });
Expr::register('bit-xor', function($args) { return $args->get(1) ^ $args->get(2); });

Expr::register('not', function($args) { return !$args->get(1); });
Expr::register('neg', function($args) { return -$args->get(1); });

Expr::register('_and', function($parts, $data) { for ($i = 1; $i < $parts->length(); $i++) { $v = Expr::value($parts->get($i), $data); if (!$v) return $v; } return $v; });
Expr::register('_or', function($parts, $data) { for ($i = 1; $i < $parts->length(); $i++) { $v = Expr::value($parts->get($i), $data); if (!!$v) return $v; } return $v; });
Expr::register('_coalesce', function($parts, $data) { for ($i = 1; $i < $parts->length(); $i++) { $v = Expr::value($parts->get($i), $data); if ($v !== null) return $v; } return null; });
Expr::register('_??', function($parts, $data) { for ($i = 1; $i < $parts->length(); $i++) { $v = Expr::value($parts->get($i), $data); if ($v !== null) return $v; } return null; });

Expr::register('eq', function($args) { return $args->get(1) == $args->get(2); });
Expr::register('eqq', function($args) { return $args->get(1) === $args->get(2); });
Expr::register('ne', function($args) { return $args->get(1) != $args->get(2); });
Expr::register('lt', function($args) { return $args->get(1) < $args->get(2); });
Expr::register('le', function($args) { return $args->get(1) <= $args->get(2); });
Expr::register('gt', function($args) { return $args->get(1) > $args->get(2); });
Expr::register('ge', function($args) { return $args->get(1) >= $args->get(2); });
Expr::register('isnotnull', function($args) { return $args->get(1) !== null; });
Expr::register('isnull', function($args) { return $args->get(1) === null; });
Expr::register('iszero', function($args) { return (float)$args->get(1) == 0; });

Expr::register('eq?', function($args) { return $args->get(1) == $args->get(2); });
Expr::register('eqq?', function($args) { return $args->get(1) === $args->get(2); });
Expr::register('ne?', function($args) { return $args->get(1) != $args->get(2); });
Expr::register('lt?', function($args) { return $args->get(1) < $args->get(2); });
Expr::register('le?', function($args) { return $args->get(1) <= $args->get(2); });
Expr::register('gt?', function($args) { return $args->get(1) > $args->get(2); });
Expr::register('ge?', function($args) { return $args->get(1) >= $args->get(2); });
Expr::register('not-null?', function($args) { return $args->get(1) !== null; });
Expr::register('null?', function($args) { return $args->get(1) === null; });
Expr::register('zero?', function($args) { return (float)$args->get(1) == 0; });
Expr::register('even?', function($args) { return ((int)$args->get(1) & 1) == 0; });
Expr::register('odd?', function($args) { return ((int)$args->get(1) & 1) == 1; });

Expr::register('int?', function($args) { return typeOf($args->get(1), true) === 'int'; });
Expr::register('str?', function($args) { return typeOf($args->get(1), true) === 'string'; });
Expr::register('bool?', function($args) { return typeOf($args->get(1), true) === 'bool'; });
Expr::register('float?', function($args) { return typeOf($args->get(1), true) === 'number'; });
Expr::register('array?', function($args) { return typeOf($args->get(1), true) === 'Rose\\Arry'; });
Expr::register('object?', function($args) { return typeOf($args->get(1), true) === 'Rose\\Map'; });
Expr::register('map?', function($args) { return typeOf($args->get(1), true) === 'Rose\\Map'; });
Expr::register('fn?', function($args) { return typeOf($args->get(1), true) === 'function'; });

Expr::register('starts-with', function($args) { return Text::startsWith($args->get(2), $args->get(1)); });
Expr::register('ends-with', function($args) { return Text::endsWith($args->get(2), $args->get(1)); });

Expr::register('typeof', function($args)
{
    $type = typeOf($args->get(1), true);

    if ($type == 'Rose\\Arry') $type = 'array';
    if ($type == 'Rose\\Map') $type = 'object';

    if (Text::substring($type, 0, 5) == 'Rose\\')
        $type = Text::toLowerCase(Text::substring($type, 5));

    return $type;
});

Expr::register('*', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum*$value; }); });
Expr::register('/', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum/$value; }); });
Expr::register('+', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum+$value; }); });
Expr::register('-', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum-$value; }); });
Expr::register('mul', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return (int)($accum*$value); }); });
Expr::register('div', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return (int)($accum/$value); }); });
Expr::register('mod', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return $accum%$value; }); });
Expr::register('pow', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return pow($accum, $value); }); });

Expr::register('min', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return Math::min($accum, $value); }); });
Expr::register('max', function($args) { return Expr::reduce($args->get(1), $args, 2, function($accum, $value) { return Math::max($accum, $value); }); });
Expr::register('abs', function($args) { return Math::abs($args->get(1)); });
Expr::register('round', function($args) { return Math::round($args->get(1)); });
Expr::register('ceil', function($args) { return Math::ceil($args->get(1)); });
Expr::register('floor', function($args) { return Math::floor($args->get(1)); });

/**
 * Check if the specified subject matches any of the given values.
 * in <subject> <values...>
 */
Expr::register('in?', function ($args)
{
    $value = $args->get(1);

    for ($i = 2; $i < $args->length; $i++)
    {
        if ($value == $args->get($i))
            return true;
    }

    return false;
});

/**
**	Does nothing but return null.
**
**	nop <block>
*/
Expr::register('_nop', function ($parts, $data)
{
    return null;
});

/**
**	Returns the JSON representation of the expression.
**
**	dump <expr>
*/
Expr::register('dump', function ($args)
{
    $value = $args->get(1);

    if (\Rose\typeOf($value) === 'primitive')
        return JSON::stringify($value);

    return (string)$value;
});

/**
**	Sets one or more variables in the data context.
**
**	set <varname-expr> <expr> [<varname-expr> <expr>]*
**	set <expr>
*/
Expr::register('_set', function ($parts, $data)
{
    if ($parts->length == 2) {
        Expr::$context->defaultValue = Expr::value($parts->get(1), $data);
        return null;
    }

    for ($i = 1; $i+1 < $parts->length; $i += 2)
    {
        $value = Expr::value($parts->get($i+1), $data);
        if ($parts->get($i)->length > 1)
        {
            $ref = Expr::expand($parts->get($i), $data, 'varref');
            if ($ref != null) {
                if (!$ref[0])
                    throw new Error('unable to assign: ' . $parts->get($i)->map(function($i) { return $i->data; })->join('')  );
                Expr::accessSet($ref[0], $ref[1], $value);
            }
        }
        else {
            $data->set(Expr::value($parts->get($i), $data), Expr::$context->defaultValue = $value);
        }
    }

    return null;
});

/**
**	Increases the value of a variable by the given value (or 1 if none provided).
**
**	inc <varname-expr> [<value>]
*/
Expr::register('_inc', function ($parts, $data)
{
    $ref = Expr::expand($parts->get(1), $data, 'varref');
    if (!$ref) return null;

    if (!$ref[0])
        throw new Error('unable to assign: ' . $parts->get(1)->map(function($i) { return $i->data; })->join(''));

    $value = $parts->has(2) ? Expr::value($parts->get(2), $data) : 1;
    Expr::accessSet($ref[0], $ref[1], Expr::accessGet($ref[0], $ref[1]) + $value);
});

/**
**	Decreases the value of a variable by the given value (or 1 if none provided).
**
**	dec <varname-expr> [<value>]
*/
Expr::register('_dec', function ($parts, $data)
{
    $ref = Expr::expand($parts->get(1), $data, 'varref');
    if (!$ref) return null;

    if (!$ref[0])
        throw new Error('unable to assign: ' . $parts->get(1)->map(function($i) { return $i->data; })->join(''));

    $value = $parts->has(2) ? Expr::value($parts->get(2), $data) : 1;
    Expr::accessSet($ref[0], $ref[1], Expr::accessGet($ref[0], $ref[1]) - $value);
});

/**
**	Appends the value to a variable.
**
**	append <varname-expr> <value> [<value>...]
*/
Expr::register('_append', function ($parts, $data)
{
    $ref = Expr::expand($parts->get(1), $data, 'varref');
    if (!$ref) return null;

    if (!$ref[0])
        throw new Error('unable to assign: ' . $parts->get(1)->map(function($i) { return $i->data; })->join(''));

    for ($i = 2; $i < $parts->length; $i++)
    {
        $value = Expr::value($parts->get($i), $data);
        Expr::accessSet($ref[0], $ref[1], Expr::accessGet($ref[0], $ref[1]) . $value);
    }

    return Expr::accessGet($ref[0], $ref[1]);
});

/**
**	Removes one or more variables from the data context.
**
**	unset <varname-expr> [<varname-expr>]*
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
**	Returns the expression without white-space on the left or right.
**
**	trim <kargs>
*/
Expr::register('trim', function ($args)
{
    return Expr::apply($args->slice(1), function($value) { return Text::trim($value); });
});

/**
**	Returns the expression in uppercase.
**
**	upper <kargs>
*/
Expr::register('upper', function ($args)
{
    return Expr::apply($args->slice(1), function($value) { return Text::toUpperCase($value, 'utf8'); });
});

/**
**	Returns the expression in lower.
**
**	lower <kargs>
*/
Expr::register('lower', function ($args)
{
    return Expr::apply($args->slice(1), function($value) { return Text::toLowerCase($value, 'utf8'); });
});

/**
**	Returns a sub-string of the given string.
**
**	substr <start> <count> <value>
**	substr <start> <value>
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
**	Replaces a matching string with the given replacement string.
**
**	replace <search> <replacement> <kargs>
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
**	Returns the index of a sub-string in the given text. Returns -1 when not found.
**
**	str::indexOf <search> <value>
*/
Expr::register('str::indexOf', function ($args)
{
    $i = Text::indexOf($args->get(2), $args->get(1));
    return $i === false ? -1 : $i;
});

/**
**	Returns the last index of a sub-string in the given text. Returns -1 when not found.
**
**	str::lastIndexOf <search> <value>
*/
Expr::register('str::lastIndexOf', function ($args)
{
    $i = Text::revIndexOf($args->get(2), $args->get(1));
    return $i === false ? -1 : $i;
});

/**
 * Compares two strings and returns 0 if they are equal, -1 if the first is smaller than the second and 1 if
 * the first is greater than the second.
 *
 * str::compare <a> <b>
 */
Expr::register('str::compare', function ($args) {
	return Text::compare($args->get(1), $args->get(2));
});

/**
**	Converts all new-line chars in the expression to <br/>.
**
**	nl2br <kargs>
*/
Expr::register('nl2br', function ($args)
{
    return Expr::apply($args->slice(1), function($value) { return Text::replace("\n", '<br/>', $value); });
});


/**
**	Joins the array into a string. If glue is provided, it will be used as separator.
**
**	join <glue> <array-expr>
**	join <array-expr>
*/
Expr::register('join', function ($args)
{
    $glue = '';
    $list = $args->get(1);

    if ($args->length == 3)
    {
        $glue = $args->get(1);
        $list = $args->get(2);
    }

    return \Rose\typeOf($list) == 'Rose\Arry' ? $list->join($glue) : '';
});

/**
**	Splits the string by the specified delimiter (or empty string if none specified). Returns an array.
**
**	split <delimiter> <str-expr>
**	split <str-expr>
*/
Expr::register('split', function ($args)
{
    if ($args->length == 2)
        return Text::split('', $args->get(1));

    return Text::split($args->get(1), (string)$args->get(2));
});

/**
 * Returns an array with the keys of the object.
 * keys <object-expr>
 */
Expr::register('keys', function ($args)
{
    if ($args->get(1) && typeOf($args->get(1)) == 'Rose\\Map')
        return $args->get(1)->keys();

    return new Arry();
});

/**
 * Returns an array with the values of the object.
 * values <object-expr>
 */
Expr::register('values', function ($args)
{
    if ($args->get(1) && typeOf($args->get(1)) == 'Rose\\Map')
        return $args->get(1)->values();

    return [];
});

/**
 * Executes the specified block for each item in the list.
 * for [<varname>] <array-expr> <block>
 */
Expr::register('_for', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::value($parts->get($i), $data);
    $j = 0;

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $block = $parts->slice($i+1);
    foreach ($list->__nativeArray as $key => $item)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        try {
            Expr::blockValue($block, $data);
        }
        catch (\Throwable $e) {
            $name = $e->getMessage();
            if ($name == 'EXC_BREAK') break;
            if ($name == 'EXC_CONTINUE') continue;
            throw $e;
        }
    }

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $list;
});


/**
 * Returns `valueA` if the expression is `true` otherwise returns `valueB` or empty string if valueB was not specified. This is a short version of the `if` function.
 * ? <expr> <valueA> [<valueB>]
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
 * Returns the value if the expression is true, supports 'elif' and 'else' as well.
 * if <condition> <block> [elif <condition> <block>] [else <block>]
 */
Expr::register('_if', function ($parts, $data)
{
    $mode = 0;
    $start = 0;

    for ($i = 1; $i < $parts->length(); $i++)
    {
        $value = $parts->get($i)->get(0);
        
        switch ($mode)
        {
            case 0: // Verify condition.
                if (\Rose\bool(Expr::value($parts->get($i), $data)))
                {
                    $mode = 1;
                    $start = $i+1;
                }
                else
                    $mode = 2;

                break;

            case 1: // Find else/elif and run block.
                if ($value->type == 'identifier' && ($value->data === 'else' || $value->data === 'elif'))
                    return Expr::blockValue($parts->slice($start, $i-$start), $data);

                break;

            case 2: // Find else and run block, or elif and evaluate condition.
                if ($value->type == 'identifier')
                {
                    if ($value->data == 'else') {
                        $mode = 1;
                        $start = $i+1;
                    }
                    else if ($value->data == 'elif') {
                        $mode = 0;
                    }
                }

                break;
        }
    }

    return $mode == 1 ? Expr::blockValue($parts->slice($start, $parts->length-$start), $data) : null;
});

/**
 * Returns the value of the block.
 * block <block...>
 */
Expr::register('_block', function ($parts, $data)
{
    return Expr::blockValue($parts->slice(1), $data);
});

/**
 * Executes the block if the condition is true.
 * when <condition> <block>
 */
Expr::register('_when', function ($parts, $data)
{
    if (Expr::expand($parts->get(1), $data, 'arg'))
        return Expr::blockValue($parts->slice(2), $data);

    return null;
});

/**
 * Executes the block if the condition is false.
 * when-not <condition> <block>
 */
Expr::register('_when-not', function ($parts, $data)
{
    if (!Expr::expand($parts->get(1), $data, 'arg'))
        return Expr::blockValue($parts->slice(2), $data);

    return null;
});

/**
**	switch <expr> case <case1> <value1> ... case <caseN> <valueN> default <defvalue> 
*/
Expr::register('_switch', function ($parts, $data)
{
    $value = (string)Expr::expand($parts->get(1), $data, 'arg');
    $name = '';
    $state = 0;
    $case_value = '';
    $j = -1;

    for ($i = 2; $i < $parts->length() && $state != 2; )
    {
        switch ($state)
        {
            case 0:
                if (!Expr::takeIdentifier($parts, $data, $i, $name)) {
                    $i++;
                    break;
                }

                if ($name === 'case') {
                    $case_value = (string)Expr::expand($parts->get($i), $data, 'arg');
                    if ($case_value == $value) {
                        $state = 1;
                        $j = ++$i;
                    }
                }
                elseif ($name === 'default') {
                    $state = 1;
                    $j = $i;
                }
                break;

            case 1:
                if (Expr::takeIdentifier($parts, $data, $i, $name)) {
                    if ($name === 'case' || $name === 'default') {
                        $i--;
                        $state = 2;
                        break;
                    }
                }
                else
                    $i++;

                break;
        }
    }

    if ($j != -1)
    {
        try {
            return Expr::blockValue($parts->slice($j, $i-$j), $data);
        }
        catch (\Throwable $e) {
            $name = $e->getMessage();
            if ($name != 'EXC_BREAK') throw $e;
        }
    }

    return null;
});

/**
**	case <expr> <case1> <value1> ... <caseN> <valueN> [else] <defvalue> 
*/
Expr::register('_case', function ($parts, $data)
{
    $value = (string)Expr::expand($parts->get(1), $data, 'arg');
    $n = $parts->length();

    for ($i = 2; $i < $n; $i += 2)
    {
        $case_value = (string)Expr::expand($parts->get($i), $data, 'arg');
        if ($i == $n-1 && ($n&1)) return $case_value;

        if ($case_value == $value || $case_value == 'else')
            return Expr::expand($parts->get($i+1), $data, 'arg');
    }

    return '';
});

/**
**	break
*/
Expr::register('_break', function ($parts, $data)
{
    throw new \Exception('EXC_BREAK');
});

/**
**	continue
*/
Expr::register('_continue', function ($parts, $data)
{
    throw new \Exception('EXC_CONTINUE');
});

/**
 * Repeats the specified block the specified number of times and gathers the results to construct an array.
 * gather [<varname>] [from <number>] [to <number>] [times <number>] [step <number>] <block>
 */
Expr::register('_gather', function ($parts, $data)
{
    $var_name = Expr::value($parts->get(1), $data);
    $i = 2;

    if ($var_name == 'from' || $var_name == 'to' || $var_name == 'times' || $var_name == 'step') {
        $var_name = 'i';
        $i = 1;
    }

    $count = null;
    $from = 0;
    $to = null;
    $step = null;

    while ($i < $parts->length-1)
    {
        $tmp = '';
        if (!Expr::takeIdentifier ($parts, $data, $i, $tmp))
            break;

        switch ($tmp)
        {
            case 'from':
                $from = (float)Expr::value($parts->get($i++), $data);
                break;

            case 'to':
                $to = (float)Expr::value($parts->get($i++), $data);
                break;

            case 'times':
                $count = (float)Expr::value($parts->get($i++), $data);
                break;

            case 'step':
                $step = (float)Expr::value($parts->get($i++), $data);
                break;
        }
    }

    $block = $parts->slice($i);
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
                    $arr->push(Expr::blockValue($block, $data));
                }
                catch (\Throwable $e) {
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
                    $arr->push(Expr::blockValue($block, $data));
                }
                catch (\Throwable $e) {
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
                $arr->push(Expr::blockValue($block, $data));
            }
            catch (\Throwable $e) {
                $name = $e->getMessage();
                if ($name == 'EXC_BREAK') break;
                if ($name == 'EXC_CONTINUE') continue;
                throw $e;
            }
        }
    }
    else
        throw new Error('gather requires the `to` or `times` parameter');

    $data->remove($var_name);
    return $arr;
});

/**
 * Repeats the specified block the specified number of times.
 * repeat [<varname>] [from <number>] [to <number>] [times <number>] [step <number>] <block>
 */
Expr::register('_repeat', function ($parts, $data)
{
    $var_name = Expr::value($parts->get(1), $data);
    $i = 2;

    if ($var_name === 'from' || $var_name === 'to' || $var_name === 'times' || $var_name === 'step') {
        $var_name = 'i';
        $i = 1;
    }

    $count = null;
    $from = 0;
    $to = null;
    $step = null;

    while ($i < $parts->length-1)
    {
        $tmp = '';
        if (!Expr::takeIdentifier ($parts, $data, $i, $tmp))
            break;

        switch ($tmp)
        {
            case 'from':
                $from = (float)Expr::value($parts->get($i++), $data);
                break;

            case 'to':
                $to = (float)Expr::value($parts->get($i++), $data);
                break;

            case 'times':
                $count = (float)Expr::value($parts->get($i++), $data);
                break;

            case 'step':
                $step = (float)Expr::value($parts->get($i++), $data);
                break;
        }
    }

    $block = $parts->slice($i);

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
                    Expr::blockValue($block, $data);
                }
                catch (\Throwable $e) {
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
                    Expr::blockValue($block, $data);
                }
                catch (\Throwable $e) {
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
                Expr::blockValue($block, $data);
            }
            catch (\Throwable $e) {
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
                Expr::blockValue($block, $data);
            }
            catch (\Throwable $e) {
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
 * Repeats the specified block infinitely until a "break" is found.
 * loop <block>
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
        catch (\Throwable $e) {
            $name = $e->getMessage();
            if ($name == 'EXC_BREAK') break;
            if ($name == 'EXC_CONTINUE') continue;
            throw $e;
        }
    }

    return null;
});

/**
**	Repeats the specified block until the condition is false or a "break" is found.
**
**	while <condition> <block>
*/
Expr::register('_while', function ($parts, $data)
{
    if ($parts->length < 3)
        return '(`while`: Wrong number of parameters)';

    $block = $parts->slice(2);
    $condition = $parts->get(1);

    while ( Expr::value($condition, $data) )
    {
        try {
            Expr::blockValue($block, $data);
        }
        catch (\Throwable $e) {
            $name = $e->getMessage();
            if ($name == 'EXC_BREAK') break;
            if ($name == 'EXC_CONTINUE') continue;
            throw $e;
        }
    }

    return null;
});

/**
**	Writes the specified arguments to standard output separated by space.
**
**	echo <expr> [<expr>...]
*/
Expr::register('_echo', function ($parts, $data)
{
    for ($i = 1; $i < $parts->length(); $i++)
        echo(Expr::expand($parts->get($i), $data, 'arg').' ');

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
**	Constructs an object.
**
**	& <name> <expr> [<name> <expr>...]
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
 **	&& <name> <expr> [<name> <expr>...]
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
        if (!$value->has($args->get($i), true))
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
**	has <value> <array-expr>
**  has <value> <string-expr>
*/
Expr::register('has', function ($args, $parts, $data)
{
    $value = $args->get(2);
    $typeOf = typeOf($value);

    if ($typeOf === 'Rose\\Map')
        return $value->has($args->get(1), true);

    if ($typeOf === 'Rose\\Arry')
        return $value->indexOf($args->get(1)) !== null;

    if ($typeOf === 'primitive')
        return Text::indexOf($value, $args->get(1)) !== false;

    return false;
});

/**
**	map [<varname>] <array-expr> <block>
*/
Expr::register('_map', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $arrayMode = \Rose\typeOf($list) == 'Rose\\Arry' ? true : false;
    $output = $arrayMode ? new Arry() : new Map();
    $j = 0;

    $block = $parts->slice($i+1);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$arrayMode, &$data, &$block)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        try {
            if ($arrayMode)
                $output->push(Expr::blockValue($block, $data));
            else
                $output->set($key, Expr::blockValue($block, $data));
        }
        catch (\Throwable $e) {
            $name = $e->getMessage();
            if ($name == 'EXC_BREAK') return false;
            if ($name == 'EXC_CONTINUE') return;
            throw $e;
        }
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	filter [<varname>] <array-expr> <block>
*/
Expr::register('_filter', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $arrayMode = typeOf($list) == 'Rose\\Arry' ? true : false;
    $output = $arrayMode ? new Arry() : new Map();
    $j = 0;

    $block = $parts->slice($i+1);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$arrayMode, &$data, &$block)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        if (!!Expr::blockValue($block, $data))
        {
            if ($arrayMode)
                $output->push($item);
            else
                $output->set($key, $item);
        }
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	every [<varname>] <array-expr> <block>
*/
Expr::register('_every', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $output = true;
    $j = 0;

    $block = $parts->slice($i+1);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$data, &$block)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        if (!Expr::blockValue($block, $data))
        {
            $output = false;
            return false;
        }
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	some [<varname>] <array-expr> <block>
*/
Expr::register('_some', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $output = false;
    $j = 0;

    $block = $parts->slice($i+1);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$data, &$block)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        if (!!Expr::blockValue($block, $data))
        {
            $output = true;
            return false;
        }
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	find [<varname>] <array-expr> <block>
*/
Expr::register('_find', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $output = null;
    $j = 0;

    $block = $parts->slice($i+1);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$data, &$block)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        if (!!Expr::blockValue($block, $data))
        {
            $output = $item;
            return false;
        }
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	findIndex [<varname>] <array-expr> <block>
*/
Expr::register('_findIndex', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $output = null;
    $j = 0;

    $block = $parts->slice($i+1);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$data, &$block)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        if (!!Expr::blockValue($block, $data))
        {
            $output = $key;
            return false;
        }
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	reduce [<iter-var>] [<init-var>] <initial> <array-expr> <block>
*/
Expr::register('_reduce', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name, 'a');

    $initial_name = 'b';
    Expr::takeIdentifier($parts, $data, $i, $initial_name);

    $initial = Expr::expand($parts->get($i++), $data, 'arg');
    $list = Expr::expand($parts->get($i++), $data, 'arg');
    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $j = 0;

    $block = $parts->slice($i);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$initial_name, &$initial, &$j, &$data, &$block)
    {
        $data->set($initial_name, $initial);
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        $initial = Expr::blockValue($block, $data);
    });

    $data->remove($initial_name);
    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $initial;
});

/**
**	select [<varname>] <condition> <array-expr>
*/
Expr::register('_select', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = $parts->has($i+1) ? Expr::expand($parts->get($i+1), $data, 'arg') : Expr::$context->defaultValue;

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $arrayMode = typeOf($list) == 'Rose\\Arry' ? true : false;
    $output = $arrayMode ? new Arry() : new Map();
    $j = 0;

    $condition = $parts->get($i);
    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$arrayMode, &$data, &$condition)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        if (!!Expr::value($condition, $data))
        {
            if ($arrayMode)
                $output->push($item);
            else
                $output->set($key, $item);
        }
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	pipe <expression>+
*/
Expr::register('_pipe', function ($parts, $data)
{
    $i = 1;

    Expr::takeIdentifier($parts, $data, $i, $var_name);

    Expr::$context->defaultValue = null;

    for (; $i < $parts->length; $i++)
    {
        Expr::$context->defaultValue = $value = Expr::value($parts->get($i), $data);
    }

    return $value;
});

/**
**	expand <template> <data>
*/
Expr::register('expand', function ($args, $parts, $data)
{
    if (typeOf($args->get(1)) == 'Rose\\Arry')
        return Expr::expand ($args->get(1), $args->length == 3 ? $args->get(2) : $data);
    else
        return Expr::expand (Expr::parseTemplate (Expr::clean($args->get(1)), '{', '}', false, 1, false), $args->length == 3 ? $args->get(2) : $data);
});

/**
**	eval <expression-string> [<data>]
*/
Expr::register('eval', function ($args, $parts, $data)
{
    try {
        if (typeOf($args->get(1)) == 'Rose\\Arry')
            return Expr::expand(Expr::clean($args->get(1)), $args->length == 3 ? $args->get(2) : $data, 'last');
        else
            return Expr::expand(Expr::clean(Expr::parseTemplate (Expr::clean($args->get(1)), '(', ')', false, 1, true)), $args->length == 3 ? $args->get(2) : $data, 'last');
    }
    catch (MetaError $e)
    {
        switch ($e->isForMe(-1) ? $e->code : null)
        {
            case 'EXPR_YIELD':
                return $e->value;

            case 'FN_RET':
                return $e->value;
        }

        throw $e;
    }
});

/**
**	try <block> [catch <block>] [finally <block>]
*/
Expr::register('_try', function ($parts, $data)
{
    $n = $parts->length;

    $code = null;
    $catch = null;
    $finally = null;

    $j = 1;

    for ($i = 1; $i < $n; $i++)
    {
        if ($parts->get($i)->get(0)->type == 'identifier')
        {
            switch ($parts->get($i)->get(0)->data)
            {
                case 'catch':
                    if ($code === null)
                        $code = $parts->slice($j, $i-$j);

                    $catch = true;
                    $j = $i+1;
                    break;

                case 'finally':
                    if ($code === null)
                        $code = $parts->slice($j, $i-$j);

                    if ($catch === true)
                        $catch = $parts->slice($j, $i-$j);
            
                    $finally = true;
                    $j = $i+1;
                    break;
            }
        }
    }

    if ($code === null)
        $code = $parts->slice($j);

    if ($catch === true)
        $catch = $parts->slice($j);

    if ($finally === true)
        $finally = $parts->slice($j);

    MetaError::incBaseLevel();

    $value = null;

    try {
        $value = Expr::blockValue($code, $data);
    }
    catch (MetaError $e)
    {
        switch ($e->isForMe(-1) ? $e->code : null)
        {
            case 'EXPR_YIELD':
                $value = $e->value;
                break;

            case 'FN_RET':
                throw $e;
                break;

            default:
                throw $e;
                break;
        }
    }
    catch (\Throwable $e)
    {
        switch ($e->getMessage())
        {
            case 'EXC_BREAK':
            case 'EXC_CONTINUE':
                throw $e;
        }

        $data->err = $e->getMessage();
        $data->ex = $e;

        if ($catch !== null)
            $value = Expr::blockValue($catch, $data);
    }
    finally
    {
        MetaError::decBaseLevel();

        if ($finally !== null)
            Expr::blockValue($finally, $data);
    }

    return $value;
});

/**
**	throw <expr>
**	throw
*/
Expr::register('throw', function ($args, $parts, $data)
{
    if ($args->length > 1)
        throw new \Exception ($args->get(1) ?? '');

    throw new \Exception ($data->get('err') ?? '');
});

/**
**	assert <condition> [<message>]
*/
Expr::register('_assert', function ($parts, $data)
{
    if (Expr::expand($parts->get(1), $data, 'arg'))
        return null;

    throw new \Exception ($parts->has(2) ? Expr::expand($parts->get(2), $data) : 'assertion failed');
});

/**
**	yield <level> <value>
**	yield [<value>]
*/
Expr::register('yield', function($args) {
    
    if ($args->length == 3)
        throw new MetaError('EXPR_YIELD', $args->get(2), Math::max(1, $args->get(1)));
    else
        throw new MetaError('EXPR_YIELD', $args->has(1) ? $args->get(1) : null, 1);
});

/**
**	exit <level>
*/
Expr::register('exit', function($args) {
    
    throw new MetaError('EXPR_YIELD', null, Math::max(1, $args->get(1)));
});

/**
**	with <varname> <value> <block>
*/
Expr::register('_with', function($parts, $data)
{
    $var_name = 'i';
    $i = 1;

    Expr::takeIdentifier($parts, $data, $i, $var_name);

    $old_value_present = false;
    $old_value = null;

    if ($data->has($var_name))
    {
        $old_value_present = true;
        $old_value = $data->{$var_name};
    }

    $data->{$var_name} = Expr::expand($parts->get($i), $data, 'arg');

    $value = Expr::blockValue($parts->slice($i+1), $data);

    if ($old_value_present)
        $data->{$var_name} = $old_value;
    else
        $data->remove($var_name);

    return $value;
});

/**
**	ret [<value>]
*/
Expr::register('ret', function($args) {
    throw new MetaError ('FN_RET', $args->has(1) ? $args->get(1) : null);
});

/*
**	fn <param-name>* <block>
*/
Expr::register('_fn', function($parts, $data)
{
    $params = new Arry();
    $minParams = 0;
    $defValues = new Map();

    $context = Expr::$context;
    $contextData = $context->getId() == 0 && $data !== $context->data ? $data : $context->data;
    $rootData = $data;
    $restParam = null;

    $fn = null;
    for ($i = 1; $i < $parts->length; $i++)
    {
        // function body
        if ($parts->get($i)->get(0)->type !== 'identifier' || Expr::isSpecialType($parts->get($i)->get(0)->data))
        {
            $block = $parts->slice($i);
            $fn = function ($args, $parts, $data) use (&$params, &$restParam, &$block, &$name, &$context, &$contextData, &$rootData, &$minParams, &$defValues)
            {
                global $_glb_object;

                if ($args->length-1 < $params->length)
                    throw new Error ("invalid number of parameters, expected ".($params->length)." got ".($args->length-1));

                $newData = new Map();
                $newData->set('local', $contextData);
                $newData->set('global', $_glb_object);
                $newData->set('self', $rootData);

                $params->forEach(function ($param, $index) use (&$newData, &$args, &$data, &$defValues)
                {
                    if ($index+1 >= $args->length()) {
                        $tmp = $defValues->get($param);
                        if ($tmp->get(0) == 0)
                            $newData->set($param, Expr::value($tmp->get(1), $data));
                        else
                            $newData->set($param, $tmp->get(1));
                    }
                    else
                        $newData->set($param, $args->get($index+1));
                });

                if ($restParam !== null)
                    $newData->set($restParam, $args->slice($params->length+1));

                Expr::$contextStack->push(Expr::$context);
                Expr::$context = $context;

                $value = null;
                MetaError::incBaseLevel();

                try {
                    $value = Expr::blockValue($block, $newData);
                }
                catch (MetaError $e)
                {
                    if (!$e->isForMe(-1)) throw $e;

                    switch ($e->code)
                    {
                        case 'EXPR_YIELD':
                            $value = $e->value;
                            break;

                        case 'FN_RET':
                            $value = $e->value;
                            break;

                        default:
                            throw $e;
                    }
                }
                finally {
                    Expr::$context = Expr::$contextStack->pop();
                    MetaError::decBaseLevel();
                }

                return $value;
            };

            break;
        }

        // variable
        $val = $parts->get($i)->get(0)->data;

        if ($restParam !== null)
            throw new Error('(fn) found parameter `' . $val . '` after rest parameter `&' . $restParam . '`');

        if (Text::startsWith($val, "&")) {
            $restParam = Text::substring($val, 1);
            continue;
        }

        if (Text::indexOf($val, '=') !== false)
        {
            if (Text::endsWith($val, '=')) {
                $tmp = $parts->get($i)->length() > 1 ? $parts->get($i)->slice(1) : $parts->get(++$i);
                $val = Text::substring($val, 0, -1);
                $defValues->set($val, new Arry([ 0, $tmp ]));
            }
            else {
                $val = Text::split('=', $val);
                $tmp = $val->get(1);
                $val = $val->get(0);

                if ($tmp === 'null')
                    $tmp = null;
                else if ($tmp === 'true')
                    $tmp = true;
                else if ($tmp === 'false')
                    $tmp = false;
                else if (Text::indexOf($tmp, '.') !== false)
                    $tmp = (float)$tmp;
                else
                    $tmp = (int)$tmp;

                $defValues->set($val, new Arry([ 1, $tmp ]));
            }
        }
        else {
            if ($defValues->length() != 0)
                throw new Error('(def-fn) ' . $name . ': parameter `' . $val . '` must have a default value');
            $minParams++;
        }

        $params->push($val);
    }

    return $fn;
});


/*
**	def-fn [private|public] <fn-name> <param-name>* <block>
*/
Expr::register('_def-fn', function($parts, $data)
{
    $scope = Expr::$context->currentScope;
    $i = 1;

    while ($i < $parts->length) {
        if ($parts->get($i)->get(0)->data === 'private') {
            $scope = 'private';
            $i++;
            continue;
        }

        if ($parts->get($i)->get(0)->data === 'public') {
            $scope = 'public';
            $i++;
            continue;
        }

        break;
    }

    if ($i >= $parts->length)
        return null;

    $name = Expr::value($parts->get($i++), $data);
    if ($name[0] === '_')
        throw new Error('(def-fn) function name cannot start with an underscore: ' . $name);

    $params = new Arry();
    $minParams = 0;
    $defValues = new Map();

    $context = Expr::$context;
    $contextData = $context->getId() == 0 && $data !== $context->data ? $data : $context->data;
    $rootData = $data;
    $restParam = null;

    $fn = null;
    for (; $i < $parts->length; $i++)
    {
        // function body
        if ($parts->get($i)->get(0)->type !== 'identifier' || Expr::isSpecialType($parts->get($i)->get(0)->data))
        {
            $block = $parts->slice($i);
            $fn = function ($args, $parts, $data) use (&$params, &$restParam, &$block, &$name, &$context, &$contextData, &$rootData, &$minParams, &$defValues)
            {
                global $_glb_object;

                if ($args->length-1 < $minParams)
                    throw new Error ("(".$name.") expects ".($minParams)." parameters got ".($args->length-1));

                $newData = new Map();
                $newData->set('local', $contextData);
                $newData->set('global', $_glb_object);
                // self is only available to anonymous functions to capture the outer scope
                //$newData->set('self', $rootData);

                $params->forEach(function ($param, $index) use (&$newData, &$args, &$data, &$defValues)
                {
                    if ($index+1 >= $args->length()) {
                        $tmp = $defValues->get($param);
                        if ($tmp->get(0) == 0)
                            $newData->set($param, Expr::value($tmp->get(1), $data));
                        else
                            $newData->set($param, $tmp->get(1));
                    }
                    else
                        $newData->set($param, $args->get($index+1));
                });

                if ($restParam !== null)
                    $newData->set($restParam, $args->slice($params->length+1));

                Expr::$contextStack->push(Expr::$context);
                Expr::$context = $context;

                $value = null;
                MetaError::incBaseLevel();

                try {
                    $value = Expr::blockValue($block, $newData);
                }
                catch (MetaError $e)
                {
                    if (!$e->isForMe(-1)) throw $e;

                    switch ($e->code)
                    {
                        case 'EXPR_YIELD':
                            $value = $e->value;
                            break;

                        case 'FN_RET':
                            $value = $e->value;
                            break;

                        default:
                            throw $e;
                    }
                }
                finally {
                    Expr::$context = Expr::$contextStack->pop();
                    MetaError::decBaseLevel();
                }

                return $value;
            };

            break;
        }

        // variable
        $val = $parts->get($i)->get(0)->data;

        if ($restParam !== null)
            throw new Error('(def-fn) '.$name.': found parameter `' . $val . '` after rest parameter `&' . $restParam . '`');

        if (Text::startsWith($val, "&")) {
            $restParam = Text::substring($val, 1);
            continue;
        }
    
        if (Text::indexOf($val, '=') !== false)
        {
            if (Text::endsWith($val, '=')) {
                $tmp = $parts->get($i)->length() > 1 ? $parts->get($i)->slice(1) : $parts->get(++$i);
                $val = Text::substring($val, 0, -1);
                $defValues->set($val, new Arry([ 0, $tmp ]));
            }
            else {
                $val = Text::split('=', $val);
                $tmp = $val->get(1);
                $val = $val->get(0);

                if ($tmp === 'null')
                    $tmp = null;
                else if ($tmp === 'true')
                    $tmp = true;
                else if ($tmp === 'false')
                    $tmp = false;
                else if (Text::indexOf($tmp, '.') !== false)
                    $tmp = (float)$tmp;
                else
                    $tmp = (int)$tmp;

                $defValues->set($val, new Arry([ 1, $tmp ]));
            }
        }
        else {
            if ($defValues->length() != 0)
                throw new Error('(def-fn) ' . $name . ': parameter `' . $val . '` must have a default value');
            $minParams++;
        }

        $params->push($val);
    }

    $ns = Expr::$context->currentNamespace;
    $isPrivate = $scope === 'private';
    $flag = false;
    if ($ns && Text::startsWith($name, $ns.'::')) {
        $name = Text::substring($name, Text::length($ns)+2);
        $flag = true;
    }

    if (Text::startsWith($name, '::')) {
        $name = Text::substring($name, 2);
        Context::getContext(0)->registerFunction($name, new ExprFn ($name, $fn, Expr::$context));
    }
    else if (Text::indexOf($name, '::') !== false && !$flag) {
        if ($ns)
            Expr::$context->registerFunction($ns.'::'.$name, new ExprFn ($ns.'::'.$name, $fn, Expr::$context), $isPrivate);
        else
            Expr::$context->registerFunction($name, new ExprFn ($name, $fn, Expr::$context), $isPrivate);
        //VIOLET??? Context::getContext(0)->registerFunction($name, new ExprFn ($name, $fn, Expr::$context));
    }
    else {
        if ($ns)
            Expr::$context->registerFunction($ns.'::'.$name, new ExprFn ($ns.'::'.$name, $fn, Expr::$context), $isPrivate);
        else
            Expr::$context->registerFunction($name, new ExprFn ($name, $fn, Expr::$context), $isPrivate);
    }

    return null;
});


/*
**	def [public|private] <varname> <value>
*/
Expr::register('_def', function($parts, $data)
{
    $scope = Expr::$context->currentScope;

    $i = 1;

    while (true)
    {
        if ($parts->get($i)->get(0)->data === 'private') {
            $scope = 'private';
            $i++;
            continue;
        }
    
        if ($parts->get($i)->get(0)->data === 'public') {
            $scope = 'public';
            $i++;
            continue;
        }
    
        break;
    }

    $name = Expr::value($parts->get($i++), $data);
    $value = Expr::value($parts->get($i), $data);

    $fn = function () use (&$value) {
        return $value;
    };

    $ns = Expr::$context->currentNamespace;
    $flag = false;

    if ($ns && Text::startsWith($name, $ns.'::'))
    {
        $name = Text::substring($name, Text::length($ns)+2);
        $flag = true;
    }

    $isPrivate = $scope === 'private';

    if (Text::startsWith($name, '::'))
    {
        $name = Text::substring($name, 2);
        Context::getContext(0)->registerFunction($name, new ExprFn ($name, $fn, Expr::$context));
    }
    else if (Text::indexOf($name, '::') !== false && !$flag)
    {
        if ($isPrivate)
            Expr::$context->registerFunction($name, new ExprFn ($name, $fn, Expr::$context), true);
        else
            Context::getContext(0)->registerFunction($name, new ExprFn ($name, $fn, Expr::$context));
    }
    else
    {
        if ($ns)
        {
            if (!$isPrivate)
                Expr::$context->registerFunction($ns.'::'.$name, new ExprFn ($ns.'::'.$name, $fn, Expr::$context));
            else
                Expr::$context->registerFunction($ns.'::'.$name, new ExprFn ($ns.'::'.$name, $fn, Expr::$context), true);
        }
        else
            Expr::$context->registerFunction($name, new ExprFn ($name, $fn, Expr::$context), $isPrivate);
    }
    
    return null;
});


/*
**	ns [public|private] [<str-expr>]
*/
Expr::register('ns', function($args, $parts, $data)
{
    $scope = 'public';
    $i = 1;

    while ($i < $args->length)
    {
        if ($args->get($i) == 'public' || $args->get($i) == 'private')
        {
            $scope = $args->get($i);
            $i++;
            continue;
        }

        break;
    }

    Expr::$context->currentScope = $scope;
    Expr::$context->currentNamespace = $args->has($i) ? (string)$args->get($i) : '';

    return null;
});

/*
**	include <source-path>+
*/
Expr::register('include', function($args, $parts, $data)
{
    for ($i = 1; $i < $args->length; $i++)
    {
        $path = $args->get($i);

        if (Text::startsWith($path, './'))
            $path = Path::append(Expr::$context->currentPath, Text::substring($path, 2));
        else if (!Text::startsWith($path, '/'))
            $path = Path::append(Expr::$importPath, $path);

        if (!Text::endsWith($path, '.fn'))
            $path .= '.fn';

        $path = Path::resolve($_path = $path);
        if (!Path::exists($path))
            throw new Error ("source does not exist: " . $_path);

        $expr = Expr::parse(Regex::_replace ('|/\*(.*?)\*/|s', '', File::getContents($path)));

        for ($j = 0; $j < $expr->length; $j++)
        {
            if ($expr->get($j)->type != 'template')
            {
                $expr->remove($j);
                $j--;
            }
        }

        MetaError::incBaseLevel();

        try {
            Expr::expand ($expr, Expr::$context->data, 'void');
        }
        catch (MetaError $e)
        {
            if (!$e->isForMe(-1)) throw $e;

            switch ($e->code)
            {
                case 'EXPR_YIELD':
                    break;

                case 'FN_RET':
                    break;

                default:
                    throw $e;
            }
        }
        finally
        {
            MetaError::decBaseLevel();
        }
    }

    return null;
});


/*
**	import (<source-path> [as <namespace-name>])+
*/
Expr::register('import', function($args, $parts, $data)
{
    for ($i = 1; $i < $args->length; $i++)
    {
        $path = $args->get($i);
        $ns = '';

        if ($i+1 < $args->length && $args->get($i+1) == 'as') {
            $ns = $args->get($i+2);
            $i += 2;
        }

        if (Text::startsWith($path, './'))
            $path = Path::append(Expr::$context->currentPath, Text::substring($path, 2));
        else if (!Text::startsWith($path, '/'))
            $path = Path::append(Expr::$importPath, $path);

        if (!Text::endsWith($path, '.fn'))
        {
            if (!Path::exists(Path::resolve($path.'.fn'))) {
                $env = Configuration::getInstance()->env;
                if ($env && Path::exists(Path::resolve($path.'.'.$env.'.fn')))
                    $path .= '.'.$env;
            }
            $path .= '.fn';
        }

        $path = Path::resolve($_path = $path);
        $path_cache = null;

        if (Expr::$cachePath && Text::startsWith($path, Expr::$importPath))
            $path_cache = Path::append(Expr::$cachePath, Text::replace('/', '-', Text::substring($path, 1+Text::length(Expr::$importPath))));

        if (!Path::exists($path))
            throw new Error ("source does not exist: " . $_path);

        if (Expr::$importedTime->get($path) == File::mtime($path, true))
        {
            Expr::$context->linkContext (Expr::$importedContext->get($path), $ns);
            continue;
        }

        Expr::$importedTime->set($path, File::mtime($path, true));

        if ($path_cache && Path::exists($path_cache) && File::mtime($path_cache, true) == File::mtime($path, true))
        {
            $expr = unserialize(File::getContents($path_cache));
        }
        else
        {
            $expr = Expr::parse(Regex::_replace ('|/\*(.*?)\*/|s', '', File::getContents($path)));

            for ($j = 0; $j < $expr->length; $j++)
            {
                if ($expr->get($j)->type != 'template')
                {
                    $expr->remove($j);
                    $j--;
                }
            }

            if ($path_cache) {
                File::setContents($path_cache, serialize($expr));
                File::touch($path_cache, File::mtime($path, true));
            }
        }

        Expr::$contextStack->push(Expr::$context);

        Expr::$context = new Context();
        Expr::$context->currentPath = Path::dirname($path);

        Expr::$importedContext->set($path, Expr::$context);

        MetaError::incBaseLevel();

        try {
            Expr::expand ($expr, Expr::$context->data, 'void');
        }
        catch (MetaError $e)
        {
            if (!$e->isForMe(-1)) throw $e;

            switch ($e->code)
            {
                case 'EXPR_YIELD':
                    break;

                case 'FN_RET':
                    break;

                default:
                    throw $e;
            }
        }
        finally
        {
            $context = Expr::$context;
            Expr::$context = Expr::$contextStack->pop();
            Expr::$context->linkContext ($context, $ns);

            MetaError::decBaseLevel();
        }
    }

    return null;
});

/*
**	zipmap <key-name...> <array-expr>
**	zipmap <array-expr> <array-expr>
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

/*
**	map-get <key-name...> <map-expr>
**	map-get <arr-expr> <map-expr>
*/
Expr::register('map-get', function($args, $parts, $data)
{
    $map = new Map();

    if (\Rose\typeOf($args->get(1)) == 'Rose\Arry')
    {
        $keys = $args->get(1);
        $values = $args->get(2);
    }
    else
    {
        $keys = $args->slice(1, $args->length-2);
        $values = $args->get($args->length-1);
    }

    $keys->forEach(function ($key) use (&$map, &$values)
    {
        if ($values->has($key))
            $map->set ($key, $values->get($key));
    });

    return $map;
});

/**
**	mapify [<varname>] <array-expr> <key-expr> [<value-expr>]
*/
Expr::register('_mapify', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $output = new Map();
    $j = 0;

    $key_expr = $parts->get($i+1);
    $val_expr = $parts->has($i+2) ? $parts->get($i+2) : null;

    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$arrayMode, &$data, &$key_expr, &$val_expr)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        $key = Expr::expand($key_expr, $data, 'arg');
        $value = $item;

        if ($val_expr != null)
            $value = Expr::expand($val_expr, $data, 'arg');

        $output->set($key, $value);
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
**	groupify [<varname>] <array-expr> <key-expr> [<value-expr>]
*/
Expr::register('_groupify', function ($parts, $data)
{
    $i = 1;
    Expr::getIteratorName($parts, $data, $i, $var_name, $key_name, $index_name);

    $list = Expr::expand($parts->get($i), $data, 'arg');

    if (!$list || (\Rose\typeOf($list) != 'Rose\Arry' && \Rose\typeOf($list) != 'Rose\Map'))
        return $list;

    $output = new Map();
    $j = 0;

    $key_expr = $parts->get($i+1);
    $val_expr = $parts->has($i+2) ? $parts->get($i+2) : null;

    $list->forEach(function($item, $key) use(&$var_name, &$key_name, &$index_name, &$output, &$j, &$arrayMode, &$data, &$key_expr, &$val_expr)
    {
        $data->set($var_name, $item);
        $data->set($index_name, $j++);
        $data->set($key_name, $key);

        $key = (string)Expr::expand($key_expr, $data, 'arg');
        $value = $item;

        if ($val_expr != null)
            $value = Expr::expand($val_expr, $data, 'arg');

        if (!$output->has($key))
            $output->set($key, new Arry());

        $output->get($key)->push($value);
    });

    $data->remove($var_name);
    $data->remove($index_name);
    $data->remove($key_name);

    return $output;
});

/**
 * Dumps the current context chain.
 * (debug::dumpContextChain [include-root] [include-private])
 */
Expr::register('debug::dumpContextChain', function($args)
{
    echo "Context " . Expr::$context->getId() . ":\n";

    $i = 1;
    $includeRoot = false;
    $includePrivate = false;

    while ($i < $args->length)
    {
        $value = $args->get($i++);

        if ($value == 'include-root') {
            $includeRoot = true;
            continue;
        }

        if ($value == 'include-private') {
            $includePrivate = true;
            continue;
        }
    }

    Expr::$context->publicFunctions->forEach(function($fn) use (&$includeRoot) {
        if ($fn->isRoot && !$includeRoot) return;
        echo "  public " . $fn->name . "\n";
    });

    Expr::$context->exportedFunctions->forEach(function($fn) use (&$includeRoot) {
        if ($fn->isRoot && !$includeRoot) return;
        echo "  exported " . $fn->name . "\n";
    });

    if ($includePrivate) Expr::$context->privateFunctions->forEach(function($fn) use (&$includeRoot) {
        if ($fn->isRoot && !$includeRoot) return;
        echo "  private " . $fn->name . "\n";
    });

    echo "\n";

    Expr::$context->chain->forEach(function($linked) use (&$includeRoot, &$includePrivate)
    {
        echo "  Linked Context " . $linked->context->getId() . " AS '" . $linked->ns . "'\n";

        $linked->context->publicFunctions->forEach(function($fn) use (&$includeRoot) {
            if ($fn->isRoot && !$includeRoot) return;
            echo "    public " . $fn->name . "\n";
        });

        $linked->context->exportedFunctions->forEach(function($fn) use (&$includeRoot) {
            if ($fn->isRoot && !$includeRoot) return;
            echo "    exported " . $fn->name . "\n";
        });

        if ($includePrivate) $linked->context->privateFunctions->forEach(function($fn) use (&$includeRoot) {
            if ($fn->isRoot && !$includeRoot) return;
            echo "    private " . $fn->name . "\n";
        });
    });
});

/**
 * Returns all functions in the root context. Optionally with some prefix.
 * (debug::fn [prefix])
 */
Expr::register('debug::fn', function($args)
{
    $context = Context::getContext(0);
    $list = new Arry();
    $prefix = $args->has(1) ? $args->get(1) : null;

    $context->publicFunctions->forEach(function($fn) use(&$list, &$prefix) {
        $name = Text::startsWith($fn->name, "_") ? Text::substring($fn->name, 1) : $fn->name;
        if ($prefix && !Text::startsWith($name, $prefix)) return;
        $list->push($name);
    });

    $context->exportedFunctions->forEach(function($fn) use(&$list, &$prefix) {
        $name = Text::startsWith($fn->name, "_") ? Text::substring($fn->name, 1) : $fn->name;
        if ($prefix && !Text::startsWith($name, $prefix)) return;
        $list->push($name);
    });

    Expr::$context->chain->forEach(function($linked) use (&$list, &$prefix)
    {
        if ($linked->context->getId() == 0) return;

        $linked->context->publicFunctions->forEach(function($fn) use(&$list, &$linked, &$prefix) {
            $name = Text::startsWith($fn->name, "_") ? Text::substring($fn->name, 1) : $fn->name;
            if ($prefix && !Text::startsWith($name, $prefix)) return;
            $list->push($name);
        });

        $linked->context->exportedFunctions->forEach(function($fn) use(&$list, &$linked, &$prefix) {
            $name = Text::startsWith($fn->name, "_") ? Text::substring($fn->name, 1) : $fn->name;
            if ($prefix && !Text::startsWith($name, $prefix)) return;
            $list->push($name);
        });
    });

    return $list;
});

/**
 * Returns the current execution context ID.
 * (debug::contextId)
 */
Expr::register('debug::contextId', function($args) {
    return Expr::$context->getId();
});

/**
 * Returns the reference to the specified function.
 * (get-fn function-name)
 */
Expr::register('get-fn', function($args) {
    if ($args->length < 2)
        throw new Error('(get-fn) function name missing');

    return Expr::getFunction($args->get(1));
});

/**
 * Sets or removes the reference of a function in the root context.
 * (set-fn function-name [function-reference|null])
 */
Expr::register('set-fn', function($args) {
    if ($args->length < 2)
        throw new Error('(set-fn) function name missing');

    if ($args->length == 2)
        $args->{2} = null;

    if ($args->get(2) === null) {
        Context::getContext(0)->unregisterFunction($args->get(1));
        return null;
    }

    if (\Rose\typeOf($args->get(2)) === 'function') {
        Context::getContext(0)->registerFunction($args->get(1), new ExprFn ($args->get(1), $args->get(2), Expr::$context));
        return null;
    }

    if (\Rose\typeOf($args->get(2)) === 'Rose\ExprFn') {
        Context::getContext(0)->registerFunction($args->get(1), $args->get(2));
        return null;
    }

    throw new Error('(set-fn) invalid function reference');
});
