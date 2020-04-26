<?php

class _Eax
{
    private static $__classAttributes = null;
    private static $consts;
    private $input;
    private $out;
    private $p;
    private $q;
    private $kw;
    private $level;
    private $kl;
    private $ki;
    private static $defContext;


    public static function classAttributes ()
    {
        return _Eax::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _Eax::$consts=alpha(_Array::fromNativeArray(array(67,53,419,709,373,101,401,761,997,739,641,313,61,83,59,769,1129,2969,4937,5743,7237,6571,8167,8713,8933,5179,3673,3727,3083,4817,4523,4507),false))->__nativeArray;
        _Eax::$defContext=alpha (new _Eax (null));
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    private static function rol ($v, $n, $m)
    {
        $v&=(((1<<$m))-1);
        return ((((($v<<$n))|(((($v>>(($m-$n))))&((((1<<$n))-1))))))&((((1<<$m))-1)));
    }

    private static function ror ($v, $n, $m)
    {
        return _Eax::rol($v,($m-$n),$m);
    }

    public function __construct ($keyword)
    {
        _Eax::__instanceInit ($this);
        $this->input=alpha(_Array::fromNativeArray(array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),false))->__nativeArray;
        $this->out=alpha(_Array::fromNativeArray(array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),false))->__nativeArray;
        $this->p=alpha(_Array::fromNativeArray(array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),false))->__nativeArray;
        $this->q=alpha(_Array::fromNativeArray(array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),false))->__nativeArray;
        if (($keyword!=null))
        {
            $this->reset($keyword);
        }
    }

    public function reset ($keyword)
    {
        $i=null;;
        $j=null;;
        $sumL=null;;
        $sumH=null;;
        $this->level=$this->ki=0;
        $this->kw=$keyword->__nativeArray;
        $this->kl=$keyword->length();
        for ($i=0; ($i<32); $i++)
        {
            $this->p[$i]=_Eax::$consts[$i];
            $this->q[$i]=_Eax::$consts[((32-$i)-1)];
        }
        if (($this->kl!=0))
        {
            for ($i=$j=0; ($i<$this->kl); $i++)
            {
                $j+=(3*$this->kw[$i]);
            }
        }
        else
        {
            $j=42279;
        }
        $sumH=((($j>>8))&255);
        $sumL=($j&255);
        for ($i=0; ($i<32); $i++)
        {
            $this->p[($i&31)]=(_Eax::ror(($this->p[$i]+$sumH),($sumH&5),16)-$this->p[((($i+1))&31)]);
            $this->q[($i&31)]=(_Eax::rol(($this->q[$i]+$this->q[((($i+1))&31)]),($sumL&3),16)-$sumL);
            $j=($this->p[$i]&$this->q[$i]);
            $sumH+=(($j&240));
            $sumL-=(($j&15));
        }
    }

    public function isInputFull ()
    {
        return ($this->level==32);
    }

    public function isInputEmpty ()
    {
        return ($this->level==0);
    }

    public function inputSpace ()
    {
        return (32-$this->level);
    }

    public function getOutput ()
    {
        return $this->out;
    }

    public function getInput ()
    {
        return $this->input;
    }

    public static function getOutputSize ($n)
    {
        return (((($n+32)-1))&-32);
    }

    public static function getBottomBytes ($n)
    {
        return (((($n&31))!=0)?(($n&31)):32);
    }

    public function encryptBlock ()
    {
        $i=null;;
        $k=null;;
        $k2=null;;
        if (($this->kl!=0))
        {
            for ($i=0; ($i<32); $i++)
            {
                $this->q[$i]=_Eax::rol(($this->q[$i]+$this->kw[$this->ki]),($this->kw[$this->ki]&3),16);
                $this->ki=(++$this->ki%$this->kl);
            }
        }
        for ($i=0; ($i<32); $i++)
        {
            $this->q[$i]=(_Eax::ror($this->q[$i],($this->q[$i]&5),16)^_Eax::rol($this->p[$i],($this->p[$i]&7),16));
        }
        for ($i=0; ($i<32); $i++)
        {
            $k=$this->input[$i];
            $k2=(_Eax::rol($k,4,8)^$this->p[$i]);
            $k2=($k2+_Eax::ror($this->q[((32-$i)-1)],($this->p[$i]&3),8));
            $k&=255;
            $k2&=255;
            $this->p[$i]^=(($k+$k2));
            $this->out[$i]=$k2;
        }
        $this->level=0;
        return $this->out;
    }

    public function decryptBlock ()
    {
        $i=null;;
        $k=null;;
        $k2=null;;
        if (($this->kl!=0))
        {
            for ($i=0; ($i<32); $i++)
            {
                $this->q[$i]=_Eax::rol(($this->q[$i]+$this->kw[$this->ki]),($this->kw[$this->ki]&3),16);
                $this->ki=(++$this->ki%$this->kl);
            }
        }
        for ($i=0; ($i<32); $i++)
        {
            $this->q[$i]=(_Eax::ror($this->q[$i],($this->q[$i]&5),16)^_Eax::rol($this->p[$i],($this->p[$i]&7),16));
        }
        for ($i=0; ($i<32); $i++)
        {
            $k2=$this->input[$i];
            $k=($k2-_Eax::ror($this->q[((32-$i)-1)],($this->p[$i]&3),8));
            $k=_Eax::ror(($k^$this->p[$i]),4,8);
            $k&=255;
            $k2&=255;
            $this->p[$i]^=(($k+$k2));
            $this->out[$i]=$k;
        }
        $this->level=0;
        return $this->out;
    }

    public function feed ($value)
    {
        if (($this->level==32))
        {
            return false;
        }
        $this->input[$this->level]=(((($value+256))&255));
        $this->level++;
        return true;
    }

    public static function encrypt ($keyword, $value, $rand)
    {
        _Eax::$defContext->reset(_Text::explode('',$keyword)->format('{f:fromByte:0}'));
        $b_dest='';
        $i=null;;
        $j=null;;
        $z=null;;
        $length=_Text::length($value);
        $s=($rand?_Math::rand():33);
        _Eax::$defContext->feed($s);
        for ($i=0; ($i<$length); $i++)
        {
            if (!_Eax::$defContext->feed(ord($value[$i])))
            {
                _Eax::$defContext->encryptBlock();
                $z=_Eax::$defContext->getOutput();
                for ($j=0; ($j<32); $j++)
                {
                    $b_dest.=chr($z[$j]);
                }
                _Eax::$defContext->feed(ord($value[$i]));
            }
        }
        if (!_Eax::$defContext->feed($s))
        {
            _Eax::$defContext->encryptBlock();
            $z=_Eax::$defContext->getOutput();
            for ($j=0; ($j<32); $j++)
            {
                $b_dest.=chr($z[$j]);
            }
            _Eax::$defContext->feed($s);
        }
        if ((_Eax::$defContext->inputSpace()==0))
        {
            _Eax::$defContext->encryptBlock();
            $z=_Eax::$defContext->getOutput();
            for ($j=0; ($j<32); $j++)
            {
                $b_dest.=chr($z[$j]);
            }
        }
        $s=0;
        while ((_Eax::$defContext->inputSpace()!=1))
        {
            _Eax::$defContext->feed(0);
            $s++;
        };
        _Eax::$defContext->feed($s);
        if (!_Eax::$defContext->isInputEmpty())
        {
            _Eax::$defContext->encryptBlock();
            $z=_Eax::$defContext->getOutput();
            for ($j=0; ($j<32); $j++)
            {
                $b_dest.=chr($z[$j]);
            }
        }
        return $b_dest;
    }

    public static function decrypt ($keyword, $value)
    {
        _Eax::$defContext->reset(_Text::explode('',$keyword)->format('{f:fromByte:0}'));
        $b_dest='';
        $i=null;;
        $j=null;;
        $z=null;;
        $length=_Text::length($value);
        for ($i=0; ($i<$length); $i++)
        {
            if (!_Eax::$defContext->feed(ord($value[$i])))
            {
                $z=_Eax::$defContext->decryptBlock();
                for ($j=0; ($j<32); $j++)
                {
                    $b_dest.=chr($z[$j]);
                }
                _Eax::$defContext->feed(ord($value[$i]));
            }
        }
        if (!_Eax::$defContext->isInputEmpty())
        {
            $z=_Eax::$defContext->decryptBlock();
            for ($j=0; ($j<32); $j++)
            {
                $b_dest.=chr($z[$j]);
            }
        }
        $s=_Convert::fromByte(_Text::substring($b_dest,-1));
        if ((($s<0)||($s>32)))
        {
            return '';
        }
        $b_dest=_Text::substring($b_dest,0,(-$s-1));
        if (($b_dest[0]!=$b_dest[(_Text::length($b_dest)-1)]))
        {
            return '';
        }
        return _Text::substring($b_dest,1,-1);
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