<?php

class _StreamDescriptor
{
    private static $__classAttributes = null;
    protected $_desc;


    public static function classAttributes ()
    {
        return _StreamDescriptor::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->_desc=null;
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __construct ($desc=null)
    {
        _StreamDescriptor::__instanceInit ($this);
        $this->_desc=$desc;
    }

    public function __destruct ()
    {
        $this->close();
    }

    public function isOpen ()
    {
        return (($this->_desc!=null)?true:false);
    }

    public function close ()
    {
        if (!$this->isOpen())
        {
            return ;
        }
        fclose($this->_desc);
        $this->_desc=null;
    }

    public function readBytes ($numBytes)
    {
        if (!$this->isOpen())
        {
            return null;
        }
        $result='';
        if (($numBytes===null))
        {
            while (!$this->eof())
            {
                $block=fread($this->_desc,512);
                $result.=$block;
            };
            return $result;
        }
        if (!$numBytes)
        {
            return '';
        }
        while (($numBytes&&!$this->eof()))
        {
            $block=fread($this->_desc,$numBytes);
            $result.=$block;
            $numBytes-=strlen($block);
        };
        return $result;
    }

    public function writeBytes ($buffer, $numBytes=0)
    {
        return ($this->isOpen()?fwrite($this->_desc,$buffer,(!$numBytes?strlen($buffer):$numBytes)):0);
    }

    public function eof ()
    {
        return ($this->isOpen()?feof($this->_desc):true);
    }

    public function copyToStream ($s)
    {
        return stream_copy_to_stream($this->_desc,$s->_desc);
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