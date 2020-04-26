<?php

class _Api extends _BluxApiService
{
    private static $__classAttributes = null;
    public static $instance;


    public static function classAttributes ()
    {
        return _Api::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _Api::$instance=alpha (new _Api ());
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    protected function RT_Connect ($action, $data)
    {
        $user=$this->attr('user',$action,$data);
        $scope=$this->attr('scope',$action,$data);
        if (!$scope)
        {
            $scope='default';
        }
        if (!$user)
        {
            $user=_Session::getInstance ()->CurrentUser->user_id;
        }
        $dir=_RT_Api::RT_Directory($scope);
        if (!_Directory::exists($dir))
        {
            return _Convert::toJson(_Map::fromNativeArray(array('response'=>504),false));
        }
        $o=_RT_Api::$instance->RT_StartListener($dir,$user);
        if (($o!=null))
        {
            return _Convert::toJson($o);
        }
        return false;
    }

    protected function RT_Status ($action, $data)
    {
        $user=$this->attr('user',$action,$data);
        $dir=_RT_Api::RT_Directory(($action->hasAttribute('scope')?$this->attr('scope',$action,$data):'default'));
        if (!$user)
        {
            $user=_Session::getInstance ()->CurrentUser->user_id;
        }
        $data->arraySetElement ($this->attr('field',$action,$data),(_RT_Api::RT_IsConnected($dir,$user)?'1':'0'));
        return false;
    }

    protected function RT_Push ($action, $data)
    {
        $user=$this->attr('user',$action,$data);
        $dir=_RT_Api::RT_Directory(($action->hasAttribute('scope')?$this->attr('scope',$action,$data):'default'));
        if (!$user)
        {
            $user=_Session::getInstance ()->CurrentUser->user_id;
        }
        $m='RT_Data('._Convert::toJson($action->toMap($data->formData,$data)).');';
        if (($this->attr('trace',$action,$data)=='true'))
        {
            trace($m);
        }
        if (($this->attr('escape',$action,$data)=='true'))
        {
            $m=_Text::replace('<','&lt;',$m);
            $m=_Text::replace('>','&gt;',$m);
        }
        _RT_Api::RT_SendMessage($dir,$this->attr('from',$action,$data),$user,$m,($this->attr('offline',$action,$data)=='true'));
        return false;
    }

    protected function RT_Read ($action, $data)
    {
        $user=$this->attr('user',$action,$data);
        $dir=_RT_Api::RT_Directory(($action->hasAttribute('scope')?$this->attr('scope',$action,$data):'default'));
        if (!$user)
        {
            $user=_Session::getInstance ()->CurrentUser->user_id;
        }
        $data->arraySetElement ($this->attr('field',$action,$data),_RT_Api::RT_Read($dir.$user.'/'.$this->attr('path',$action,$data)));
        return false;
    }

    protected function RT_Write ($action, $data)
    {
        $user=$this->attr('user',$action,$data);
        $dir=_RT_Api::RT_Directory(($action->hasAttribute('scope')?$this->attr('scope',$action,$data):'default'));
        if (!$user)
        {
            $user=_Session::getInstance ()->CurrentUser->user_id;
        }
        _RT_Api::RT_Write($dir.$user.'/'.$this->attr('path',$action,$data),($this->attr('append',$action,$data)=='true'),$this->fmt($action->plainContent(),$data));
        return false;
    }

    public function __construct ()
    {
        _Api::__instanceInit ($this);
        parent::__construct('api');
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