<?php

class _DateTimeTFA
{
    private static $__classAttributes = null;


    public static function classAttributes ()
    {
        return _DateTimeTFA::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        $clsAttr = array();
        _Resources::getInstance ()->register('DateTime',alpha (new _DateTimeTFA ()));
    }


    public function __destruct ()
    {
    }

    public function __construct ()
    {
        _DateTimeTFA::__instanceInit ($this);
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
            case 'Now':
                return _DateTime::nowUnixTime(true);
            case 'sNow':
                return _Locale::getInstance ()->format('SDATETIME',_DateTime::nowUnixTime(true));
            case 'NowF':
                return _DateTime::nowUnixTime(false);
            case 'NowMicro':
                $t=_DateTime::nowUnixTime(false);
                $a=((int)$t);
                $b=($t-$a);
                return sprintf('%u%05u',$a,($b*100000));
            case 'Today':
                return (86400*(((int)((_DateTime::nowUnixTime(true)/86400)))));
            case 'sToday':
                return _Locale::getInstance ()->format('SDATE',_DateTime::nowUnixTime(true));
            case 'Year':
                return _DateTime::now()->year;
            case 'Month':
                return _DateTime::now()->month;
            case 'Day':
                return _DateTime::now()->day;
            case 'WeekDay':
                return _DateTime::now()->weekDay();
            case 'Hours':
                return _DateTime::now()->hour;
            case 'Minutes':
                return _DateTime::now()->minutes;
            case 'Seconds':
                return _DateTime::now()->seconds;
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