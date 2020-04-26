<?php

class _ClassInterface
{
    private static $__classAttributes = null;
    private $className;
    private $object;


    public static function classAttributes ()
    {
        return _ClassInterface::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->object=null;
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ($className)
    {
        _ClassInterface::__instanceInit ($this);
        $this->className=$className;
    }

    public static function fromObject ($object)
    {
        return alpha (new _ClassInterface (typeOf($object)))->bind($object);
    }

    public function bind ($object=null)
    {
        $this->object=((($object===null)?$this->construct():$object));
        return $this;
    }

    public function construct ($args)
    {
        return __instantiate($this->className,$args);
    }

    public static function newInstanceOf ($className, $args)
    {
        return __instantiate($className,$args);
    }

    public function getProperty ($propertyName)
    {
        if (($this->object==null))
        {
            throw alpha (new _ArgumentException ('Object instance not bind.'));
        }
        return readProperty($this->object,$propertyName);
    }

    public function getStaticProperty ($propertyName)
    {
        return readStaticProperty($this->className,$propertyName);
    }

    public function setProperty ($propertyName, $value)
    {
        if (($this->object==null))
        {
            throw alpha (new _ArgumentException ('Object instance not bind.'));
        }
        return writeProperty($this->object,$propertyName,$value);
    }

    public function setStaticProperty ($propertyName, $value)
    {
        return writeStaticProperty($this->className,$propertyName,$value);
    }

    public function invoke ($methodName)
    {
        $args = _Array::fromNativeArray (func_get_args (), false)->slice (1);
        if (($this->object==null))
        {
            throw alpha (new _ArgumentException ('Object instance not bind.'));
        }
        return invokeMethod($this->object,$methodName,$args);
    }

    public function pinvoke ($methodName, $args)
    {
        if (($this->object==null))
        {
            throw alpha (new _ArgumentException ('Object instance not bind.'));
        }
        return invokeMethod($this->object,$methodName,$args);
    }

    public function invokeStatic ($methodName)
    {
        $args = _Array::fromNativeArray (func_get_args (), false)->slice (1);
        return invokeStaticMethod($this->className,$methodName,$args);
    }

    public function getClassAttributes ()
    {
        return invokeStaticMethod($this->className,'classAttributes',null);
    }

    public function getClassAttributeOf ($name)
    {
        $attrs=$this->getClassAttributes();
        if (($attrs==null))
        {
            return null;
        }
        return $attrs->getElement($name);
    }

    public function rootAttributesFor ($name)
    {
        $attrs=$this->getClassAttributes();
        if (($attrs==null))
        {
            return _Map::$emptyMap;
        }
        $attrs=$attrs->getElement('__construct');
        if (!$attrs)
        {
            return _Map::$emptyMap;
        }
        $attrs=$attrs->getElement($name);
        if (!$attrs)
        {
            return _Map::$emptyMap;
        }
        return $attrs;
    }

    public static function stRootAttributesFor ($name, $className)
    {
        $attrs=invokeStaticMethod($className,'classAttributes',null);
        if (($attrs==null))
        {
            return _Map::$emptyMap;
        }
        $attrs=$attrs->getElement('__construct');
        if (!$attrs)
        {
            return _Map::$emptyMap;
        }
        $attrs=$attrs->getElement($name);
        if (!$attrs)
        {
            return _Map::$emptyMap;
        }
        return $attrs;
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