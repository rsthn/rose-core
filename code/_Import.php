<?php

class _Import extends _UIElement
{
    private static $__classAttributes = null;
    private static $elementId;


    public static function classAttributes ()
    {
        return _Import::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _Import::$elementId=_UIElementLoader::register('Import');
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ()
    {
        parent::__construct ();
        _Import::__instanceInit ($this);
    }

    public function asXml ($indent='', $scope='def')
    {
        $file='resources/blocks/'._Text::format($this->attribute('src'));
        if (_File::exists($file))
        {
            return _Text::format(_File::getContents($file));
        }
        return '';
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