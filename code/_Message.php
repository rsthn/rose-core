<?php

class _Message
{
    private static $__classAttributes = null;
    private $message;
    private $cssClass;


    public static function classAttributes ()
    {
        return _Message::$__classAttributes;

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

    public function __construct ($cssClass, $message)
    {
        _Message::__instanceInit ($this);
        $this->cssClass=$cssClass;
        $this->message=$message;
    }

    public function __typeCast ()
    {
        return '<span class=\''.$this->cssClass.'\'>'.$this->message.'</span>';
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