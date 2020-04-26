<?php

class _GSql
{
    private static $__classAttributes = null;
    private static $objectInstance;


    public static function classAttributes ()
    {
        return _GSql::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _GSql::$objectInstance=alpha (new _GSql ());
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public static function getInstance ()
    {
        return _GSql::$objectInstance;
    }

    private function __construct ()
    {
        _GSql::__instanceInit ($this);
        _Resources::getInstance ()->registerConstructor('sqlConn',$this,'buildConnection');
    }

    public function buildConnection ()
    {
        return _SqlConnection::fromConfig(_Configuration::getInstance ()->GSql);
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