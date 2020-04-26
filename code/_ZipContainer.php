<?php

class _ZipContainer
{
    private static $__classAttributes = null;
    private $zipEntries;
    private $numEntries;
    private $zipDir;
    private $cMethod;
    private $zipComment;


    public static function classAttributes ()
    {
        return _ZipContainer::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->zipEntries='';
        $__this__->numEntries=0;
        $__this__->zipDir='';
        $__this__->cMethod=0;
        $__this__->zipComment='';
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
        _ZipContainer::__instanceInit ($this);
    }

    public function save ($target)
    {
        _File::putContents($target,$this->data());
        return $this;
    }

    public function comment ($value)
    {
        $this->zipComment=$value;
        return $this;
    }

    public function useMethod ($method)
    {
        switch ($method)
        {
            case 'DEFLATE':
            $this->cMethod=8;
            break;
            case 'STORE':
            default:
            $this->cMethod=0;
            break;
        }
        return $this;
    }

    public function data ()
    {
        $zipDirEnd='';
        $zipDirEnd.=_Convert::toDword(101010256);
        $zipDirEnd.=_Convert::toWord(0);
        $zipDirEnd.=_Convert::toWord(0);
        $zipDirEnd.=_Convert::toWord($this->numEntries);
        $zipDirEnd.=_Convert::toWord($this->numEntries);
        $zipDirEnd.=_Convert::toDword(_Text::length($this->zipDir));
        $zipDirEnd.=_Convert::toDword(_Text::length($this->zipEntries));
        $zipDirEnd.=_Convert::toWord(_Text::length($this->zipComment));
        $zipDirEnd.=$this->zipComment;
        return $this->zipEntries.$this->zipDir.$zipDirEnd;
    }

    public function addEntry ($type, $name, $data='', $comment='')
    {
        $b1='';
        $b2='';
        $crc32=0;
        $cData=$data;
        if (!$type)
        {
            $crc32=_Convert::filter('crc32',$data);
            if (($this->cMethod==8))
            {
                $cData=_Convert::toDeflate($data);
            }
        }
        $b1.=_Convert::toDword(67324752);
        $b1.=_Convert::toWord(20);
        $b1.=_Convert::toWord(2048);
        $b1.=_Convert::toWord(($type?0:$this->cMethod));
        $b1.=_Convert::toWord(0);
        $b1.=_Convert::toWord(0);
        $b1.=_Convert::toDword($crc32);
        $b1.=_Convert::toDword(_Text::length($cData));
        $b1.=_Convert::toDword(_Text::length($data));
        $b1.=_Convert::toWord(_Text::length($name));
        $b1.=_Convert::toWord(0);
        $b1.=$name;
        $b1.=$cData;
        $b2.=_Convert::toDword(33639248);
        $b2.=_Convert::toWord(82);
        $b2.=_Convert::toWord(20);
        $b2.=_Convert::toWord(2048);
        $b2.=_Convert::toWord(($type?0:$this->cMethod));
        $b2.=_Convert::toWord(0);
        $b2.=_Convert::toWord(0);
        $b2.=_Convert::toDword($crc32);
        $b2.=_Convert::toDword(_Text::length($cData));
        $b2.=_Convert::toDword(_Text::length($data));
        $b2.=_Convert::toWord(_Text::length($name));
        $b2.=_Convert::toWord(0);
        $b2.=_Convert::toWord(_Text::length($comment));
        $b2.=_Convert::toWord(0);
        $b2.=_Convert::toWord(0);
        $b2.=_Convert::toDword(0);
        $b2.=_Convert::toDword(_Text::length($this->zipEntries));
        $b2.=$name;
        $b2.=$comment;
        $this->numEntries++;
        $this->zipEntries.=$b1;
        $this->zipDir.=$b2;
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