<?php

class _TcpConnection
{
    private static $__classAttributes = null;
    protected $_socket;
    protected $_stream;
    protected $_hostname;
    protected $_port;


    public static function classAttributes ()
    {
        return _TcpConnection::$__classAttributes;

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

    public function __construct ($hostname, $port, $timeout=30)
    {
        _TcpConnection::__instanceInit ($this);
        $this->_stream=alpha (new _DataStream ($this->_socket=alpha (new _Socket ($this->_hostname=$hostname,$this->_port=$port,'tcp',$timeout))));
    }

    public function connected ()
    {
        return ($this->_socket->errno==0);
    }

    public function stream ()
    {
        return $this->_stream;
    }

    public function hostname ()
    {
        return $this->_hostname;
    }

    public function port ()
    {
        return $this->_port;
    }

    public function socket ()
    {
        return $this->_socket;
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