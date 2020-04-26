<?php

class _PackContainer
{
    private static $__classAttributes = null;
    private $_data;


    public static function classAttributes ()
    {
        return _PackContainer::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->_data='';
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
        _PackContainer::__instanceInit ($this);
    }

    public static function extractFromBuffer ($buffer, $output)
    {
        $offs=0;
        $v=null;;
        $v=_Convert::fromWord(_Text::substring($buffer,(($offs+=2)-2),2));
        if (($v!=36933))
        {
            return 300;
        }
        $len=_Convert::fromDword(_Text::substring($buffer,(($offs+=4)-4),4));
        $v=_Convert::fromDword(_Text::substring($buffer,(($offs+=4)-4),4));
        if ((($len+1)!=(_Text::length($buffer)-$offs)))
        {
            return 300;
        }
        if (($v!=_Convert::filter('crc32',_Text::substring($buffer,$offs,$len))))
        {
            return 301;
        }
        if ((_Convert::fromByte(_Text::substring($buffer,-1,1))!=130))
        {
            return 300;
        }
        while (true)
        {
            $v=_Convert::fromByte(_Text::substring($buffer,(($offs+=1)-1),1));
            if (($v==130))
            {
                break;
            }
            if ((($v!=128)&&($v!=129)))
            {
                return 302;
            }
            $len=_Convert::fromWord(_Text::substring($buffer,(($offs+=2)-2),2));
            $name=_Text::substring($buffer,(($offs+=$len)-$len),$len);
            if (($v==129))
            {
                $v=_Convert::fromDword(_Text::substring($buffer,(($offs+=4)-4),4));
                $len=_Convert::fromDword(_Text::substring($buffer,(($offs+=4)-4),4));
                $s=_Convert::fromDeflate(_Text::substring($buffer,(($offs+=$len)-$len),$len));
                if (($v!=_Convert::filter('crc32',$s)))
                {
                    return 301;
                }
                _File::putContents($output.$name,$s);
            }
            else
            {
                _Directory::create($output.$name,true);
            }
        };
        return true;
    }

    public function save ($target)
    {
        _File::putContents($target,$this->data());
        return $this;
    }

    public function data ()
    {
        return _Convert::toWord(36933)._Convert::toDword(_Text::length($this->_data))._Convert::toDword(_Convert::filter('crc32',$this->_data)).$this->_data._Convert::toByte(130);
    }

    public function addEntry ($type, $name, $data='', $comment='')
    {
        if ($type)
        {
            $this->_data.=_Convert::toByte(128);
            $this->_data.=_Convert::toWord(_Text::length($name));
            $this->_data.=$name;
            return $this;
        }
        $crc32=_Convert::filter('crc32',$data);
        $data=_Convert::toDeflate($data);
        $this->_data.=_Convert::toByte(129);
        $this->_data.=_Convert::toWord(_Text::length($name));
        $this->_data.=$name;
        $this->_data.=_Convert::toDword($crc32);
        $this->_data.=_Convert::toDword(_Text::length($data));
        $this->_data.=$data;
        return $this;
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