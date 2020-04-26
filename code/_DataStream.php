<?php

class _DataStream
{
    private static $__classAttributes = null;
    protected $_desc;


    public static function classAttributes ()
    {
        return _DataStream::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __construct ($desc=null)
    {
        _DataStream::__instanceInit ($this);
        $this->_desc=$desc;
    }

    public function __destruct ()
    {
        $this->close();
    }

    public function descriptor ()
    {
        return $this->_desc;
    }

    public function readBytes ($numBytes=null)
    {
        return $this->_desc->readBytes($numBytes);
    }

    public function close ()
    {
        if ((($this->_desc!=null)&&$this->_desc->isOpen()))
        {
            $this->_desc->close();
        }
    }

    public function readByte ()
    {
        return $this->readBytes(1);
    }

    public function readLine ()
    {
        $result='';
        while (!$this->eof())
        {
            $byte=$this->readBytes(1);
            if (($byte=="\n"))
            {
                break;
            }
            if ((ord($byte)<32))
            {
                continue;
            }
            $result.=$byte;
        };
        return $result;
    }

    public function writeBytes ($buffer, $numBytes=0)
    {
        return $this->_desc->writeBytes($buffer,$numBytes);
    }

    public function writeByte ($value)
    {
        return (($this->writeBytes($value,1)==1)?true:false);
    }

    public function writeLine ($data)
    {
        $this->writeBytes($data."\r\n");
    }

    public function eof ()
    {
        return $this->_desc->eof();
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