<?php

class _Sentinel
{
    private static $__classAttributes = null;
    private static $objectInstance;


    public static function classAttributes ()
    {
        return _Sentinel::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _Sentinel::$objectInstance=null;
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public static function getInstance ()
    {
        if ((_Sentinel::$objectInstance==null))
        {
            _Sentinel::$objectInstance=alpha (new _Sentinel ());
        }
        return _Sentinel::$objectInstance;
    }

    private function __construct ()
    {
        _Sentinel::__instanceInit ($this);
    }

    public function userAuthenticated ()
    {
        return (_Session::getInstance ()->CurrentUser!=null);
    }

    public function validUser ($username, $password)
    {
        return ((_Resources::getInstance ()->sqlConn->execScalar('SELECT COUNT(user_id) FROM ##users WHERE username='._Convert::filter('escape',$username).' AND password='._Convert::filter('escape',$password))>0)?true:false);
    }

    public function authenticate ($username, $password)
    {
        $data=_Resources::getInstance ()->sqlConn->execAssoc('SELECT * FROM ##users WHERE username='._Convert::filter('escape',$username).' AND password='._Convert::filter('escape',$password));
        if (($data==null))
        {
            return false;
        }
        if ((_Configuration::getInstance ()->Sentinel->level>=16))
        {
            _Resources::getInstance ()->sqlConn->execQuery('UPDATE ##users SET last_login=latest_login, latest_login=NOW() WHERE user_id='.$data->user_id);
        }
        else
        {
            _Resources::getInstance ()->sqlConn->execQuery('UPDATE ##users SET last_login=latest_login, latest_login='.(((int)_DateTime::nowUnixTime())).' WHERE user_id='.$data->user_id);
        }
        _Session::getInstance ()->CurrentUser=$data;
        _Session::getInstance ()->CurrentUser->privileges=$this->getPrivileges();
        return true;
    }

    public function getPrivileges ($username=null)
    {
        if (($username==null))
        {
            if (!$this->userAuthenticated())
            {
                return alpha (new _Array ());
            }
            if ((_Configuration::getInstance ()->Sentinel->level>=14))
            {
                if ((_Configuration::getInstance ()->Sentinel->level>=15))
                {
                    return _Resources::getInstance ()->sqlConn->execScalars('SELECT priv.name FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)WHERE uprv.user_id='._Session::getInstance ()->CurrentUser->user_id);
                }
                else
                {
                    return _Resources::getInstance ()->sqlConn->execScalars('SELECT priv.name FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)WHERE ugrp.user_id='._Session::getInstance ()->CurrentUser->user_id.' UNION SELECT priv.name FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)WHERE uprv.user_id='._Session::getInstance ()->CurrentUser->user_id);
                }
            }
            else
            {
                return _Resources::getInstance ()->sqlConn->execScalars('SELECT priv.name FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)WHERE ugrp.user_id='._Session::getInstance ()->CurrentUser->user_id);
            }
        }
        if ((_Configuration::getInstance ()->Sentinel->level>=14))
        {
            if ((_Configuration::getInstance ()->Sentinel->level>=15))
            {
                return _Resources::getInstance ()->sqlConn->execScalars('SELECT priv.name FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).' AND uprv.user_id=usr.user_id)');
            }
            else
            {
                return _Resources::getInstance ()->sqlConn->execScalars('SELECT priv.name FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).')WHERE ugrp.user_id=usr.user_id UNION SELECT priv.name FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).' AND uprv.user_id=usr.user_id)');
            }
        }
        else
        {
            return _Resources::getInstance ()->sqlConn->execScalars('SELECT priv.name FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).')WHERE ugrp.user_id=usr.user_id');
        }
    }

    public function hasPrivilege ($privilege, $username=null)
    {
        $count=null;;
        $privilege=_Text::explode(',',(((_Configuration::getInstance ()->Sentinel->enableMaster=='true')?'master,':'')).$privilege)->format('{filter:escape:0}');
        if (($username==null))
        {
            if (!$this->userAuthenticated())
            {
                return false;
            }
            if ((_Configuration::getInstance ()->Sentinel->level>=14))
            {
                if ((_Configuration::getInstance ()->Sentinel->level>=15))
                {
                    $count=_Resources::getInstance ()->sqlConn->execScalar('SELECT COUNT(*) FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)WHERE uprv.user_id='._Session::getInstance ()->CurrentUser->user_id.' AND priv.name IN '.$privilege);
                }
                else
                {
                    $count=_Resources::getInstance ()->sqlConn->execScalar('SELECT COUNT(*) FROM (SELECT priv.name FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)WHERE ugrp.user_id='._Session::getInstance ()->CurrentUser->user_id.' AND priv.name IN '.$privilege.'UNION SELECT priv.name FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)WHERE uprv.user_id='._Session::getInstance ()->CurrentUser->user_id.' AND priv.name IN '.$privilege.') AS Tbl');
                }
            }
            else
            {
                $count=_Resources::getInstance ()->sqlConn->execScalar('SELECT COUNT(*) FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)WHERE ugrp.user_id='._Session::getInstance ()->CurrentUser->user_id.' AND priv.name IN '.$privilege);
            }
        }
        else
        {
            if ((_Configuration::getInstance ()->Sentinel->level>=14))
            {
                if ((_Configuration::getInstance ()->Sentinel->level>=15))
                {
                    $count=_Resources::getInstance ()->sqlConn->execScalar('SELECT COUNT(*) FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).' AND uprv.user_id=usr.user_id)WHERE priv.name IN '.$privilege);
                }
                else
                {
                    $count=_Resources::getInstance ()->sqlConn->execScalar('SELECT COUNT(*) FROM (SELECT priv.name FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).')WHERE ugrp.user_id=usr.user_id AND priv.name IN '.$privilege.'UNION SELECT priv.name FROM ##privileges AS priv INNER JOIN ##user_privileges AS uprv ON(uprv.privilege_id=priv.privilege_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).' AND uprv.user_id=usr.user_id)WHERE priv.name IN '.$privilege.') AS Tbl');
                }
            }
            else
            {
                $count=_Resources::getInstance ()->sqlConn->execScalar('SELECT COUNT(*) FROM ##privileges AS priv INNER JOIN ##group_privileges AS grp ON (grp.privilege_id=priv.privilege_id)INNER JOIN ##user_groups AS ugrp ON (ugrp.group_id=grp.group_id)INNER JOIN ##users AS usr ON (usr.username='._Convert::filter('escape',$username).')WHERE ugrp.user_id=usr.user_id AND priv.name IN '.$privilege);
            }
        }
        return (($count!=0)?true:false);
    }

    public function reloadDetails ()
    {
        $data=_Resources::getInstance ()->sqlConn->execAssoc('SELECT * FROM ##users WHERE user_id='._Session::getInstance ()->CurrentUser->user_id);
        if (($data==null))
        {
            return false;
        }
        _Session::getInstance ()->CurrentUser=$data;
        _Session::getInstance ()->CurrentUser->privileges=$this->getPrivileges();
        return true;
    }

    public function clear ()
    {
        _Session::getInstance ()->CurrentUser=null;
    }

    public static function verifyPrivileges ($text, $silent=false, $username=null)
    {
        if ((!$username&&!_Sentinel::getInstance ()->userAuthenticated()))
        {
            if ($silent)
            {
                return true;
            }
            throw alpha (new _SentinelNotAuthenticated ('User has not been authenticated.'));
        }
        if (($text==null))
        {
            return false;
        }
        $groups=_Text::explode(',',_Text::trim($text));
        if (!$groups->length())
        {
            return false;
        }
        foreach($groups->__nativeArray as $group)
        {
            $groupFailed=false;
            foreach(_Text::explode(' ',_Text::trim($group))->__nativeArray as $privilege)
            {
                if (!_Sentinel::getInstance ()->hasPrivilege($privilege,$username))
                {
                    $groupFailed=true;
                    break;
                }
            }
            if (($groupFailed==false))
            {
                return false;
            }
        }
        if ($silent)
        {
            return true;
        }
        throw alpha (new _SentinelAccessDenied ('User does not have enough privileges.'));
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
            case 'CurrentPrivileges':
                return $this->getPrivileges();
            case 'IsPrivate':
                return _Sentinel::getInstance ()->userAuthenticated();
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