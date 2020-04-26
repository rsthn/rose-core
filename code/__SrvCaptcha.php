<?php

class __SrvCaptcha extends _SystemService
{
    private static $__classAttributes = null;
    public static $instance;


    public static function classAttributes ()
    {
        return __SrvCaptcha::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        __SrvCaptcha::$instance=alpha (new __SrvCaptcha ());
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ()
    {
        __SrvCaptcha::__instanceInit ($this);
        parent::__construct('captcha');
    }

    public function main ($internallyCalled=false)
    {
        $i=null;;
        $j=null;;
        $t=null;;
        $_w=_Text::format(_Configuration::getInstance ()->Captcha->width);
        $_h=_Text::format(_Configuration::getInstance ()->Captcha->height);
        $image=alpha (new _Image ());
        $image->create($_w,$_h);
        $j=_Text::length($t=_Text::format(_Configuration::getInstance ()->Captcha->charset));
        $code='';
        for ($i=_Text::format(_Configuration::getInstance ()->Captcha->length); ($i>0); $i--)
        {
            $code.=$t[(_Math::rand()%$j)];
        }
        $_bg=_Text::format(_Configuration::getInstance ()->Captcha->background);
        $image->fillRect(0,0,$_w,$_h,_Convert::fromHexInteger($_bg));
        $_font=_Text::format(_Configuration::getInstance ()->Captcha->font);
        $_size=_Text::format(_Configuration::getInstance ()->Captcha->fsize);
        $t=_Image::boundBoxTTF($_font,$_size,0,$code);
        $_fg=_Text::format(_Configuration::getInstance ()->Captcha->color);
        $image->writeTextTTF($_font,$_size,0,(($t->arrayGetElement (0)+(((($_w-$t->arrayGetElement (4)))/2)))-5),(($t->arrayGetElement (1)+(((($_h-$t->arrayGetElement (5)))/2)))-5),_Convert::fromHexInteger($_fg),$code);
        $_lines=_Text::format(_Configuration::getInstance ()->Captcha->lines);
        if (($_lines>0))
        {
            $_line_min=_Convert::fromHexInteger(_Text::format(_Configuration::getInstance ()->Captcha->line_min));
            $_line_mid=(_Convert::fromHexInteger(_Text::format(_Configuration::getInstance ()->Captcha->line_max))-$_line_min);
            if (!$_line_mid)
            {
                for ($i=$_lines; ($i>0); $i--)
                {
                    $image->line((_Math::rand()%$_w),(_Math::rand()%$_h),(_Math::rand()%$_w),(_Math::rand()%$_h),$_line_min);
                }
            }
            else
            {
                for ($i=$_lines; ($i>0); $i--)
                {
                    $image->line((_Math::rand()%$_w),(_Math::rand()%$_h),(_Math::rand()%$_w),(_Math::rand()%$_h),($_line_min+((((_Math::rand()*_Math::rand()))%$_line_mid))));
                }
            }
        }
        _Session::getInstance ()->CaptchaCode=$code;
        $t=_Text::format(_Configuration::getInstance ()->Captcha->output);
        $this->reply('image/'.$t,$image->data($t));
        return null;
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