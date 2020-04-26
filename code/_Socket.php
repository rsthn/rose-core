<?php

class _Socket extends _StreamDescriptor
{
    private static $__classAttributes = null;
    public $errno;
    public static $CryptoType;
    public $errstr;


    public static function classAttributes ()
    {
        return _Socket::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        _Socket::$CryptoType=_Enum::fromMap(_Map::fromNativeArray(array('SSLv2_CLIENT'=>'STREAM_CRYPTO_METHOD_SSLv2_CLIENT','SSLv3_CLIENT'=>'STREAM_CRYPTO_METHOD_SSLv3_CLIENT','SSLv23_CLIENT'=>'STREAM_CRYPTO_METHOD_SSLv23_CLIENT','TLS_CLIENT'=>'STREAM_CRYPTO_METHOD_TLS_CLIENT','SSLv2_SERVER'=>'STREAM_CRYPTO_METHOD_SSLv2_SERVER','SSLv3_SERVER'=>'STREAM_CRYPTO_METHOD_SSLv3_SERVER','SSLv23_SERVER'=>'STREAM_CRYPTO_METHOD_SSLv23_SERVER','TLS_SERVER'=>'STREAM_CRYPTO_METHOD_TLS_SERVER','TLSv1_0_CLIENT'=>'STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT','TLSv1_1_CLIENT'=>'STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT','TLSv1_2_CLIENT'=>'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')));
        $clsAttr = array();
    }


    public function __destruct ()
    {
        parent::__destruct ();
    }

    public function __construct ($hostname, $port, $transport='tcp', $timeout=30)
    {
        parent::__construct ();
        _Socket::__instanceInit ($this);
        if ((_Text::position($hostname,'//')===false))
        {
            $hostname=$transport.'://'.$hostname;
        }
        $context=stream_context_create();
        stream_context_set_option($context,'ssl','verify_peer',false);
        stream_context_set_option($context,'ssl','verify_peer_name',false);
        stream_context_set_option($context,'ssl','allow_self_signed',true);
        $this->_desc=stream_socket_client($hostname.':'.$port,$this->errno,$this->errstr,$timeout,beta('STREAM_CLIENT_CONNECT'),$context);
    }

    public function setTimeout ($timeout)
    {
        socket_set_timeout($this->_desc,$timeout,0);
    }

    public function enableCrypto ($type)
    {
        return stream_socket_enable_crypto($this->_desc,true,beta($type));
    }

    public function status ()
    {
        return _Map::fromNativeArray(socket_get_status($this->_desc));
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