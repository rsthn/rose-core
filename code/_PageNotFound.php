<?php

class _PageNotFound extends __DualPage
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _PageNotFound::$__classAttributes;

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

    public function __construct ()
    {
        parent::__construct ();
        _PageNotFound::__instanceInit ($this);
        $this->HEADER('HTTP/1.1 404 Not Found');
        $this->PANEL('404/index.xhtml');
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