<?php

class _StaticPage extends __DualPage
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _StaticPage::$__classAttributes;

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
        _StaticPage::__instanceInit ($this);
        STLOC()->MENU()->PANEL('static/'._Regex::stExtract('|[-/A-Za-z0-9]+|',_Gateway::getInstance ()->RQP->p));
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