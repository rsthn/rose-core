<?php

class _BluxApiService extends _SystemService
{
    private static $__classAttributes = null;
    private $SendMail_attrs;
    protected $apiName;
    protected $responseInXml;
    protected $responseStrFmt;
    protected $compressResponse;
    protected $forceInternalMode;
    public $dataMap;
    public $objMap;
    protected static $_Brk;
    protected static $_Cont;
    protected $multiResponseMode;
    protected $cacheId;
    private static $__sortF;


    public static function classAttributes ()
    {
        return _BluxApiService::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->SendMail_attrs=_Array::fromNativeArray(array('server','host','port','ssl','username','password','from','fromName','native'),false);
        $__this__->responseInXml=0;
        $__this__->responseStrFmt=null;
        $__this__->compressResponse=null;
        $__this__->forceInternalMode=0;
        $__this__->dataMap=null;
        $__this__->objMap=null;
        $__this__->multiResponseMode=0;
        $__this__->cacheId=null;
    }

    public static function __classInit ()
    {
        _BluxApiService::$_Brk='<!BRK>';
        _BluxApiService::$_Cont='<!CONT>';
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    protected function Clickatell_SendMsg ($action, $data)
    {
        $params=$action->toMap($data->formData,$data);
        $tmp=alpha (new _ClickatellApi (_Configuration::getInstance ()->Clickatell));
        $tmp->send($params->to,$params->message);
        return false;
    }

    protected function PayPalCall ($action, $data)
    {
        $params=$action->toMap($data->formData,$data);
        $name=$this->attr('name',$action,$data);
        if (!$name)
        {
            $name='PayPalApi_Instance';
        }
        $ret=$this->attr('ref',$action,$data);
        if (!$ret)
        {
            $ret='paypal';
        }
        if (($params->METHOD=='INITIALIZE'))
        {
            $params->removeElement('METHOD');
            _Resources::getInstance ()->register($name,alpha (new _PayPalApi ($params)));
            return false;
        }
        if (!_Resources::getInstance ()->exists($name))
        {
            _Resources::getInstance ()->register($name,alpha (new _PayPalApi ()));
        }
        $data->arraySetElement ($ret,_Resources::getInstance ()->retrieve($name)->Call($params));
        if ($data->arrayGetElement ($ret)->hasElement('TOKEN'))
        {
            $data->arrayGetElement ($ret)->REDIRECT_URL=_Resources::getInstance ()->retrieve($name)->redirectUrl($data->arrayGetElement ($ret)->TOKEN);
        }
        return false;
    }

    protected function SendMail ($action, $data)
    {
        $params=$action->toMap($data->formData,$data);
        foreach($this->SendMail_attrs->__nativeArray as $i)
        {
            if ($action->hasAttribute($i))
            {
                $params->arraySetElement ($i,$this->attr($i,$action,$data));
            }
        }
        $params->email=_Text::explode(',',$params->email);
        _SmtpClient::sendMail($params);
        return false;
    }

    public function __construct ($name='blux')
    {
        _BluxApiService::__instanceInit ($this);
        parent::__construct($this->apiName=$name);
        $this->responseInXml=((_Configuration::getInstance ()->Output->returnsXml=='true')?1:0);
    }

    protected function functionFile ($name, $rep=true)
    {
        if ($rep)
        {
            $name=_Text::replace('.','/',$name);
        }
        if ((_Text::substring($name,0,5)=='mods/'))
        {
            $path='resources/mods';
            $found=false;
            $name=_Text::explode('/',$name);
            $name->shift();
            if (_File::exists('resources/mods/manifest.xml'))
            {
                $manifest=_XmlElement::loadFrom('resources/mods/manifest.xml')->toMap();
                $id='';
                for ($i=0;($i<$name->length()); $i++)
                {
                    $id.=(($i?'.':'')).$name->arrayGetElement ($i);
                    $path.='/'.$name->arrayGetElement ($i);
                    if (!$manifest->hasElement($id))
                    {
                        continue;
                    }
                    $mod=$manifest->arrayGetElement ($id);
                    if (!$mod)
                    {
                        $mod=_Map::fromNativeArray(array(),false);
                    }
                    while (($i-->=0))
                    {
                        $name->shift();
                    };
                    if ($mod->source)
                    {
                        $path=$this->fmt($mod->source,_Map::fromNativeArray(array(),false));
                        if ((_Text::substring($path,-1)=='/'))
                        {
                            $path=_Text::substring($path,0,-1);
                        }
                    }
                    if (!_File::exists($path.'/module.xml'))
                    {
                        $path=_Configuration::getInstance ()->Output->commonModules._Text::replace('.','/',$id);
                    }
                    $found=true;
                    break;
                }
            }
            if (!$found)
            {
                $path='resources/mods';
                while ($name->length())
                {
                    $path.='/'.$name->shift();
                    if (_File::exists($path.'/module.xml'))
                    {
                        $xml=_XmlElement::loadFrom($path.'/module.xml');
                        if ((($xml->name()=='module')&&$xml->hasAttribute('source')))
                        {
                            $path=$this->attr('source',$xml,_Map::fromNativeArray(array(),false));
                            if ((_Text::substring($path,-1)=='/'))
                            {
                                $path=_Text::substring($path,0,-1);
                            }
                        }
                        break;
                    }
                };
            }
            $this->dataMap->modBase=$path.'/';
            return $path.'/api/'.$name->implode('/').'.xml';
        }
        return 'resources/'.$this->apiName.'/'.$name.'.xml';
    }

    protected function processDefinition ($name, $extraData=null)
    {
        $data=_Map::fromNativeArray(array('formData'=>_Map::fromNativeArray(array(),false)),false);
        $content=null;
        if ($extraData)
        {
            $data->merge($extraData,true);
        }
        _Resources::getInstance ()->DataMap=$this->dataMap=$data;
        _Resources::getInstance ()->ObjMap=$this->objMap=_Map::fromNativeArray(array(),false);
        $apiFuncFile=$this->functionFile($name);
        if (!_File::exists($apiFuncFile))
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>400),false));
        }
        $data->evalBase=_File::getInstance ()->path($apiFuncFile).'/';
        $content=_XmlElement::loadFrom($apiFuncFile)->content;
        try
        {
            foreach($content->__nativeArray as $action)
            {
                $response=$this->{$action->name()}($action,$data);
                if (($response!==false))
                {
                    return $response;
                }
            }
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>409,'error'=>$e->getMessage()),false));
        }
        return null;
    }

    public function processBuffer ($buff)
    {
        $data=_Map::fromNativeArray(array('formData'=>_Map::fromNativeArray(array(),false)),false);
        _Resources::getInstance ()->DataMap=$this->dataMap=$data;
        _Resources::getInstance ()->ObjMap=$this->objMap=_Map::fromNativeArray(array(),false);
        try
        {
            foreach(_XmlElement::loadFromBuffer($buff)->content->__nativeArray as $action)
            {
                $response=$this->{$action->name()}($action,$data);
                if (($response!==false))
                {
                    return $response;
                }
            }
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>409,'error'=>$e->getMessage()),false));
        }
        return null;
    }

    public function main ($internallyCalled=false)
    {
        if ((_Gateway::getInstance ()->requestParams->rpkg!=null))
        {
            $reqs=_Text::explode(';',_Gateway::getInstance ()->requestParams->rpkg);
            $this->multiResponseMode=1;
            $a=null;;
            $t=null;;
            $tt=null;;
            $n=0;
            $r=alpha (new _Map ());
            $original=_Gateway::getInstance ()->requestParams;
            foreach($reqs->__nativeArray as $i)
            {
                if (!$i)
                {
                    continue;
                }
                $t=_Text::explode(',',$i);
                if (($t->length()!=2))
                {
                    continue;
                }
                if ((++$n>256))
                {
                    break;
                }
                _Gateway::getInstance ()->requestParams=alpha (new _Map ())->merge($original,true);
                parse_str(base64_decode($t->arrayGetElement (1)),$a);
                _Gateway::getInstance ()->requestParams->__nativeArray=array_merge(_Gateway::getInstance ()->requestParams->__nativeArray,$a);
                $tt=_Text::explode('|',_Gateway::getInstance ()->requestParams->f);
                if (($tt->length()>1))
                {
                    $tmp=_Map::fromNativeArray(array('INPUT'=>null),false);
                    for ($i=0;($i<$tt->length()); $i++)
                    {
                        $tmp->INPUT=$this->processDefinition($tt->arrayGetElement ($i),$tmp);
                    }
                    $r->arraySetElement ($t->arrayGetElement (0),$tmp->INPUT);
                }
                else
                {
                    $r->arraySetElement ($t->arrayGetElement (0),$this->processDefinition($tt->arrayGetElement (0)));
                }
            }
            $this->multiResponseMode=0;
            return $this->FmtResult($r);
        }
        try
        {
            $t=null;;
            $f=_Text::explode(',',_Regex::stExtract('/[#A-Za-z0-9.,_:|-]+/',_Gateway::getInstance ()->requestParams->f));
            if (($f->length()==1))
            {
                $t=_Text::explode('|',$f->arrayGetElement (0));
                if (($t->length()>1))
                {
                    $tmp=_Map::fromNativeArray(array('INPUT'=>null),false);
                    $this->multiResponseMode=1;
                    for ($i=0;($i<$t->length()); $i++)
                    {
                        $tmp->INPUT=$this->processDefinition($t->arrayGetElement ($i),$tmp);
                    }
                    $this->multiResponseMode=0;
                    return $this->FmtResult($tmp->INPUT);
                }
                $t=_Text::explode(':',$f->arrayGetElement (0));
                for ($i=1;($i<$t->length()); $i++)
                {
                    _Gateway::getInstance ()->requestParams->arraySetElement ('f'.$i,$t->arrayGetElement ($i));
                }
                return $this->processDefinition($t->arrayGetElement (0));
            }
            $this->multiResponseMode=1;
            $r=alpha (new _Map ());
            foreach($f->__nativeArray as $i)
            {
                if (!$i)
                {
                    continue;
                }
                $t=_Text::explode(':',$i);
                for ($j=1;($j<$t->length()); $j++)
                {
                    _Gateway::getInstance ()->requestParams->arraySetElement ('f'.$j,$t->arrayGetElement ($j));
                }
                $r->arraySetElement ($i,$this->processDefinition($t->arrayGetElement (0)));
            }
            $this->multiResponseMode=0;
            return $this->FmtResult($r);
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
    }

    public function FmtResult ($data, $plain=false)
    {
        if ($this->multiResponseMode)
        {
            return $data;
        }
        if ($this->forceInternalMode)
        {
            return _Convert::toJson($data);
        }
        if ($this->responseInXml)
        {
            $data="<?xml version=\"1.0\" encoding=\"utf-8\"?>"._Convert::toXml($data,($this->responseInXml==2),'xml',($this->responseInXml==3));
        }
        else
        {
            $data=_Convert::toJson($data);
        }
        if (($this->responseStrFmt!=null))
        {
            $data=_Text::format($this->responseStrFmt,_Array::fromNativeArray(array($data),false),$this->dataMap->formData,$this->dataMap);
        }
        $hdr=null;;
        $headers='';
        if (((($plain==false)&&(((_Configuration::getInstance ()->Output->{$this->apiName.'_compress'}=='true')||($this->compressResponse=='true'))))&&($this->compressResponse!='false')))
        {
            if (($this->resultContentType==null))
            {
                $this->setContentType(($this->responseInXml?'text/xml; charset=UTF-8':'application/json; charset=UTF-8'));
            }
            $headers.=$this->resultContentType."\n";
            if ((_Text::position(_Gateway::getInstance ()->serverParams->HTTP_ACCEPT_ENCODING,'deflate')!==false))
            {
                $this->header($hdr='Content-Encoding: deflate');
                $headers.=$hdr."\n";
                $data=_Convert::toDeflate($data);
            }
            else
            {
                if ((_Text::position(_Gateway::getInstance ()->serverParams->HTTP_ACCEPT_ENCODING,'gzip')!==false))
                {
                    $this->header($hdr='Content-Encoding: gzip');
                    $headers.=$hdr."\n";
                    $data=_Convert::toGzip($data);
                }
            }
        }
        if ($this->cacheId)
        {
            $cacheFile='resources/cache/'.$this->cacheId.'.dat';
            $cacheHdr='resources/cache/'.$this->cacheId.'.hdr';
            if (!_Directory::exists(_Directory::getInstance ()->path($cacheFile)))
            {
                _Directory::create(_Directory::getInstance ()->path($cacheFile),true);
            }
            _File::putContents($cacheFile,$data);
            _File::putContents($cacheHdr,$headers);
        }
        return $data;
    }

    public function attr ($name, $action, $data)
    {
        return $this->fmt($action->attribute($name),$data);
    }

    public function attrs ($action, $data, $scope='def')
    {
        $r=alpha (new _Map ());
        foreach($action->attributes($scope)->__nativeArray as $item)
        {
            $r->arraySetElement ($item->name(),$this->fmt($item->value,$data));
        }
        return $r;
    }

    public function fmt ($s, $data)
    {
        if (($s[0]=='#'))
        {
            return $this->objMap->arrayGetElement ($s);
        }
        return _Text::format($s,$data->formData,$data);
    }

    protected function FalseAuth ($action, $data)
    {
        if (($this->attr('persistent',$action,$data)!='true'))
        {
            _Session::getInstance ()->close();
        }
        $from=$this->attr('from',$action,$data);
        if ($from)
        {
            _Session::getInstance ()->CurrentUser=$data->arrayGetElement ($from);
        }
        else
        {
            _Session::getInstance ()->CurrentUser=$action->toMap($data->formData,$data);
        }
        return false;
    }

    protected function AuthRequired ($action, $data)
    {
        $field=$this->attr('field',$action,$data);
        if (!_Sentinel::getInstance ()->userAuthenticated())
        {
            if ($field)
            {
                $data->{$field}='0';
                return false;
            }
            return $this->FmtResult($this->attrs($action,$data)->setElement('response',408));
        }
        if ($field)
        {
            $data->{$field}='1';
        }
        return false;
    }

    protected function ReturnsXml ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        if (($this->fmt($action->plainContent(),$data)=='false'))
        {
            $this->responseInXml=0;
        }
        else
        {
            if (($this->attr('debugStyle',$action,$data)=='true'))
            {
                $this->responseInXml=2;
            }
            else
            {
                if (($this->attr('strict',$action,$data)=='true'))
                {
                    $this->responseInXml=3;
                }
                else
                {
                    $this->responseInXml=1;
                }
            }
        }
        return false;
    }

    protected function ResponseStrFmt ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        $this->responseStrFmt=$action->plainContent();
        return false;
    }

    protected function Cache ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        $this->cacheId=$this->attr('id',$action,$data);
        if (!$this->cacheId)
        {
            return false;
        }
        $cacheFile='resources/cache/'.$this->cacheId.'.dat';
        $cacheHdr='resources/cache/'.$this->cacheId.'.hdr';
        if ((_File::exists($cacheHdr)&&_File::exists($cacheFile)))
        {
            if ((!$action->hasAttribute('expires')||(((time()-_File::getInstance ()->mtime($cacheFile))<$this->attr('expires',$action,$data)))))
            {
                $headers=_Text::explode("\n",_File::getContents($cacheHdr));
                if ((($headers->length()>1)&&$headers->arrayGetElement (1)))
                {
                    $this->header($headers->arrayGetElement (1));
                }
                $this->replyFile($headers->arrayGetElement (0),$cacheFile);
                return null;
            }
        }
        return false;
    }

    protected function CompressResponse ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        $this->compressResponse=$this->attr('value',$action,$data);
        return false;
    }

    protected function PrivilegeRequired ($action, $data)
    {
        $field=$this->attr('field',$action,$data);
        $username=$this->attr('username',$action,$data);
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        if ((!$username&&!_Sentinel::getInstance ()->userAuthenticated()))
        {
            if ($field)
            {
                $data->{$field}='0';
                return false;
            }
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>408),false));
        }
        try
        {
            $privlist=$this->fmt($action->plainContent(),$data);
            _Sentinel::verifyPrivileges($privlist,false,$username);
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $tm_1)
        {
            if ($field)
            {
                $data->{$field}='0';
                return false;
            }
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>403,'error'=>'Privilege required: '.$privlist),false));
        }
        if ($field)
        {
            $data->{$field}='1';
        }
        return false;
    }

    protected function RetrieveData ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $m=(($this->attr('merge',$action,$data)=='true')?true:false);
        $ff=$this->attr('fields',$action,$data);
        $x=$this->attr('fromXml',$action,$data);
        if (($x!=null))
        {
            if ($m)
            {
                $data->arrayGetElement ($s)->merge(_XmlElement::loadFrom($x)->toArray($data->formData,$data),true);
            }
            else
            {
                $data->arraySetElement ($s,_XmlElement::loadFrom($x)->toArray($data->formData,$data->formData));
            }
            if (($ff&&($data->arrayGetElement ($s)->length()>0)))
            {
                $data->arraySetElement ($ff,$data->arrayGetElement ($s)->arrayGetElement (0)->elements());
            }
            else
            {
                $data->arraySetElement ($ff,alpha (new _Array ()));
            }
            return false;
        }
        try
        {
            if (($action->attribute('scalars')=='true'))
            {
                $action=_Resources::getInstance ()->sqlConn->execScalars($this->fmt($action->plainContent(),$data),true);
            }
            else
            {
                $action=_Resources::getInstance ()->sqlConn->execQueryA($this->fmt($action->plainContent(),$data),true);
            }
            if ($m)
            {
                if ($data->arrayGetElement ($s))
                {
                    $data->arrayGetElement ($s)->merge($action,true);
                }
                else
                {
                    $data->arraySetElement ($s,$action);
                }
            }
            else
            {
                $data->arraySetElement ($s,$action);
            }
            $data->sqlError='';
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            if (($this->attr('inhibit',$action,$data)=='true'))
            {
                $data->sqlError=$e->getMessage();
                return false;
            }
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>401,'error'=>$e->getMessage()),false));
        }
        if (($ff&&($data->arrayGetElement ($s)->length()>0)))
        {
            $data->arraySetElement ($ff,$data->arrayGetElement ($s)->arrayGetElement (0)->elements());
        }
        else
        {
            $data->arraySetElement ($ff,alpha (new _Array ()));
        }
        return false;
    }

    protected function SortData ($action, $data)
    {
        $s=$action->attribute('ref');
        $o=$this->attr('order',$action,$data);
        if (!$s)
        {
            $s='data';
        }
        if (!$o)
        {
            $o='asc';
        }
        $o=_Text::toUpperCase($o);
        if (($this->attr('scalars',$action,$data)=='true'))
        {
            if (($this->attr('byLength',$action,$data)=='true'))
            {
                $data->arrayGetElement ($s)->sortl($o);
            }
            else
            {
                $data->arrayGetElement ($s)->sort($o);
            }
        }
        else
        {
            _BluxApiService::$__sortF=$this->attr('field',$action,$data);
            if (($this->attr('byLength',$action,$data)=='true'))
            {
                if (($o=='ASC'))
                {
                    $data->arrayGetElement ($s)->sortx(array($this,'__sortfl1'));
                }
                else
                {
                    $data->arrayGetElement ($s)->sortx(array($this,'__sortfl2'));
                }
            }
            else
            {
                if (($this->attr('numeric',$action,$data)=='true'))
                {
                    if (($o=='ASC'))
                    {
                        $data->arrayGetElement ($s)->sortx(array($this,'__sortfn1'));
                    }
                    else
                    {
                        $data->arrayGetElement ($s)->sortx(array($this,'__sortfn2'));
                    }
                }
                else
                {
                    if (($o=='ASC'))
                    {
                        $data->arrayGetElement ($s)->sortx(array($this,'__sortf1'));
                    }
                    else
                    {
                        $data->arrayGetElement ($s)->sortx(array($this,'__sortf2'));
                    }
                }
            }
        }
        return false;
    }

    public static function __sortf1 ($a, $b)
    {
        return strcmp($a->arrayGetElement (_BluxApiService::$__sortF),$b->arrayGetElement (_BluxApiService::$__sortF));
    }

    public static function __sortf2 ($a, $b)
    {
        return strcmp($b->arrayGetElement (_BluxApiService::$__sortF),$a->arrayGetElement (_BluxApiService::$__sortF));
    }

    public static function __sortfl1 ($a, $b)
    {
        return (strlen($a->arrayGetElement (_BluxApiService::$__sortF))-strlen($b->arrayGetElement (_BluxApiService::$__sortF)));
    }

    public static function __sortfl2 ($a, $b)
    {
        return (strlen($b->arrayGetElement (_BluxApiService::$__sortF))-strlen($a->arrayGetElement (_BluxApiService::$__sortF)));
    }

    public static function __sortfn1 ($a, $b)
    {
        return ($a->arrayGetElement (_BluxApiService::$__sortF)-$b->arrayGetElement (_BluxApiService::$__sortF));
    }

    public static function __sortfn2 ($a, $b)
    {
        return ($b->arrayGetElement (_BluxApiService::$__sortF)-$a->arrayGetElement (_BluxApiService::$__sortF));
    }

    protected function FormatData ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $q=_Map::fromNativeArray(array(),false);
        foreach($action->element('Field')->__nativeArray as $i=>$field)
        {
            $q->setElement($i.'|'.$field->attribute('name').'|'.((($field->attribute('update')=='true')?'u':'a')),$field->plainContent());
        }
        unset ($i);
        foreach($data->arrayGetElement ($s)->__nativeArray as $row)
        {
            foreach($q->__nativeArray as $field=>$content)
            {
                $tmp=_Text::explode('|',$field);
                if ((($tmp->arrayGetElement (2)=='u')&&!$row->hasElement($tmp->arrayGetElement (1))))
                {
                    continue;
                }
                $row->arraySetElement ($tmp->arrayGetElement (1),_Text::format($content,$data,$row));
            }
            unset ($field);
        }
        return false;
    }

    protected function AddField ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $v=$action->plainContent();
        $q=$action->attribute('name');
        foreach($data->arrayGetElement ($s)->__nativeArray as $row)
        {
            $row->setElement($q,_Text::format($v,$data,$row));
        }
        return false;
    }

    protected function RemoveField ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $q=$action->attribute('name');
        if (($s=='root'))
        {
            $data->removeElement($q);
        }
        else
        {
            foreach($data->arrayGetElement ($s)->__nativeArray as $row)
            {
                $row->removeElement($q);
            }
        }
        return false;
    }

    protected function ImplodeData ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $q=$action->attribute('target');
        $t=$action->plainContent();
        $x=alpha (new _Array ());
        $refData=null;
        if (($s[0]=='{'))
        {
            $refData=$this->fmt($s,$data);
        }
        else
        {
            $refData=$data->arrayGetElement ($s);
        }
        foreach($refData->__nativeArray as $row)
        {
            $x->push(_Text::format($t,$data,$row));
        }
        $data->arraySetElement ($q,$x->implode($action->attribute('delimiter')));
        return false;
    }

    protected function SplitString ($action, $data)
    {
        $q=$action->attribute('into');
        $d=$this->attr('delimiter',$action,$data);
        $data->arraySetElement ($q,_Text::explode(($d?$d:','),$this->fmt($action->plainContent(),$data)));
        return false;
    }

    protected function ExtractData ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $t=_Regex::stMatchAll($this->fmt($action->Pattern->arrayGetElement (0)->plainContent(),$data),$this->fmt($action->Source->arrayGetElement (0)->plainContent(),$data),true);
        $m=$t->arrayGetElement (0)->length();
        $r=null;;
        $k=null;;
        if (($action->attribute('merge')!='true'))
        {
            $r=alpha (new _Array ());
            $data->arraySetElement ($s,$r);
            for ($k=0; ($k<$m); $k++)
            {
                $r->arraySetElement ($k,alpha (new _Map ()));
            }
        }
        else
        {
            $r=$data->arrayGetElement ($s);
            if (!$r)
            {
                $r=alpha (new _Array ());
                $data->arraySetElement ($s,$r);
                for ($k=0; ($k<$m); $k++)
                {
                    $r->arraySetElement ($k,alpha (new _Map ()));
                }
            }
        }
        foreach($action->Field->__nativeArray as $field)
        {
            $n=$field->attribute('name');
            $i=((int)$field->attribute('index'));
            $f=$field->plainContent();
            for ($k=0; ($k<$m); $k++)
            {
                if (($f!=null))
                {
                    $r->arrayGetElement ($k)->{$n}=$this->fmt(_Text::replace('$',$t->arrayGetElement ($i)->arrayGetElement ($k),$f),$data);
                }
                else
                {
                    $r->arrayGetElement ($k)->{$n}=$t->arrayGetElement ($i)->arrayGetElement ($k);
                }
            }
        }
        return false;
    }

    protected function RetrieveAssoc ($action, $data)
    {
        $x=$this->attr('fromXml',$action,$data);
        $r=null;;
        $f=$this->attr('sessionField',$action,$data);
        $n=$this->attr('resourceName',$action,$data);
        $f2=$this->attr('field',$action,$data);
        if (!$f2)
        {
            $f2=$this->attr('ref',$action,$data);
        }
        if (($x!=null))
        {
            $r=_XmlElement::loadFrom($x)->toMap($data->formData,$data);
        }
        else
        {
            $r=_Resources::getInstance ()->sqlConn->execAssoc($this->fmt($action->plainContent(),$data));
        }
        if (($n!=null))
        {
            if ((!_Resources::getInstance ()->exists($n)||(_Resources::getInstance ()->{$n}===null)))
            {
                _Resources::getInstance ()->{$n}=alpha (new _Map ());
            }
            try
            {
                _Resources::getInstance ()->{$n}->merge($r,true);
            }
            catch (Exception $e){}
        }
        else
        {
            if (($f!=null))
            {
                if ((!_Session::getInstance ()->hasElement($f)||(_Session::getInstance ()->{$f}===null)))
                {
                    _Session::getInstance ()->{$f}=alpha (new _Map ());
                }
                try
                {
                    _Session::getInstance ()->{$f}->merge($r,true);
                }
                catch (Exception $e){}
            }
            else
            {
                if (($f2!=null))
                {
                    if (($this->attr('merge',$action,$data)=='true'))
                    {
                        if (!$data->arrayGetElement ($f2))
                        {
                            $data->arraySetElement ($f2,alpha (new _Map ()));
                        }
                        try
                        {
                            $data->arrayGetElement ($f2)->merge($r,true);
                        }
                        catch (Exception $e){}
                    }
                    else
                    {
                        $data->arraySetElement ($f2,($r?$r:alpha (new _Map ())));
                    }
                }
                else
                {
                    try
                    {
                        $data->merge($r,true);
                    }
                    catch (Exception $e){}
                }
            }
        }
        return false;
    }

    protected function GroupData ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $group='/^('._Text::explode(',',$this->fmt($action->attribute('group'),$data))->format('{f:trim:0}')->implode('|').')$/';
        $into=$this->fmt($action->attribute('into'),$data);
        $dofmt=($this->attr('format',$action,$data)=='true');
        $refData=null;;
        $refMap=alpha (new _Map ());
        $content=$action->plainContent();
        $pcontent=$content;
        if ($content)
        {
            $content=$this->_PrepareBuildAssoc($content,$data);
        }
        if (($s[0]=='{'))
        {
            $refData=$this->fmt($s,$data);
        }
        else
        {
            $refData=$data->arrayGetElement ($s);
        }
        foreach($refData->__nativeArray as $row)
        {
            $tmp=$row->selectAll($group,true);
            $row->removeAll($group,true);
            $id=$tmp->format('{1}')->implode('|');
            $out=$refMap->arrayGetElement ($id);
            if (!$out)
            {
                $refMap->arraySetElement ($id,$tmp);
                $tmp->arraySetElement ($into,alpha (new _Array ()));
                $out=$refMap->arrayGetElement ($id);
            }
            $isNull=true;
            foreach($row->__nativeArray as $field)
            {
                if (($field!==null))
                {
                    $isNull=false;
                    break;
                }
            }
            if (!$isNull)
            {
                if ($content)
                {
                    $out->arrayGetElement ($into)->push($this->_ExecBuildAssoc($content,$row,$pcontent,$dofmt));
                }
                else
                {
                    $out->arrayGetElement ($into)->push($row);
                }
            }
        }
        $refData->clear();
        foreach($refMap->__nativeArray as $row)
        {
            $refData->push($row);
        }
        return false;
    }

    protected function ReturnData ($action, $data)
    {
        if ($action->plainContent())
        {
            $n=$action->attribute('into');
            if ($n)
            {
                if (($action->attribute('merge')=='true'))
                {
                    if (($action->attribute('type')=='array'))
                    {
                        if (!$data->arrayGetElement ($n))
                        {
                            $data->arraySetElement ($n,alpha (new _Array ()));
                        }
                        $data->arrayGetElement ($n)->merge($action->toArray($data->formData,$data),true);
                    }
                    else
                    {
                        if (!$data->arrayGetElement ($n))
                        {
                            $data->arraySetElement ($n,alpha (new _Map ()));
                        }
                        $data->arrayGetElement ($n)->merge($action->toMap($data->formData,$data),true);
                    }
                }
                else
                {
                    if (($action->attribute('type')=='array'))
                    {
                        $data->arraySetElement ($n,$action->toArray($data->formData,$data));
                    }
                    else
                    {
                        $data->arraySetElement ($n,$action->toMap($data->formData,$data));
                    }
                }
                return false;
            }
            if (($action->attribute('type')=='array'))
            {
                return $this->FmtResult($action->toArray($data->formData,$data));
            }
            else
            {
                return $this->FmtResult($action->toMap($data->formData,$data));
            }
        }
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        if (($action->attribute('noWrap')=='true'))
        {
            return $this->FmtResult($data->arrayGetElement ($s));
        }
        $q=$data->arrayGetElement ($s);
        $r=_Map::fromNativeArray(array('response'=>200),false);
        if (($action->attribute('compact')=='true'))
        {
            if ($q->length())
            {
                $r->fields=$q->arrayGetElement (0)->elements();
                $r->data=_Array::fromNativeArray(array(),false);
                foreach($q->__nativeArray as $item)
                {
                    $r->data->push($item->values());
                }
            }
        }
        else
        {
            $r->data=$q;
        }
        return $this->FmtResult($r);
    }

    protected function _PrepareBuildAssoc ($content, $data)
    {
        return _Text::explode(',',$this->fmt(_Regex::stExtract('/[\/A-Za-z0-9:,._=@}{]+/',$this->fmt($content,$data)),$data));
    }

    protected function _ExecBuildAssoc ($prepared, $data, $pcontent, $dformat)
    {
        if ($dformat)
        {
            return $this->fmt($pcontent,$data);
        }
        $output=_Map::fromNativeArray(array(),false);
        foreach($prepared->__nativeArray as $field)
        {
            $inf=_Text::explode(':',$field);
            if (($inf->length()==2))
            {
                $inf->arraySetElement (1,_Text::explode('=',$inf->arrayGetElement (1)));
                if (($inf->arrayGetElement (1)->length()==1))
                {
                    $inf->arrayGetElement (1)->push($inf->arrayGetElement (0));
                }
                switch ($inf->arrayGetElement (1)->arrayGetElement (0))
                {
                    case 'int':
                    $output->arraySetElement ($inf->arrayGetElement (0),((int)$this->fmt('{'.$inf->arrayGetElement (1)->arrayGetElement (1).'}',$data)));
                    break;
                    case 'bool':
                    $output->arraySetElement ($inf->arrayGetElement (0),((bool)$this->fmt('{'.$inf->arrayGetElement (1)->arrayGetElement (1).'}',$data)));
                    break;
                    case 'float':
                    $output->arraySetElement ($inf->arrayGetElement (0),((float)$this->fmt('{'.$inf->arrayGetElement (1)->arrayGetElement (1).'}',$data)));
                    break;
                    case 'direct':
                    case 'object':
                    $output->arraySetElement ($inf->arrayGetElement (0),$data->{$inf->arrayGetElement (1)->arrayGetElement (1)});
                    break;
                    default:
                    $output->arraySetElement ($inf->arrayGetElement (0),$this->fmt('{'.$inf->arrayGetElement (1)->arrayGetElement (1).'}',$data));
                    break;
                }
            }
            else
            {
                $output->arraySetElement ($inf->arrayGetElement (0),((String)$this->fmt('{'.$inf->arrayGetElement (0).'}',$data)));
            }
        }
        return $output;
    }

    protected function _BuildAssoc ($action, $data)
    {
        $content=$action->plainContent();
        $dofmt=($this->attr('format',$action,$data)=='true');
        return $this->_ExecBuildAssoc($this->_PrepareBuildAssoc($content,$data),$data,$content,$dofmt);
    }

    protected function ReturnAssoc ($action, $data)
    {
        $f=$this->attr('field',$action,$data);
        $r=$this->attr('resource',$action,$data);
        $n=$this->attr('into',$action,$data);
        $s=$this->attr('sessionField',$action,$data);
        if ($action->hasAttribute('source'))
        {
            return $this->FmtResult($this->attr('source',$action,$data));
        }
        $output=$this->_BuildAssoc($action,$data);
        if (($r!=null))
        {
            _Resources::getInstance ()->{$r}=$output;
            return false;
        }
        if (($n!=null))
        {
            $data->arraySetElement ($n,$output);
            return false;
        }
        if (($f!=null))
        {
            $data->arraySetElement ($f,$this->FmtResult($output,true));
            return false;
        }
        if (($s!=null))
        {
            if ((!_Session::getInstance ()->hasElement($s)||(_Session::getInstance ()->{$s}===null)))
            {
                _Session::getInstance ()->{$s}=alpha (new _Map ());
            }
            try
            {
                _Session::getInstance ()->{$s}->merge($output,true);
            }
            catch (Exception $e){}
        }
        else
        {
            return $this->FmtResult($output);
        }
    }

    protected function PushAssoc ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $output=$this->_BuildAssoc($action,$data);
        if (($data->arrayGetElement ($s)==null))
        {
            $data->arraySetElement ($s,alpha (new _Array ()));
        }
        $data->arrayGetElement ($s)->push($output);
        return false;
    }

    protected function UnshiftAssoc ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $output=$this->_BuildAssoc($action,$data);
        if (($data->arrayGetElement ($s)==null))
        {
            $data->arraySetElement ($s,alpha (new _Array ()));
        }
        $data->arrayGetElement ($s)->unshift($output);
        return false;
    }

    protected function MergeAssoc ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $output=$this->_BuildAssoc($action,$data);
        if (($data->arrayGetElement ($s)==null))
        {
            $data->arraySetElement ($s,alpha (new _Map ()));
        }
        $data->arrayGetElement ($s)->merge($output,true);
        return false;
    }

    protected function ReturnModules ($action, $data)
    {
        $result=_Map::fromNativeArray(array('response'=>200,'data'=>_Map::fromNativeArray(array(),false)),false);
        $params=_Map::fromNativeArray(array(),false);
        $list=_Text::explode(',',$this->fmt($action->plainContent(),$data));
        $root=_Configuration::getInstance ()->General->root;
        $manifest=null;
        if (_File::exists('resources/mods/manifest.xml'))
        {
            $manifest=_XmlElement::loadFrom('resources/mods/manifest.xml')->toMap();
        }
        foreach($list->__nativeArray as $item)
        {
            $item=_Text::trim($item);
            $temp=_Text::replace('.','/',$item);
            $base='resources/mods/'.$temp.'/';
            if (!_File::exists($base.'module.xml'))
            {
                if ((!$manifest||(($manifest&&!$manifest->hasElement($item)))))
                {
                    continue;
                }
                $mod=$manifest->arrayGetElement ($item);
                if (!$mod)
                {
                    $mod=_Map::fromNativeArray(array(),false);
                }
                if ($mod->source)
                {
                    $base=$this->fmt($mod->source,_Map::fromNativeArray(array(),false));
                    if ((_Text::substring($base,-1)=='/'))
                    {
                        $base=_Text::substring($base,0,-1);
                    }
                }
                if (!_File::exists($base.'/module.xml'))
                {
                    $base=_Configuration::getInstance ()->Output->commonModules.$temp;
                }
                if (!_File::exists($base.'/module.xml'))
                {
                    continue;
                }
                $base.='/';
            }
            $params->base=$base;
            $params->baseUrl=$root.$params->base;
            try
            {
                $xml=_XmlElement::loadFrom($params->base.'module.xml');
                if (($xml->name()!='module'))
                {
                    trace('Warning: module.xml file for '.$item.' doesn\'t contain a module definition.');
                    continue;
                }
                while ((($xml!=null)&&$xml->hasAttribute('source')))
                {
                    $params->base=$this->attr('source',$xml,$data);
                    $xml=_XmlElement::loadFrom($params->base.'module.xml');
                    if (($xml->name()!='module'))
                    {
                        $xml=null;
                        break;
                    }
                };
                if (($xml==null))
                {
                    trace('Warning: module source was not found or doesn\'t contain a module definition.');
                    continue;
                }
                $inf=_Map::fromNativeArray(array('dets'=>$xml->details->arrayGetElement (0)->toMap($params),'res'=>_Array::fromNativeArray(array(),false)),false);
                $infBase=$inf->replicate(true);
                $blocks=$xml->block;
                $blocks->unshift($xml);
                if ((($inf->dets->privileges!=null)&&_Sentinel::verifyPrivileges($inf->dets->privileges,true)))
                {
                    $inf->dets->removeElement('privileges');
                    $result->data->arraySetElement ($item,$inf);
                    continue;
                }
                $inf->dets->removeElement('privileges');
                $path=null;;
                $temp=null;;
                $base2=null;;
                if ($inf->dets->name)
                {
                    $result->data->arraySetElement ($inf->dets->name,$inf);
                }
                if ($inf->dets->base)
                {
                    $base2=(($inf->dets->base[0]=='/')?$inf->dets->base:($params->base.$inf->dets->base));
                }
                else
                {
                    $base2=$params->base;
                }
                $params->base=$base2;
                $params->baseUrl=$root.$params->base;
                foreach($blocks->__nativeArray as $xml)
                {
                    if ((($xml->hasAttribute('privileges')!=null)&&_Sentinel::verifyPrivileges($xml->attribute('privileges'),true)))
                    {
                        continue;
                    }
                    if (($xml->hasAttribute('moduleName')!=null))
                    {
                        $inf=$infBase->replicate(true);
                        $inf->dets->name=$this->attr('moduleName',$xml,$data);
                        $result->data->arraySetElement ($inf->dets->name,$inf);
                    }
                    try
                    {
                        foreach($xml->strings->__nativeArray as $res)
                        {
                            $path=$base2.$this->attr('file',$res,$data);
                            $temp=$res->attribute('name');
                            if (!$temp)
                            {
                                $temp='/Local';
                            }
                            _Strings::getInstance ()->load($temp,$path,true);
                        }
                    }
                    catch (_Exception $e)
                    {
                        trace($e);
                        $result->data->arraySetElement ($item,$inf);
                        continue;
                    }
                    foreach($xml->resource->__nativeArray as $res)
                    {
                        foreach(_Text::explode(',',$res->attribute('name'))->__nativeArray as $resName)
                        {
                            $resName=_Text::format($resName);
                            $path=$res->attribute('path');
                            if (!$path)
                            {
                                $path=$res->attribute('base').$resName.'.'.$res->attribute('type');
                            }
                            $path=$base2.$path;
                            $content=_File::getContents($path);
                            $error='';
                            $params->rbase=_Directory::getInstance ()->path($path).'/';
                            $params->rbaseUrl=$root.$params->rbase;
                            if (($res->attribute('uieval')=='true'))
                            {
                                try
                                {
                                    $content=_UIElement::loadFromBuffer($content)->asXml();
                                }
                                catch (_Exception $e)
                                {
                                    $error=$e->__typeCast ('String');
                                    $content='';
                                }
                            }
                            if (($res->attribute('format')=='true'))
                            {
                                $content=_Text::format($content,$params,$data);
                            }
                            if ($root)
                            {
                                if (_Configuration::getInstance ()->Locale->lang)
                                {
                                    $content=_Text::replace('////',$root._Gateway::getInstance ()->requestParams->lang.'/',$content);
                                }
                                $content=_Text::replace('///',$root,$content);
                            }
                            $inf->res->push(_Map::fromNativeArray(array('name'=>$resName,'type'=>$res->attribute('type'),'data'=>$content,'error'=>$error),false));
                        }
                    }
                }
            }
            catch (_FalseException $e)
            {
                throw $e;
            }
            catch (_Exception $e)
            {
                trace($e);
                continue;
            }
        }
        return $this->FmtResult($result);
    }

    protected function ReturnValue ($action, $data)
    {
        return _Text::explode("\n",$this->fmt($action->plainContent(),$data))->format('{filter:trim:0}')->implode("\n");
    }

    protected function ReturnBinary ($action, $data)
    {
        return $this->fmt($action->plainContent(),$data);
    }

    protected function ReturnXml ($action, $data)
    {
        $n=$action->attribute('into');
        if ($n)
        {
            if (($action->attribute('type')=='array'))
            {
                $data->arraySetElement ($n,$action->toArray($data->formData,$data));
            }
            else
            {
                $data->arraySetElement ($n,$action->toMap($data->formData,$data));
            }
            return false;
        }
        if (($action->attribute('json')=='true'))
        {
            return $this->FmtResult($action->toMap($data->formData,$data));
        }
        else
        {
            return $this->fmt($action->plainContent(),$data);
        }
    }

    protected function ReturnFile ($action, $data)
    {
        $this->replyFile($this->attr('contentType',$action,$data),$this->attr('path',$action,$data));
        if (($this->attr('finalize',$action,$data)=='false'))
        {
            return false;
        }
        return null;
    }

    protected function ValidateFields ($action, $data)
    {
        $errors=alpha (new _Map ());
        $masterMap=_Gateway::getInstance ()->requestParams->replicate();
        if (($this->attr('fromLocalData',$action,$data)=='true'))
        {
            $masterMap=$data;
        }
        if (($this->attr('fromFormData',$action,$data)=='true'))
        {
            $masterMap=$data->formData;
        }
        $data->validationError='0';
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='formData';
        }
        $outp=$data->arrayGetElement ($s);
        if (!$outp)
        {
            $outp=$data->arraySetElement ($s,alpha (new _Map ()));
        }
        if (($this->attr('overrideLocalData',$action,$data)=='true'))
        {
            $outp=$data;
        }
        foreach($action->content->__nativeArray as $field)
        {
            if ($field->hasAttribute('condition'))
            {
                if (!$this->attr('condition',$field,$data))
                {
                    continue;
                }
            }
            $s=$field->attribute('name');
            $v=_Text::trim($masterMap->arrayGetElement ($s));
            if (($v===null))
            {
                $v='';
            }
            foreach($field->content->__nativeArray as $rule)
            {
                try
                {
                    $i=_FormValidator::validateRule($rule,$s,$v,$masterMap,$outp);
                    if (($i===null))
                    {
                        continue;
                    }
                    if ((($i===false)||((($i!==true)&&($i!==null)))))
                    {
                        $v=$i;
                        break;
                    }
                    $v=$rule->attribute('failureMsg');
                    if (($v==null))
                    {
                        $v=_FormValidator::getMessage(null,$rule->name(),$outp);
                    }
                    if (($v[0]=='#'))
                    {
                        $v=_FormValidator::getMessage(null,_Text::substring($v,1),$outp);
                    }
                    else
                    {
                        $v=$this->fmt($v,$data);
                    }
                    throw alpha (new _Exception ($v));
                }
                catch (_FalseException $e)
                {
                    throw $e;
                }
                catch (_Exception $e)
                {
                    $v=$field->attribute('default');
                    if (($v!=null))
                    {
                        $v=$this->fmt($v,$data);
                        break;
                    }
                    $v=false;
                    $errors->setElement($s,$e->getMessage());
                    break;
                }
            }
            if (($v===false))
            {
                continue;
            }
            $outp->arraySetElement ($s,$v);
        }
        if ($errors->length())
        {
            $n=((int)$this->attr('response',$action,$data));
            if (($n==null))
            {
                $n=407;
            }
            if ($action->hasAttribute('failureMsg'))
            {
                $s=$this->attr('failureMsg',$action,$data);
                if (($s!=null))
                {
                    if (($action->attribute('includeFields')=='true'))
                    {
                        return $this->FmtResult(_Map::fromNativeArray(array('response'=>$n,'error'=>$s,'fields'=>$errors),false));
                    }
                    else
                    {
                        return $this->FmtResult(_Map::fromNativeArray(array('response'=>$n,'error'=>$s),false));
                    }
                }
                else
                {
                    if (($action->attribute('includeFields')=='true'))
                    {
                        return $this->FmtResult(_Map::fromNativeArray(array('response'=>$n,'fields'=>$errors),false));
                    }
                    else
                    {
                        return $this->FmtResult(_Map::fromNativeArray(array('response'=>$n),false));
                    }
                }
            }
            if (($this->attr('quiet',$action,$data)=='true'))
            {
                $data->validationError='1';
                $data->validationErrors=$errors;
                return false;
            }
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>$n,'fields'=>$errors),false));
        }
        return false;
    }

    protected function Handler ($action, $data)
    {
        try
        {
            $reuse=$action->attribute('name');
            $instance=null;;
            if (($reuse!=null))
            {
                if (!_Resources::getInstance ()->exists($reuse))
                {
                    _Resources::getInstance ()->{$reuse}=vnew ($action->attribute('class'));
                }
                $instance=_Resources::getInstance ()->{$reuse};
            }
            else
            {
                $instance=vnew ($action->attribute('class'));
            }
            foreach($action->Invoke->__nativeArray as $invoke)
            {
                $params=$invoke->toMap($data->formData,$data);
                $params->formData=$data->formData;
                $r=$instance->{$invoke->attribute('method')}($params);
                $n=$this->attr('field',$invoke,$data);
                if (($invoke->attribute('merge')=='true'))
                {
                    try
                    {
                        $data->merge($r,true);
                    }
                    catch (Exception $e){}
                }
                if ($n)
                {
                    $data->arraySetElement ($n,$r);
                }
            }
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>407,'error'=>$e->getMessage()),false));
        }
        return false;
    }

    protected function Conditional ($action, $data)
    {
        $subject=$this->fmt($action->attribute('subject'),$data);
        $child=null;;
        $response=0;
        foreach($action->content->__nativeArray as $child)
        {
            switch ($child->name())
            {
                case 'True':
                if ($subject)
                {
                    $response=1;
                }
                break;
                case 'False':
                if (!$subject)
                {
                    $response=1;
                }
                break;
                case 'Default':
                $response=1;
                break;
                case 'Numeric':
                if ((((float)$child->attribute('value'))==((float)$subject)))
                {
                    $response=1;
                }
                break;
                case 'String':
                if (($this->fmt($child->attribute('value'),$data)==$subject))
                {
                    $response=1;
                }
                break;
                case 'Check':
                if (_Text::format($child->attribute('condition'),$data,_Array::fromNativeArray(array($subject),false)))
                {
                    $response=1;
                }
                break;
                default:
                if (($child->name()==$subject))
                {
                    $response=1;
                }
                break;
            }
            if ($response)
            {
                break;
            }
        }
        if (!$response)
        {
            return false;
        }
        foreach($child->content->__nativeArray as $n_action)
        {
            $response=$this->{$n_action->name()}($n_action,$data);
            if (($response===_BluxApiService::$_Brk))
            {
                return false;
            }
            if (($response!==false))
            {
                return $response;
            }
        }
        return false;
    }

    protected function Traversal ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $field=$this->attr('field',$action,$data);
        $update=$this->attr('update',$action,$data);
        $i=null;;
        $index=null;;
        $refData=null;;
        if ($update)
        {
            $update=_Text::explode(',',$update);
        }
        if (($s[0]=='{'))
        {
            $refData=$this->fmt($s,$data);
        }
        else
        {
            $refData=$data->arrayGetElement ($s);
        }
        $i1=0;
        $i2=$refData->size;
        $i3=1;
        $original=$data->formData->replicate();
        if (($this->attr('reversed',$action,$data)=='true'))
        {
            $i1=($i2-1);
            $i2=-1;
            $i3=-1;
            for ($index=$i1; ($index!=$i2); $index+=$i3)
            {
                $data->__index=$index;
                if (($field==null))
                {
                    $data->formData->merge($refData->arrayGetElement ($index),true);
                }
                else
                {
                    $data->{$field}=$refData->arrayGetElement ($index);
                }
                foreach($action->content->__nativeArray as $n_action)
                {
                    $response=$this->{$n_action->name()}($n_action,$data);
                    if (($response===_BluxApiService::$_Brk))
                    {
                        $data->formData=$original;
                        return false;
                    }
                    if (($response===_BluxApiService::$_Cont))
                    {
                        break;
                    }
                    if (($response!==false))
                    {
                        return $response;
                    }
                }
                if ($update)
                {
                    foreach($update->__nativeArray as $i)
                    {
                        $refData->arrayGetElement ($index)->arraySetElement ($i,$this->fmt('{'.$i.'}',$data));
                    }
                }
            }
        }
        else
        {
            foreach($refData->__nativeArray as $index=>$value)
            {
                $data->__index=$index;
                if (($field==null))
                {
                    $data->formData->merge($value,true);
                }
                else
                {
                    $data->{$field}=$value;
                }
                foreach($action->content->__nativeArray as $n_action)
                {
                    $response=$this->{$n_action->name()}($n_action,$data);
                    if (($response===_BluxApiService::$_Brk))
                    {
                        $data->formData=$original;
                        return false;
                    }
                    if (($response===_BluxApiService::$_Cont))
                    {
                        break;
                    }
                    if (($response!==false))
                    {
                        return $response;
                    }
                }
                if ($update)
                {
                    foreach($update->__nativeArray as $i)
                    {
                        $value->arraySetElement ($i,$this->fmt('{'.$i.'}',$data));
                    }
                }
            }
            unset ($index);
        }
        $data->formData=$original;
        return false;
    }

    protected function DataReaderTraversal ($action, $data)
    {
        $dr=_Resources::getInstance ()->sqlConn->execReader($this->attr('source',$action,$data));
        $row=null;;
        $index=0;
        $field=$this->attr('field',$action,$data);
        while ((($row=$dr->getAssoc())!=null))
        {
            $data->__index=$index++;
            if (($field==null))
            {
                $data->formData->merge($row,true);
            }
            else
            {
                $data->{$field}=$row;
            }
            foreach($action->content->__nativeArray as $n_action)
            {
                $response=$this->{$n_action->name()}($n_action,$data);
                if (($response===_BluxApiService::$_Brk))
                {
                    return false;
                }
                if (($response===_BluxApiService::$_Cont))
                {
                    break;
                }
                if (($response!==false))
                {
                    return $response;
                }
            }
        };
        $dr->close();
        return false;
    }

    protected function Sql ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        $delim=$this->attr('delimiter',$action,$data);
        if (($delim==''))
        {
            $delim=';';
        }
        $preFormat=($this->attr('preformat',$action,$data)=='true');
        try
        {
            if (!$preFormat)
            {
                foreach(_Text::explode($delim,$action->plainContent())->__nativeArray as $query)
                {
                    $query=_Text::trim($query);
                    if (!$query)
                    {
                        continue;
                    }
                    _Resources::getInstance ()->sqlConn->execQuery($this->fmt($query,$data));
                }
            }
            else
            {
                foreach(_Text::explode($delim,$this->fmt($action->plainContent(),$data))->__nativeArray as $query)
                {
                    $query=_Text::trim($query);
                    if (!$query)
                    {
                        continue;
                    }
                    _Resources::getInstance ()->sqlConn->execQuery($query);
                }
            }
            $data->sqlError='';
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            if (($this->attr('inhibit',$action,$data)=='true'))
            {
                $data->sqlError=$e->getMessage();
                return false;
            }
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>401,'error'=>$e->getMessage()),false));
        }
        return false;
    }

    protected function Stdout ($action, $data)
    {
        $f=$this->attr('field',$action,$data);
        if (($f!=null))
        {
            if (($this->attr('prepend',$action,$data)=='true'))
            {
                $data->arraySetElement ($f,$this->fmt($action->plainContent(),$data).$data->arrayGetElement ($f));
            }
            else
            {
                $data->arraySetElement ($f,($data->arrayGetElement ($f)).$this->fmt($action->plainContent(),$data));
            }
        }
        else
        {
            echo($this->fmt($action->plainContent(),$data));
        }
        return false;
    }

    protected function Log ($action, $data)
    {
        $outfile=$this->attr('file',$action,$data);
        trace($this->fmt($action->plainContent(),$data),$outfile);
        return false;
    }

    protected function Exec ($action, $data)
    {
        $this->fmt($action->plainContent(),$data);
        return false;
    }

    protected function __setPropertyValue ($data, $path, $value, $concat)
    {
        $k1=_Text::explode('.',$path);
        if (($k1->length()==1))
        {
            if ($concat)
            {
                $data->arraySetElement ($k1->arrayGetElement (0),($data->arrayGetElement ($k1->arrayGetElement (0))).$value);
            }
            else
            {
                $data->arraySetElement ($k1->arrayGetElement (0),$value);
            }
            return ;
        }
        $tmp=$data->arrayGetElement ($k1->arrayGetElement (0));
        if (!$tmp)
        {
            return ;
        }
        $k1->shift();
        $item=null;
        while (($k1->length()>1))
        {
            $item=$k1->shift();
            if (is_numeric($item))
            {
                $tmp=$tmp->arrayGetElement ($item);
            }
            else
            {
                $tmp=$tmp->{$item};
            }
        };
        $item=$k1->shift();
        if (is_numeric($item))
        {
            if ($concat)
            {
                $tmp->arraySetElement ($item,($tmp->arrayGetElement ($item)).$value);
            }
            else
            {
                $tmp->arraySetElement ($item,$value);
            }
        }
        else
        {
            if ($concat)
            {
                $tmp->{$item}.=$value;
            }
            else
            {
                $tmp->{$item}=$value;
            }
        }
    }

    protected function __removeProperty ($data, $path)
    {
        $k1=_Text::explode('.',$path);
        if (($k1->length()==1))
        {
            if (is_numeric($k1->arrayGetElement (0)))
            {
                $data->remove($k1->arrayGetElement (0));
            }
            else
            {
                $data->removeElement($k1->arrayGetElement (0));
            }
            return ;
        }
        $tmp=$data->arrayGetElement ($k1->arrayGetElement (0));
        if (!$tmp)
        {
            return ;
        }
        $k1->shift();
        $item=null;
        while (($k1->length()>1))
        {
            $item=$k1->shift();
            if (is_numeric($item))
            {
                $tmp=$tmp->arrayGetElement ($item);
            }
            else
            {
                $tmp=$tmp->{$item};
            }
        };
        $item=$k1->shift();
        if (is_numeric($item))
        {
            $tmp->remove($item);
        }
        else
        {
            $tmp->removeElement($item);
        }
    }

    protected function Set ($action, $data)
    {
        $concat=($this->attr('concat',$action,$data)=='true');
        $v=$this->fmt($action->plainContent(),$data);
        $k=null;;
        $i=null;;
        $n=null;;
        $o=null;
        if ($action->hasAttribute('condition'))
        {
            if (!$this->fmt($action->attribute('condition'),$data))
            {
                return false;
            }
        }
        foreach($action->attributes()->__nativeArray as $att)
        {
            switch ($att->name())
            {
                case 'param':
                if ($concat)
                {
                    _Gateway::getInstance ()->requestParams->{$this->fmt($att->value,$data)}.=$v;
                }
                else
                {
                    _Gateway::getInstance ()->requestParams->{$this->fmt($att->value,$data)}=$v;
                }
                break;
                case 'formField':
                if ($concat)
                {
                    $data->formData->{$this->fmt($att->value,$data)}.=$v;
                }
                else
                {
                    $data->formData->{$this->fmt($att->value,$data)}=$v;
                }
                break;
                case 'sessionField':
                $k=_Text::explode('.',$this->fmt($att->value,$data));
                $n=$k->length();
                $o=_Session::getInstance();
                for ($i=0; ($i<($n-1)); $i++)
                {
                    if (($o->{$k->arrayGetElement ($i)}==null))
                    {
                        $o->{$k->arrayGetElement ($i)}=alpha (new _Map ());
                    }
                    $o=$o->{$k->arrayGetElement ($i)};
                }
                if ($concat)
                {
                    $o->{$k->arrayGetElement ($i)}.=$v;
                }
                else
                {
                    $o->{$k->arrayGetElement ($i)}=$v;
                }
                break;
                case 'userField':
                if ((_Session::getInstance ()->CurrentUser==null))
                {
                    if (($this->attr('force',$action,$data)!='true'))
                    {
                        break;
                    }
                    _Session::getInstance ()->CurrentUser=alpha (new _Map ());
                }
                if ($concat)
                {
                    _Session::getInstance ()->CurrentUser->{$this->fmt($att->value,$data)}.=$v;
                }
                else
                {
                    _Session::getInstance ()->CurrentUser->{$this->fmt($att->value,$data)}=$v;
                }
                break;
                case 'cookieField':
                if ($concat)
                {
                    _SystemParameters::getInstance ()->{$this->fmt($att->value,$data)}.=$v;
                }
                else
                {
                    _SystemParameters::getInstance ()->{$this->fmt($att->value,$data)}=$v;
                }
                break;
                case 'field':
                $s=$this->attr('resource',$action,$data);
                if (($s!=null))
                {
                    $this->__setPropertyValue(_Resources::getInstance ()->{$s},$this->fmt($att->value,$data),$v,$concat);
                }
                else
                {
                    $this->__setPropertyValue($data,$this->fmt($att->value,$data),$v,$concat);
                }
                break;
                case 'var':
                if ($concat)
                {
                    _Resources::getInstance ()->Vars->{$this->fmt($att->value,$data)}.=$v;
                }
                else
                {
                    _Resources::getInstance ()->Vars->{$this->fmt($att->value,$data)}=$v;
                }
                break;
                case 'resource':
                if ($concat)
                {
                    _Resources::getInstance ()->{$this->fmt($att->value,$data)}.=$v;
                }
                else
                {
                    _Resources::getInstance ()->{$this->fmt($att->value,$data)}=$v;
                }
                break;
            }
        }
        return false;
    }

    protected function Block ($action, $data)
    {
        $data->blockExecuted='0';
        if ($action->hasAttribute('condition'))
        {
            if (!$this->fmt($action->attribute('condition'),$data))
            {
                return false;
            }
        }
        if ($action->hasAttribute('requires'))
        {
            if (_Sentinel::verifyPrivileges($this->attr('requires',$action,$data),true))
            {
                return false;
            }
        }
        if ($action->hasAttribute('not-requires'))
        {
            if (!_Sentinel::verifyPrivileges($this->attr('not-requires',$action,$data),true))
            {
                return false;
            }
        }
        $data->blockExecuted='1';
        try
        {
            foreach($action->content->__nativeArray as $n_action)
            {
                $response=$this->{$n_action->name()}($n_action,$data);
                if (($response!==false))
                {
                    return $response;
                }
            }
            $data->blockError='';
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            if (($this->attr('inhibit',$action,$data)=='true'))
            {
                $data->blockError=$e->getMessage();
                return false;
            }
            if (($this->attr('failureMsg',$action,$data)!=''))
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>409,'error'=>$this->attr('failureMsg',$action,$data)),false));
            }
            if (($this->attr('debug',$action,$data)=='true'))
            {
                trace($e);
            }
            else
            {
                throw $e;
            }
        }
        return false;
    }

    protected function EvalPHP ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->fmt($action->attribute('condition'),$data))
            {
                return false;
            }
        }
        $s=$action->plainContent();
        if (($action->attribute('format')!='false'))
        {
            $s=$this->fmt($s,$data);
        }
        if (($action->attribute('trace')=='true'))
        {
            trace($s);
        }
        if ($action->attribute('param'))
        {
            _Gateway::getInstance ()->requestParams->{$this->attr('param',$action,$data)}=eval($s);
        }
        else
        {
            if ($action->attribute('formField'))
            {
                $data->formData->{$this->attr('formField',$action,$data)}=eval($s);
            }
            else
            {
                $data->{$this->attr('field',$action,$data)}=eval($s);
            }
        }
        return false;
    }

    protected function HttpHeader ($action, $data)
    {
        foreach(_Text::explode("\n",$this->fmt($action->plainContent(),$data))->__nativeArray as $line)
        {
            if (!($line=_Text::trim($line)))
            {
                continue;
            }
            $this->header($line);
        }
        return false;
    }

    protected function ContentType ($action, $data)
    {
        $this->setContentType($this->attr('value',$action,$data));
        return false;
    }

    protected function LoadStrings ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        _Strings::getInstance ()->load($this->attr('name',$action,$data),$this->attr('path',$action,$data),(($this->attr('merge',$action,$data)=='true')?true:false));
        return false;
    }

    protected function StringsLang ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        _Strings::getInstance ()->setLang($this->attr('code',$action,$data));
        return false;
    }

    protected function StringsBase ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        $tmp=$this->attr('path',$action,$data);
        if ($tmp)
        {
            _Strings::getInstance ()->setBase($tmp);
        }
        $tmp=$this->attr('alt',$action,$data);
        if ($tmp)
        {
            _Strings::getInstance ()->setAltBase($tmp);
        }
        return false;
    }

    protected function Stop ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        return null;
    }

    protected function Brk ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        return _BluxApiService::$_Brk;
    }

    protected function Cont ($action, $data)
    {
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        return _BluxApiService::$_Cont;
    }

    protected function Evaluate ($action, $data)
    {
        $apiFuncFile=$this->attr('src',$action,$data).'.xml';
        $cont=($this->attr('continue',$action,$data)=='true');
        if (!_File::exists($apiFuncFile))
        {
            if (($this->attr('inhibit',$action,$data)=='true'))
            {
                return false;
            }
            else
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>400),false));
            }
        }
        $data->evalBase=_File::getInstance ()->path($apiFuncFile).'/';
        try
        {
            foreach(_XmlElement::loadFrom($apiFuncFile)->content->__nativeArray as $action)
            {
                $response=$this->{$action->name()}($action,$data);
                if (($response!==false))
                {
                    return ($cont?false:$response);
                }
            }
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>409,'error'=>$e->getMessage()),false));
        }
        return false;
    }

    protected function SetFile ($action, $data)
    {
        $path=$this->attr('path',$action,$data);
        if (($action->attribute('allowSpecial')!='true'))
        {
            if (($path[0]=='.'))
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>402),false));
            }
        }
        if (!_Directory::exists(_Directory::getInstance ()->path($path)))
        {
            _Directory::create(_Directory::getInstance ()->path($path),true);
        }
        $c=$this->fmt($action->plainContent(),$data);
        if (($this->attr('fromSql',$action,$data)=='true'))
        {
            $c=_Resources::getInstance ()->sqlConn->execScalar($c);
        }
        if (($this->attr('append',$action,$data)=='true'))
        {
            _File::appendContents($path,$c);
        }
        else
        {
            _File::putContents($path,$c);
        }
        return false;
    }

    protected function GetFile ($action, $data)
    {
        $path=$this->attr('path',$action,$data);
        if (($action->attribute('allowSpecial')!='true'))
        {
            if (($path[0]=='.'))
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>402),false));
            }
        }
        $empty=0;
        if (($this->attr('remote',$action,$data)!='true'))
        {
            if (!_File::exists($path))
            {
                $empty=1;
            }
        }
        $fdat=($empty?'':_File::getContents($path));
        $type=$action->attribute('type');
        if (($type=='conf'))
        {
            $data->confBase=_File::getInstance ()->path($path).'/';
            if (($action->attribute('pure')!='true'))
            {
                $fdat=$this->fmt($fdat,$data);
            }
            $fdat=_Configuration::loadFromBuffer($fdat);
            if (($action->attribute('pure')=='true'))
            {
                $fdat=_Map::fromNativeArray(array('data'=>$fdat),false);
            }
            $fdat->response=200;
            return $this->FmtResult($fdat);
        }
        $type=$this->attr('into',$action,$data);
        if (($type!=null))
        {
            $data->arraySetElement ($type,$fdat);
            return false;
        }
        if (($this->attr('return',$action,$data)=='true'))
        {
            return $fdat;
        }
        if (($action->attribute('verbose')!='true'))
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>200,'data'=>$fdat),false));
        }
        return $this->FmtResult(_Map::fromNativeArray(array('response'=>200,'path'=>$path,'name'=>_File::getInstance ()->name($path),'size'=>_File::getInstance ()->size($path),'data'=>$fdat),false));
    }

    protected function CreateDir ($action, $data)
    {
        $path=$this->attr('path',$action,$data);
        if (($action->attribute('allowSpecial')!='true'))
        {
            if (($path[0]=='.'))
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>402),false));
            }
        }
        if (!_Directory::exists($path))
        {
            _Directory::create($path,true);
        }
        return false;
    }

    protected function DirContents ($action, $data)
    {
        $root=$this->attr('root',$action,$data);
        $path=$this->attr('path',$action,$data);
        $filter=$this->attr('filter',$action,$data);
        $flags=((int)$this->attr('flags',$action,$data));
        $into=$this->attr('into',$action,$data);
        $filter='/^[^.]'.$filter.'/';
        if (!$flags)
        {
            $flags=3;
        }
        $dirs=alpha (new _Array ());
        $files=alpha (new _Array ());
        $r=alpha (new _Directory ($root.$path))->readEntries($filter,$flags,($this->attr('recursive',$action,$data)=='true'),_Text::length($root));
        $ref=$this->attr('ref',$action,$data);
        if ($ref)
        {
            $data->arraySetElement ($ref,$r);
            return false;
        }
        if (($into!=null))
        {
            $queue=_Array::fromNativeArray(array($r),false);
            $res=_Array::fromNativeArray(array(),false);
            $data->arraySetElement ($into,$res);
            while ($queue->length())
            {
                $z=$queue->shift();
                $z->is_dir=true;
                if ((($z->path!='')&&(($flags&2))))
                {
                    $res->push($z);
                }
                if ($z->dirs)
                {
                    foreach($z->dirs->__nativeArray as $x)
                    {
                        $queue->push($x);
                    }
                }
                if (($z->files&&(($flags&1))))
                {
                    foreach($z->files->__nativeArray as $x)
                    {
                        $res->push($x);
                        $x->is_dir=false;
                    }
                }
                $z->removeElement('dirs');
                $z->removeElement('files');
            };
            return false;
        }
        $r->response=200;
        return $this->FmtResult($r);
    }

    protected function Copy ($action, $data)
    {
        $field=$this->attr('field',$action,$data);
        if ($field)
        {
            $data->arraySetElement ($this->attr('to',$action,$data),$data->arrayGetElement ($field));
            return false;
        }
        _Directory::copy($this->attr('source',$action,$data),$this->attr('dest',$action,$data),($this->attr('recursive',$action,$data)=='true'),($this->attr('clear',$action,$data)=='true'),$this->attr('pattern',$action,$data));
        return false;
    }

    protected function StreamCopy ($action, $data)
    {
        $file1=$this->attr('from',$action,$data);
        $file2=$this->attr('into',$action,$data);
        $fp1=fopen($file1,'rb');
        $fp2=fopen($file2,'wb');
        while (!feof($fp1))
        {
            fwrite($fp2,fread($fp1,8192));
        };
        fclose($fp1);
        fclose($fp2);
        return false;
    }

    protected function Rename ($action, $data)
    {
        $data->ret=0;
        $field=$this->attr('field',$action,$data);
        if ($field)
        {
            $data->arraySetElement ($this->attr('to',$action,$data),$data->arrayGetElement ($field));
            $data->removeElement($field);
            return false;
        }
        try
        {
            _File::move($this->attr('source',$action,$data),$this->attr('dest',$action,$data));
            $data->ret=1;
        }
        catch (Exception $e){}
        return false;
    }

    protected function Remove ($action, $data)
    {
        $field=$this->attr('field',$action,$data);
        if ($field)
        {
            $this->__removeProperty($data,$field);
            return false;
        }
        _Directory::remove($this->attr('path',$action,$data),($this->attr('recursive',$action,$data)=='true'));
        return false;
    }

    protected function EvalUI ($action, $data)
    {
        try
        {
            $x=$this->attr('path',$action,$data);
            $f=$action->attribute('field');
            if (($x!=null))
            {
                $x=_UIElement::loadFrom($x)->asXml();
            }
            else
            {
                $x=_UIElement::loadFromBuffer($action->plainContent())->asXml();
            }
            $root=_Text::format(_Configuration::getInstance ()->General->root);
            if (_Configuration::getInstance ()->Locale->lang)
            {
                $x=_Text::replace('////',$root._Gateway::getInstance ()->requestParams->lang.'/',$x);
            }
            $x=_Text::replace('///',$root,$x);
            if (($action->attribute('format')=='true'))
            {
                $x=$this->fmt($x,$data);
            }
            $data->{($f?$f:'evalui')}=$x;
            return false;
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>409,'error'=>$e->getMessage()),false));
        }
    }

    protected function SetConf ($action, $data)
    {
        $path=$this->attr('path',$action,$data);
        if (!$path)
        {
            $path='resources/system.conf';
        }
        if (($action->attribute('allowSpecial')!='true'))
        {
            if (($path[0]=='.'))
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>402),false));
            }
        }
        $conf=null;;
        if (_File::exists($path))
        {
            $conf=_Configuration::loadFromBuffer(_File::getContents($path));
        }
        else
        {
            $conf=_Map::fromNativeArray(array(),false);
        }
        foreach(_Text::explode(';',$this->fmt($action->plainContent(),$data))->__nativeArray as $block)
        {
            $s=null;;
            $t=null;;
            $info=_Text::explode(':',$block);
            $tmp=null;;
            if ((($s=_Text::trim($info->arrayGetElement (0)))!=null))
            {
                switch ($s[0])
                {
                    case '*':
                    $s=_Text::substring($s,1);
                    $conf->arraySetElement ($s,_Map::fromNativeArray(array(),false));
                    break;
                    case '-':
                    $conf->removeElement(_Text::substring($s,1));
                    $s=null;
                    break;
                    case '~':
                    $tmp=_Text::explode('~',_Text::substring($s,1));
                    $s=$conf->arrayGetElement ($tmp->arrayGetElement (0));
                    $conf->removeElement($tmp->arrayGetElement (0));
                    $conf->arraySetElement ($tmp->arrayGetElement (1),$s);
                    $s=$tmp->arrayGetElement (1);
                    break;
                }
                if (!$s)
                {
                    continue;
                }
                if (!$conf->hasElement($s))
                {
                    $conf->arraySetElement ($s,_Map::fromNativeArray(array(),false));
                }
                $s=$conf->arrayGetElement ($s);
            }
            else
            {
                $s=$conf;
            }
            if (($info->length()!=2))
            {
                continue;
            }
            foreach(_Text::explode(',',$info->arrayGetElement (1))->__nativeArray as $field)
            {
                $field=_Text::trim($field);
                if (!$field)
                {
                    continue;
                }
                $f=_Text::substring($field,1);
                $tmp=_Text::explode('=',$f);
                switch ($field[0])
                {
                    case '-':
                    if (($f=='*'))
                    {
                        $s->clear();
                    }
                    else
                    {
                        $s->removeElement($f);
                    }
                    break;
                    case '&':
                    if (($tmp->length()==2))
                    {
                        $s->arraySetElement ($tmp->arrayGetElement (0),($s->arrayGetElement ($tmp->arrayGetElement (0))).$this->fmt('{'.$tmp->arrayGetElement (1).'}',$data));
                    }
                    else
                    {
                        $s->arraySetElement ($f,($s->arrayGetElement ($f)).$this->fmt('{'.$f.'}',$data));
                    }
                    break;
                    case '+':
                    if (($tmp->length()==2))
                    {
                        if ($s->hasElement($tmp->arrayGetElement (0)))
                        {
                            $s->arraySetElement ($tmp->arrayGetElement (0),$this->fmt('{'.$tmp->arrayGetElement (1).'}',$data));
                        }
                    }
                    else
                    {
                        if ($s->hasElement($f))
                        {
                            $s->arraySetElement ($f,$this->fmt('{'.$f.'}',$data));
                        }
                    }
                    break;
                    case '~':
                    $tmp=_Text::explode('~',$f);
                    $t=$s->arrayGetElement ($tmp->arrayGetElement (0));
                    $s->removeElement($tmp->arrayGetElement (0));
                    $s->arraySetElement ($tmp->arrayGetElement (1),$t);
                    break;
                    default:
                    $tmp=_Text::explode('=',$field);
                    if (($tmp->length()==2))
                    {
                        $s->arraySetElement ($tmp->arrayGetElement (0),$this->fmt('{'.$tmp->arrayGetElement (1).'}',$data));
                    }
                    else
                    {
                        $s->arraySetElement ($field,$this->fmt('{'.$field.'}',$data));
                    }
                    break;
                }
            }
        }
        if (!_Directory::exists(_Directory::getInstance ()->path($path)))
        {
            _Directory::create(_Directory::getInstance ()->path($path),true);
        }
        _File::putContents($path,_Configuration::saveToBuffer($conf));
        if ((($path=='resources/system.conf')&&($this->attr('reload',$action,$data)=='true')))
        {
            _Configuration::getInstance()->reload();
        }
        return false;
    }

    protected function GetConf ($action, $data)
    {
        $path=$this->attr('path',$action,$data);
        if (($action->attribute('allowSpecial')!='true'))
        {
            if (($path[0]=='.'))
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>402),false));
            }
        }
        $conf=null;;
        $out=_Map::fromNativeArray(array(),false);
        if (_File::exists($path))
        {
            $conf=_Configuration::loadFromBuffer(_File::getContents($path));
        }
        else
        {
            $conf=_Map::fromNativeArray(array(),false);
        }
        $txt=$this->fmt($action->plainContent(),$data);
        if (($txt!=null))
        {
            foreach(_Text::explode(';',$txt)->__nativeArray as $block)
            {
                $o=null;;
                $s=null;;
                $s1=null;;
                $info=_Text::explode(':',$block);
                if (($info->length()!=2))
                {
                    continue;
                }
                if ((($s1=_Text::trim($info->arrayGetElement (0)))!=null))
                {
                    if (!$conf->hasElement($s1))
                    {
                        $conf->arraySetElement ($s1,_Map::fromNativeArray(array(),false));
                    }
                    $s=$conf->arrayGetElement ($s1);
                    if (($info->arrayGetElement (1)=='*'))
                    {
                        $out->arraySetElement ($s1,$s);
                        continue;
                    }
                    else
                    {
                        $out->arraySetElement ($s1,_Map::fromNativeArray(array(),false));
                    }
                    $o=$out->arrayGetElement ($s1);
                }
                else
                {
                    $s=$conf;
                    if (($info->arrayGetElement (1)=='*'))
                    {
                        foreach($conf->__nativeArray as $n=>$x)
                        {
                            if ((typeOf($x)=='Map'))
                            {
                                continue;
                            }
                            $out->arraySetElement ($n,$x);
                        }
                        unset ($n);
                        continue;
                    }
                    $o=$out;
                }
                foreach(_Text::explode(',',$info->arrayGetElement (1))->__nativeArray as $f)
                {
                    $f=_Text::trim($f);
                    if ($f)
                    {
                        $o->arraySetElement ($f,$s->arrayGetElement ($f));
                    }
                }
            }
        }
        else
        {
            $out=$conf;
        }
        return $this->FmtResult(_Map::fromNativeArray(array('response'=>200,'data'=>$out),false));
    }

    protected function MergeRequestParams ($action, $data)
    {
        $data->merge(_Gateway::getInstance ()->requestParams,true);
        return false;
    }

    protected function MergeData ($action, $data)
    {
        if (($this->attr('intoFormData',$action,$data)=='true'))
        {
            $data->formData->merge($this->attr('from',$action,$data),true);
        }
        else
        {
            $data->merge($this->attr('from',$action,$data),true);
        }
        return false;
    }

    protected function Image ($action, $data)
    {
        $im=alpha (new _Image ());
        $line=null;;
        $t1=null;;
        $src=$this->attr('fromUploaded',$action,$data);
        $src2=$this->attr('fromFile',$action,$data);
        $src3=$this->attr('fromUrl',$action,$data);
        $imf=$this->attr('field',$action,$data);
        if (!$imf)
        {
            $imf='im';
        }
        if (((($src!=null)||($src2!=null))||($src3!=null)))
        {
            $data->imageReady=false;
            if (($src!=null))
            {
                $fd=_Gateway::getInstance ()->requestParams->{$src};
                if (((($fd==null)||!_File::isUploaded($fd->tmp_name))||($fd->error!=0)))
                {
                    if (!_Gateway::getInstance ()->serverParams->CONTENT_LENGTH)
                    {
                        return false;
                    }
                    $fd=_Map::fromNativeArray(array('tmp_name'=>'php://input'),false);
                }
                try
                {
                    $im->load($fd->tmp_name);
                }
                catch (_Exception $e)
                {
                    $data->errstr=$e->getMessage();
                    return false;
                }
            }
            else
            {
                if (($src2!=null))
                {
                    try
                    {
                        $im->load($src2);
                    }
                    catch (_Exception $e)
                    {
                        $data->errstr=$e->getMessage();
                        return false;
                    }
                }
                else
                {
                    $src2='tmp/'._Math::rand()._Math::rand();
                    try
                    {
                        _File::putContents($src2,_File::getContents($src3));
                        $im->load($src2);
                        _File::remove($src2);
                    }
                    catch (_Exception $e)
                    {
                        $data->errstr=$e->getMessage();
                        _File::remove($src2);
                        return false;
                    }
                }
            }
            $this->objMap->arraySetElement ('#'.$imf,$im);
        }
        $im=$this->objMap->arrayGetElement ('#'.$imf);
        foreach(_Text::explode(';',$this->fmt(';'.$action->plainContent(),$data))->__nativeArray as $line)
        {
            $line=_Text::trim($line);
            if (!$line)
            {
                continue;
            }
            $m=null;;
            $p=null;;
            $i=null;;
            $t1=_Text::explode(':',$line);
            if (($t1->length()==2))
            {
                $m=_Text::trim($t1->arrayGetElement (1));
            }
            else
            {
                $m=_Text::trim($t1->arrayGetElement (0));
            }
            if ((_Text::substring($m,-1,1)!=')'))
            {
                continue;
            }
            $m=_Text::explode('(',_Text::substring($m,0,-1));
            $m->arraySetElement (0,_Text::trim($m->arrayGetElement (0)));
            $p=_Text::explode(',',$m->arrayGetElement (1))->format('{f:trim:0}');
            for ($i=0; ($i<$p->length()); $i++)
            {
                if ((_Text::substring($p->arrayGetElement ($i),0,1)=='#'))
                {
                    $p->arraySetElement ($i,$this->objMap->arrayGetElement ($p->arrayGetElement ($i)));
                }
            }
            switch ($m->arrayGetElement (0))
            {
                case 'save':
                $m=$p->arrayGetElement (0)->save($p->arrayGetElement (1),$p->arrayGetElement (2));
                break;
                case 'output':
                $p->arrayGetElement (0)->output($p->arrayGetElement (1));
                return null;
                case 'data':
                $m=$p->arrayGetElement (0)->data($p->arrayGetElement (1),($p->arrayGetElement (2)=='true'),($p->arrayGetElement (3)=='true'));
                break;
                case 'width':
                if (($p->arrayGetElement (1)=='null'))
                {
                    $m=$p->arrayGetElement (0)->width(null);
                }
                else
                {
                    $m=$p->arrayGetElement (0)->width(((float)$p->arrayGetElement (1)),($p->arrayGetElement (2)=='true'));
                }
                break;
                case 'height':
                if (($p->arrayGetElement (1)=='null'))
                {
                    $m=$p->arrayGetElement (0)->height(null);
                }
                else
                {
                    $m=$p->arrayGetElement (0)->height(((float)$p->arrayGetElement (1)),($p->arrayGetElement (2)=='true'));
                }
                break;
                case 'resize':
                $m=$p->arrayGetElement (0)->resize(((float)$p->arrayGetElement (1)),((float)$p->arrayGetElement (2)),($p->arrayGetElement (3)=='true'));
                break;
                case 'scale':
                $m=$p->arrayGetElement (0)->scale(((float)$p->arrayGetElement (1)),((float)$p->arrayGetElement (2)),($p->arrayGetElement (3)=='true'));
                break;
                case 'fit':
                $m=$p->arrayGetElement (0)->fit(((float)$p->arrayGetElement (1)),((float)$p->arrayGetElement (2)),($p->arrayGetElement (3)=='true'));
                break;
                case 'smartCut':
                $m=$p->arrayGetElement (0)->smartCut(((float)$p->arrayGetElement (1)),((float)$p->arrayGetElement (2)),((int)$p->arrayGetElement (3)),((int)$p->arrayGetElement (4)),($p->arrayGetElement (5)=='true'));
                break;
                case 'release':
                $m=null;
                $p->arrayGetElement (0)->setDescriptor(null);
                break;
                case 'background':
                $m=$p->arrayGetElement (0)->fillRect(0,0,$p->arrayGetElement (0)->width(),$p->arrayGetElement (0)->height(),_Convert::fromHexInteger($p->arrayGetElement (1)));
                break;
            }
            if (($t1->length()==2))
            {
                $this->objMap->arraySetElement (_Text::trim($t1->arrayGetElement (0)),$m);
            }
        }
        if (((($src!=null)||($src2!=null))||($src3!=null)))
        {
            $data->imageReady=true;
        }
        return false;
    }

    protected function UploadedFile ($action, $data)
    {
        $src=$this->attr('name',$action,$data);
        if (($src==null))
        {
            $src='file';
        }
        $fd=_Gateway::getInstance ()->requestParams->{$src};
        if (((($fd==null)||!_File::isUploaded($fd->tmp_name))||($fd->error!=0)))
        {
            if ((!_Gateway::getInstance ()->serverParams->CONTENT_LENGTH||!$fd))
            {
                $data->arraySetElement ('fileReady','0');
                return false;
            }
            $fd=_Map::fromNativeArray(array('tmp_name'=>'php://input','name'=>$fd),false);
        }
        $data->arraySetElement ('fileReady','1');
        $data->arraySetElement ($src,$fd->tmp_name);
        $data->arraySetElement ($src.'_n',$fd->name);
        return false;
    }

    protected function PackExtract ($action, $data)
    {
        $path=$this->attr('path',$action,$data);
        $target=$this->attr('target',$action,$data);
        $format=$this->attr('format',$action,$data);
        if (((_Text::substring($path,-4)=='.zip')||($format=='zip')))
        {
            $temp=vnew ('::ZipArchive');
            if (($temp->open($path)===true))
            {
                $temp->extractTo($target);
                $temp->close();
                $data->ret='1';
            }
            else
            {
                $data->ret='0';
            }
        }
        else
        {
            $data->ret=(_PackContainer::extractFromBuffer(_File::getContents($path),$target)?'1':'0');
        }
        return false;
    }

    protected function PackCreate ($action, $data)
    {
        $root=$this->attr('root',$action,$data);
        $path=$this->attr('path',$action,$data);
        $filter=$this->attr('filter',$action,$data);
        $into=$this->attr('dest',$action,$data);
        $pack=null;;
        $queue=_Array::fromNativeArray(array(alpha (new _Directory ($root.$path))->readEntries($filter,3,($this->attr('recursive',$action,$data)=='true'),_Text::length($root))),false);
        if (($this->attr('type',$action,$data)=='zip'))
        {
            $pack=alpha (new _ZipContainer ());
        }
        else
        {
            $pack=alpha (new _PackContainer ());
        }
        while ($queue->length())
        {
            $r=$queue->shift();
            if (($r->path!=''))
            {
                $pack->addEntry(1,$r->path);
            }
            if ($r->dirs)
            {
                foreach($r->dirs->__nativeArray as $x)
                {
                    $queue->push($x);
                }
            }
            if ($r->files)
            {
                foreach($r->files->__nativeArray as $x)
                {
                    $pack->addEntry(0,$x->path,_File::getContents($root.$x->path));
                }
            }
        };
        if (($into!=null))
        {
            $pack->save($into);
        }
        else
        {
            $data->arraySetElement ($this->attr('field',$action,$data),$pack->data());
        }
        return false;
    }

    protected function StackLoop ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            return false;
        }
        $s=$data->arrayGetElement ($s);
        try
        {
            while ($s->length())
            {
                foreach($action->content->__nativeArray as $n_action)
                {
                    $response=$this->{$n_action->name()}($n_action,$data);
                    if (($response===_BluxApiService::$_Brk))
                    {
                        return false;
                    }
                    if (($response===_BluxApiService::$_Cont))
                    {
                        break;
                    }
                    if (($response!==false))
                    {
                        return $response;
                    }
                }
            };
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            if (($this->attr('debug',$action,$data)=='true'))
            {
                trace($e);
            }
            else
            {
                throw $e;
            }
        }
        return false;
    }

    protected function StackPush ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            $s=$data->arraySetElement ($s,alpha (new _Array ()));
        }
        else
        {
            $s=$data->arrayGetElement ($s);
        }
        $s->push($this->attr('value',$action,$data));
        return false;
    }

    protected function StackUnshift ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            $s=$data->arraySetElement ($s,alpha (new _Array ()));
        }
        else
        {
            $s=$data->arrayGetElement ($s);
        }
        $s->unshift($this->attr('value',$action,$data));
        return false;
    }

    protected function StackPop ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            return false;
        }
        $s=$data->arrayGetElement ($s)->pop();
        if ($action->attribute('param'))
        {
            _Gateway::getInstance ()->requestParams->{$this->attr('param',$action,$data)}=$s;
        }
        else
        {
            if ($action->attribute('formField'))
            {
                $data->formData->{$this->attr('formField',$action,$data)}=$s;
            }
            else
            {
                $data->{$this->attr('field',$action,$data)}=$s;
            }
        }
        return false;
    }

    protected function StackShift ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            return false;
        }
        $s=$data->arrayGetElement ($s)->shift();
        if ($action->attribute('param'))
        {
            _Gateway::getInstance ()->requestParams->{$this->attr('param',$action,$data)}=$s;
        }
        else
        {
            if ($action->attribute('formField'))
            {
                $data->formData->{$this->attr('formField',$action,$data)}=$s;
            }
            else
            {
                $data->{$this->attr('field',$action,$data)}=$s;
            }
        }
        return false;
    }

    protected function StackClear ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        $data->arraySetElement ($s,alpha (new _Array ()));
        return false;
    }

    protected function StackRemove ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            $s=$data->arraySetElement ($s,alpha (new _Array ()));
        }
        else
        {
            $s=$data->arrayGetElement ($s);
        }
        $s->remove($this->attr('index',$action,$data));
        return false;
    }

    protected function StackImplode ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        $d=$action->attribute('delimiter');
        if (!$d)
        {
            $d=',';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            return false;
        }
        $s=$data->arrayGetElement ($s)->implode($d);
        if ($action->attribute('param'))
        {
            _Gateway::getInstance ()->requestParams->{$this->attr('param',$action,$data)}=$s;
        }
        else
        {
            if ($action->attribute('formField'))
            {
                $data->formData->{$this->attr('formField',$action,$data)}=$s;
            }
            else
            {
                $data->{$this->attr('field',$action,$data)}=$s;
            }
        }
        return false;
    }

    protected function StackFormat ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='stack';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            return false;
        }
        $data->arraySetElement ($s,$data->arrayGetElement ($s)->format($action->plainContent()));
        return false;
    }

    protected function ApiCall ($action, $data)
    {
        $cont=(($this->attr('passControl',$action,$data)=='false')?true:false);
        $shared=(($this->attr('shared',$action,$data)=='true')?true:false);
        $name=_Text::replace('.','/',$this->attr('name',$action,$data));
        if ($action->hasAttribute('condition'))
        {
            if (!$this->attr('condition',$action,$data))
            {
                return false;
            }
        }
        $apiFuncFile=null;;
        $modBase=$this->dataMap->modBase;
        if ($action->hasAttribute('file'))
        {
            $apiFuncFile=_Text::replace('.','/',$this->attr('file',$action,$data)).'.xml';
        }
        else
        {
            if (($name[0]=='@'))
            {
                $apiFuncFile=$data->evalBase.$name.'.xml';
            }
            else
            {
                $apiFuncFile=$this->functionFile($name,false);
            }
        }
        if (!_File::exists($apiFuncFile))
        {
            if (($this->attr('inhibit',$action,$data)=='true'))
            {
                return false;
            }
            else
            {
                return $this->FmtResult(_Map::fromNativeArray(array('response'=>400),false));
            }
        }
        $oldDataMap=_Resources::getInstance ()->DataMap;
        $oldDataMap->modBase=$modBase;
        $tempData=_Map::fromNativeArray(array(),false);
        $oldVars=_Map::fromNativeArray(array('evalBase'=>$oldDataMap->evalBase,'internalCall'=>$oldDataMap->internalCall),false);
        if ($shared)
        {
            $tempData=$oldDataMap;
        }
        $params=$this->attr('params',$action,$data);
        $params=($params?(((typeOf($params)=='Map')?$params:$data->arrayGetElement ($params))):$action->toMap($data->formData,$data));
        if ($cont)
        {
            $this->forceInternalMode++;
        }
        if ((!$shared&&($this->attr('includeRequestParams',$action,$data)=='true')))
        {
            $tempData->merge(_Gateway::getInstance ()->requestParams,true);
        }
        if (($this->attr('overrideRequestParams',$action,$data)=='true'))
        {
            _Gateway::getInstance ()->requestParams->merge($params,true);
        }
        else
        {
            $tempData->merge($params,true);
        }
        $tempData->evalBase=_File::getInstance ()->path($apiFuncFile).'/';
        $tempData->internalCall='1';
        if (!$shared)
        {
            $tempData->formData=_Map::fromNativeArray(array(),false);
        }
        _Resources::getInstance ()->DataMap=$tempData;
        $ref=$this->attr('ref',$action,$data);
        if ($ref)
        {
            $data->arraySetElement ($ref,$tempData);
        }
        try
        {
            foreach(_XmlElement::loadFrom($apiFuncFile)->content->__nativeArray as $action)
            {
                $response=$this->{$action->name()}($action,$tempData);
                if (($response===_BluxApiService::$_Brk))
                {
                    break;
                }
                if (($response===_BluxApiService::$_Cont))
                {
                    break;
                }
                if (($response!==false))
                {
                    $s='apicall';
                    $data->arraySetElement ($s,alpha (new _Map ()));
                    try
                    {
                        $data->arrayGetElement ($s)->merge(($this->multiResponseMode?$response:_Convert::fromJson($response)),true);
                    }
                    catch (Exception $e){}
                    if ($cont)
                    {
                        break;
                    }
                    _Resources::getInstance ()->DataMap=$oldDataMap;
                    return $response;
                }
            }
            $k=null;;
            $v=null;;
            foreach($tempData->__nativeArray as $k=>$v)
            {
                if (($k[0]=='_'))
                {
                    $data->arraySetElement (_Text::substring($k,1),$v);
                }
            }
            unset ($k);
        }
        catch (_FalseException $e)
        {
            _Resources::getInstance ()->DataMap=$oldDataMap;
            throw $e;
        }
        catch (_Exception $e)
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>409,'error'=>$e->getMessage()),false));
        }
        if ($cont)
        {
            $this->forceInternalMode--;
        }
        $oldDataMap->merge($oldVars,true);
        _Resources::getInstance ()->DataMap=$oldDataMap;
        return false;
    }

    protected function RebuildData ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        $x=null;;
        try
        {
            if (($x=$this->attr('fromXml',$action,$data)))
            {
                $data->arraySetElement ($s,_XmlElement::loadFrom($x));
            }
            else
            {
                if (($x=$this->attr('fromXmlBuffer',$action,$data)))
                {
                    if (($x=='true'))
                    {
                        $data->arraySetElement ($s,_XmlElement::loadFromBuffer($this->fmt($action->plainContent(),$data)));
                    }
                    else
                    {
                        $data->arraySetElement ($s,_XmlElement::loadFromBuffer($x));
                    }
                }
                else
                {
                    if (($x=$this->attr('from',$action,$data)))
                    {
                        try
                        {
                            $data->arraySetElement ($s,_Convert::fromJson(_File::getContents($x)));
                        }
                        catch (_Exception $e1)
                        {
                            $data->arraySetElement ($s,alpha (new _Map ()));
                        }
                    }
                    else
                    {
                        if (($this->attr('inData',$action,$data)=='true'))
                        {
                            $data->merge(_Convert::fromJson($this->attr('source',$action,$data)),true);
                        }
                        else
                        {
                            if (($this->attr('inFormData',$action,$data)=='true'))
                            {
                                $data->formData->merge(_Convert::fromJson($this->attr('source',$action,$data)),true);
                            }
                            else
                            {
                                $data->arraySetElement ($s,_Convert::fromJson($this->attr('source',$action,$data)));
                            }
                        }
                    }
                }
            }
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>410,'error'=>$e->getMessage()),false));
        }
        return false;
    }

    protected function Clear ($action, &$data)
    {
        $s=null;;
        if (($this->attr('formData',$action,$data)=='true'))
        {
            $data->formData=_Map::fromNativeArray(array(),false);
        }
        if (($this->attr('data',$action,$data)=='true'))
        {
            if (($this->attr('preserveFormData',$action,$data)=='true'))
            {
                $data=_Map::fromNativeArray(array('formData'=>$data->formData),false);
            }
            else
            {
                $data=_Map::fromNativeArray(array('formData'=>_Map::fromNativeArray(array(),false)),false);
            }
        }
        if ((($s=$this->attr('ref',$action,$data))!=null))
        {
            if (($this->attr('asObject',$action,$data)=='true'))
            {
                $data->arraySetElement ($s,alpha (new _Map ()));
            }
            else
            {
                $data->arraySetElement ($s,alpha (new _Array ()));
            }
        }
        return false;
    }

    protected function sApiCall ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='data';
        }
        _Gateway::getInstance ()->requestParams->merge($action->toMap($data->formData,$data),true);
        if (($data->arrayGetElement ($s)==null))
        {
            $data->arraySetElement ($s,alpha (new _Map ()));
        }
        $result=_Gateway::getInstance ()->getService($this->attr('name',$action,$data))->main(true);
        if (($result!=null))
        {
            $data->arrayGetElement ($s)->merge(_Convert::fromJson($result),true);
        }
        return false;
    }

    protected function rApiCall ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='rapi';
        }
        if (($data->arrayGetElement ($s)==null))
        {
            $data->arraySetElement ($s,alpha (new _Map ()));
        }
        $result=null;;
        if (($action->attribute('post')=='true'))
        {
            $url=alpha (new _Url ($this->attr('url',$action,$data)));
            $conn=alpha (new _HttpClient ($url->host(),($url->port()?$url->port():((($url->protocol()=='https')?443:80)))));
            if (($action->attribute('debug')=='true'))
            {
                $conn->tracing(true);
            }
            if ($action->hasAttribute('content-type'))
            {
                $result=$conn->rawPost($url->root().$url->resource().$url->query(),_Array::fromNativeArray(array($action->attribute('content-type'),$this->fmt($action->plainContent(),$data)),false),true);
            }
            else
            {
                $result=$conn->postData($url->root().$url->resource().$url->query(),$action->toMap($data->formData,$data),true);
            }
            $data->arraySetElement ('http_response',$conn->rheader->response);
        }
        else
        {
            $url=$this->attr('url',$action,$data);
            $result=_File::getContents($url.((_Text::indexOf($url,'?')?'&':'?')).$action->toMap($data->formData,$data)->format('{f:urlencode:0}={f:urlencode:1}')->implode('&'));
        }
        $output=$this->attr('output',$action,$data);
        if ($output)
        {
            $data->arraySetElement ($output,$result);
        }
        else
        {
            $output=$this->attr('saveto',$action,$data);
            if ($output)
            {
                _File::putContents($output,$result);
            }
            else
            {
                if (($result!=null))
                {
                    $data->arraySetElement ($s,_Convert::fromJson($result));
                }
            }
        }
        return false;
    }

    protected function ExecutionLimit ($action, $data)
    {
        $s=$this->attr('value',$action,$data);
        if (!$s)
        {
            $s='0';
        }
        set_time_limit($s);
        if (!$s)
        {
            ignore_user_abort(true);
        }
        return false;
    }

    protected function UrlRedirect ($action, $data)
    {
        $s=$this->attr('location',$action,$data);
        if (!$s)
        {
            return false;
        }
        _Gateway::urlRedirect($s);
        return false;
    }

    protected function ReleaseSession ($action, $data)
    {
        _Session::getInstance ()->close();
        return false;
    }

    protected function FlushConnection ($action, $data)
    {
        _Gateway::connectionFlush();
        return false;
    }

    protected function Loop ($action, $data)
    {
        $p_var=$this->attr('var',$action,$data);
        $p_from=((int)$this->attr('from',$action,$data));
        $p_to=((int)$this->attr('to',$action,$data));
        $p_condition=$this->attr('condition',$action,$data);
        $p_step=((int)$this->attr('step',$action,$data));
        if (!$p_from)
        {
            $p_from=0;
        }
        if (!$p_to)
        {
            $p_to=0;
        }
        if (!$p_step)
        {
            $p_step=1;
        }
        if ($p_var)
        {
            try
            {
                $data->arraySetElement ($p_var,$p_from);
                if ($p_condition)
                {
                    while ($this->attr('condition',$action,$data))
                    {
                        foreach($action->content->__nativeArray as $n_action)
                        {
                            $response=$this->{$n_action->name()}($n_action,$data);
                            if (($response===_BluxApiService::$_Brk))
                            {
                                return false;
                            }
                            if (($response===_BluxApiService::$_Cont))
                            {
                                break;
                            }
                            if (($response!==false))
                            {
                                return $response;
                            }
                        }
                        $data->arraySetElement ($p_var,($data->arrayGetElement ($p_var)+$p_step));
                    };
                }
                else
                {
                    while (((((int)$data->arrayGetElement ($p_var)))<=(((int)$p_to))))
                    {
                        foreach($action->content->__nativeArray as $n_action)
                        {
                            $response=$this->{$n_action->name()}($n_action,$data);
                            if (($response===_BluxApiService::$_Brk))
                            {
                                return false;
                            }
                            if (($response===_BluxApiService::$_Cont))
                            {
                                break;
                            }
                            if (($response!==false))
                            {
                                return $response;
                            }
                        }
                        $data->arraySetElement ($p_var,($data->arrayGetElement ($p_var)+$p_step));
                    };
                }
            }
            catch (_FalseException $e)
            {
                throw $e;
            }
            catch (_Exception $e)
            {
                if (($this->attr('debug',$action,$data)=='true'))
                {
                    trace($e);
                }
                else
                {
                    throw $e;
                }
            }
        }
        else
        {
            try
            {
                while ($this->attr('condition',$action,$data))
                {
                    foreach($action->content->__nativeArray as $n_action)
                    {
                        $response=$this->{$n_action->name()}($n_action,$data);
                        if (($response===_BluxApiService::$_Brk))
                        {
                            return false;
                        }
                        if (($response===_BluxApiService::$_Cont))
                        {
                            break;
                        }
                        if (($response!==false))
                        {
                            return $response;
                        }
                    }
                };
            }
            catch (_FalseException $e)
            {
                throw $e;
            }
            catch (_Exception $e)
            {
                if (($this->attr('debug',$action,$data)=='true'))
                {
                    trace($e);
                }
                else
                {
                    throw $e;
                }
            }
        }
        return false;
    }

    protected function ChunkedTransfer ($action, $data)
    {
        _Gateway::ignoreUserAbort((($this->attr('ignoreUserAbort',$action,$data)=='true')?true:false));
        _Gateway::enableBlockTransfer();
        _Session::getInstance ()->close();
        return false;
    }

    protected function WriteChunk ($action, $data)
    {
        _Gateway::writeBlock($this->fmt($action->plainContent(),$data));
        return false;
    }

    protected function Sleep ($action, $data)
    {
        sleep($this->attr('value',$action,$data));
        return false;
    }

    protected function ReturnCache ($action, $data)
    {
        $etag=$this->attr('etag',$action,$data);
        $ts=$this->attr('ts',$action,$data);
        $if_modified_since=_Gateway::getInstance ()->serverParams->HTTP_IF_MODIFIED_SINCE;
        $if_none_match=_Gateway::getInstance ()->serverParams->HTTP_IF_NONE_MATCH;
        if ((($if_modified_since==$ts)&&((($if_none_match==$etag)||($if_none_match=="\"")).$etag."\"")))
        {
            $this->header('HTTP/1.1 304 Not Modified');
            return null;
        }
        return false;
    }

    protected function LoadConfig ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='config';
        }
        if ($action->attribute('from'))
        {
            $data->arraySetElement ($s,_Configuration::loadFrom($this->attr('from',$action,$data)));
        }
        else
        {
            $data->arraySetElement ($s,_Configuration::loadFromBuffer($this->attr('fromBuffer',$action,$data)));
        }
        return false;
    }

    protected function SqlConnect ($action, $data)
    {
        if (_Resources::getInstance ()->existsNow('sqlConn'))
        {
            _Resources::getInstance ()->sqlConn->close();
        }
        try
        {
            if ($action->hasAttribute('config'))
            {
                _Resources::getInstance ()->sqlConn=_SqlConnection::fromConfig($this->attr('config',$action,$data));
            }
            else
            {
                _Resources::getInstance ()->sqlConn=_SqlConnection::fromConfig($action->toMap($data->formData,$data));
            }
            $data->sqlError='';
            return false;
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_Exception $e)
        {
            if (($this->attr('inhibit',$action,$data)=='true'))
            {
                $data->sqlError=$e->getMessage();
                return false;
            }
            return $this->FmtResult(_Map::fromNativeArray(array('response'=>401,'error'=>$e->getMessage()),false));
        }
        return false;
    }

    protected function Shell ($action, $data)
    {
        $s=$action->attribute('ref');
        if (!$s)
        {
            $s='shell';
        }
        $data->arraySetElement ($s,shell_exec($this->fmt($action->plainContent(),$data)));
        return false;
    }

    protected function ChmodR ($action, $data)
    {
        $mode=$this->attr('mode',$action,$data);
        if (!$mode)
        {
            $mode='0777';
        }
        invokeStaticMethod('MiscUtils','chmodR',_Array::fromNativeArray(array($this->attr('path',$action,$data),octdec($mode)),false));
        return false;
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