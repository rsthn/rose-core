<?php

class _SException extends _Exception
{
    private static $__classAttributes = null;
    private $descriptor;


    public static function classAttributes ()
    {
        return _SException::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->descriptor=null;
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ($message='', $code=0)
    {
        _SException::__instanceInit ($this);
        parent::__construct($message,$code);
    }

    public function setDescriptor ($descriptor)
    {
        $this->descriptor=$descriptor;
        return $this;
    }

    public function getDescriptor ()
    {
        return $this->descriptor;
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