<?php

class _EvalPHP extends _UIElement
{
    private static $__classAttributes = null;
    private static $elementId;


    public static function classAttributes ()
    {
        return _EvalPHP::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _EvalPHP::$elementId=_UIElementLoader::register('EvalPHP');
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ()
    {
        parent::__construct ();
        _EvalPHP::__instanceInit ($this);
    }

    public function epilogue ()
    {
        if (($this->attribute('immediate','local')=='true'))
        {
            eval($this->plainContent());
        }
    }

    public function asXml ($indent='', $scope='def')
    {
        if (($this->attribute('immediate','local')!='true'))
        {
            ob_start();
            eval($this->plainContent());
            return ob_get_clean();
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