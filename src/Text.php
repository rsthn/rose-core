<?php

namespace Rose;

use Rose\Map;
use Rose\Arry;
use Rose\Strings;
use Rose\Regex;

class Text
{
    public static function format ($text, $values=null, $gbl=null)
    {
        $k1=null;;
        $k2=null;;
        $tmp=null;;
        $i=0;
        $j=null;;
        $length=Text::length($text);
        $result='';
        $stack = new Arry ();
        $locals = new Map ();
        $values=Arry::fromNativeArray(array($locals,$gbl,$values),false);
        $i4=Text::length($text);
        while (true)
        {
            $i2=Text::position($text,'{',$i);
            $i3=Text::position($text,'}',$i);
            if (($i2===false))
            {
                $i2=$i4;
            }
            if (($i3===false))
            {
                $i3=$i4;
            }
            if ((($i2==$i4)&&($i3==$i4)))
            {
                if (($i4!=$i))
                {
                    $result.=Text::substring($text,$i,($i4-$i));
                }
                break;
            }
            if (($i2<$i3))
            {
                if (($i2!=$i))
                {
                    $stack->push($result.Text::substring($text,$i,($i2-$i)));
                }
                else
                {
                    $stack->push($result);
                }
                $result='';
                $i=($i2+1);
                continue;
            }
            $result.=Text::substring($text,$i,($i3-$i));
            $i=($i3+1);
            if ((($result=='')||Regex::stMatch("/^[ \t\n\r\f\v\"']/",$result[0])))
            {
                if (($result==''))
                {
                    $result=$stack->pop();
                }
                else
                {
                    $result=$stack->pop().'{'.$result.'}';
                }
                continue;
            }
            if (($result[0]=='@'))
            {
                $result=Text::replace(' ','&nbsp;',Text::substring($result,1));
                $result=Text::replace(':','&#58;',$result);
                $result=Text::replace('{','&#123;',$result);
                $result=Text::replace('}','&#125;',$result);
                $result=$stack->pop().$result;
                continue;
            }
            if ((($result[0]=='~')&&($result[1]!=' ')))
            {
                $result=$stack->pop().Text::format(Text::substring($result,1),$values->arrayGetElement (2),$values->arrayGetElement (1));
                continue;
            }
            if (($result[0]=='#'))
            {
                $locals->setElement($tmp='T'.$locals->length(),Text::substring($result,1));
                $result=$stack->pop().$tmp;
                continue;
            }
            if (($result[0]==':'))
            {
                $result=$stack->pop().'{'.Text::substring($result,1).'}';
                continue;
            }
            $params=Text::split(':',$result);
            switch ($params->length())
            {
                case 4:
                switch ($params->arrayGetElement (0))
                {
                    case 'extract':
                    if ((Text::substring($params->arrayGetElement (1),0,1)=='/'))
                    {
                        $result=Regex::getString($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (3)),$params->arrayGetElement (2));
                    }
                    else
                    {
                        $result=Regex::getString(Strings::getInstance ()->Patterns->{$params->arrayGetElement (1)},Text::getParam($values,$params->arrayGetElement (3)),$params->arrayGetElement (2));
                    }
                    break;
                    case 'replace':
                    $result=Text::replace(Text::getParam($values,$params->arrayGetElement (1)),Text::getParam($values,$params->arrayGetElement (2)),Text::getParam($values,$params->arrayGetElement (3)));
                    break;
                    case 'wreplace':
                    if ((Text::substring($params->arrayGetElement (1),0,1)=='/'))
                    {
                        $result=Regex::stReplace($params->arrayGetElement (1),$params->arrayGetElement (2),Text::getParam($values,$params->arrayGetElement (3)));
                    }
                    else
                    {
                        $result=Regex::stReplace(Text::getParam($values,$params->arrayGetElement (1)),$params->arrayGetElement (2),Text::getParam($values,$params->arrayGetElement (3)));
                    }
                    break;
                    case 'substr':
                    if (($params->arrayGetElement (2)==0))
                    {
                        $result=Text::substring(Text::getParam($values,$params->arrayGetElement (3)),$params->arrayGetElement (1));
                    }
                    else
                    {
                        $result=Text::substring(Text::getParam($values,$params->arrayGetElement (3)),$params->arrayGetElement (1),$params->arrayGetElement (2));
                    }
                    break;
                    case 'c':
                    $result=_Resources::getInstance ()->exists('conf_'.$params->arrayGetElement (1));
                    if (!$result)
                    {
                        try
                        {
                            $result=_Configuration::loadFrom('resources/conf/'.$params->arrayGetElement (1).'.conf');
                        }
                        catch (_Exception $e)
                        {
                            $result=Map::fromNativeArray(array(),false);
                        }
                        _Resources::getInstance ()->{'conf_'.$params->arrayGetElement (1)}=$result;
                        if ($params->arrayGetElement (2))
                        {
                            $result=$result->{$params->arrayGetElement (2)};
                        }
                    }
                    else
                    {
                        if ($params->arrayGetElement (2))
                        {
                            $result=_Resources::getInstance ()->{'conf_'.$params->arrayGetElement (1)}->{$params->arrayGetElement (2)};
                        }
                        else
                        {
                            $result=_Resources::getInstance ()->{'conf_'.$params->arrayGetElement (1)};
                        }
                    }
                    $result=Text::format($result->{$params->arrayGetElement (3)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 's':
                    $result=Text::format(Strings::getInstance ()->{'/'.$params->arrayGetElement (1)}->{$params->arrayGetElement (2)}->{$params->arrayGetElement (3)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'S':
                    $result=Text::format(Strings::getInstance ()->{$params->arrayGetElement (1)}->{$params->arrayGetElement (2)}->{$params->arrayGetElement (3)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'matchAll':
                    if ((Text::substring($params->arrayGetElement (2),0,1)=='/'))
                    {
                        $result=Regex::stMatchAll($params->arrayGetElement (2),Text::getParam($values,$params->arrayGetElement (3)),((int)$params->arrayGetElement (1)));
                    }
                    else
                    {
                        $result=Regex::stMatchAll(Strings::getInstance ()->Patterns->{$params->arrayGetElement (2)},Text::getParam($values,$params->arrayGetElement (3)),((int)$params->arrayGetElement (1)));
                    }
                    break;
                    case 'sm':
                    try
                    {
                        $k1=Text::getParam($values,$params->arrayGetElement (3));
                        if ((typeOf($k1)!='Array'))
                        {
                            $k1=_Resources::getInstance ()->sqlConn->execQueryA($k1,true);
                        }
                        $result=Map::fromNativeArray(array(),false);
                        foreach($k1->__nativeArray as $tmp)
                        {
                            $result->arraySetElement ($tmp->arrayGetElement ($params->arrayGetElement (1)),$tmp->arrayGetElement ($params->arrayGetElement (2)));
                        }
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result=new Map();
                    }
                    break;
                }
                break;
                case 3:
                $s=null;;
                switch ($params->arrayGetElement (0))
                {
                    case 'l':
                    $result=Text::format(Strings::getInstance ()->{'/Local'}->{$params->arrayGetElement (1)}->{$params->arrayGetElement (2)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'truncate':
                    $result=Text::truncate(Text::getParam($values,$params->arrayGetElement (2)),$params->arrayGetElement (1));
                    break;
                    case 'substr':
                    $result=Text::substring(Text::getParam($values,$params->arrayGetElement (2)),$params->arrayGetElement (1));
                    break;
                    case 'pad':
                    $result=Text::padString(Text::getParam($values,$params->arrayGetElement (2)),$params->arrayGetElement (1));
                    break;
                    case 'len':
                    $result=Text::length(Text::getParam($values,$params->arrayGetElement (2)),$params->arrayGetElement (1));
                    break;
                    case 'map':
                    $s=Text::getParam($values,$params->arrayGetElement (2));
                    $k1=Text::split('=',$params->arrayGetElement (1));
                    $k2=Text::split(',',$k1->arrayGetElement (0));
                    $k1=Text::split(',',$k1->arrayGetElement (1));
                    $result=$k1->arrayGetElement ($k2->indexOf($s));
                    if (($result==null))
                    {
                        $result=$k1->arrayGetElement ($k2->indexOf(''));
                    }
                    break;
                    case 'alt':
                    $s=Text::getParam($values,$params->arrayGetElement (2));
                    $k1=Text::split(',',$params->arrayGetElement (1));
                    switch ($k1->arrayGetElement (0))
                    {
                        case '<':
                        $s=(($s<$k1->arrayGetElement (1))?$k1->arrayGetElement (2):$k1->arrayGetElement (3));
                        break;
                        case '<=':
                        $s=(($s<=$k1->arrayGetElement (1))?$k1->arrayGetElement (2):$k1->arrayGetElement (3));
                        break;
                        case '>':
                        $s=(($s>$k1->arrayGetElement (1))?$k1->arrayGetElement (2):$k1->arrayGetElement (3));
                        break;
                        case '>=':
                        $s=(($s>=$k1->arrayGetElement (1))?$k1->arrayGetElement (2):$k1->arrayGetElement (3));
                        break;
                        case '==':
                        $s=(($s==$k1->arrayGetElement (1))?$k1->arrayGetElement (2):$k1->arrayGetElement (3));
                        break;
                        case '!=':
                        $s=(($s!=$k1->arrayGetElement (1))?$k1->arrayGetElement (2):$k1->arrayGetElement (3));
                        break;
                    }
                    $result=$s;
                    break;
                    case 'filter':
                    case 'f':
                    $result=_Convert::filter($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    break;
                    case 'extract':
                    if ((Text::substring($params->arrayGetElement (1),0,1)=='/'))
                    {
                        $result=Regex::stExtract($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    else
                    {
                        $result=Regex::stExtract(Strings::getInstance ()->Patterns->{$params->arrayGetElement (1)},Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    break;
                    case 'e':
                    $result=Regex::stExtract(Strings::getInstance ()->Patterns->{$params->arrayGetElement (1)},_Gateway::getInstance ()->requestParams->{$params->arrayGetElement (2)});
                    _Gateway::getInstance ()->requestParams->{$params->arrayGetElement (2)}=$result;
                    $result=(($result=='')?'0':'1');
                    break;
                    case 'test':
                    if ((Text::substring($params->arrayGetElement (1),0,1)=='/'))
                    {
                        $result=Regex::stMatch($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    else
                    {
                        $result=Regex::stMatch(Strings::getInstance ()->Patterns->{$params->arrayGetElement (1)},Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    $result=($result?'1':'0');
                    break;
                    case 'matches':
                    $result=Regex::stMatch(Text::getParam($values,$params->arrayGetElement (1)),Text::getParam($values,$params->arrayGetElement (2)));
                    $result=($result?'1':'0');
                    break;
                    case 'match':
                    if ((Text::substring($params->arrayGetElement (1),0,1)=='/'))
                    {
                        $result=Regex::stMatchFirst($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    else
                    {
                        $result=Regex::stMatchFirst(Strings::getInstance ()->Patterns->{$params->arrayGetElement (1)},Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    break;
                    case 'matchAll':
                    if ((Text::substring($params->arrayGetElement (1),0,1)=='/'))
                    {
                        $result=Regex::stMatchAll($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    else
                    {
                        $result=Regex::stMatchAll(Strings::getInstance ()->Patterns->{$params->arrayGetElement (1)},Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    break;
                    case 'E':
                    $result=Regex::stExtract(Strings::getInstance ()->Patterns->{$params->arrayGetElement (1)},_Gateway::getInstance ()->requestParams->{$params->arrayGetElement (2)});
                    _Gateway::getInstance ()->requestParams->{$params->arrayGetElement (2)}=$result;
                    break;
                    case 's':
                    $result=Text::format(Strings::getInstance ()->{'/'.$params->arrayGetElement (1)}->{$params->arrayGetElement (2)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'S':
                    $result=Text::format(Strings::getInstance ()->{$params->arrayGetElement (1)}->{$params->arrayGetElement (2)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'c':
                    $result=Text::format(_Configuration::getInstance ()->{$params->arrayGetElement (1)}->{$params->arrayGetElement (2)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'replace':
                    if ((Text::substring($params->arrayGetElement (1),0,1)=='!'))
                    {
                        $k1=Text::split(Text::substring($params->arrayGetElement (1),1,1),Text::substring($params->arrayGetElement (1),2));
                    }
                    else
                    {
                        $k1=Text::split(',',$params->arrayGetElement (1));
                    }
                    if (($k1->length()>1))
                    {
                        $result=Text::replace($k1->arrayGetElement (0),$k1->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    else
                    {
                        $result=Text::replace($k1->arrayGetElement (0),'',Text::getParam($values,$params->arrayGetElement (2)));
                    }
                    break;
                    case 'res':
                    $result=_Resources::getInstance ()->{$params->arrayGetElement (1)}->{$params->arrayGetElement (2)};
                    break;
                    case 'sysp':
                    _SystemParameters::getInstance ()->setElement($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    $result='';
                    break;
                    case 'usrp':
                    _Session::getInstance ()->CurrentUser->setElement($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    $result='';
                    break;
                    case 'hmac':
                    $result=_Convert::toHmac(Text::getParam($values,$params->arrayGetElement (1)),Text::getParam($values,$params->arrayGetElement (2)));
                    break;
                    case 'sess':
                    _Session::getInstance ()->{$params->arrayGetElement (1)}=Text::getParam($values,$params->arrayGetElement (2));
                    $result='';
                    break;
                    case 'tag':
                    $result='<'.$params->arrayGetElement (1).'>'.Text::getParam($values,$params->arrayGetElement (2)).'</'.$params->arrayGetElement (1).'>';
                    break;
                    case 'wordwrap':
                    $result=Text::wordWrap(Text::getParam($values,$params->arrayGetElement (2)),$params->arrayGetElement (1),"\n",true);
                    break;
                    case 'sm':
                    try
                    {
                        $k1=Text::getParam($values,$params->arrayGetElement (2));
                        if ((typeOf($k1)!='Array'))
                        {
                            $k1=_Resources::getInstance ()->sqlConn->execQueryA($k1,true);
                        }
                        $result=Map::fromNativeArray(array(),false);
                        foreach($k1->__nativeArray as $tmp)
                        {
                            $result->arraySetElement ($tmp->arrayGetElement ($params->arrayGetElement (1)),$tmp);
                        }
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result=new Map();
                    }
                    break;
                    case 'join':
                    $result=Text::getParam($values,$params->arrayGetElement (2))->implode($params->arrayGetElement (1));
                    break;
                    case 'split':
                    $result=Text::split($params->arrayGetElement (1),Text::getParam($values,$params->arrayGetElement (2)));
                    break;
                    case 'indexOf':
                    $result=Text::getParam($values,$params->arrayGetElement (2))->indexOf($params->arrayGetElement (1));
                    break;
                    case 'has':
                    $result=((Text::getParam($values,$params->arrayGetElement (2))->indexOf($params->arrayGetElement (1))!==null)?'1':'0');
                    break;
                    case 'fmt':
                    $result=Text::getParam($values,$params->arrayGetElement (2))->format(Text::getParam($values,$params->arrayGetElement (1)));
                    break;
                }
                break;
                case 2:
                switch ($params->arrayGetElement (0))
                {
                    case 'p':
                    $result=_Gateway::getInstance ()->requestParams->{$params->arrayGetElement (1)};
                    break;
                    case 's':
                    $result=Text::format(Strings::getInstance ()->Def->{$params->arrayGetElement (1)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'l':
                    $result=Text::format(Strings::getInstance ()->{'/Local'}->{$params->arrayGetElement (1)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'x':
                    $result=Text::format(Strings::getInstance ()->LocalDef->{$params->arrayGetElement (1)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'c':
                    $result=Text::format(_Configuration::getInstance ()->General->{$params->arrayGetElement (1)},$values->arrayGetElement (2),$values->arrayGetElement (1));
                    break;
                    case 'u':
                    if ((_Session::getInstance ()->CurrentUser==null))
                    {
                        $result='';
                    }
                    else
                    {
                        $result=_Session::getInstance ()->CurrentUser->{$params->arrayGetElement (1)};
                    }
                    break;
                    case 'z':
                    $result=_Session::getInstance ()->{$params->arrayGetElement (1)};
                    break;
                    case 'sess':
                    $result=_Session::getInstance ()->{$params->arrayGetElement (1)};
                    break;
                    case 'priv':
                    if ((_Session::getInstance ()->CurrentUser==null))
                    {
                        $result='0';
                        break;
                    }
                    if ((_Session::getInstance ()->CurrentUser->privileges->indexOf('master')!==null))
                    {
                        $result='1';
                        break;
                    }
                    $result=((_Session::getInstance ()->CurrentUser->privileges->indexOf($params->arrayGetElement (1))!==null)?'1':'0');
                    break;
                    case 'r':
                    try
                    {
                        $result=_File::getContents(Text::getParam($values,$params->arrayGetElement (1)));
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result='';
                    }
                    break;
                    case 'ui':
                    try
                    {
                        $result=_File::getContents(Text::getParam($values,$params->arrayGetElement (1)));
                        $result=_UIElement::loadFromBuffer($result)->asXml();
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result='';
                    }
                    break;
                    case 'sq':
                    try
                    {
                        (_Resources::getInstance ()->sqlConn->execQuery(Text::getParam($values,$params->arrayGetElement (1)))?true:false);
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                    }
                    $result='';
                    break;
                    case 'ss':
                    try
                    {
                        $result=_Resources::getInstance ()->sqlConn->execScalar(Text::getParam($values,$params->arrayGetElement (1)));
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result='';
                    }
                    break;
                    case 'sa':
                    try
                    {
                        $result=_Resources::getInstance ()->sqlConn->execQueryA(Text::getParam($values,$params->arrayGetElement (1)),true);
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result=new Arry();
                    }
                    break;
                    case 'so':
                    try
                    {
                        $result=_Resources::getInstance ()->sqlConn->execAssoc(Text::getParam($values,$params->arrayGetElement (1)),true);
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result=new Arry();
                    }
                    break;
                    case 'sss':
                    try
                    {
                        $result=_Resources::getInstance ()->sqlConn->execScalars(Text::getParam($values,$params->arrayGetElement (1)));
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result=new Arry();
                    }
                    break;
                    case 'php':
                    try
                    {
                        $result=eval(Text::getParam($values,$params->arrayGetElement (1)));
                    }
                    catch (_Exception $e)
                    {
                        trace($e->getMessage());
                        $result='';
                    }
                    break;
                    case 'exists':
                    $result=_File::exists(Text::getParam($values,$params->arrayGetElement (1)));
                    break;
                    case 'filesize':
                    $result=_File::getInstance ()->size(Text::getParam($values,$params->arrayGetElement (1)));
                    break;
                    case 'trace':
                    trace(Text::getParam($values,$params->arrayGetElement (1)));
                    $result='';
                    break;
                    case 'len':
                    $result=Text::getParam($values,$params->arrayGetElement (1));
                    if ((typeOf($result)=='Array'))
                    {
                        $result=$result->length();
                    }
                    else
                    {
                        $result=Text::length($result);
                    }
                    break;
                    case 'ch':
                    $result=_Convert::toByte($params->arrayGetElement (1));
                    break;
                    case 'n':
                    $result='';
                    break;
                    case 'typeof':
                    $result=typeof(Text::getParam($values,$params->arrayGetElement (1)));
                    break;
                    case 'clone':
                    $result=Text::getParam($values,$params->arrayGetElement (1))->replicate();
                    break;
                    default:
                    $result=sprintf('%'.$params->arrayGetElement (0),Text::getParam($values,$params->arrayGetElement (1)));
                }
                break;
                case 1:
                if (($params->arrayGetElement (0)=='null'))
                {
                    $result=null;
                    break;
                }
                if ((Text::substring($params->arrayGetElement (0),0,1)=='>'))
                {
                    $result=Text::getParam($values,$params->arrayGetElement (0));
                    break;
                }
                if (((Text::substring($params->arrayGetElement (0),0,1)=='=')&&(Text::substring($params->arrayGetElement (0),1,1)!=' ')))
                {
                    $k1=Text::split(' ',$params->arrayGetElement (0));
                    $functionName=Text::substring($k1->shift(),1);
                    for ($result=0; ($result<$k1->length()); $result++)
                    {
                        $k1->arraySetElement ($result,Text::getParam($values,$k1->arrayGetElement ($result)));
                    }
                    if ((Text::$StaticFunctions==null))
                    {
                        Text::$StaticFunctions=new _ClassInterface ('StaticFunctions');
                    }
                    $result=Text::$StaticFunctions->invokeStatic($functionName,$k1);
                    break;
                }
                if (Text::position($params->arrayGetElement (0),' '))
                {
                    $k1=Text::split(' ',$params->arrayGetElement (0));
                    switch ($k1->arrayGetElement (0))
                    {
                        case '?':
                        if (($k1->length()==4))
                        {
                            $tmp=($k1->arrayGetElement (1)?$k1->arrayGetElement (2):$k1->arrayGetElement (3));
                        }
                        else
                        {
                            $tmp=($k1->arrayGetElement (1)?$k1->arrayGetElement (2):'');
                        }
                        break;
                        case 'true':
                        $tmp=($k1->arrayGetElement (1)?((($k1->length()==3)?$k1->arrayGetElement (2):'1')):'0');
                        break;
                        case 'false':
                        $tmp=(!$k1->arrayGetElement (1)?((($k1->length()==3)?$k1->arrayGetElement (2):'1')):'0');
                        break;
                        case 'not':
                        $tmp=(((($k1->length()>1)&&$k1->arrayGetElement (1)))?'0':'1');
                        break;
                        case '$':
                        if (($k1->length()==3))
                        {
                            if (!_Resources::getInstance ()->exists('Vars'))
                            {
                                _Resources::getInstance ()->register('Vars',new Map());
                            }
                            _Resources::getInstance ()->Vars->{$k1->arrayGetElement (1)}=$k1->arrayGetElement (2);
                            $tmp='';
                        }
                        else
                        {
                            $tmp=_Resources::getInstance ()->Vars->{$k1->arrayGetElement (1)};
                        }
                        break;
                        case '=':
                        if (!_Resources::getInstance ()->exists('Vars'))
                        {
                            _Resources::getInstance ()->register('Vars',new Map());
                        }
                        $tmp=_Resources::getInstance ()->Vars->{$k1->arrayGetElement (1)}=$k1->arrayGetElement (2);
                        break;
                        case '$$':
                        if (($k1->length()==3))
                        {
                            if (!_Resources::getInstance ()->exists('Vars'))
                            {
                                _Resources::getInstance ()->register('Vars',new Map());
                            }
                            _Resources::getInstance ()->Vars->{$k1->arrayGetElement (1)}=Text::getParam($values,$k1->arrayGetElement (2));
                            $tmp='';
                        }
                        break;
                        case '$$$':
                        if (($k1->length()==3))
                        {
                            if (!_Resources::getInstance ()->exists('Vars'))
                            {
                                _Resources::getInstance ()->register('Vars',new Map ());
                            }
                            _Resources::getInstance ()->Vars->{$k1->arrayGetElement (1)}=Text::format('{'.Text::getParam($values,$k1->arrayGetElement (2)).'}',$values->arrayGetElement (2),$values->arrayGetElement (1));
                            $tmp='';
                        }
                        break;
                        case '!':
                        $tmp=(((($k1->length()>1)&&$k1->arrayGetElement (1)))?'0':'1');
                        break;
                        case '~':
                        $tmp=~(((int)$k1->arrayGetElement (1)));
                        break;
                        case '+':
                        $tmp=($k1->arrayGetElement (1)+$k1->arrayGetElement (2));
                        break;
                        case '-':
                        if (($k1->length()==2))
                        {
                            $tmp=-$k1->arrayGetElement (1);
                        }
                        else
                        {
                            $tmp=($k1->arrayGetElement (1)-$k1->arrayGetElement (2));
                        }
                        break;
                        case '*':
                        $tmp=($k1->arrayGetElement (1)*$k1->arrayGetElement (2));
                        break;
                        case '/':
                        $tmp=(!$k1->arrayGetElement (2)?0:($k1->arrayGetElement (1)/$k1->arrayGetElement (2)));
                        break;
                        case '\\':
                        $tmp=((int)((!$k1->arrayGetElement (2)?0:($k1->arrayGetElement (1)/$k1->arrayGetElement (2)))));
                        break;
                        case 'shl':
                        $tmp=($k1->arrayGetElement (1)<<$k1->arrayGetElement (2));
                        break;
                        case 'shr':
                        $tmp=($k1->arrayGetElement (1)>>$k1->arrayGetElement (2));
                        break;
                        case '%':
                        $tmp=($k1->arrayGetElement (1)%$k1->arrayGetElement (2));
                        break;
                        case '&':
                        case 'bwand':
                        $tmp=((((int)$k1->arrayGetElement (1)))&(((int)$k1->arrayGetElement (2))));
                        break;
                        case '|':
                        case 'bwor':
                        $tmp=((((int)$k1->arrayGetElement (1)))|(((int)$k1->arrayGetElement (2))));
                        break;
                        case '<':
                        case 'lt':
                        $tmp=(($k1->arrayGetElement (1)<$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case '>':
                        case 'gt':
                        $tmp=(($k1->arrayGetElement (1)>$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case '<=':
                        case 'le':
                        $tmp=(($k1->arrayGetElement (1)<=$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case '>=':
                        case 'ge':
                        $tmp=(($k1->arrayGetElement (1)>=$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case '==':
                        case 'eq':
                        $tmp=(($k1->arrayGetElement (1)==$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case '!=':
                        case 'ne':
                        $tmp=(($k1->arrayGetElement (1)!=$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case '&&':
                        case 'and':
                        $tmp=(($k1->arrayGetElement (1)&&$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case '||':
                        case 'or':
                        $tmp=(($k1->arrayGetElement (1)||$k1->arrayGetElement (2))?'1':'0');
                        break;
                        case 'null':
                        $tmp=(((($k1->length()<2)||($k1->arrayGetElement (1)==null)))?'1':'0');
                        break;
                        case 'min':
                        $tmp=(($k1->arrayGetElement (1)<$k1->arrayGetElement (2))?$k1->arrayGetElement (1):$k1->arrayGetElement (2));
                        break;
                        case 'max':
                        $tmp=(($k1->arrayGetElement (1)>$k1->arrayGetElement (2))?$k1->arrayGetElement (1):$k1->arrayGetElement (2));
                        break;
                        case 'between':
                        $tmp=(((($k1->arrayGetElement (3)>=$k1->arrayGetElement (1))&&($k1->arrayGetElement (3)<=$k1->arrayGetElement (2))))?'1':'0');
                        break;
                        case 'abs':
                        $tmp=(($k1->arrayGetElement (1)<0)?-$k1->arrayGetElement (1):$k1->arrayGetElement (1));
                        break;
                        case 'floor':
                        $tmp=floor($k1->arrayGetElement (1));
                        break;
                        case 'ceil':
                        $tmp=ceil($k1->arrayGetElement (1));
                        break;
                        case 'pow':
                        $tmp=pow($k1->arrayGetElement (1),$k1->arrayGetElement (2));
                        break;
                        case 'div':
                        $tmp=((int)((!$k1->arrayGetElement (2)?0:($k1->arrayGetElement (1)/$k1->arrayGetElement (2)))));
                        if ((($tmp*$k1->arrayGetElement (2))<$k1->arrayGetElement (1)))
                        {
                            $tmp++;
                        }
                        break;
                        case 'align':
                        $tmp=($k1->arrayGetElement (2)*(((int)((!$k1->arrayGetElement (2)?0:($k1->arrayGetElement (1)/$k1->arrayGetElement (2)))))));
                        break;
                        case 'int':
                        $tmp=((int)$k1->arrayGetElement (1));
                        if (!$tmp)
                        {
                            $tmp='0';
                        }
                        break;
                        case 'fract':
                        $tmp=($k1->arrayGetElement (1)-(((int)$k1->arrayGetElement (1))));
                        if (!$tmp)
                        {
                            $tmp='0';
                        }
                        break;
                        case 'float':
                        $tmp=((float)$k1->arrayGetElement (1));
                        if (!$tmp)
                        {
                            $tmp='0';
                        }
                        break;
                        case 'round':
                        $tmp=round($k1->arrayGetElement (1));
                        if (!$tmp)
                        {
                            $tmp='0';
                        }
                        break;
                        case 'instr':
                        $tmp=((Text::position(Text::getParam($values,$k1->arrayGetElement (1)),Text::getParam($values,$k1->arrayGetElement (2)))!==false)?'1':'0');
                        break;
                        case 'list':
                        $k1->shift();
                        if ((($k1->length()==1)&&Text::position($k1->arrayGetElement (0),'..')))
                        {
                            $k1=Text::split('..',$k1->arrayGetElement (0));
                            $tmp=new Arry();
                            for ($j=$k1->arrayGetElement (0); ($j<=$k1->arrayGetElement (1)); $j++)
                            {
                                $tmp->push($j);
                            }
                        }
                        else
                        {
                            $tmp=$k1;
                        }
                        break;
                        case 'merge':
                        $k1->shift();
                        $j=Text::getParam($values,$k1->shift());
                        if ((typeOf($j)=='Array'))
                        {
                            $tmp=Arry::fromNativeArray(array(),false);
                        }
                        else
                        {
                            $tmp=Map::fromNativeArray(array(),false);
                        }
                        $tmp->merge($j,true);
                        while ($k1->length())
                        {
                            $tmp->merge(Text::getParam($values,$k1->shift()),true);
                        };
                        break;
                        case '_merge':
                        $k1->shift();
                        $tmp=Text::getParam($values,$k1->shift());
                        while ($k1->length())
                        {
                            $tmp->merge(Text::getParam($values,$k1->shift()),true);
                        };
                        break;
                        case 'in':
                        $result=$k1->arrayGetElement (1);
                        $k1->shift();
                        $k1->shift();
                        $tmp=(($k1->indexOf($result)===null)?'0':'1');
                        break;
                        case 'fmt':
                        $result=Text::getParam($values,$k1->arrayGetElement (1));
                        $k1->shift();
                        $k1->shift();
                        for ($tmp=0; ($tmp<$k1->length()); $tmp++)
                        {
                            $k1->arraySetElement ($tmp,Text::getParam($values,$k1->arrayGetElement ($tmp)));
                        }
                        $tmp=Text::format($result,$k1,$values->arrayGetElement (1));
                        break;
                        case 'set':
                        $params=$k1;
                        $k1=Text::split('.',$params->arrayGetElement (1));
                        if ((($tmp=Text::getParam($values,$k1->arrayGetElement (0)))==null))
                        {
                            if ((Text::substring($k1->arrayGetElement (0),0,1)=='@'))
                            {
                                $tmp=(new _ClassInterface (Text::substring($k1->arrayGetElement (0),1)))->getStaticProperty($k1->arrayGetElement (1));
                                $k1->shift();
                                $k1->shift();
                            }
                            else
                            {
                                $tmp=(new _ClassInterface ($k1->arrayGetElement (0)))->invokeStatic('getInstance');
                                $k1->shift();
                            }
                        }
                        else
                        {
                            $k1->shift();
                        }
                        if ($k1->length())
                        {
                            $item=null;;
                            for ($result=0; ($result<($k1->length()-1)); $result++)
                            {
                                $item=$k1->arrayGetElement ($result);
                                if (is_numeric($item))
                                {
                                    $tmp=$tmp->arrayGetElement ($item);
                                }
                                else
                                {
                                    $tmp=$tmp->{$item};
                                }
                            }
                            $item=$k1->arrayGetElement (($k1->length()-1));
                            if (is_numeric($item))
                            {
                                $tmp->arraySetElement ($item,Text::getParam($values,$params->arrayGetElement (2)));
                            }
                            else
                            {
                                $tmp->{$item}=Text::getParam($values,$params->arrayGetElement (2));
                            }
                        }
                        else
                        {
                            $result='';
                        }
                        break;
                        case 'ifnull':
                        $tmp=($k1->arrayGetElement (1)?$k1->arrayGetElement (1):$k1->arrayGetElement (2));
                        break;
                        default:
                        $tmp=null;
                        break;
                    }
                    if (($tmp!==null))
                    {
                        $result=$tmp;
                        break;
                    }
                }
                if (Text::position($params->arrayGetElement (0),'.'))
                {
                    $k1=Text::split('.',$params->arrayGetElement (0));
                    $kx=false;
                    if ((($tmp=Text::getParam($values,$k1->arrayGetElement (0)))==null))
                    {
                        if ((Text::substring($k1->arrayGetElement (0),0,1)=='@'))
                        {
                            $tmp=(new _ClassInterface (Text::substring($k1->arrayGetElement (0),1)))->getStaticProperty($k1->arrayGetElement (1));
                            $k1->shift();
                            $k1->shift();
                        }
                        else
                        {
                            $kx=(Text::length($k1->arrayGetElement (0))==1);
                            $tmp=(new _ClassInterface ($k1->arrayGetElement (0)))->invokeStatic('getInstance');
                            $k1->shift();
                        }
                    }
                    else
                    {
                        $k1->shift();
                    }
                    foreach($k1->__nativeArray as $item)
                    {
                        if (is_numeric($item))
                        {
                            $tmp=$tmp->arrayGetElement ($item);
                        }
                        else
                        {
                            if ((Text::substring($item,0,1)=='*'))
                            {
                                $tmp=$tmp->{Text::getParam($values,Text::substring($item,1))};
                            }
                            else
                            {
                                $tmp=$tmp->{$item};
                            }
                        }
                    }
                    $result=$tmp;
                    if (($kx&&(typeOf($result)=='PrimitiveType')))
                    {
                        $result=Text::format($result,$values->arrayGetElement (2),$values->arrayGetElement (1));
                    }
                }
                else
                {
                    $result=Text::getParam($values,$params->arrayGetElement (0));
                }
                break;
            }
            if ((($tmp=$stack->pop())!=''))
            {
                $result=$tmp.$result;
            }
        };
        return $result;
    }

    private static function getParam ($values, $item, $alt=false)
    {
        if ($alt)
        {
            if (($item[0]=='@'))
            {
                return Text::getParam($values,Text::substring($item,1));
            }
            return $item;
        }
        if (($item[0]=='@'))
        {
            return Text::substring($item,1);
        }
        if (($item[0]=='#'))
        {
            return Text::getParam($values,Text::substring($item,1));
        }
        if (($item[0]=='>'))
        {
            if (($item[1]=='>'))
            {
                return Text::format('{'.Text::getParam($values,Text::substring($item,1)).'}',$values->arrayGetElement (2),$values->arrayGetElement (1));
            }
            else
            {
                return Text::format('{'.Text::substring($item,1).'}',$values->arrayGetElement (2),$values->arrayGetElement (1));
            }
        }
        if (($item[0]=='$'))
        {
            return _Resources::getInstance ()->Vars->{Text::substring($item,1)};
        }
        foreach($values->__nativeArray as $collection)
        {
            if (!$collection)
            {
                continue;
            }
            if ((typeOf($collection)=='Map'))
            {
                if ($collection->hasElement($item))
                {
                    return $collection->arrayGetElement ($item);
                }
            }
            else
            {
                return $collection->arrayGetElement ($item);
            }
        }
        return null;
    }

    public static function truncate ($text, $maxLength)
    {
        if ((Text::length($text)>$maxLength))
        {
            return Text::substring($text,0,($maxLength-3)).'...';
        }
        return $text;
    }

    public static function formatString ($fmt)
    {
        $args = Arry::fromNativeArray (func_get_args (), false)->slice (1);
        return vsprintf($fmt,$args->__nativeArray);
    }

    public static function reverse ($text)
    {
        return strrev($text);
    }

    public static function replace ($a, $b, $text)
    {
        return str_replace($a,$b,$text);
    }

    public static function substring ($text, $start, $length=null)
    {
        if (($start<0))
        {
            $text=substr($text,$start);
        }
        else
        {
            if (($length===null))
            {
                $text=substr($text,$start);
            }
            else
            {
                $text=substr($text,$start,$length);
            }
        }
        return (($text===false)?'':$text);
    }

    public static function split ($delimiter, $text, $allowEmpty=true)
    {
        if ((!$allowEmpty&&!$text))
        {
            return new Arry();
        }
        if (($delimiter!=''))
        {
            return Arry::fromNativeArray(call_user_func('explode',$delimiter,$text));
        }
        else
        {
            return Arry::fromNativeArray(str_split($text));
        }
    }

    public static function repeat ($text, $N)
    {
        return str_repeat($text,$N);
    }

    public static function toUpperCase ($text, $encoding=null)
    {
        if (!$encoding)
        {
            return strtoupper($text);
        }
        return mb_strtoupper($text,$encoding);
    }

    public static function toLowerCase ($text, $encoding=null)
    {
        if (!$encoding)
        {
            return strtolower($text);
        }
        return mb_strtolower($text,$encoding);
    }

    public static function upperCaseFirst ($text)
    {
        return ucfirst($text);
    }

    public static function upperCaseWords ($text)
    {
        return ucwords($text);
    }

    public static function position ($text, $needle, $offs=0)
    {
        return strpos($text,$needle,$offs);
    }

    public static function indexOf ($text, $value)
    {
        return strpos($text,$value);
    }

    public static function revIndexOf ($text, $value, $offset=0)
    {
        return strrpos($text,$value,$offset);
    }

    public static function length ($text, $encoding=null)
    {
        if (!$encoding)
        {
            return strlen($text);
        }
        else
        {
            return mb_strlen($text,$encoding);
        }
    }

    public static function wordWrap ($text, $length, $lineBreak, $brk=false)
    {
        return wordwrap($text,$length,$lineBreak,$brk);
    }

    public static function slice ($text, $length)
    {
        $i=null;;
        $textLength=Text::length($text);
        $result=new Arry();
        for ($i=0; ($i<$textLength); $i+=$length)
        {
            $result->push(Text::substring($text,$i,$length));
        }
        return $result;
    }

    public static function trim ($text, $chars=null)
    {
        if (($chars!=null))
        {
            return call_user_func('trim',$text,$chars);
        }
        else
        {
            return call_user_func('trim',$text);
        }
    }

    public static function blanks ($text)
    {
        $result='';
        $prev='';
        $n=Text::length($text);
        $i=null;;
        for ($i=0; ($i<$n); $i++)
        {
            switch ($text[$i])
            {
                case ' ':
                case '\t':
                case "\r":
                case "\n":
                case "\f":
                case "\v":
                if (($prev==' '))
                {
                    continue;
                }
                else
                {
                    $result.=' ';
                }
                $prev=' ';
                break;
                default:
                $result.=($prev=$text[$i]);
                break;
            }
        }
        return $result;
    }

    public static function padString ($text, $len, $str=' ')
    {
        return str_pad($text,$len,$str);
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
