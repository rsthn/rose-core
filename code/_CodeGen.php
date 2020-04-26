<?php

class _CodeGen
{
    private static $__classAttributes = null;
    public static $charset;
    public static $charsetLen;


    public static function classAttributes ()
    {
        return _CodeGen::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _CodeGen::$charset=_Configuration::getInstance ()->CodeGen->charset;
        _CodeGen::$charsetLen=strlen(_CodeGen::$charset);
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ()
    {
        _CodeGen::__instanceInit ($this);
    }

    public static function getRandom ($length)
    {
        $result='';
        while ($length--)
        {
            $result.=_CodeGen::$charset[(_Math::rand()%_CodeGen::$charsetLen)];
        };
        return $result;
    }

    public static function getFromValue ($value)
    {
        $result='';
        if (($value==0))
        {
            return _CodeGen::$charset[0];
        }
        while (($value!=0))
        {
            $result.=_CodeGen::$charset[($value%_CodeGen::$charsetLen)];
            $value=((int)(($value/_CodeGen::$charsetLen)));
        };
        return $result;
    }

    public static function validCode ($code)
    {
        return _Regex::stExtract('/['._CodeGen::$charset.']+/',$code);
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
        }

        if (method_exists (get_parent_class (), '__get')) return parent::__get ($gsprn);
        throw new _UndefinedProperty ($gsprn);
    }

    public function __set ($gsprn, $sprv)
    {
        switch ($gsprn)
        {
        }
        if (method_exists (get_parent_class (), '__set')) parent::__set ($gsprn, $sprv);
    }

    public function __toString ()
    {
        return $this->__typeCast('String');
    }

}