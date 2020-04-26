<?php

class _R
{
    private static $__classAttributes = null;
    private static $objectInstance;


    public static function classAttributes ()
    {
        return _R::$__classAttributes;

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
    }

    public function __construct ()
    {
        _R::__instanceInit ($this);
    }

    public static function getInstance ()
    {
        if ((_R::$objectInstance==null))
        {
            _R::$objectInstance=alpha (new _R ());
        }
        return _R::$objectInstance;
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
            default:
                return _Resources::getInstance ()->{$gsprn};
        }

        if (method_exists (get_parent_class (), '__get')) return parent::__get ($gsprn);
        throw new _UndefinedProperty ($gsprn);
    }

    public function __set ($gsprn, $sprv)
    {
        switch ($gsprn)
        {
            default:
                _Resources::getInstance ()->{$gsprn}=$sprv;
        }
        if (method_exists (get_parent_class (), '__set')) parent::__set ($gsprn, $sprv);
    }

    public function __toString ()
    {
        return $this->__typeCast('String');
    }

}