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
	public $request;

	/*
	**	Server parameters (basically the $_SERVER array).
	*/
	public $serverParams;
	public $server;

	/*
	**	Relative path (if any) obtained from the PHP_SELF server parameter.
	*/
	public $relativePath;

	/*
	**	Available cookies (basically the $_COOKIES array).
	*/
    public $cookies;

	/*
	**	Full URL address to the Gateway's entry point.
	*/
	public $ep;

	/*
	**	HTTP Method used to access this entry point.
	*/
	public $method;

	/*
	**	Remote client address and port.
	*/
	public $remoteAddress, $remotePort;

	/*
	**	Relative URL root where the index is found. Usually it is "/".
	*/
	public $root;
	
	/*
	**	Local file system root where the index is found.
	*/
    public $localroot;

	/*
	**	Indicates if we're on a secure context (HTTPS).
	*/
	public $secure;

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

		$this->request = $this->requestParams = new Map (array_merge ($_REQUEST, $_FILES));
		$this->server = $this->serverParams = new Map ($_SERVER);
		$this->cookies = new Map ($_COOKIE);

		$this->registeredServices = new Map();
	}

	/*
	**	Executed by the framework initializer to initialize the gateway.
	*/
	public function init ()
	{
		// Set entry point URL and root.
		$this->root = Text::substring($this->serverParams->SCRIPT_NAME, 0, -9);
		$this->secure = Text::toUpperCase($this->serverParams->HTTPS) == 'ON';

		$n = Text::length(Regex::_getString ('/(.+)index\.php/', $this->serverParams->SCRIPT_NAME, 1));
		$this->relativePath = Regex::_getString ('/^.{'.$n.'}(index\.php)?\/?([-\/_A-Za-z0-9]+)/', $this->serverParams->REQUEST_URI, 2);
		if ($this->relativePath) $this->relativePath = '/'.$this->relativePath;

		$this->method = $this->serverParams->REQUEST_METHOD;
		$this->remoteAddress = $this->serverParams->REMOTE_ADDR;
		$this->remotePort = $this->serverParams->REMOTE_PORT;

		$this->ep = ($this->secure ? 'https://' : 'http://')
					.(Configuration::getInstance()->Gateway->server_name ? Configuration::getInstance()->Gateway->server_name : $this->serverParams->SERVER_NAME)
					.(
						($this->secure ? $this->serverParams->SERVER_PORT != '443' : $this->serverParams->SERVER_PORT != '80')
						? ':'.$this->serverParams->SERVER_PORT
						: ''
					)
					.$this->root;

		if (Configuration::getInstance()->Gateway->allow_origin && $this->serverParams->has('HTTP_ORIGIN'))
		{
			if (Configuration::getInstance()->Gateway->allow_origin == '*')
				header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
			else
				header('Access-Control-Allow-Origin: '.Configuration::getInstance()->Gateway->allow_origin);

			header('Access-Control-Allow-Credentials: true');
		}

		$this->localroot = getcwd();

        if (Text::substring($this->localroot, -1) != '/')
            $this->localroot .= '/';

		// Initialize system classes.
		Session::init();
		Strings::init();
		Locale::init();
		Extensions::init();
	}

	/*
	**	Executed by the framework initializer when a client has sent a request to the system.
	*/
    public function main ()
    {
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
        if (Gateway::getInstance()->registeredServices->has($serviceCode))
            return Gateway::getInstance()->registeredServices->get($serviceCode);
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
