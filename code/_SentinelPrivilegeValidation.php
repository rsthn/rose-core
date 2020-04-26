<?php

class _SentinelPrivilegeValidation extends _ValidationRule
{
    private static $__classAttributes = null;
    private $pattern;
    private $privilege;


    public static function classAttributes ()
    {
        return _SentinelPrivilegeValidation::$__classAttributes;

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

    public function __construct ($pattern, $privilege)
    {
        parent::__construct ();
        _SentinelPrivilegeValidation::__instanceInit ($this);
        $this->privilege=$privilege;
        $this->pattern=$pattern;
    }

    public function validate ($data, $context=null)
    {
        if (!_Regex::stMatch($this->pattern,$data))
        {
            return ;
        }
        if ((_Sentinel::getInstance ()->hasPrivilege($this->privilege)==false))
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