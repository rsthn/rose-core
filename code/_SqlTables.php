<?php

class _SqlTables extends _SqlTable
{
    private static $__classAttributes = null;
    private static $objectInstance;


    public static function classAttributes ()
    {
        return _SqlTables::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _SqlTables::$objectInstance=null;
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public static function getInstance ()
    {
        if ((_SqlTables::$objectInstance==null))
        {
            _SqlTables::$objectInstance=alpha (new _SqlTables ());
        }
        return _SqlTables::$objectInstance;
    }

    public function __construct ()
    {
        _SqlTables::__instanceInit ($this);
        parent::__construct(null,_Resources::getInstance ()->sqlConn);
    }

    public function table ($name)
    {
        $this->table=$name;
        return $this;
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
            default:
                return $this->table($gsprn);
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