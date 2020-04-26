<?php

class _GailHandler
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _GailHandler::$__classAttributes;

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

    public function __construct ()
    {
        _GailHandler::__instanceInit ($this);
    }

    public function sendMail ($params)
    {
        _SmtpClient::sendMail($params);
    }

    public function verifyCaptcha ($params)
    {
        if ((_Configuration::getInstance ()->Captcha->customEnabled=='true'))
        {
            if ((_Text::toUpperCase(_Gateway::getInstance ()->requestParams->captcha_response)==_Text::toUpperCase(_Session::getInstance ()->CaptchaCode)))
            {
                return ;
            }
            if ((_Strings::getInstance ()->Forms->Captcha!=''))
            {
                throw alpha (new _Exception (_Strings::getInstance ()->Forms->Captcha));
            }
            throw alpha (new _Exception (_Strings::getInstance ()->{'/Forms'}->Captcha));
        }
        $hc=alpha (new _HttpClient ('www.google.com'));
        $params=_Map::fromNativeArray(array('privatekey'=>_Configuration::getInstance ()->Captcha->PrivateKey,'remoteip'=>_Gateway::getInstance ()->serverParams->REMOTE_ADDR,'challenge'=>_Gateway::getInstance ()->requestParams->recaptcha_challenge_field,'response'=>_Gateway::getInstance ()->requestParams->recaptcha_response_field),false);
        $response=$hc->postData('/recaptcha/api/verify',$params,false);
        if (($response===false))
        {
            throw alpha (new _Exception ('Unable to connect to Google\'s Recaptcha server!'));
        }
        $response=_Text::explode("\n",$response);
        if (($response->arrayGetElement (0)=='false'))
        {
            if ((_Strings::getInstance ()->Forms->Captcha!=''))
            {
                throw alpha (new _Exception (_Strings::getInstance ()->Forms->Captcha));
            }
            throw alpha (new _Exception (_Strings::getInstance ()->{'/Forms'}->Captcha));
        }
    }

    public function fsOperation ($params)
    {
        switch ($params->action)
        {
            case 'mkdir':
            try
            {
                _Directory::create($params->path,(($params->recursive=='true')?1:0));
            }
            catch (Exception $e){}
            break;
            case 'rmdir':
            try
            {
                _Directory::remove($params->path,(($params->recursive=='true')?1:0));
            }
            catch (Exception $e){}
            break;
            case 'del':
            try
            {
                _File::remove($params->path);
            }
            catch (Exception $e){}
            break;
            case 'save':
            try
            {
                _File::putContents($params->path,$params->data);
            }
            catch (Exception $e){}
            break;
            case 'append':
            try
            {
                _File::appendContents($params->path,$params->data);
            }
            catch (Exception $e){}
            break;
            case 'copy':
            try
            {
                _Directory::copy($params->source,$params->dest);
            }
            catch (Exception $e){}
            break;
            case 'move':
            try
            {
                _File::move($params->source,$params->dest);
            }
            catch (Exception $e){}
            break;
        }
    }

    public function exec ($params)
    {
        _Text::format($params->data);
    }

    public function verifyCondition ($params)
    {
        if (($params->condition==false))
        {
            throw alpha (new _Exception ($params->failureMsg));
        }
    }

    public function storeData ($params)
    {
        if ($params->hasElement('condition'))
        {
            if (($params->condition==false))
            {
                return ;
            }
        }
        $src=$params->data;
        if (($params->useFormData=='true'))
        {
            $src=$params->formData;
        }
        if (($params->target!=null))
        {
            if ((_Session::getInstance ()->{$params->target}==null))
            {
                _Session::getInstance ()->{$params->target}=alpha (new _Map ());
            }
            _Session::getInstance ()->{$params->target}->merge($src,true);
        }
        else
        {
            foreach($src->__nativeArray as $item=>$value)
            {
                _Session::getInstance ()->{$item}=$value;
            }
            unset ($item);
        }
    }

    public function urlRedirect ($params)
    {
        if (($params->hasElement('condition')&&($params->condition==false)))
        {
            return ;
        }
        _Gateway::header('location: '.$params->location);
        throw alpha (new _FalseException ());
    }

    public function formMsg ($params)
    {
        throw alpha (new _Exception ($params->content));
    }

    public function setHttpHeader ($params)
    {
        _Gateway::header($params->value);
    }

    public function dumpContents ($params)
    {
        if (($params->arrayGetElement ('trace')!='true'))
        {
            throw alpha (new _Exception ($params->__toString()));
        }
        else
        {
            trace($params->__toString());
        }
    }

    public function executeSql ($params)
    {
        if (($params->hasElement('condition')&&($params->condition==false)))
        {
            return ;
        }
        _Resources::getInstance ()->sqlConn->execQuery($params->query);
    }

    public function reEnter ($params)
    {
        if (($params->hasElement('condition')&&($params->condition==false)))
        {
            return ;
        }
        _Gateway::getInstance ()->reEnter($params);
    }

    public function dbUpdate ($params)
    {
        if (($params->discard!=null))
        {
            if (($params->discard[0]=='/'))
            {
                $params->formData=$params->formData->replicate()->removeAll($params->discard,true);
            }
            else
            {
                $params->formData=$params->formData->replicate()->removeAll('/^('.$params->discard.')$/',true);
            }
        }
        if (($params->allow!=null))
        {
            $params->formData=$params->formData->selectAll('/^('.$params->allow.')$/',true);
        }
        $data=null;;
        if (($params->allowBlank!='true'))
        {
            $data=$params->formData->replicate()->removeAll('/^$/');
        }
        else
        {
            $data=$params->formData;
        }
        _SqlTables::getInstance ()->table($params->table)->update($params->condition,$data);
    }

    public function dbInsert ($params)
    {
        if (($params->discard!=null))
        {
            if (($params->discard[0]=='/'))
            {
                $params->formData=$params->formData->replicate()->removeAll($params->discard,true);
            }
            else
            {
                $params->formData=$params->formData->replicate()->removeAll('/^('.$params->discard.')$/',true);
            }
        }
        if (($params->allow!=null))
        {
            $params->formData=$params->formData->selectAll('/^('.$params->allow.')$/',true);
        }
        if (($params->allowBlank!='true'))
        {
            $params->formData=$params->formData->replicate()->removeAll('/^$/');
        }
        _SqlTables::getInstance ()->table($params->table)->insert($params->formData);
    }

    public function dbDelete ($params)
    {
        _SqlTables::getInstance ()->table($params->table)->delete($params->condition);
    }

    public function dbLoad ($params)
    {
        _Resources::getInstance ()->register($params->resource,_Resources::getInstance ()->sqlConn->execAssoc($params->query));
    }

    public function validUser ($params)
    {
        if (!_Sentinel::getInstance ()->validUser($params->username,$params->password))
        {
            throw alpha (new _Exception ($params->failureMsg));
        }
    }

    public function reloadUserDetails ($params)
    {
        if (!_Sentinel::getInstance ()->reloadDetails())
        {
            throw alpha (new _Exception ($params->failureMsg));
        }
    }

    public function authenticate ($params)
    {
        if (!_Sentinel::getInstance ()->authenticate($params->username,$params->password))
        {
            throw alpha (new _Exception ($params->failureMsg));
        }
    }

    public function hasPrivilege ($params)
    {
        if (!_Sentinel::getInstance ()->hasPrivilege($params->privilege,$params->username))
        {
            throw alpha (new _Exception ($params->failureMsg));
        }
    }

    public function resetCurrentUser ($params)
    {
        _Sentinel::getInstance ()->clear();
    }

    public function clearSession ($params)
    {
        _Session::getInstance ()->invalidate();
    }

    public function logout ()
    {
        if (_Configuration::getInstance ()->Session->remember)
        {
            _SystemParameters::getInstance ()->removeElement(_Configuration::getInstance ()->Session->remember);
        }
        _Sentinel::getInstance ()->clear();
    }

    public function rememberUser ($params)
    {
        if (!_Gateway::getInstance ()->RQP->remember)
        {
            return ;
        }
        $r=_Math::rand();
        $s=$r.'|'.$params->formData->username.'|'.$params->formData->password.'|'.$r;
        _SystemParameters::getInstance ()->setElement(_Configuration::getInstance ()->Session->remember,_Convert::toBase64(_Eax::encrypt(_Configuration::getInstance ()->Session->keyword,$s,true)));
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