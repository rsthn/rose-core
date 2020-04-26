<?php

class _ValidationRule
{
    private static $__classAttributes = null;
    protected $_failureMessage;


    public static function classAttributes ()
    {
        return _ValidationRule::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->_failureMessage='';
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ()
    {
        _ValidationRule::__instanceInit ($this);
    }

    public function failureMessage ($message)
    {
        $this->_failureMessage=$message;
        return $this;
    }

    public function validate ($data, $context=null)
    {
    }

    protected function failed ()
    {
        throw alpha (new _ValidationException ($this->_failureMessage));
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