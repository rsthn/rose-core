<?php

class _Angela
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _Angela::$__classAttributes;

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

    public function __construct ()
    {
        _Angela::__instanceInit ($this);
    }

    public static function encode ($data, $keyword=null, $dynamicVals=false)
    {
        if (($keyword==null))
        {
            $keyword=_Configuration::getInstance ()->Angela->keyword;
        }
        $salt=_Convert::toByte(($dynamicVals?((_Math::rand()&255)):'~'));
        $data=$salt.$data.$salt;
        $n=(_Text::length($data)+1);
        $m=((((($n+3))&-4))>>2);
        $i=null;;
        $j=null;;
        $k=null;;
        $dest='';
        $j=((($m<<2))-$n);
        for ($i=0; ($i<$j); $i++)
        {
            $data.=_Convert::toByte(($dynamicVals?((_Math::rand()&255)):'#'));
        }
        $data.=_Convert::toByte($j);
        $key=_Text::explode('',$keyword);
        foreach($key->__nativeArray as &$value)
        {
            $value=_Convert::fromByte($value);
        }
        unset ($value);
        $p=0;
        $kL=($key->length()-1);
        for ($i=0; ($i<=$kL); $i++)
        {
            $p=_Convert::toUnsigned(($p+(3*$key->arrayGetElement ($i))),16);
        }
        $sH=_Convert::toUnsigned(($p>>8));
        $sL=_Convert::toUnsigned($p);
        $kP=0;
        $bP=0;
        while ($m--)
        {
            $value=0;
            for ($i=0; ($i<4); $i++)
            {
                $k=_Convert::toUnsigned(_Convert::fromByte($data,$bP++));
                $temp=0;
                for ($j=0; ($j<8); $j++)
                {
                    $temp|=((((((($value&15))<<1))|(($k&1))))<<(($j<<2)));
                    $value>>=4;
                    $k>>=1;
                }
                $value=$temp;
            }
            for ($i=0; ($i<4); $i++)
            {
                $k=_Convert::toUnsigned(((($value&4278190080))>>24));
                $value<<=8;
                if (($kP>$kL))
                {
                    $kP=0;
                }
                if (($i&1))
                {
                    $k=(_Convert::toSigned(($k^$sL),16)+$key->arrayGetElement ($kP));
                    $key->arraySetElement ($kP,_Convert::toUnsigned((($key->arrayGetElement ($kP)+$sH)-$k)));
                    $kP++;
                }
                else
                {
                    $k=(_Convert::toSigned(($k-$sH),16)^$key->arrayGetElement ($kP));
                    $key->arraySetElement ($kP,_Convert::toUnsigned((($key->arrayGetElement ($kP)+$sL)+$k)));
                    $kP++;
                }
                $sH=_Convert::toUnsigned(($sH+(($k&240))));
                $sL=_Convert::toUnsigned(($sL-(($k&15))));
                $dest.=_Convert::toByte($k=_Convert::toSigned($k));
            }
        };
        return $dest;
    }

    public static function decode ($data, $keyword=null)
    {
        if (($keyword==null))
        {
            $keyword=_Configuration::getInstance ()->Angela->keyword;
        }
        $i=null;;
        $j=null;;
        $k=null;;
        $t=null;;
        $u=null;;
        $tmp=alpha (new _Array ());
        $dest='';
        $n=_Text::length($data);
        $m=(_Text::length($data)>>2);
        $key=_Text::explode('',$keyword);
        foreach($key->__nativeArray as &$value)
        {
            $value=_Convert::fromByte($value);
        }
        unset ($value);
        $p=0;
        $kL=($key->length()-1);
        for ($i=0; ($i<=$kL); $i++)
        {
            $p=_Convert::toUnsigned(($p+(3*$key->arrayGetElement ($i))),16);
        }
        $sH=_Convert::toUnsigned(($p>>8));
        $sL=_Convert::toUnsigned($p);
        $kP=0;
        $bP=0;
        for ($i=0; ($i<$m); $i++)
        {
            for ($j=0; ($j<4); $j++)
            {
                $tmp->arraySetElement ($j,0);
            }
            for ($j=$t=0; ($j<4); $j++)
            {
                $lt=null;;
                $rt=null;;
                $lt=$rt=_Convert::toUnsigned(_Convert::fromByte($data,$bP++));
                if (($kP>$kL))
                {
                    $kP=0;
                }
                $u=$key->arrayGetElement ($kP);
                if (($j&1))
                {
                    $key->arraySetElement ($kP,(($key->arrayGetElement ($kP)+$sH)-$lt));
                    $kP++;
                    $lt=_Convert::toSigned(((($lt-$u))^$sL),16);
                }
                else
                {
                    $key->arraySetElement ($kP,(($key->arrayGetElement ($kP)+$sL)+$lt));
                    $kP++;
                    $lt=_Convert::toSigned(((($lt^$u))+$sH),16);
                }
                $sH=_Convert::toUnsigned(($sH+(($rt&240))));
                $sL=_Convert::toUnsigned(($sL-(($rt&15))));
                $rt=$lt;
                for ($k=0; ($k<4); $k++)
                {
                    $tmp->arraySetElement ((3-$k),($tmp->arrayGetElement ((3-$k))|(((($rt&1))<<((6-$t))))));
                    $tmp->arraySetElement ($k,($tmp->arrayGetElement ($k)|(((($lt&128))>>$t))));
                    $lt<<=1;
                    $rt>>=1;
                }
                $t+=2;
            }
            for ($k=0; ($k<4); $k++)
            {
                $dest.=_Convert::toByte($tmp->arrayGetElement ($k));
            }
        }
        $salt=_Text::substring($dest,0,1);
        $i=(_Convert::fromByte(_Text::substring($dest,-1,1))+1);
        $dest=_Text::substring($dest,0,(_Text::length($dest)-$i));
        if ((_Text::substring($dest,-1,1)!=$salt))
        {
            return null;
        }
        return _Text::substring($dest,1,-1);
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