<?php

class _SystemService
{
    private static $__classAttributes = null;
    protected $resultContentType;


    public static function classAttributes ()
    {
        return _SystemService::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->resultContentType=null;
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function setContentType ($type)
    {
        $this->resultContentType=$type;
    }

    public function reply ($type, $data)
    {
        $this->header('Content-Type: '.(($this->resultContentType?$this->resultContentType:$type)));
        $this->header('Content-Length: '._Text::length($data));
        echo($data);
    }

    public function replyFile ($type, $filePath)
    {
        $this->header('Content-Type: '.(($this->resultContentType?$this->resultContentType:$type)));
        if (_File::exists($filePath))
        {
            $this->header('Content-Length: '._File::getInstance ()->size($filePath));
        }
        _File::getInstance ()->writeToStdout($filePath);
    }

    public function __construct ($serviceName)
    {
        _SystemService::__instanceInit ($this);
        _Gateway::getInstance ()->registerService($serviceName,$this);
    }

    public function execute ()
    {
        $result=$this->main();
        if (($result==null))
        {
            return ;
        }
        if ((((typeOf($result)=='Xml')||(typeOf($result)=='XmlElement'))||($result[0]=='<')))
        {
            $this->reply('text/xml; charset=UTF-8',$result);
        }
        else
        {
            if (($result[0]=='{'))
            {
                $this->reply('application/json; charset=UTF-8',$result);
            }
            else
            {
                $this->reply('text/plain; charset=UTF-8',$result);
            }
        }
    }

    public function main ($internallyCalled=false)
    {
        return null;
    }

    public function error ($message)
    {
        trace(typeOf($this).': '.$message);
    }

    public function header ($value)
    {
        _Gateway::header($value);
    }

    public function write ($value)
    {
        echo($value);
    }

    public function writeLine ($value)
    {
        echo($value."\r\n");
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