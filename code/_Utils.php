<?php

class _Utils
{
    private static $__classAttributes = null;
    private static $objectInstance;


    public static function classAttributes ()
    {
        return _Utils::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _Utils::$objectInstance=null;
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ()
    {
        _Utils::__instanceInit ($this);
    }

    public static function getInstance ()
    {
        if (!_Utils::$objectInstance)
        {
            _Utils::$objectInstance=alpha (new _Utils ());
        }
        return _Utils::$objectInstance;
    }

    public static function Sleep ($milli)
    {
        return call_user_func('usleep',($milli*1000));
    }

    public static function Alt ($a, $b)
    {
        return ($a?$a:$b);
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
            case 'Rand':
                return _Math::rand();
            case 'CWD':
                return getcwd();
            case 'UUID':
                $tmp=explode(' ',microtime());
                mt_srand((($tmp[0])*10000));
                $id=strtolower(sha1(uniqid(mt_rand(),true)));
                return substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
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