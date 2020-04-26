<?php

class _HttpClient
{
    private static $__classAttributes = null;
    public $maxBuffer;
    private $hostname;
    private $port;
    private $secure;
    public $rheader;
    public $header;
    public $cookies;
    private $_tracing;
    private $state;
    private static $regex;


    public static function classAttributes ()
    {
        return _HttpClient::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->maxBuffer=0;
        $__this__->header=alpha (new _Array ());
        $__this__->cookies=alpha (new _Map ());
        $__this__->_tracing=0;
        $__this__->state=null;
    }

    public static function __classInit ()
    {
        _HttpClient::$regex=_Map::fromNativeArray(array('responseCode'=>alpha (new _Regex ('/^HTTP\/([0-9\.])+\s([0-9]+)\s(.*)$/')),'headerParams'=>alpha (new _Regex ('/^([^:]+):\s+(.+)$/'))),false);
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ($hostname, $port=null, $secure=null)
    {
        _HttpClient::__instanceInit ($this);
        $this->setConfig($hostname,$port,$secure);
    }

    public function setConfig ($hostname, $port=null, $secure=null)
    {
        $this->hostname=$hostname;
        $this->port=($port?$port:80);
        $this->secure=(($secure===null)?(($port==443)):$secure);
        return $this;
    }

    private function pushConfig ()
    {
        if (!$this->state)
        {
            $this->state=alpha (new _Array ());
        }
        $this->state->push(_Array::fromNativeArray(array($this->hostname,$this->port,$this->secure),false));
        if (($this->state->length()>32))
        {
            $this->state=null;
            throw alpha (new _Error ('HttpClient maximum redirection level reached!!'));
        }
        return $this;
    }

    private function popConfig ()
    {
        if (!$this->state)
        {
            return $this;
        }
        $temp=$this->state->pop();
        $this->hostname=$temp->arrayGetElement (0);
        $this->port=$temp->arrayGetElement (1);
        $this->secure=$temp->arrayGetElement (2);
        return $this;
    }

    public function tracing ($state=null)
    {
        if (($state!==null))
        {
            $this->_tracing=$state;
            return $this;
        }
        return $this->_tracing;
    }

    private function getHttpResponse ($stream)
    {
        $response='';
        try
        {
            $response=_HttpClient::$regex->responseCode->matchFirst($stream->readLine())->arrayGetElement (2);
        }
        catch (Exception $e){}
        return intval($response);
    }

    private function getHttpHeaders ($stream)
    {
        $line=null;;
        $tempArray=null;;
        $cookieInfo=null;;
        $headers=alpha (new _Map ());
        while (1)
        {
            if ((($line=$stream->readLine())==''))
            {
                break;
            }
            if ($this->_tracing)
            {
                trace($line);
            }
            $tempArray=_HttpClient::$regex->headerParams->matchFirst($line);
            if (($tempArray->length()!=3))
            {
                continue;
            }
            $tempArray->arraySetElement (1,_Text::toLowerCase($tempArray->arrayGetElement (1)));
            if (($tempArray->arrayGetElement (1)=='set-cookie'))
            {
                $delim=_Text::position($tempArray->arrayGetElement (2),';');
                if (($delim!==false))
                {
                    $tempArray->arraySetElement (2,_Text::substring($tempArray->arrayGetElement (2),0,$delim));
                }
                $cookieInfo=_Text::explode('=',$tempArray->arrayGetElement (2));
                $this->cookies->arraySetElement ($cookieInfo->arrayGetElement (0),$cookieInfo->arrayGetElement (1));
            }
            else
            {
                $headers->setElement($tempArray->arrayGetElement (1),$tempArray->arrayGetElement (2));
            }
        };
        return $headers;
    }

    private function retrieveContent ($stream, $header, $lengthOnly=false)
    {
        $fullLength=0;
        $length=null;;
        $buffLen=0;
        $line=null;;
        $buffer='';
        switch (_Text::toLowerCase($header->arrayGetElement ('transfer-encoding')))
        {
            case 'chunked':
            $maxLen=$this->maxBuffer;
            $buffLen=0;
            while (true)
            {
                $line=$stream->readLine();
                if (($line==''))
                {
                    continue;
                }
                $length=_Convert::fromHexInteger($line);
                if (($length==0))
                {
                    break;
                }
                if (($this->maxBuffer&&($length>$maxLen)))
                {
                    $length=$maxLen;
                }
                if ($this->maxBuffer)
                {
                    $maxLen-=$length;
                }
                $fullLength+=$length;
                while ($length)
                {
                    $line=$stream->readBytes($length);
                    if (!$lengthOnly)
                    {
                        $buffer.=$line;
                    }
                    $length-=_Text::length($line);
                };
                if (($this->maxBuffer&&!$maxLen))
                {
                    break;
                }
            };
            break;
            default:
            if ($header->hasElement('content-length'))
            {
                $length=((int)$header->arrayGetElement ('content-length'));
            }
            else
            {
                $length=null;
            }
            if ($this->maxBuffer)
            {
                $length=$this->maxBuffer;
            }
            if ($length)
            {
                $fullLength+=$length;
                if (!$lengthOnly)
                {
                    $buffer=$stream->readBytes($length);
                }
            }
            else
            {
                $buffer=$stream->readBytes();
            }
            break;
        }
        if ($lengthOnly)
        {
            return $fullLength;
        }
        switch (_Text::toLowerCase($header->arrayGetElement ('content-encoding')))
        {
            case 'deflate':
            $buffer=deflate($buffer);
            break;
            case 'gzip':
            $buffer=gzdecode($buffer);
            break;
        }
        return $buffer;
    }

    private function handleResponse ($stream, $readData=true, $readLengthOnly=false)
    {
        $response=$this->getHttpResponse($stream);
        if ($this->_tracing)
        {
            trace('HTTP '.$response);
        }
        $this->rheader=$this->getHttpHeaders($stream);
        $this->rheader->response=$response;
        $buffer=null;
        switch ($response)
        {
            case 302:
            case 301:
            $location=alpha (new _Url ($this->rheader->arrayGetElement ('location')));
            if ($this->_tracing)
            {
                trace('HttpClient: Redirecting to location: '.$location->full());
            }
            $this->pushConfig();
            $this->port=($location->port()?$location->port():((($location->protocol()=='https')?443:80)));
            $this->hostname=$location->host();
            $buffer=$this->retrieve($location->root()._Text::substring($location->resource(),1),$readData,$readLengthOnly);
            $this->rheader->arraySetElement ('redirected',$location->full());
            $this->popConfig();
            if ((!$readData&&!$readLengthOnly))
            {
                return '';
            }
            $response=(($buffer===false)?false:true);
            break;
            case 200:
            case 500:
            case 400:
            if ((!$readData&&!$readLengthOnly))
            {
                return '';
            }
            $buffer=$this->retrieveContent($stream,$this->rheader,$readLengthOnly);
            $response=true;
            break;
            default:
            if ((!$readData&&!$readLengthOnly))
            {
                return '';
            }
            $buffer=$this->retrieveContent($stream,$this->rheader,$readLengthOnly);
            $response=true;
            break;
        }
        if (($this->_tracing&&($buffer!=null)))
        {
            trace('---Content---'.$this->retrieveContent($stream,$this->rheader,$readLengthOnly).'-------------');
        }
        return ($response?$buffer:false);
    }

    public function retrieve ($resourceName, $readData=true, $readLengthOnly=false)
    {
        $stream=alpha (new _TcpConnection ($this->hostname,$this->port))->stream();
        $headers=alpha (new _Array ());
        if (($this->secure&&($stream->descriptor()->enableCrypto(_Socket::$CryptoType->{_Configuration::getInstance ()->Shield->socketCryptoType})==false)))
        {
            throw alpha (new _Exception ('Unable to start TLS client on socket.'));
        }
        if ($this->_tracing)
        {
            trace(sprintf('HttpClient: Retrieving %s from %s:%u(%u)',$resourceName,$this->hostname,$this->port,$this->secure));
        }
        $headers->push('GET '.$resourceName.' HTTP/1.1');
        $headers->push('Host: '.$this->hostname);
        if (($this->cookies->length()!=0))
        {
            $headers->push('Cookie: '.$this->cookies->format('{0}={1}')->implode('; '));
        }
        $headers=$headers->merge($this->header);
        $headers->push('Connection: close');
        $headers->push("\r\n");
        if ($this->_tracing)
        {
            trace('Headers Sent (GET): '.$headers->implode("\n"));
        }
        $stream->writeBytes($headers->implode("\r\n"));
        return $this->handleResponse($stream,$readData,$readLengthOnly);
    }

    public function rawPost ($resourceName, $formData, $readData=true, $readLengthOnly=false)
    {
        $stream=alpha (new _TcpConnection ($this->hostname,$this->port))->stream();
        $headers=alpha (new _Array ());
        if (($this->secure&&($stream->descriptor()->enableCrypto(_Socket::$CryptoType->{_Configuration::getInstance ()->Shield->socketCryptoType})==false)))
        {
            throw alpha (new _Exception ('Unable to start crypto on socket, type: \''._Configuration::getInstance ()->Shield->socketCryptoType.'\'.'));
        }
        $headers->push('POST '.$resourceName.' HTTP/1.1');
        $headers->push('Host: '.$this->hostname);
        if ($formData)
        {
            $headers->push('Content-Type: '.$formData->arrayGetElement (0));
            $headers->push('Content-Length: '._Text::length($formData->arrayGetElement (1)));
        }
        if (($this->cookies->length()!=0))
        {
            $headers->push('Cookie: '.$this->cookies->format('{0}={1}')->implode('; '));
        }
        $headers=$headers->merge($this->header);
        $headers->push('Connection: close');
        $headers->push("\r\n");
        if ($this->_tracing)
        {
            trace("Headers Sent (POST):\n".$headers->implode("\n"));
        }
        $stream->writeBytes($headers->implode("\r\n"));
        if ($formData)
        {
            if (($this->_tracing==3))
            {
                trace('--DATA--'.$formData->arrayGetElement (1).'--/DATA--');
            }
            $stream->writeBytes($formData->arrayGetElement (1));
        }
        return $this->handleResponse($stream,$readData,$readLengthOnly);
    }

    public function postData ($resourceName, $fields=null, $multipart=false)
    {
        if (($fields==null))
        {
            return $this->rawPost($resourceName,null);
        }
        return $this->rawPost($resourceName,($multipart?_HttpClient::encodeMultipartFormData($fields):_HttpClient::encodeFormData($fields)));
    }

    private static function encodeMultipartFormData ($fields)
    {
        $boundary=_DateTime::nowUnixTime();
        $data=alpha (new _Array ());
        foreach($fields->__nativeArray as $fieldName=>$fieldValue)
        {
            if ((typeOf($fieldValue)=='Array'))
            {
                $data->push('--'.$boundary);
                $data->push(sprintf('Content-Disposition: form-data; name=\'%s\'; filename=\'%s\'',$fieldName,$fieldValue->arrayGetElement (0)));
                $data->push(sprintf('Content-Type: %s',$fieldValue->arrayGetElement (1)));
                $data->push('');
                $data->push(((!$fieldValue->arrayGetElement (2)&&($fieldValue->length()==4))?_File::getContents($fieldValue->arrayGetElement (3)):$fieldValue->arrayGetElement (2)));
            }
            else
            {
                $data->push('--'.$boundary);
                $data->push(sprintf('Content-Disposition: form-data; name=\'%s\'',$fieldName));
                $data->push('');
                $data->push($fieldValue);
            }
        }
        unset ($fieldName);
        $data->push('--'.$boundary.'--');
        $data->push("\r\n");
        return _Array::fromNativeArray(array('multipart/form-data; boundary='.$boundary,$data->implode("\r\n")),false);
    }

    public static function encodeFormData ($fields)
    {
        $formData='';
        foreach($fields->__nativeArray as $name=>$value)
        {
            $formData.=(($formData?'&':null)).($name.'='.urlencode($value));
        }
        unset ($name);
        return _Array::fromNativeArray(array('application/x-www-form-urlencoded',$formData),false);
    }

    public static function decodeFormData ($data)
    {
        $map=alpha (new _Map ());
        $i=0;
        while (_Text::length($data))
        {
            $k=_Text::position($data,'=');
            $p=_Text::position($data,'&');
            if (!$p)
            {
                $p=_Text::length($data);
            }
            $map->arraySetElement (_Convert::filter('urldecode',_Text::substring($data,$i,$k)),_Convert::filter('urldecode',_Text::substring($data,($k+1),(($p-$k)-1))));
            $data=_Text::substring($data,($p+1));
        };
        return $map;
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