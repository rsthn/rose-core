<?php

class _Html
{
    private static $__classAttributes = null;
    public $tagName;
    public $attributes;
    public $style;
    private $content;


    public static function classAttributes ()
    {
        return _Html::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->attributes=alpha (new _Map ());
        $__this__->style=alpha (new _Map ());
        $__this__->content=alpha (new _Array ());
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ($tagName=null)
    {
        _Html::__instanceInit ($this);
        $this->tagName=$tagName;
    }

    public static function create ($tagName)
    {
        return alpha (new _Html ($tagName));
    }

    public function append ($element)
    {
        $this->content->add($element);
        return $this;
    }

    public function clear ()
    {
        $this->content->clear();
        return $this;
    }

    public function length ()
    {
        return $this->content->length();
    }

    public function text ($value, $clear=false)
    {
        if ($clear)
        {
            $this->clear();
        }
        $this->content->add($value);
        return $this;
    }

    public function prepend ($element)
    {
        $this->content->unshift($element);
        return $this;
    }

    public function attrFromXmlElement ($obj, $rData=null)
    {
        foreach($obj->attributes()->__nativeArray as $attribute)
        {
            $this->attributes->setElement($attribute->name(),_Text::format($attribute->value,$rData));
        }
        return $this;
    }

    public function attr ($name, $value=null)
    {
        if (($value===null))
        {
            return $this->attributes->getElement($name);
        }
        $this->attributes->setElement($name,$value);
        return $this;
    }

    public function css ($name, $value=null)
    {
        if (($value===null))
        {
            return $this->style->getElement($name);
        }
        $this->style->setElement($name,$value);
        return $this;
    }

    public function __toString ()
    {
        if (($this->tagName==null))
        {
            return $this->content->implode();
        }
        return sprintf('<%s %s%s>%s</%s>',$this->tagName,$this->attributes->format('{0}={filter:cescape:1}')->implode(' '),($this->style->length()?(" style=\"".$this->style->format('{0}:{1}')->implode(';')."\""):''),$this->content->implode(),$this->tagName);
    }

    public function arrayGetElement ($index)
    {
        return $this->content->arrayGetElement ($index);
    }

    public function arraySetElement ($index, $value)
    {
        $this->content->arraySetElement ($index,$value);
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

}