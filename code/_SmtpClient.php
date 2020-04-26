<?php

class _SmtpClient
{
    private static $__classAttributes = null;
    private $smtp;
    private $stream;


    public static function classAttributes ()
    {
        return _SmtpClient::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public static function sendMail ($params)
    {
        if (($params->hasElement('condition')&&($params->condition==false)))
        {
            return ;
        }
        if ((_Configuration::getInstance ()->Mail->enabled=='false'))
        {
            return ;
        }
        if (!$params->server)
        {
            $params->server=_Configuration::getInstance ()->Mail->server;
        }
        if (!$params->host)
        {
            $params->host=_Configuration::getInstance ()->Mail->host;
        }
        if (!$params->port)
        {
            $params->port=_Configuration::getInstance ()->Mail->port;
        }
        if (!$params->ssl)
        {
            $params->ssl=_Configuration::getInstance ()->Mail->ssl;
        }
        if (!$params->username)
        {
            $params->username=_Configuration::getInstance ()->Mail->username;
        }
        if (!$params->password)
        {
            $params->password=_Configuration::getInstance ()->Mail->password;
        }
        if (!$params->from)
        {
            $params->from=_Configuration::getInstance ()->Mail->from;
        }
        if (!$params->fromName)
        {
            $params->fromName=_Configuration::getInstance ()->Mail->fromName;
        }
        if (!$params->arrayGetElement ('native'))
        {
            $params->arraySetElement ('native',_Configuration::getInstance ()->Mail->arrayGetElement ('native'));
        }
        $data=alpha (new _Array ());
        if (($params->arrayGetElement ('native')=='true'))
        {
            if ($params->from)
            {
                $data->push('From: '.$params->fromName.' <'.$params->from.'>');
            }
            if (($params->headers!=null))
            {
                $data->merge(_Text::explode("\n",$params->headers)->format('{f:trim:0}'),true);
            }
            else
            {
                $data->push('MIME-Version: 1.0');
                $data->push('Content-Type: text/html; charset=UTF-8');
            }
            if ($params->rmessage)
            {
                $params->message=$params->rmessage;
            }
            else
            {
                $params->message=_Text::replace("\n",'<br>',$params->message);
            }
            if ((typeOf($params->email)=='Array'))
            {
                foreach($params->email->__nativeArray as $email)
                {
                    mail($email,$params->subject,$params->message,$data->implode("\n"));
                }
            }
            else
            {
                mail($params->email,$params->subject,$params->message,$data->implode("\n"));
            }
        }
        else
        {
            $smtp=alpha (new _SmtpClient ());
            $smtp->connect($params->server,$params->port);
            if (($params->ssl=='implicit'))
            {
                $smtp->activateTls();
            }
            $smtp->readLines();
            $smtp->hello(($params->host?$params->host:null));
            if ((($params->ssl=='true')||($params->ssl=='explicit')))
            {
                $smtp->startTls();
                $smtp->hello(($params->host?$params->host:null));
            }
            $smtp->auth($params->username,$params->password);
            $smtp->mailFrom($params->from);
            if ((typeOf($params->email)=='Array'))
            {
                foreach($params->email->__nativeArray as $email)
                {
                    $smtp->rcptTo($email);
                }
            }
            else
            {
                $smtp->rcptTo($params->email);
            }
            if ($params->from)
            {
                $data->push('From: '.$params->fromName.' <'.$params->from.'>');
            }
            if ((typeOf($params->email)!='Array'))
            {
                $data->push('To: <'.$params->email.'>');
            }
            else
            {
                if (($params->email->length()==1))
                {
                    $data->push('To: <'.$params->email->arrayGetElement (0).'>');
                }
            }
            $data->push('Subject: =?UTF-8?B?'._Convert::toBase64($params->subject).'?=');
            if (($params->headers!=null))
            {
                $data->merge(_Text::explode("\n",$params->headers)->format('{f:trim:0}'),true);
            }
            else
            {
                $data->push('MIME-Version: 1.0');
                $data->push('Content-Type: text/html; charset=UTF-8');
            }
            $data->push('');
            if ($params->rmessage)
            {
                $params->message=$params->rmessage;
            }
            else
            {
                $params->message=_Text::replace("\n",'<br>',$params->message);
            }
            $smtp->data($data->merge(_Text::explode("\n",$params->message)));
            $smtp->quit();
        }
    }

    public function __construct ()
    {
        _SmtpClient::__instanceInit ($this);
    }

    public function __destruct ()
    {
        $this->close();
    }

    private function readLines ()
    {
        $data=null;;
        $result='';
        while ((($data=$this->stream->readLine())!=''))
        {
            $result.=$data;
            if ((_Text::substring($data,3,1)==' '))
            {
                break;
            }
        };
        return $result;
    }

    private function responseCode ()
    {
        return ((int)_Text::substring($this->readLines(),0,3));
    }

    public function connect ($hostname, $port=25, $timeout=30)
    {
        $this->smtp=alpha (new _TcpConnection ($hostname,$port,$timeout));
        if (($this->smtp->connected()==null))
        {
            throw alpha (new _Exception ('Unable to connect to '.$hostname.':'.$port));
        }
        $this->stream=$this->smtp->stream();
        $this->smtp->socket()->setTimeout($timeout);
    }

    public function startTls ()
    {
        $this->stream->writeLine('STARTTLS');
        if (($this->responseCode()!=220))
        {
            throw alpha (new _Exception ('Command not accepted: STARTTLS'));
        }
        $this->activateTls();
    }

    public function activateTls ()
    {
        if (($this->smtp->socket()->enableCrypto(_Socket::$CryptoType->{_Configuration::getInstance ()->Shield->socketCryptoType})==false))
        {
            throw alpha (new _Exception ('Unable to start crypto on socket, type: \''._Configuration::getInstance ()->Shield->socketCryptoType.'\'.'));
        }
    }

    public function auth ($username, $password)
    {
        $this->stream->writeLine('AUTH LOGIN');
        if (($this->responseCode()!=334))
        {
            throw alpha (new _Exception ('Command not accepted: AUTH'));
        }
        $this->stream->writeLine(_Convert::toBase64($username));
        if (($this->responseCode()!=334))
        {
            throw alpha (new _Exception ('AUTH: Data not accepted: Username'));
        }
        $this->stream->writeLine(_Convert::toBase64($password));
        if (($this->responseCode()!=235))
        {
            throw alpha (new _Exception ('AUTH: Data not accepted: Password'));
        }
    }

    public function connected ()
    {
        if ((($this->smtp==null)||$this->smtp->socket()->status()->eof))
        {
            $this->close();
            return false;
        }
        return true;
    }

    public function close ()
    {
        $this->smtp=null;
        $this->stream=null;
    }

    private function ensureConnection ()
    {
        if (!$this->connected())
        {
            throw alpha (new _Exception ('Connection not established.'));
        }
    }

    public function data ($message)
    {
        $this->ensureConnection();
        $this->stream->writeLine('DATA');
        if (($this->responseCode()!=354))
        {
            throw alpha (new _Exception ('Command not accepted: DATA'));
        }
        if ((typeOf($message)=='Array'))
        {
            $this->stream->writeBytes($message->implode("\r\n"));
        }
        else
        {
            $this->stream->writeBytes($message);
        }
        $this->stream->writeBytes("\r\n.\r\n");
        if (($this->responseCode()!=250))
        {
            throw alpha (new _Exception ('DATA: Message body not accepted'));
        }
    }

    public function hello ($host=null)
    {
        $this->ensureConnection();
        if (($host==null))
        {
            $host=$this->smtp->hostname();
        }
        $this->stream->writeLine('EHLO '.$host);
        if (($this->responseCode()==250))
        {
            return ;
        }
        $this->stream->writeLine('HELO '.$host);
        if (($this->responseCode()==250))
        {
            return ;
        }
        throw alpha (new _Exception ('Command not accepted: EHLO/HELO ('.$host.')'));
    }

    public function mailFrom ($email)
    {
        $this->ensureConnection();
        $this->stream->writeLine('MAIL FROM: <'.$email.'>');
        if (($this->responseCode()!=250))
        {
            throw alpha (new _Exception ('Command not accepted: MAIL FROM ('.$email.')'));
        }
    }

    public function quit ($forceQuit=true)
    {
        $this->stream->writeLine('QUIT');
        if ((($this->responseCode()!=221)&&!$forceQuit))
        {
            throw alpha (new _Exception ('Command not accepted: QUIT'));
        }
        $this->close();
    }

    public function rcptTo ($email)
    {
        $response=null;;
        $this->ensureConnection();
        if ((typeOf($email)!='Array'))
        {
            $this->stream->writeLine('RCPT TO: <'.$email.'>');
            $response=$this->responseCode();
            if ((($response!=250)&&($response!=251)))
            {
                throw alpha (new _Exception ('Command not accepted: RCPT ('.$email.')'));
            }
        }
        else
        {
            foreach($email->__nativeArray as $_email)
            {
                rctpTo($_email);
            }
        }
    }

    public function cmd ($command)
    {
        $this->ensureConnection();
        if ((typeOf($command)!='Array'))
        {
            $this->stream->writeLine($command);
            if (($this->responseCode()!=250))
            {
                throw alpha (new _Exception ('Command not accepted: '.$command));
            }
        }
        else
        {
            foreach($command->__nativeArray as $command_text)
            {
                $this->cmd($command_text);
            }
        }
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