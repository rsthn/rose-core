<?php

class _ZipEntry
{
    private static $__classAttributes = null;
    public $type;
    public $name;
    public $data;
    public $comment;


    public static function classAttributes ()
    {
        return _ZipEntry::$__classAttributes;

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

    public function __construct ($name='', $data='', $comment='')
    {
        _ZipEntry::__instanceInit ($this);
        $this->type=(((!$name||(_Text::substring($name,-1)=='/')))?1:0);
        $this->data=($this->type?alpha (new _Array ()):$data);
        $this->name=$name;
        $this->comment=$comment;
    }

    public function add ($entry)
    {
        $this->data->push($entry);
        return $this;
    }

    public function store ($target, $path='')
    {
        $fullName=$path.$this->name;
        if (($this->type==0))
        {
            return $target->addEntry(0,$fullName,$this->data,$this->comment);
        }
        if (($this->name!=null))
        {
            $target->addEntry(1,$fullName);
        }
        foreach($this->data->__nativeArray as $entry)
        {
            $entry->store($target,$fullName);
        }
        return $target;
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