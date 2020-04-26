<?php

class _FormValidator
{
    private static $__classAttributes = null;
    public $formDefs;
    private static $objectInstance;
    private $data;
    private $formKey;
    private $result;
    private $form;


    public static function classAttributes ()
    {
        return _FormValidator::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->formDefs=alpha (new _Map ());
    }

    public static function __classInit ()
    {
        _FormValidator::$objectInstance=null;
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function getFormDef ($formKey=null)
    {
        if (($formKey==null))
        {
            $formKey=$this->formKey;
        }
        $defs=$this->formDefs->getElement($formKey);
        if (($defs==null))
        {
            throw alpha (new _Exception ('Gail: Form definition not defined for: '.$formKey));
        }
        return $defs;
    }

    public static function validateRule ($elem, $name, &$value, $data, $result)
    {
        $tmpMap=alpha (new _Map ())->setElement($name,$value);
        $text=_Text::format($elem->text(),$result,$tmpMap);
        $n=null;;
        $defaction=null;
        $arr=null;;
        $tmp=false;
        switch ($elem->name())
        {
            case 'Present':
            if (!$data->hasElement($name))
            {
                if ($elem->hasAttribute('default'))
                {
                    $value=_Text::format($elem->attribute('default'),$result,$tmpMap);
                    $data->{$name}=$value;
                    return $value;
                }
                $defaction='discard';
                $tmp=true;
            }
            else
            {
                $tmp=false;
            }
            break;
            case 'Required':
            if ((($value=='')&&($text=='false')))
            {
                if ($elem->hasAttribute('default'))
                {
                    $value=_Text::format($elem->attribute('default'),$result,$tmpMap);
                    $data->{$name}=$value;
                    return $value;
                }
                else
                {
                    if ((_Text::format($elem->attribute('allowEmpty'),$result,$tmpMap)=='false'))
                    {
                        return false;
                    }
                    return $value;
                }
            }
            if ((($value=='')&&($text=='true')))
            {
                if ($elem->hasAttribute('default'))
                {
                    $value=_Text::format($elem->attribute('default'),$result,$tmpMap);
                    $data->{$name}=$value;
                    break;
                }
                else
                {
                    $tmp=true;
                }
            }
            break;
            case 'Pattern':
            if ($elem->hasAttribute('negative'))
            {
                $tmp=true;
            }
            else
            {
                $tmp=false;
            }
            if (($tmp==_Regex::stMatch($text,$value)))
            {
                $tmp=true;
            }
            else
            {
                $tmp=false;
            }
            break;
            case 'Filter':
            try
            {
                $value=_Convert::filter($text,$value);
            }
            catch (_Exception $e)
            {
                $tmp=true;
            }
            break;
            case 'MustMatch':
            if (($value!=$data->{$text}))
            {
                $tmp=true;
            }
            break;
            case 'Discard':
            $result->removeElement($name);
            $data->removeElement($name);
            return false;
            case 'CopyTo':
            if (($result!=null))
            {
                $result->setElement($text,$value);
                $data->arraySetElement ($text,$value);
            }
            break;
            case 'RealName':
            if (($result!=null))
            {
                $result->setElement($text,$value);
            }
            return false;
            case 'Rewrite':
            $result->setElement($name,$value);
            $data->{$name}=$value;
            break;
            case 'MinLength':
            if ((_Text::length($value)<(((int)$text))))
            {
                $tmp=true;
            }
            break;
            case 'MaxLength':
            if ((_Text::length($value)>(((int)$text))))
            {
                $tmp=true;
            }
            break;
            case 'Min':
            if (($value<(((int)$text))))
            {
                $tmp=true;
            }
            break;
            case 'Max':
            if (($value>(((int)$text))))
            {
                $tmp=true;
            }
            break;
            case 'DbScalar':
            $t1=_Resources::getInstance ()->sqlConn->execScalar($text);
            $t2=$elem->attribute('value');
            switch ($elem->attribute('operator'))
            {
                case 'lt':
                if (($t1<$t2))
                {
                    $tmp=true;
                }
                break;
                case 'le':
                if (($t1<=$t2))
                {
                    $tmp=true;
                }
                break;
                case 'gt':
                if (($t1>$t2))
                {
                    $tmp=true;
                }
                break;
                case 'ge':
                if (($t1>=$t2))
                {
                    $tmp=true;
                }
                break;
                case 'eq':
                if (($t1==$t2))
                {
                    $tmp=true;
                }
                break;
                case 'ne':
                if (($t1!=$t2))
                {
                    $tmp=true;
                }
                break;
            }
            break;
            case 'DbCheck':
            $n=$elem->attribute('resource');
            if (!$n)
            {
                $n='DbCheck';
            }
            try
            {
                if (!_Text::format($elem->attribute('condition'),_Resources::getInstance ()->{$n}=_Resources::getInstance ()->sqlConn->execAssoc($text),$result,$tmpMap))
                {
                    $tmp=true;
                }
            }
            catch (_Exception $e)
            {
                $tmp=true;
            }
            break;
            case 'DbValue':
            $n=$elem->attribute('resource');
            if (!$n)
            {
                $n='DbValue';
            }
            $value=_Text::format($elem->attribute('format'),_Resources::getInstance ()->{$n}=_Resources::getInstance ()->sqlConn->execAssoc($text),$result,$tmpMap);
            break;
            case 'Check':
            if (!$text)
            {
                $tmp=true;
                break;
            }
            break;
            case 'MapImplode':
            $value=_Gateway::getInstance ()->requestParams->{$name}->values()->implode($text);
            break;
            case 'Date':
            $arr=_Regex::stMatchFirst($text,$value);
            if (($arr->length()<4))
            {
                $tmp=true;
                break;
            }
            if (!_DateTime::isValidDate($arr->arrayGetElement (1),$arr->arrayGetElement (2),$arr->arrayGetElement (3)))
            {
                $tmp=true;
                break;
            }
            if (($elem->attribute('convert')=='true'))
            {
                $value=_DateTime::fromDate($arr->arrayGetElement (1),$arr->arrayGetElement (2),$arr->arrayGetElement (3));
            }
            else
            {
                if (($elem->attribute('convert')=='unix'))
                {
                    $value=_DateTime::fromDate($arr->arrayGetElement (1),$arr->arrayGetElement (2),$arr->arrayGetElement (3))->unixTime();
                }
            }
            break;
            case 'Time':
            $arr=_Regex::stMatchFirst($text,$value);
            if (($arr->length()<3))
            {
                $tmp=true;
                break;
            }
            if (!_DateTime::isValidTime($arr->arrayGetElement (1),$arr->arrayGetElement (2),(($arr->length()>3)?$arr->arrayGetElement (3):0)))
            {
                $tmp=true;
                break;
            }
            if (($elem->attribute('convert')=='true'))
            {
                $value=_DateTime::fromTime($arr->arrayGetElement (1),$arr->arrayGetElement (2),(($arr->length()>3)?$arr->arrayGetElement (3):0));
            }
            else
            {
                if (($elem->attribute('convert')=='unix'))
                {
                    $value=_DateTime::fromTime($arr->arrayGetElement (1),$arr->arrayGetElement (2),(($arr->length()>3)?$arr->arrayGetElement (3):0))->unixTime();
                }
            }
            break;
            case 'Dump':
            throw alpha (new _Exception ($value));
            break;
            case 'DumpData':
            throw alpha (new _Exception ($data));
            break;
            case 'Set':
            if ($elem->hasAttribute('field'))
            {
                $n=$elem->attribute('field');
                $result->setElement($n,$text);
                $data->arraySetElement ($n,$text);
            }
            else
            {
                $value=$text;
            }
            break;
            case 'Sql':
            try
            {
                _Resources::getInstance ()->sqlConn->execQuery($text);
            }
            catch (_Exception $e)
            {
                $tmp=true;
            }
            break;
            case 'ValidFile':
            $tmp=_Gateway::getInstance ()->requestParams->{$name};
            if ((typeof($tmp)=='Map'))
            {
                if ((($tmp->hasElement('tmp_name')&&$tmp->hasElement('type'))&&$tmp->hasElement('size')))
                {
                    if (!$tmp->error)
                    {
                        $tmp=false;
                        break;
                    }
                }
            }
            $tmp=true;
            break;
            case 'FileType':
            $tmp=_Gateway::getInstance ()->requestParams->{$name};
            if ((typeof($tmp)=='Map'))
            {
                if ((($tmp->hasElement('tmp_name')&&$tmp->hasElement('type'))&&$tmp->hasElement('size')))
                {
                    if ((!$tmp->error&&_Regex::stMatch($text,$tmp->type)))
                    {
                        $tmp=false;
                        break;
                    }
                }
            }
            $tmp=true;
            break;
            case 'FileName':
            $tmp=_Gateway::getInstance ()->requestParams->{$name};
            if ((typeof($tmp)=='Map'))
            {
                if ((($tmp->hasElement('tmp_name')&&$tmp->hasElement('name'))&&$tmp->hasElement('size')))
                {
                    if ((!$tmp->error&&_Regex::stMatch($text,$tmp->name)))
                    {
                        $tmp=false;
                        break;
                    }
                }
            }
            $tmp=true;
            break;
            case 'UseFilePath':
            $tmp=_Gateway::getInstance ()->requestParams->{$name};
            if ((typeof($tmp)=='Map'))
            {
                if ((($tmp->hasElement('tmp_name')&&$tmp->hasElement('type'))&&$tmp->hasElement('size')))
                {
                    if (!$tmp->error)
                    {
                        if ($elem->hasAttribute('field'))
                        {
                            $n=$elem->attribute('field');
                            $result->setElement($n,$tmp->tmp_name);
                            $data->arraySetElement ($n,$tmp->tmp_name);
                        }
                        else
                        {
                            $value=$tmp->tmp_name;
                        }
                        $tmp=false;
                        break;
                    }
                }
            }
            $tmp=true;
            break;
            case 'UseFileData':
            $tmp=_Gateway::getInstance ()->requestParams->{$name};
            if ((typeof($tmp)=='Map'))
            {
                if ((($tmp->hasElement('tmp_name')&&$tmp->hasElement('type'))&&$tmp->hasElement('size')))
                {
                    if (!$tmp->error)
                    {
                        $tmp=_File::getContents($tmp->tmp_name);
                        if ($elem->hasAttribute('field'))
                        {
                            $n=$elem->attribute('field');
                            $result->setElement($n,$tmp);
                            $data->arraySetElement ($n,$tmp);
                        }
                        else
                        {
                            $value=$tmp;
                        }
                        $tmp=false;
                        break;
                    }
                }
            }
            $tmp=true;
            break;
            case 'UseFileInfo':
            $tmp=_Gateway::getInstance ()->requestParams->{$name};
            if ((typeof($tmp)=='Map'))
            {
                if ((($tmp->hasElement('tmp_name')&&$tmp->hasElement('type'))&&$tmp->hasElement('size')))
                {
                    if (!$tmp->error)
                    {
                        $value=$tmp;
                        $tmp=false;
                        break;
                    }
                }
            }
            $tmp=true;
            break;
            default:
            return _FormValidator::customRule($elem,$name,$value,$data,$result,$text);
        }
        if (($tmp==true))
        {
            switch (($elem->attribute('action')?$elem->attribute('action'):$defaction))
            {
                case 'discard':
                $result->removeElement($name);
                $data->removeElement($name);
                return false;
                case 'stop':
                return $data->{$name};
                case 'continue':
                return null;
                case 'set-and-stop':
                return ($data->{$name}=_Text::format($elem->attribute('value'),$result,$tmpMap));
                case 'set-and-continue':
                $data->{$name}=$value=_Text::format($elem->attribute('value'),$result,$tmpMap);
                return null;
                case 'set-and-fail':
                $data->{$name}=$value=_Text::format($elem->attribute('value'),$result,$tmpMap);
                return true;
                case 'nullify':
                return ($data->{$name}=_Resources::getInstance ()->Null);
                default:
                return true;
            }
        }
        return null;
    }

    public static function customRule ($elem, $name, &$value, $data, $result, $text)
    {
        return null;
    }

    public static function formMsg ($cssClass, $text)
    {
        if (($text[0]=='<'))
        {
            return $text;
        }
        return "<span class=\"".$cssClass."\">".$text.'</span>';
    }

    public static function getMessage ($formDef, $name, $data)
    {
        $m=($formDef?$formDef->Messages:null);
        if ((!$formDef||!$m->length()))
        {
            $s=null;;
            if (($name[0]=='#'))
            {
                $s=_Text::format(_Strings::getInstance ()->{'/Forms'}->arrayGetElement (_Text::substring($name,1)),$data);
            }
            else
            {
                $s=_Text::format(_Strings::getInstance ()->Forms->{$name},$data);
                if (($s==null))
                {
                    $s=_Text::format(_Strings::getInstance ()->{'/Forms'}->arrayGetElement ($name),$data);
                }
            }
            if (($s!=null))
            {
                return $s;
            }
        }
        else
        {
            $msgElem=$m->arrayGetElement (0)->select($name,false,true);
            if (($msgElem!=null))
            {
                return _Text::format($msgElem->text(),$data);
            }
        }
        return $name;
    }

    public function gail_fvtor ($name, $value)
    {
        $formDef=$this->getFormDef();
        $field=$formDef->select('Field[name=\'/^'.$name.'$/\']',false,true);
        if (($field==null))
        {
            return false;
        }
        $value=_Text::trim($value);
        $msg=null;
        foreach($field->content->__nativeArray as $elem)
        {
            try
            {
                $msg=_FormValidator::validateRule($elem,$name,$value,$this->data,$this->result);
                if (((($msg!==false)&&($msg!==true))&&($msg!==null)))
                {
                    return $msg;
                }
                if (($msg===false))
                {
                    return false;
                }
                if (($msg===true))
                {
                    if (($elem->attribute('failureMsg')==null))
                    {
                        $msg=_FormValidator::getMessage($formDef,$elem->name(),$this->data);
                    }
                    else
                    {
                        $msg=$elem->attribute('failureMsg');
                        if (($msg[0]=='#'))
                        {
                            $msg=_FormValidator::getMessage($formDef,_Text::substring($msg,1),$this->data);
                        }
                        else
                        {
                            $msg=_Text::format($msg,$this->data);
                        }
                    }
                }
                else
                {
                    $msg=null;
                }
            }
            catch (_FalseException $e)
            {
                throw $e;
            }
            catch (_Exception $e)
            {
                $msg=$e->getMessage();
            }
            if (($msg!=null))
            {
                throw alpha (new _SException (_FormValidator::formMsg('field-error',$msg)));
            }
        }
        return $value;
    }

    public function gail_gvtor ($data)
    {
        $formDef=$this->getFormDef();
        if (($data==null))
        {
            $msg=_FormValidator::getMessage($formDef,'FormError',$data);
            if (($msg!=''))
            {
                throw alpha (new _SException (_FormValidator::formMsg('form-error',$msg)));
            }
        }
        else
        {
            foreach($formDef->content->__nativeArray as $field)
            {
                if (($field->name()!='Field'))
                {
                    continue;
                }
                $name=$field->attribute('name');
                if ($this->data->hasElement($name))
                {
                    continue;
                }
                if (($field->attribute('hidden')=='true'))
                {
                    $data->setElement($name,$this->gail_fvtor($name,''));
                    continue;
                }
                $data->missingField=$field->attribute('name');
                $msg=_FormValidator::getMessage($formDef,'MissingField',$data);
                if (($msg!=''))
                {
                    throw alpha (new _SException (_FormValidator::formMsg('form-error',$msg)));
                }
            }
            try
            {
                foreach($formDef->Handler->__nativeArray as $handler)
                {
                    $instance=vnew ($handler->attribute('class'));
                    foreach($handler->Invoke->__nativeArray as $invoke)
                    {
                        $params=$invoke->toMap($data);
                        $params->formData=$data;
                        $params->form=$this->form;
                        $instance->{$invoke->attribute('method')}($params);
                    }
                }
            }
            catch (_FalseException $e)
            {
                throw $e;
            }
            catch (_Exception $e)
            {
                throw alpha (new _SException (_FormValidator::formMsg('form-error',$e->getMessage())));
            }
        }
    }

    public static function getInstance ()
    {
        if ((_FormValidator::$objectInstance==null))
        {
            _FormValidator::$objectInstance=alpha (new _FormValidator ());
        }
        return _FormValidator::$objectInstance;
    }

    private function __construct ()
    {
        _FormValidator::__instanceInit ($this);
    }

    public function validate ($form, $key, $handler)
    {
        if (($handler==null))
        {
            $handler=$key;
        }
        $this->result=alpha (new _Map ());
        $this->data=alpha (new _Map ());
        foreach($form->members()->__nativeArray as $name)
        {
            $o=$form->{$name};
            if (!isSubTypeOf($o,'UIElement'))
            {
                continue;
            }
            $this->data->setElement($name,$o->value);
        }
        $fvtor=$handler.'_fvtor';
        $gvtor=$handler.'_gvtor';
        $this->form=$form;
        $this->formKey=$key;
        foreach($this->data->__nativeArray as $field=>$value)
        {
            try
            {
                $r_value=$this->{$fvtor}($field,$value);
                if ((($r_value!==false)&&($this->result!=null)))
                {
                    $this->result->setElement($field,$r_value);
                }
            }
            catch (_FalseException $e)
            {
                throw $e;
            }
            catch (_SException $e)
            {
                $d=$e->getDescriptor();
                $form->{$field}->insertFront(($d?$d:alpha (new _XmlText ($e->getMessage()))));
                $d=$form->{$field}->getAttribute('class');
                if (($d==null))
                {
                    $form->{$field}->setAttribute($d=alpha (new _XmlAttribute ('class')));
                }
                $d->value.=' x-error';
                $this->result=null;
            }
            catch (_Exception $e)
            {
                $form->insertBack(alpha (new _XmlText ($e->getMessage())));
                $this->result=null;
            }
        }
        unset ($field);
        try
        {
            if (($this->{$gvtor}($this->result)===false))
            {
                $this->result=null;
            }
        }
        catch (_FalseException $e)
        {
            throw $e;
        }
        catch (_SException $e)
        {
            $d=$e->getDescriptor();
            $form->insertBack(($d?$d:alpha (new _XmlText ($e->getMessage()))));
            $this->result=null;
        }
        catch (_Exception $e)
        {
            $form->insertBack(alpha (new _XmlText ($e->getMessage())));
            $this->result=null;
        }
        return $this->result;
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