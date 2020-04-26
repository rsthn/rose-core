<?php

class _Page extends _UIElement
{
    private static $__classAttributes = null;
    protected $disposeContent;
    public $cleanContent;
    public static $Header;


    public static function classAttributes ()
    {
        return _Page::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->disposeContent=false;
        $__this__->cleanContent=false;
    }

    public static function __classInit ()
    {
        _Page::$Header="<!DOCTYPE html>\n";
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function isRoot ()
    {
        return true;
    }

    public function __construct ()
    {
        parent::__construct ();
        _Page::__instanceInit ($this);
        _Gateway::getInstance ()->currentPage=$this;
    }

    public function dispose ()
    {
        $this->disposeContent=true;
    }

    public static function format ($body)
    {
        $root=_Configuration::getInstance ()->General->root;
        if (_Configuration::getInstance ()->Locale->lang)
        {
            $body=_Text::replace('////',$root._Gateway::getInstance ()->requestParams->lang.'/',$body);
        }
        else
        {
            $body=_Text::replace('////',$root,$body);
        }
        return _Text::replace('///',$root,$body);
    }

    public function flush ($returnContents=false)
    {
        if ($this->disposeContent)
        {
            return '';
        }
        $content=$this->asXml();
        $output=_Page::format($content);
        if (!$returnContents)
        {
            $size=strlen($output);
            if (!$this->cleanContent)
            {
                $size+=strlen(_Page::$Header);
            }
            header('Content-Length: '.$size);
        }
        if (!$this->cleanContent)
        {
            if ($returnContents)
            {
                $this->disposeContent=1;
                return _Page::format(_Page::$Header.$content);
            }
            echo(_Page::$Header);
        }
        if ($returnContents)
        {
            $this->disposeContent=1;
            return $output;
        }
        echo($output);
        $this->disposeContent=1;
    }

    public function initialize ()
    {
        return $this;
    }

    public function run ()
    {
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