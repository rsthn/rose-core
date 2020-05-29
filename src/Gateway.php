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

use Rose\Errors\FalseError;
use Rose\Errors\Error;

use Rose\Configuration;
use Rose\Strings;
use Rose\Session;
use Rose\Map;
use Rose\Text;
use Rose\Regex;
use Rose\Extensions;

/*
**	Provides an interface between clients and the system. No client can have access to the system without passing first through the Gateway.
*/

class Gateway
{
	/*
	**	Primary and only instance of this class.
	*/
	private static $instance = null;

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
		if (Gateway::$instance == null)
			Gateway::$instance = new Gateway();

        return Gateway::$instance;
    }

	/*
	**	Constructs the Gateway object, this is a private constructor as this class can have only one instance.
	*/
	private function __construct()
	{
		foreach ($_FILES as &$file) {
			$file['path'] = $file['tmp_name'];
		}

		$this->requestParams = new Map (array_merge ($_REQUEST, $_FILES));
		$this->serverParams = new Map ($_SERVER);
		$this->cookies = new Map ($_COOKIE);

		$this->registeredServices = new Map();
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

		// Initialize session.
		Session::init();

		// Initialize strings.
		Strings::init();

		// Load extensions from the 'Ext' directory.
		Extensions::init();

		// If service parameter is set in Gateway configuration, load it as 'srv' to force activation of service.
		if (Configuration::getInstance()->Gateway->service != null)
			$this->requestParams->srv = Configuration::getInstance()->Gateway->service;

		// Detect is a service is being requested.
        if ($this->requestParams->srv != null)
        {
			$this->requestParams->srv = Regex::_extract('/[A-Za-z0-9_-]+/', $this->requestParams->srv);

            if ($this->registeredServices->has($this->requestParams->srv))
				$this->registeredServices->get($this->requestParams->srv)->main();
			else
				throw new Error ("Service `" . $this->requestParams->srv . "` is not registered.");

            return;
		}
    }

	/*
	**	Registers a service.
	*/
    public static function registerService ($serviceCode, $handlerObject)
    {
        Gateway::getInstance()->registeredServices->set ($serviceCode, $handlerObject);
    }

	/*
	**	Returns the service handler object given a service code.
	*/
    public static function getService ($serviceCode)
    {
        if (Gateway::getInstance()->registeredServices->hasElement($serviceCode))
            return Gateway::getInstance()->registeredServices->getElement($serviceCode);
        else
            return null;
    }

	/*
	**	Invalidates the gateway instance.
	*/
    public static function close ()
    {
		Session::close();
		Gateway::$instance = null;
    }

	/*
	**	Adds a header to the HTTP response.
	*/
    public static function header ($headerItem)
    {
        \header ($headerItem);
    }

	/*
	**	Immediately redirects to the specified URL. A FalseError is triggered to ensure immediate exit.
	*/
    public static function redirect ($location)
    {
        Gateway::header('location: '.$location);
        throw new FalseError ();
    }

	/*
	**	Exits immediately.
	*/
    public static function exit ()
    {
        throw new FalseError ();
    }

	/*
	**	Flushes all output buffers and prepares for direct output.
	*/
    public static function flush ()
    {
		if (function_exists("apache_setenv"))
			apache_setenv("no-gzip", 1);

		ini_set("zlib.output_compression", "0");
		ini_set("output_buffering", "0");
		ini_set("implicit_flush", "1");

		for ($i = 0; $i < ob_get_level(); $i++)
			ob_end_flush();

		//set_time_limit(0);
		//ob_implicit_flush(1);
		flush();
    }
};
