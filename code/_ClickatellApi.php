<?php

class _ClickatellApi
{
    private static $__classAttributes = null;
    protected $apiDetails;
    protected $sessionId;
    protected $httpClient;


    public static function classAttributes ()
    {
        return _ClickatellApi::$__classAttributes;

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

    public function __construct ($details)
    {
        _ClickatellApi::__instanceInit ($this);
        $this->httpClient=alpha (new _HttpClient ('api.clickatell.com'));
        $this->apiDetails=$details;
        $this->sessionId=null;
    }

    private function parseResponse ($r)
    {
        $inf=_Map::fromNativeArray(array('successRate'=>0,'errorRate'=>0),false);
        foreach(_Text::explode("\n",$r)->__nativeArray as $item)
        {
            $r=_Text::explode(':',$item);
            switch (_Text::toUpperCase($r->arrayGetElement (0)))
            {
                case 'OK':
                $inf->success=true;
                $inf->value=_Text::trim($r->arrayGetElement (1));
                break;
                case 'CREDIT':
                $inf->success=true;
                $inf->value=_Text::trim($r->arrayGetElement (1));
                break;
                case 'ERR':
                $inf->success=false;
                $inf->error=_Text::trim($r->arrayGetElement (1));
                $inf->errorRate++;
                break;
                case 'ID':
                $inf->success=true;
                $inf->value=_Text::trim($r->arrayGetElement (1));
                $inf->successRate++;
                break;
            }
        }
        return $inf;
    }

    public function apiCall ($handler, $usePost=false, $params=null, $addSession=true, $rawResponse=false)
    {
        if (($params==null))
        {
            $params=alpha (new _Map ());
        }
        if ($addSession)
        {
            $params->arraySetElement ('session_id',$this->sessionId);
        }
        if ($rawResponse)
        {
            if ($usePost)
            {
                return $this->httpClient->postData($handler,$params);
            }
            else
            {
                return $this->httpClient->retrieve($handler.'?'.$params->format('{f:urlencode:0}={f:urlencode:1}')->implode('&'));
            }
        }
        else
        {
            if ($usePost)
            {
                return $this->parseResponse($this->httpClient->postData($handler,$params));
            }
            else
            {
                return $this->parseResponse($this->httpClient->retrieve($handler.'?'.$params->format('{f:urlencode:0}={f:urlencode:1}')->implode('&')));
            }
        }
    }

    private function newSession ()
    {
        $r=$this->apiCall('/http/auth',false,_Map::fromNativeArray(array('api_id'=>$this->apiDetails->id,'user'=>$this->apiDetails->username,'password'=>$this->apiDetails->password),false),false);
        if (!$r->success)
        {
            throw alpha (new _Exception ('Authentication Failed'));
        }
        $this->sessionId=$r->value;
    }

    private function checkSession ()
    {
        if ((($this->sessionId!=null)&&$this->apiCall('/http/ping')->success))
        {
            return ;
        }
        $this->newSession();
    }

    public function balance ()
    {
        $this->checkSession();
        $r=$this->apiCall('/http/getbalance');
        if ($r->success)
        {
            return ((float)$r->value);
        }
        return 0;
    }

    public function bulkBegin ($message)
    {
        $this->checkSession();
        $r=$this->apiCall('/http_batch/startbatch',true,_Map::fromNativeArray(array('template'=>$message),false));
        if ($r->success)
        {
            return $r->value;
        }
        return null;
    }

    public function bulkEnd ($id)
    {
        $this->checkSession();
        return $this->apiCall('/http_batch/endbatch',true,_Map::fromNativeArray(array('batch_id'=>$id),false))->success;
    }

    public function bulkSend ($id, $targets)
    {
        $this->checkSession();
        if ((typeOf($targets)=='Array'))
        {
            $targets=$targets->implode(',');
        }
        return $this->apiCall('/http_batch/quicksend',true,_Map::fromNativeArray(array('batch_id'=>$id,'to'=>$targets),false),true,true);
    }

    public function send ($target, $message)
    {
        return $this->apiCall('/http/sendmsg',false,_Map::fromNativeArray(array('api_id'=>$this->apiDetails->id,'user'=>$this->apiDetails->username,'password'=>$this->apiDetails->password,'to'=>$target,'text'=>$message),false),false);
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