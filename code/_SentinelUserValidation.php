<?php

class _SentinelUserValidation extends _ValidationRule
{
    private static $__classAttributes = null;
    private $pattern;
    private $look;


    public static function classAttributes ()
    {
        return _SentinelUserValidation::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ($look, $pattern)
    {
        parent::__construct ();
        _SentinelUserValidation::__instanceInit ($this);
        if (isString($pattern))
        {
            $pattern=alpha (new _Regex ($pattern));
        }
        $this->pattern=$pattern;
        $this->look=$look;
    }

    public function validate ($data, $context=null)
    {
        if (!$this->pattern->match($data))
        {
            return ;
        }
        if ((((($this->look=='AUTH')&&(_Sentinel::getInstance ()->userAuthenticated()==false)))||((($this->look=='ANON')&&(_Sentinel::getInstance ()->userAuthenticated()!=false)))))
        {
            $this->failed();
        }
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

}