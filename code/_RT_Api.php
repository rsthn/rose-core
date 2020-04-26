<?php

class _RT_Api extends _SystemService
{
    private static $__classAttributes = null;
    public static $instance;


    public static function classAttributes ()
    {
        return _RT_Api::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _RT_Api::$instance=alpha (new _RT_Api ());
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ()
    {
        _RT_Api::__instanceInit ($this);
        parent::__construct('rt');
    }

    public static function RT_Directory ($scope)
    {
        return 'r/'.$scope.'/';
    }

    public static function RT_IsConnected ($dir, $user)
    {
        return _File::exists($dir.'.'.$user);
    }

    public static function RT_Write ($path, $append, $value)
    {
        if (!_Directory::exists(_Directory::getInstance ()->path($path)))
        {
            _Directory::create(_Directory::getInstance ()->path($path),true);
        }
        if ($append)
        {
            _File::appendContents($path,$value);
        }
        else
        {
            _File::putContents($path,$value);
        }
    }

    public static function RT_Read ($path)
    {
        if (!_File::exists($path))
        {
            return '';
        }
        return _File::getContents($path);
    }

    public static function RT_SendMessage ($dir, $from, $target, $msg, $offline)
    {
        if (($from==$target))
        {
            return ;
        }
        if (($target=='*'))
        {
            foreach(alpha (new _Directory ($dir))->readDirEntries()->dirs->__nativeArray as $f)
            {
                $target=$f->name;
                _RT_Api::RT_SendMessage($dir,$from,$target,$msg,$offline);
            }
            return ;
        }
        if (!$offline)
        {
            if (!_RT_Api::RT_IsConnected($dir,$target))
            {
                return ;
            }
        }
        _RT_Api::RT_Write($dir.$target.'/.bcast',true,$msg);
    }

    public function main ($internallyCalled=false)
    {
        if ((_Configuration::getInstance ()->RT->direct=='false'))
        {
            return null;
        }
        $this->setContentType('application/json; charset=UTF-8');
        $scope=_Gateway::getInstance ()->requestParams->scope;
        if (!$scope)
        {
            $scope='default';
        }
        $dir=_RT_Api::RT_Directory($scope);
        if (!_Directory::exists($dir))
        {
            return _Convert::toJson(_Map::fromNativeArray(array('response'=>504),false));
        }
        if ((_Session::getInstance ()->CurrentUser==null))
        {
            return _Convert::toJson(_Map::fromNativeArray(array('response'=>500),false));
        }
        $o=null;;
        switch (_Gateway::getInstance ()->requestParams->f)
        {
            case 'connect':
            $o=$this->RT_StartListener($dir,_Session::getInstance ()->CurrentUser->user_id);
            break;
            case 'poll-enter':
            $o=$this->RT_PollEnter($dir,_Session::getInstance ()->CurrentUser->user_id);
            break;
            case 'poll-leave':
            $o=$this->RT_PollLeave($dir,_Session::getInstance ()->CurrentUser->user_id);
            break;
            case 'poll':
            $o=$this->RT_PollStatus($dir,_Session::getInstance ()->CurrentUser->user_id);
            break;
        }
        if (($o!=null))
        {
            return _Convert::toJson($o);
        }
        else
        {
            return null;
        }
    }

    public function RT_StartListener ($dir, $me)
    {
        _Gateway::header('Content-Type: text/html');
        _Gateway::ignoreUserAbort(true);
        _Gateway::enableBlockTransfer();
        _Session::getInstance ()->close();
        $response=alpha (new _Map ());
        $temp=null;;
        $f=null;;
        $dirData=$dir.$me.'/';
        $fileStatus=$dir.'.'.$me;
        $delay=_Configuration::getInstance ()->RT->delay;
        $conn=_Resources::getInstance ()->DateTime->Now;
        _RT_Api::RT_Write($dirData.'.conn',false,$conn);
        $padsiz=_Configuration::getInstance ()->RT->padsize;
        _RT_Api::RT_SendMessage($dir,$me,'*',"RT_Data({\"sys\":\"online\", \"from\":\"".$me."\"});",false);
        try
        {
            _RT_Api::RT_Write($fileStatus,false,'Connected');
            $elapsed=0;
            $lifetime=_Configuration::getInstance ()->RT->lifetime;
            while (_Gateway::connectionAlive())
            {
                $response->data='';
                if (_File::exists($dirData.'.bcast'))
                {
                    $temp=_File::getContents($dirData.'.bcast');
                    $response->data.=$temp;
                    if (($temp!=''))
                    {
                        _File::putContents($dirData.'.bcast',_Text::substring(_File::getContents($dirData.'.bcast'),_Text::length($temp)));
                    }
                }
                if (($elapsed>=$lifetime))
                {
                    $response->data.="RT_Data({\"sys\":\"reconnect\"});";
                }
                _Gateway::writeBlock(_Text::padString(_Convert::toJson($response).'<BRK/>',$padsiz));
                _Utils::Sleep(($delay*1000));
                if (($elapsed>=$lifetime))
                {
                    return null;
                }
                $elapsed+=$delay;
            };
        }
        catch (_Exception $e)
        {
            trace('ERROR: '.$e);
        }
        _RT_Api::RT_SendMessage($dir,$me,'*',"RT_Data({\"sys\":\"offline\", \"from\":\"".$me."\"});",false);
        if ((_RT_Api::RT_Read($dirData.'.conn')<=$conn))
        {
            _File::remove($fileStatus);
            _Gateway::writeBlock('506');
        }
        return null;
    }

    public function RT_PollEnter ($dir, $me)
    {
        _RT_Api::RT_Write($dir.'.'.$me,false,'Connected');
        _RT_Api::RT_Write($dir.$me.'/.heartbeat',false,_Resources::getInstance ()->DateTime->Now);
        return _Map::fromNativeArray(array('response'=>200),false);
    }

    public function RT_PollLeave ($dir, $me)
    {
        _File::remove($dir.'.'.$me);
        _File::remove($dir.$me.'/.heartbeat');
        return _Map::fromNativeArray(array('response'=>200),false);
    }

    public function RT_PollStatus ($dir, $me)
    {
        if (!_RT_Api::RT_IsConnected($dir,$me))
        {
            return _Map::fromNativeArray(array('response'=>507),false);
        }
        $this->DoEvents($dir);
        $response=alpha (new _Map ());
        $temp=null;;
        $f=null;;
        $dirData=$dir.$me.'/';
        _RT_Api::RT_Write($dirData.'.heartbeat',false,_Resources::getInstance ()->DateTime->Now);
        $response->data='';
        if (_File::exists($dirData.'.bcast'))
        {
            $temp=_File::getContents($dirData.'.bcast');
            $response->data.=$temp;
            if (($temp!=''))
            {
                _File::putContents($dirData.'.bcast',_Text::substring(_File::getContents($dirData.'.bcast'),_Text::length($temp)));
            }
        }
        return $response;
    }

    private function DoEvents ($dir)
    {
        if (!_File::exists($dir.'#evtt'))
        {
            _File::putContents($dir.'#evtt',_Resources::getInstance ()->DateTime->Now);
        }
        if (((_File::getContents($dir.'#evtt')>_Resources::getInstance ()->DateTime->Now)||_File::exists($dir.'#evtl')))
        {
            return ;
        }
        $now=_Resources::getInstance ()->DateTime->Now;
        $n=_Configuration::getInstance ()->RT->timeout;
        foreach(alpha (new _Directory ($dir))->readFileEntries('/^\./')->files->__nativeArray as $f)
        {
            $p=$dir.($f=_Text::substring($f->name,1)).'/.heartbeat';
            if (!_File::exists($p))
            {
                continue;
            }
            try
            {
                if ((($now-_File::getContents($p))>$n))
                {
                    PollLeave($dir,$f);
                }
            }
            catch (Exception $e){}
        }
        _File::putContents($dir.'#evtt',(_Resources::getInstance ()->DateTime->Now+4));
        _File::remove($dir.'#evtl');
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