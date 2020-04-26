<?php

class _PayPalApi
{
    private static $__classAttributes = null;
    protected $config;
    protected $apiEndPoint;


    public static function classAttributes ()
    {
        return _PayPalApi::$__classAttributes;

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

    public function __construct ($config=null)
    {
        _PayPalApi::__instanceInit ($this);
        $this->config=_Map::fromNativeArray(array('version'=>'64','noShipping'=>1,'landingPage'=>'Billing','currency'=>'USD','paymentType'=>'Sale','sandbox'=>'false'),false);
        if (($config==null))
        {
            $config=_Configuration::getInstance ()->PayPalApi;
            if (($config==null))
            {
                throw alpha (new _Exception ('PayPalApi configuration not specified!'));
            }
        }
        $this->config->merge($config,true);
        if (($this->config->sandbox=='true'))
        {
            $this->config->apiEndPointUrl='api-3t.sandbox.paypal.com';
            $this->config->payPalUrl='https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=';
            if ($this->config->hasElement('sandbox_username'))
            {
                $this->config->username=$this->config->sandbox_username;
                $this->config->password=$this->config->sandbox_password;
                $this->config->signature=$this->config->sandbox_signature;
            }
        }
        else
        {
            $this->config->apiEndPointUrl='api-3t.paypal.com';
            $this->config->payPalUrl='https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
        }
        $this->apiEndPoint=alpha (new _HttpClient ($this->config->apiEndPointUrl,443,true));
        if (!_Session::getInstance ()->hasElement('PayPal'))
        {
            _Session::getInstance ()->PayPal=_Map::fromNativeArray(array(),false);
        }
    }

    public function apiCall ($method, $values)
    {
        $data=_Map::fromNativeArray(array('METHOD'=>$method,'VERSION'=>$this->config->version,'PWD'=>$this->config->password,'USER'=>$this->config->username,'SIGNATURE'=>$this->config->signature),false);
        $data->merge($values,true);
        $data->BUTTONSOURCE=$this->config->sbnCode;
        return _HttpClient::decodeFormData($this->apiEndPoint->postData('/nvp',$data));
    }

    public function redirectUrl ($token)
    {
        return $this->config->payPalUrl.$token;
    }

    public function SetExpressCheckoutSingle ($values, $extra=null)
    {
        if (!$values->hasElement('qty'))
        {
            $values->qty=1;
        }
        $data=_Map::fromNativeArray(array('RETURNURL'=>(($values->returnUrl?$values->returnUrl:$this->config->returnUrl)),'CANCELURL'=>(($values->cancelUrl?$values->cancelUrl:$this->config->cancelUrl)),'NOSHIPPING'=>(($values->noShipping?$values->noShipping:$this->config->noShipping)),'BRANDNAME'=>(($values->brandName?$values->brandName:$this->config->brandName)),'LANDINGPAGE'=>(($values->landingPage?$values->landingPage:$this->config->landingPage)),'PAYMENTREQUEST_0_AMT'=>($values->amt*$values->qty),'PAYMENTREQUEST_0_ITEMAMT'=>($values->amt*$values->qty),'PAYMENTREQUEST_0_DESC'=>$values->desc,'PAYMENTREQUEST_0_PAYMENTACTION'=>(($values->type?$values->type:$this->config->paymentType)),'PAYMENTREQUEST_0_CURRENCYCODE'=>(($values->currency?$values->currency:$this->config->currency)),'L_PAYMENTREQUEST_0_NAME0'=>$values->name,'L_PAYMENTREQUEST_0_DESC0'=>$values->desc,'L_PAYMENTREQUEST_0_QTY0'=>$values->qty,'L_PAYMENTREQUEST_0_AMT0'=>$values->amt),false);
        if (($data->notifyUrl!=''))
        {
            $data->PAYMENTREQUEST_0_NOTIFYURL=$values->notifyUrl;
        }
        if (($data->NOSHIPPING=='1'))
        {
            $data->REQCONFIRMSHIPPING='0';
            $data->L_PAYMENTREQUEST_0_ITEMCATEGORY0='Digital';
        }
        if (($values->recurring=='1'))
        {
            $data->L_BILLINGAGREEMENTDESCRIPTION0=$values->desc;
            $data->L_BILLINGTYPE0='RecurringPayments';
        }
        if (($extra!=null))
        {
            $data=$data->merge($extra,true);
        }
        $resp=$this->apiCall('SetExpressCheckout',$data);
        $resp->ACK=_Text::toUpperCase($resp->ACK);
        if ((($resp->ACK=='SUCCESS')||($resp->ACK=='SUCCESSWITHWARNING')))
        {
            _Session::getInstance ()->PayPal->TOKEN=$resp->TOKEN;
            _Session::getInstance ()->PayPal->PAYMENTTYPE=$data->PAYMENTREQUEST_0_PAYMENTACTION;
            _Session::getInstance ()->PayPal->CURRENCY=$data->PAYMENTREQUEST_0_CURRENCYCODE;
            _Session::getInstance ()->PayPal->PAYMENTAMT=$data->PAYMENTREQUEST_0_AMT;
            $resp->ACK='SUCCESS';
        }
        return $resp;
    }

    public function SetExpressCheckout ($data)
    {
        $resp=$this->apiCall('SetExpressCheckout',$data);
        $resp->ACK=_Text::toUpperCase($resp->ACK);
        if ((($resp->ACK=='SUCCESS')||($resp->ACK=='SUCCESSWITHWARNING')))
        {
            _Session::getInstance ()->PayPal->TOKEN=$resp->TOKEN;
            $resp->ACK='SUCCESS';
        }
        return $resp;
    }

    public function GetExpressCheckoutDetails ($token)
    {
        $resp=$this->apiCall('GetExpressCheckoutDetails',_Map::fromNativeArray(array('TOKEN'=>$token),false));
        $resp->ACK=_Text::toUpperCase($resp->ACK);
        if ((($resp->ACK=='SUCCESS')||($resp->ACK=='SUCCESSWITHWARNING')))
        {
            _Session::getInstance ()->PayPal->PAYERID=$resp->PAYERID;
            $resp->ACK='SUCCESS';
        }
        return $resp;
    }

    public function DoExpressCheckoutPayment ($finalAmount=null)
    {
        $data=_Map::fromNativeArray(array('TOKEN'=>_Session::getInstance ()->PayPal->TOKEN,'PAYERID'=>_Session::getInstance ()->PayPal->PAYERID,'PAYMENTREQUEST_0_PAYMENTACTION'=>_Session::getInstance ()->PayPal->PAYMENTTYPE,'PAYMENTREQUEST_0_AMT'=>(($finalAmount==null)?_Session::getInstance ()->PayPal->PAYMENTAMT:$finalAmount),'PAYMENTREQUEST_0_CURRENCYCODE'=>_Session::getInstance ()->PayPal->CURRENCY,'IPADDRESS'=>_Gateway::getInstance ()->serverParams->SERVER_NAME),false);
        $resp=$this->apiCall('DoExpressCheckoutPayment',$data);
        $resp->ACK=_Text::toUpperCase($resp->ACK);
        if ((($resp->ACK=='SUCCESS')||($resp->ACK=='SUCCESSWITHWARNING')))
        {
            $resp->ACK='SUCCESS';
        }
        return $resp;
    }

    public function MassPay ($data)
    {
        $resp=$this->apiCall('MassPay',$data);
        $resp->ACK=_Text::toUpperCase($resp->ACK);
        if ((($resp->ACK=='SUCCESS')||($resp->ACK=='SUCCESSWITHWARNING')))
        {
            $resp->ACK='SUCCESS';
        }
        return $resp;
    }

    public function Call ($data)
    {
        $resp=$this->apiCall('',$data);
        $resp->ACK=_Text::toUpperCase($resp->ACK);
        if ((($resp->ACK=='SUCCESS')||($resp->ACK=='SUCCESSWITHWARNING')))
        {
            $resp->ACK='SUCCESS';
        }
        return $resp;
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