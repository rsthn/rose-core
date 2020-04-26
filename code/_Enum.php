<?php

class _Enum extends _Map
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _Enum::$__classAttributes;

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
        _Enum::__instanceInit ($this);
    }

    public static function fromMap ($map)
    {
        $newEnum=alpha (new _Enum ());
        $prev=-1;
        foreach($map->__nativeArray as $name=>$value)
        {
            if (($value===null))
            {
                $value=($prev+1);
            }
            $prev=$value;
            $newEnum->setElement($name,$value);
        }
        unset ($name);
        return $newEnum;
    }

    public function getName ($value)
    {
        return $this->indexOf($value);
    }

    public function getNames ()
    {
        return $this->elements();
    }

    public function getValues ()
    {
        return $this->values();
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