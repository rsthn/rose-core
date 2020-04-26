<?php

class _StyledPage extends _Page
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _StyledPage::$__classAttributes;

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
        parent::__destruct ();
    }

    public function __construct ($layout=null)
    {
        parent::__construct ();
        _StyledPage::__instanceInit ($this);
        if ((($layout===false)||($layout===null)))
        {
            return ;
        }
        _UIElement::loadFrom('styler/'.$layout,$this,true);
    }

    public function selectMenuItem ($menu, $id, $elem='li')
    {
        if (!$this->hasMember($menu))
        {
            return ;
        }
        $item=$this->{$menu}->select($elem.'[local:id=\'/'.$id.'/\']',false,true);
        if (($item!=null))
        {
            if (($item->attribute('class')!=null))
            {
                $item->getAttribute('class')->value.=' active';
            }
            else
            {
                $item->setAttribute(alpha (new _XmlAttribute ('class','active')));
            }
        }
    }

    public function loadContent ($path, $target='panel', $order='', $overrideParent=false)
    {
        $path='resources/content/'.$path;
        if (($order=='first'))
        {
            _UIElement::loadFrom($path,$this->{$target},$overrideParent,'prependChild');
        }
        else
        {
            if (($order=='last'))
            {
                _UIElement::loadFrom($path,$this->{$target},$overrideParent,'appendChild');
            }
            else
            {
                $this->{$target}->loadContent($path,$overrideParent);
            }
        }
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

}