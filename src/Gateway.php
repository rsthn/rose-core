<?php
/*
**	Rose\Gateway
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose;

use Rose\Configuration;
use Rose\Strings;
use Rose\Map;
use Rose\Text;
use Rose\FalseError;

/*
**	Provides an interface between clients and the system. No client can have access to the system without passing first through the Gateway.
*/

class Gateway
{
	/*
	**	Primary and only instance of this class.
	*/
	private static $objectInstance = null;

	/*
	**	List of registered services, each time the "srv" request parameter is detected, its value will be checked in
	**	this map, if an object is found, it's main() method will be invoked.
	*/
	private $registeredServices;

	/*
	**	Client request parameters (both GET and POST).
	*/
	public $requestParams;

	/*
	**	Server parameters (basically the $_SERVER array).
	*/
	public $serverParams;

	/*
	**	Available cookies (basically the $_COOKIES array).
	*/
    public $cookies;

	/*
	**	Full URL address to the Gateway's entry point.
	*/
	public $EP;

	/*
	**	Relative URL root where the index is found. Usually it is "/".
	*/
	public $Root;
	
	/*
	**	Local file system root where the index is found.
	*/
    public $LocalRoot;

	/*
	**	Returns the instance of this class.
	*/
    public static function getInstance ()
    {
		if (Gateway::$objectInstance == null)
			Gateway::$objectInstance = new Gateway();

        return Gateway::$objectInstance;
    }

	/*
	**	Constructs the Gateway object, this is a private constructor as this class can have only one instance.
	*/
	private function __construct()
	{
		$this->requestParams = Map::fromNativeArray (array_merge ($_REQUEST, $_FILES));
		$this->serverParams = Map::fromNativeArray ($_SERVER);
		$this->cookies = Map::fromNativeArray ($_COOKIE);
	}

	/*
	**	Executed by the framework initializer when a client has sent a request to the system.
	*/
    public function main ()
    {
		// Set entry point URL and root.
		$this->Root = Text::substring($this->serverParams->SCRIPT_NAME, 0, -9);

		$this->EP = (Text::toUpperCase($this->serverParams->HTTPS) == 'ON' ? 'https://' : 'http://')
					.(Configuration::getInstance()->Gateway->serverName ? Configuration::getInstance()->Gateway->serverName : $this->serverParams->SERVER_NAME)
					.(
						(Text::toUpperCase($this->serverParams->HTTPS) == 'ON' ? $this->serverParams->SERVER_PORT != '443' : $this->serverParams->SERVER_PORT != '80')
						? ':'.$this->serverParams->SERVER_PORT
						: ''
					)
					.$this->Root;

		$this->LocalRoot = getcwd();

        if (Text::substring($this->LocalRoot, -1) != '/')
            $this->LocalRoot .= '/';

		// Start strings module for language selection.
		Strings::getInstance();

		// If service parameter is set in Gateway configuration, load it as 'srv' to force activation of service.
		if (Configuration::getInstance()->Gateway->service != null)
			$this->requestParams->srv = Configuration::getInstance()->Gateway->service;

		// Detect is a service is being requested.
        if ($this->requestParams->srv != null)
        {
            if ($this->registeredServices->hasElement ($this->requestParams->srv))
                $this->registeredServices->getElement ($this->requestParams->srv)->execute();

            return;
		}
    }

	/*
	**	Registers a service.
	*/
    public function registerService ($serviceCode, $handlerObject)
    {
        $this->registeredServices->setElement ($serviceCode, $handlerObject);
    }

	/*
	**	Returns the service handler object given a service code.
	*/
    public function getService ($serviceCode)
    {
        if ($this->registeredServices->hasElement($serviceCode))
            return $this->registeredServices->getElement($serviceCode);
        else
            return null;
    }

	/*
	**	Invalidates the gateway instance.
	*/
    public static function close ()
    {
		Gateway::$objectInstance = null;
    }

	/*
	**	Adds a header to the HTTP response.
	*/
    public static function header ($headerItem)
    {
        call_user_func('header', $headerItem);
    }

	/*
	**	Immediately redirects to the specified URL. A FalseError is triggered to ensure immediate exit.
	*/
    public static function redirect ($location)
    {
        Gateway::header('location: '.$location);
        throw new FalseError ();
    }
}
