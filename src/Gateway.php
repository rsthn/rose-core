<?php

namespace Rose;

use Rose\Errors\FalseError;
use Rose\Errors\Error;

use Rose\Configuration;
use Rose\Strings;
use Rose\Session;
use Rose\Map;
use Rose\Text;
use Rose\IO\Path;
use Rose\Regex;
use Rose\Arry;
use Rose\Extensions;
use Rose\Expr;

// @title Gateway
// @desc Provides an interface between clients and the system. No client can have access to the system without passing first through the Gateway.

class Gateway
{
    /**
     * Primary and only instance of this class.
     */
    private static $instance = null;

    /**
     * List of registered services, each time the "srv" request parameter is detected, its value will be checked in
     * this map, if an object is found, it's main() method will be invoked.
     */
    private $registeredServices;

/**
 * Map with the request parameters from both GET and POST methods.
 * @code (`gateway.request`)
 * @example
 * (gateway.request)
 * ; {"name": "John"}
 */
public $request;

/**
 * Map with the server parameters sent via CGI.
 * @code (`gateway.server`)
 * @example
 * (gateway.server)
 * ; {"SERVER_NAME": "localhost"}
 */
public $server;

/**
 * Map with the HTTP headers sent via CGI.
 * @code (`gateway.headers`)
 * @example
 * (gateway.headers)
 * ; {"HOST": "localhost", "X_KEY": "12345"}
 */
public $headers;

    /**
     * Relative path (if any) obtained from the PHP_SELF server parameter.
     */
    public $relativePath;

/**
 * Map with the cookies sent by the client.
 * @code (`gateway.cookies`)
 * @example
 * (gateway.cookies)
 * ; {"session": "123"}
 */
public $cookies;

    /**
     * Current globally set content type for the response.
     */
    public static $contentType = null;

    /**
     * Indicates if the content has been flushed.
     */
    public static $contentFlushed = false;

/**
 * Full URL address to the entry point of the active service. Never ends with slash.
 * @code (`gateway.ep`)
 * @example
 * (gateway.ep)
 * ; "http://localhost"
 */
public $ep;

/**
 * Server name obtained from the CGI fields or from the `server_name` field in the `Gateway` configuration section.
 * @code (`gateway.serverName`)
 * @example
 * (gateway.serverName)
 * ; "localhost"
 */
public $serverName;

/**
 * HTTP method used to access the gateway, will always be in uppercase.
 * @code (`gateway.method`)
 * @example
 * (gateway.method)
 * ; "GET"
 */
public $method;

/**
 * Remote address (and port) of the client.
 * @code (`gateway.remoteAddress`)
 * @code (`gateway.remotePort`)
 * @example
 * (gateway.remoteAddress)
 * ; "127.0.0.1"
 *
 * (gateway.remotePort)
 * ; 12873
 */
public $remoteAddress;
public $remotePort;

/**
 * Relative URL root where the index file is found. Usually it is "/".
 * @code (`gateway.root`)
 * @example
 * (gateway.root)
 * ; "/"
 */
public $root;

/**
 * Local file system root where the index file is found.
 * @code (`gateway.fsroot`)
 * @example
 * (gateway.fsroot)
 * ; "/var/www/html"
 */
public $fsroot;

/**
 * Indicates if we're on a secure context (HTTPS).
 * @code (`gateway.secure`)
 * @example
 * (gateway.secure)
 * ; true
 */
public $secure;

/**
 * Object contaning information about the request body received.
 * @code (`gateway.input`)
 * @example
 * (gateway.input)
 * ; {"contentType": "application/json", "size": 16, "path": "/tmp/1f29g87h12"}
 */
public $input;

/**
 * Contains a parsed object if the content-type is `application/json`. For other content types, it will be `null` and the actual data can
 * be read from the file specified in the `path` field of the `input` object.
 * @code (`gateway.body`)
 * @example
 * (gateway.body)
 * ; {"name": "John"}
 */
public $body;

    /**
     * Indicates if the Gateway is in CLI mode, when so, certain functions will not be used (i.e. header).
     */
    public static $cli = false;

    /**
     * Last value passed to the `header` function. Available only in CLI mode.
     */
    public static $header;

    /**
     * Returns the instance of this class.
     */
    public static function getInstance ()
    {
        if (Gateway::$instance == null)
            Gateway::$instance = new Gateway();

        return Gateway::$instance;
    }

    /**
     * Constructs the Gateway object, this is a private constructor as this class can have only one instance.
     */
    private function __construct()
    {
        foreach ($_FILES as &$file) {
            $file['path'] = $file['tmp_name'];
        }

        $this->request = new Map (array_merge ($_REQUEST, $_FILES));
        $this->server = new Map ($_SERVER);
        $this->cookies = new Map ($_COOKIE);

        $this->headers = $headers = new Map();
        $this->server->forEach(function ($item, $key) use (&$headers) {
            if (Text::startsWith($key, 'HTTP_'))
                $headers->set(Text::substring($key, 5), $item);
        });

        $this->registeredServices = new Map();
        $this->input = null;
        $this->body = null;

        $contentType = Text::toLowerCase(Text::trim(Text::split(";", $this->server->CONTENT_TYPE)->get(0)));
        if ($contentType) {
            $this->input = new Map([ 
                'contentType' => $contentType,
                'size' => intval($this->server->CONTENT_LENGTH),
                'path' => 'php://input'
            ]);
        }

        if ($this->input !== null)
        {
            // RCO-004
            if ($this->input->contentType === 'application/json') {
                $value = file_get_contents($this->input->path);
                $this->body = !$value ? new Map() : ($value[0] == '[' ? Arry::fromNativeArray(json_decode($value, true)) : ($value[0] == '{' ? Map::fromNativeArray(json_decode($value, true)) : json_decode($value, true)));
            }
            else if ($this->input->contentType === 'application/x-www-form-urlencoded') {
                $this->input = null;
            }
            else if ($this->input->contentType === 'multipart/form-data') {
                $this->input = null;
            }
        }

        if ($this->input === null)
            $this->input = new Map([ 'contentType' => null ]);
    }

    /**
     * Executed by the framework initializer to startup the gateway.
     */
    public function init ($cli, $fsroot)
    {
        self::$cli = $cli;

        // Set entry point URL and root.
        $this->root = Text::substring($this->server->SCRIPT_NAME, 0, -9);

        $this->secure = Text::toUpperCase($this->server->HTTPS) == 'ON';
        if (!$this->secure && Text::toUpperCase($this->server->HTTP_X_FORWARDED_PROTO) == 'HTTPS') {
            $this->server->SERVER_PORT = 443;
            $this->secure = true;
        }

        while (Text::endsWith($this->root, '/'))
            $this->root = Text::substring($this->root, 0, -1);

        $name = Regex::_getString('|.*/(.+)\.php$|', $this->server->SCRIPT_NAME, 1);

        /* ** */
        $n = Text::length(Regex::_getString ('/^(.+)'.$name.'\.php/', $this->server->SCRIPT_NAME, 1));

        $tmp = Text::substring($this->server->REQUEST_URI, $n);
        if (Text::startsWith($tmp, $name.'.php')) $tmp = Text::substring($tmp, Text::length($name)+4);
        if (Text::startsWIth($tmp, '/')) $tmp = Text::substring($tmp, 1);

        $this->relativePath = Regex::_getString ('/^[-\/_A-Za-z0-9.]+/', $tmp);
        if ($this->relativePath) $this->relativePath = '/'.$this->relativePath;

        /* ** */
        $this->method = Text::toUpperCase($this->server->REQUEST_METHOD);
        $this->remoteAddress = $this->server->REMOTE_ADDR;
        $this->remotePort = $this->server->REMOTE_PORT;

        $this->serverName = Configuration::getInstance()?->Gateway?->server_name ? Configuration::getInstance()->Gateway->server_name : $this->server->SERVER_NAME;
        $this->ep = ($this->secure ? 'https://' : 'http://')
                    .$this->serverName
                    .(
                        ($this->secure ? $this->server->SERVER_PORT != '443' : $this->server->SERVER_PORT != '80')
                        ? ':'.$this->server->SERVER_PORT
                        : ''
                    )
                    .$this->root;

        if (Configuration::getInstance()?->Gateway?->allow_origin && $this->server->has('HTTP_ORIGIN'))
        {
            self::header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
            self::header('Access-Control-Allow-Methods: HEAD, POST, GET, PUT, DELETE, PATCH, OPTIONS');
            self::header('Access-Control-Allow-Credentials: true');

            if (Configuration::getInstance()->Gateway->allow_origin == '*')
                self::header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
            else
                self::header('Access-Control-Allow-Origin: '.Configuration::getInstance()->Gateway->allow_origin);
        }

        $this->fsroot = Path::append($fsroot);
        self::header('X-Powered-By: ' . 'rsthn/rose-core ' . Main::version());

        // Initialize system classes.
        Session::init();
        Strings::init();
        Locale::init();
        Extensions::init();
    }

    /**
     * Executed by the framework initializer when a client has sent a request to the system.
     */
    public function main ()
    {
        // If service parameter is set in Gateway configuration, load it as 'srv' to force activation of service.
        if (Configuration::getInstance()?->Gateway?->service != null)
            $this->request->srv = Configuration::getInstance()->Gateway->service;

        // Detect is a service is being requested.
        if ($this->request->srv != null) {
            $this->request->srv = Regex::_extract('/[A-Za-z0-9_-]+/', $this->request->srv);
            if ($this->registeredServices->has($this->request->srv))
                $this->registeredServices->get($this->request->srv)->main();
            else
                throw new Error ("Service `" . $this->request->srv . "` is not registered.");
        }
    }

    /**
     * Registers a service.
     */
    public static function registerService ($serviceCode, $handlerObject) {
        Gateway::getInstance()->registeredServices->set ($serviceCode, $handlerObject);
    }

    /**
     * Returns the service handler object given a service code.
     */
    public static function getService ($serviceCode) {
        if (Gateway::getInstance()->registeredServices->has($serviceCode))
            return Gateway::getInstance()->registeredServices->get($serviceCode);
        return null;
    }

    /**
     * Invalidates the gateway instance.
     */
    public static function close () {
        Session::close();
        Gateway::$instance = null;
    }

    /**
     * Adds a header to the HTTP response.
     */
    public static function header ($value)
    {
        if (Text::toUpperCase(Text::substring($value, 0, 13)) === 'CONTENT-TYPE:')
            Gateway::$contentType = $value;

        if (self::$cli) {
            self::$header = $value;
            return;
        }

        \header ($value);
    }

    /**
     * Immediately redirects to the specified URL. A FalseError is triggered to ensure immediate exit.
     */
    public static function redirect ($location) {
        self::header('location: '.$location);
        throw new FalseError();
    }

    /**
     * Exits immediately.
     */
    public static function exit () {
        throw new FalseError();
    }

    /**
     * Flushes all output buffers and prepares for immediate mode (unbuffered output).
     */
    public static function flush ()
    {
        if (Gateway::$contentFlushed)
            return true;

        Gateway::$contentFlushed = true;
        Gateway::header("Cache-Control: no-store");
        Gateway::header("X-Accel-Buffering: no");

        if (function_exists("apache_setenv"))
            apache_setenv("no-gzip", 1);

        \Rose\silent_ini_set("zlib.output_compression", "0");
        \Rose\silent_ini_set("output_buffering", "0");
        \Rose\silent_ini_set("implicit_flush", "1");

        for ($i = 0; $i < ob_get_level(); $i++)
            ob_end_flush();

        //set_time_limit(0);
        ob_implicit_flush(1);
        flush();

        return true;
    }

    /**
     * Configures the system to use persistent execution mode in which the script will continue to run indefinitely for as 
     * long as the server allows, even if the client connection is lost.
     */
    public static function persistent() {
        ignore_user_abort(true);
        set_time_limit(0);
        return true;
    }

    /**
     * Sets the maximum execution time of the current operation.
     */
    public static function setTimeout ($seconds) {
        set_time_limit($seconds === 'NEVER' ? 0 : $seconds);
        return true;
    }

    /**
     * Returns boolean indicating if the client connection was been disconnected.
     */
    public static function connected() {
        return !connection_aborted();
    }

    /**
     * Sets the HTTP status code to be sent to the client.
     */
    public static function status ($code) {
        http_response_code(~~$code);
    }

    /**
     * Returns the string representation of this object.
     */
    public function __toString() {
        return '[Rose\Gateway]';
    }
};


/**
 * Provides access to the instance properties of the Gateway class.
 */
Expr::register('gateway', function ($args) {
    return Gateway::getInstance();
});


/**
 * Sets the HTTP status code to be sent to the client.
 * @code (`gateway:status` <code>)
 * @example
 * (gateway:status 404)
 * ; true
 */
Expr::register('gateway:status', function($args) {
    Gateway::status($args->get(1));
    return true;
});


/**
 * Sets a header in the current HTTP response.
 * @code (`gateway:header` <header-line...>)
 * @example
 * (gateway:header "Content-Type: application/json")
 * ; true
 */
Expr::register('gateway:header', function($args) {
    for ($i = 1; $i < $args->length; $i++)
        Gateway::header($args->get($i));
    return true;
});


/**
 * Redirects the client to the specified URL by setting the `Location` header and exiting immediately.
 * @code (`gateway:redirect` <url>)
 */
Expr::register('gateway:redirect', function ($args) {
    return Gateway::redirect($args->get(1));
});


/**
 * Flushes all output buffers and prepares for immediate mode (unbuffered output).
 * @code (`gateway:flush`)
 * @example
 * (gateway:flush)
 * ; true
 */
Expr::register('gateway:flush', function ($args) {
    return Gateway::flush();
});


/**
 * Configures the system to use persistent execution mode in which the script will continue to run indefinitely for as 
 * long as the server allows, even if the client connection is lost.
 * @code (`gateway:persistent`)
 * @example
 * (gateway:persistent)
 * ; true
 */
Expr::register('gateway:persistent', function ($args) {
    return Gateway::persistent();
});


/**
 * Sets the maximum execution time of the current operation. Use `NEVER` to disable the timeout.
 * @code (`gateway:timeout` <seconds>)
 * @example
 * (gateway:timeout 30)
 * ; true
 */
Expr::register('gateway:timeout', function ($args) {
    return Gateway::setTimeout($args->get(1));
});


/**
 * Sends a response to the client and exits immediately.
 * @code (`gateway:return` [<status>] [<response>])
 * @example
 * (gateway:return 200 "Hello, World!")
 * ; Client will receive:
 * ; Hello, World!
 */
Expr::register('gateway:return', function ($args)
{
    $status = $args->{1};
    $response = $args->{2};

    if ($status) {
        if (\Rose\isInteger($status))
            http_response_code($status);
        else
            $response = $status;
    }

    if (Gateway::$contentFlushed || !$response)
        Gateway::exit();

    if (Gateway::$contentType === null) {
        $type = \Rose\typeOf($response);
        if ($type === 'Rose\Map' || $type === 'Rose\Arry') {
            Gateway::$contentType = 'Content-Type: application/json; charset=utf-8';
        }
        else if (\Rose\isString($response) && Text::length($response) != 0) {
            Gateway::$contentType = 'Content-Type: text/plain; charset=utf-8';
        }
    }

    Gateway::header(Gateway::$contentType);
    echo (string)$response;
    Gateway::exit();
});

/**
 * Sends a response to the client, closes the connection and continues execution. Further output
 * for the client will be ignored.
 * @code (`gateway:continue` [<status>] [<response>])
 * @example
 * (gateway:continue 200 "Hello, World!")
 * ; Client will receive:
 * ; Hello, World!
 */
Expr::register('gateway:continue', function ($args)
{
    $status = $args->{1};
    $response = $args->{2};

    if ($status) {
        if (\Rose\isInteger($status))
            http_response_code($status);
        else
            $response = $status;
    }

    if (Gateway::$contentFlushed)
        return false;

    if (Gateway::$contentType === null) {
        $type = \Rose\typeOf($response);
        if ($type === 'Rose\Map' || $type === 'Rose\Arry') {
            Gateway::$contentType = 'Content-Type: application/json; charset=utf-8';
        }
        else if (\Rose\isString($response)) {
            Gateway::$contentType = 'Content-Type: text/plain; charset=utf-8';
        }
    }

    $response = (string)$response;
    Gateway::header("Connection: close");
    Gateway::header("Content-Length: " . Text::length($response));
    Gateway::header("Cache-Control: no-store");
    Gateway::header("X-Accel-Buffering: no");

    if (Gateway::$contentType)
        Gateway::header(Gateway::$contentType);

    Gateway::persistent();
    Gateway::flush();

    echo Text::rpad($response, 8192);
    return true;
});
