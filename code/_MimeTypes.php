<?php

class _MimeTypes
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _MimeTypes::$__classAttributes;

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
        _MimeTypes::__instanceInit ($this);
    }

    public static function fromExtension ($ext)
    {
        $ext=_Strings::getInstance ()->Mime->Extensions->{$ext};
        return ($ext?$ext:'application/octet-stream');
    }

    public static function fromFilename ($filename)
    {
        return _MimeTypes::fromExtension(_File::getInstance ()->extension($filename));
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