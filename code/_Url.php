<?php

class _Url
{
    private static $__classAttributes = null;
    private static $regex;
    private $url;


    public static function classAttributes ()
    {
        return _Url::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _Url::$regex='|^([a-z]+://)?([-A-Za-z0-9._]+)(:[0-9]+)?(/([^?]+/)?)([^?]+)?(.*)|';
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ($url)
    {
        _Url::__instanceInit ($this);
        $this->url=$url;
    }

    public function full ($url=null)
    {
        return ($url?$url:$this->url);
    }

    public function protocol ($url=null)
    {
        return _Regex::getString('|^([a-z]+)://|',($url?$url:$this->url),1);
    }

    public function host ($url=null)
    {
        return _Regex::getString('|^([a-z]+://)?([-A-Za-z0-9._]+)|',($url?$url:$this->url),2);
    }

    public function port ($url=null)
    {
        return _Text::substring(_Regex::getString(_Url::$regex,($url?$url:$this->url),3),1);
    }

    public function root ($url=null)
    {
        return _Regex::getString(_Url::$regex,($url?$url:$this->url),4);
    }

    public function resource ($url=null)
    {
        return _Regex::getString(_Url::$regex,($url?$url:$this->url),6);
    }

    public function query ($url=null)
    {
        return _Regex::getString(_Url::$regex,($url?$url:$this->url),7);
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