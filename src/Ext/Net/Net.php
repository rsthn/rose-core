<?php

namespace Rose\Ext;

use Rose\Errors\Error;
use Rose\IO\Path;
use Rose\IO\Directory;
use Rose\IO\File;
use Rose\Expr;
use Rose\Arry;
use Rose\Map;
use Rose\Text;

/*
**	Small utility class to execute HTTP requests.
*/

class Http
{
    private static $curl_last_info = null;
    private static $curl_last_data = null;

    /**
     * 	Indicates if request data should be output to the log file.
     */
    public static $debug = false;

    /**
     * 	Indicates if SSL verification should be performed.
     */
    public static $verify_ssl = true;

    /**
     * 	HTTP request headers.
     */
    private static $headers = null;

    /**
     * 	HTTP response headers.
     */
    public static $responseHeaders = null;

    /**
     * 	Current request method for `fetch` function.
     */
    public static $method = 'GET';

    /**
    **	Initializes the Http class properties.
    */
    public static function init ()
    {
        self::clear();
    }

    /**
    **	Clears the Http class state.
    */
    public static function clear ()
    {
        self::$headers = new Map();
        self::$responseHeaders = new Map();

        self::header ('Accept: text/html,application/json,application/xhtml+xml,application/xml, */*');
    }

    /**
    **	Sets an HTTP header for subsequent requests.
    **
    **	@param $headerLine string
    */
    public static function header ($headerLine, $responseHeader=false)
    {
        $i = Text::indexOf($headerLine, ':');
        if ($i === false) return;

        $name = Text::trim(Text::substring($headerLine, 0, $i));
        if (!$name) return;

        $val = Text::trim(Text::substring($headerLine, $i+1));

        if ($responseHeader)
            self::$responseHeaders->set(Text::toLowerCase($name), $val);
        else
            self::$headers->set($name, $val ? Text::trim($name) . ': ' . $val : Text::trim($name) . ';' );
    }

    /*
    **	Returns the HTTP code of the last request.
    */
    public static function getCode ()
    {
        return self::$curl_last_info['http_code'];
    }

    /*
    **	Returns the content-type of the last request.
    */
    public static function getContentType ()
    {
        return self::$curl_last_info['content_type'];
    }

    /*
    **	Returns the data returned by the last request.
    */
    public static function getData ()
    {
        return self::$curl_last_data;
    }

    /**
    **	Sets the HTTP Authorization header.
    **
    **	@param $method string|bool Either `Bearer` or `Basic`, or a `false` value to remove the authorization header.
    **	@param $username string The username when using `Basic` method, or the access token when using `Bearer`.
    **	@param $password string The password when using `Basic` method, or null when using `Bearer`.
    */
    public static function auth ($method, $username=null, $password=null)
    {
        if (!$method)
        {
            self::$headers->remove('Authorization');
            return;
        }

        switch (Text::toLowerCase($method))
        {
            case 'basic':
                self::$headers->set('Authorization', 'Authorization: Basic ' . base64_encode($username . ':' . $password));
                break;
    
            case 'bearer':
                self::$headers->set('Authorization', 'Authorization: Bearer ' . $username);
                break;

            default:
                self::$headers->set('Authorization', 'Authorization: ' . $username);
                break;
        }

        return null;
    }

    /**
    **	Returns the result of executing a GET request.
    **
    **	@param $url string
    **	@param $fields Rose\Map
    **	@param $requestHeaders Rose\Map
    **	@param $method string
    */
    public static function get ($url, $fields=null, $requestHeaders=null, $method='GET')
    {
        $c = curl_init();

        $method = Text::toUpperCase($method);

        $headers = new Map();
        $headers->merge(self::$headers, true);
        if ($requestHeaders) $headers->merge($requestHeaders, true);

        if (Text::indexOf($url, '?') === false)
            $url .= '?';

        $list = new Arry();

        if ($fields)
        {
            if (\Rose\typeOf($fields) !== 'Rose\\Map')
                throw new Error('Parameter `fields` for Net::get should be Map.');

            $fields->forEach(function(&$value, $name) use(&$list)
            {
                if (\Rose\typeOf($value) !== 'primitive')
                    return;

                $list->push(urlencode($name) . '=' . urlencode($value ?? ''));
            });
        }

        $ch = Text::substring($url, -1);
        if ($ch != '?' && $ch != '&') $url .= '&';

        $url .= $list->join('&');

        $ch = Text::substring($url, -1);
        if ($ch == '?' || $ch == '&') $url = Text::substring($url, 0, -1);

        curl_setopt ($c, CURLOPT_URL, $url);
        curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($c, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt ($c, CURLOPT_HTTPHEADER, $headers->values()->__nativeArray);
        curl_setopt ($c, CURLINFO_HEADER_OUT, true);

        if (!self::$verify_ssl) {
            curl_setopt ($c, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt ($c, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $data = curl_exec($c);

        if (self::$debug)
        {
            \Rose\trace($method . ' ' . $url);
            \Rose\trace(Text::split("\n", curl_getinfo($c, CURLINFO_HEADER_OUT))->forEach(function(&$value) { $value = '> ' . Text::trim($value); })->removeAll("/^> $/")->slice(1)->join("\n"));
        }

        self::$curl_last_info = curl_getinfo($c);
        self::$curl_last_data = $data;

        curl_close($c);
        return $data;
    }

    /**
    **	Returns the result of executing a POST request. The data parameter can be a Map or a string.
    **
    **	-	When a map is specified the data will be posted as "application/x-www-form-urlencoded", however note that if the map
    **		contains objects of the form { name, path } or { name, path, data } the content type will be set to "multipart/form-data"
    **		and files will be uploaded.
    **
    **	- When data is a string, it will be posted as a data stream as either "application/json" or "text/xml".
    **
    **	@param $url string
    **	@param $data Rose\Map or string
    **	@param $requestHeaders Rose\Map
    **	@param $method string
    */
    public static function post ($url, $data, $requestHeaders=null, $method='POST')
    {
        $c = curl_init();
        $method = Text::toUpperCase($method);

        $headers = new Map();
        $headers->merge(self::$headers, true);
        if ($requestHeaders) $headers->merge($requestHeaders, true);

        $responseHeaders = new Map();

        $fields = new Map();
        $debugFields = new Map();
        $tempFiles = new Arry();
        $useFormData = false;

        if (\Rose\typeOf($data) == 'Rose\Map')
        {
            $data->forEach(function($value, $name) use(&$fields, &$tempFiles, &$useFormData, &$debugFields)
            {
                $value2 = $value;

                if (\Rose\typeOf($value) == 'Rose\Map')
                {
                    if ($value->has('path'))
                    {
                        $path = Path::resolve($value->get('path'));

                        if (!Path::exists($path))
                            throw new Error ('File for field \''.$name.'\' not found.');

                        if (!Path::isFile($path))
                            throw new Error ('Path specified for field \''.$name.'\' is not a file.');

                        if ($value->has('name'))
                            $value = curl_file_create ($path, '', $value->get('name'));
                        else
                            $value = curl_file_create ($path, '', Path::basename($path));

                        $useFormData = true;
                    }
                    else if ($value->has('name') && $value->has('data'))
                    {
                        $path = './tmp/uploads/'.Expr::call('utils:uuid', null).Path::extname($value->get('name'));
                        File::setContents($path, $value->get('data'));

                        $value = curl_file_create (Path::resolve($path), '', $value->get('name'));
                        $tempFiles->push($path);

                        $useFormData = true;
                    }
                    else
                        return;
                }

                if (self::$debug)
                    $debugFields->set($name, $value2);

                $fields->set($name, $value);
            });

            if (!$useFormData && (!$headers->has('content-type') || $headers->get('content-type') == 'content-type: application/x-www-form-urlencoded'))
            {
                $temp = $fields->map(function ($value, $name) {
                    return urlencode($name) . '=' . urlencode($value ?? '');
                });

                $temp = $temp->values()->join('&');

                if (Text::length($temp) > 2048)
                {
                    $headers->set('content-type', 'content-type: multipart/form-data');
                    $fields = $fields->__nativeArray;
                }
                else
                {
                    $headers->set('content-type', 'content-type: application/x-www-form-urlencoded');
                    $fields = $temp;
                }
            }
            else
            {
                $headers->set('content-type', 'content-type: multipart/form-data');
                $fields = $fields->__nativeArray;
            }
        }
        else
        {
            if (!$headers->has('content-type') && $data !== '')
                $headers->set('content-type', 'content-type: ' . ($data[0] == '<' ? 'text/xml' : ($data[0] == '[' || $data[0] == '{' ? 'application/json' : 'application/octet-stream')));

            $fields = $data;
        }

        curl_setopt ($c, CURLOPT_URL, $url);
        curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'HEAD')
            curl_setopt ($c, CURLOPT_NOBODY, true);

        if ($method == 'POST')
            curl_setopt ($c, CURLOPT_POST, true);
        else
            curl_setopt ($c, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt ($c, CURLOPT_HTTPHEADER, $headers->values()->__nativeArray);
        curl_setopt ($c, CURLINFO_HEADER_OUT, true);
        curl_setopt ($c, CURLOPT_POSTFIELDS, $fields ? $fields : '');

         curl_setopt($c, CURLOPT_HEADERFUNCTION, function($curl, $header) {
            self::header ($header, true);
              return Text::length($header);
        });

        if (!self::$verify_ssl) {
            curl_setopt ($c, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt ($c, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $data = curl_exec($c);

        if (self::$debug)
        {
            \Rose\trace($method . ' ' . $url);
            \Rose\trace(Text::split("\n", curl_getinfo($c, CURLINFO_HEADER_OUT))->forEach(function(&$value) { $value = '> ' . Text::trim($value); })->removeAll("/^> $/")->slice(1)->join("\n"));
            \Rose\trace("DATA\n" . (\Rose\isArray($fields) ? $debugFields : $fields));
            try { \Rose\trace("RESPONSE\n" . $data); } catch (\Throwable $e) { }
        }

        $tempFiles->forEach(function($path) { File::remove($path); });

        self::$curl_last_info = curl_getinfo($c);
        self::$curl_last_data = $data;

        curl_close($c);

        return $data;
    }

    /**
    **	Forwards the parameters to fetchGet or fetchPost (based on current method), parses the JSON result and returns a Map or Arry.
    */
    public static function fetch ($url, $fields)
    {
        $method = self::$method;
        self::$method = 'GET';

        return ($method == 'POST' || $method == 'PUT' || $method == 'DELETE' || $method == 'HEAD') ? self::fetchPost($url, $fields, $method) : self::fetchGet($url, $fields, $method);
    }

    /**
    **	Forwards the parameters to Http::get(), parses the JSON result and returns a Map or Arry.
    */
    public static function fetchGet ($url, $fields, $method='GET')
    {
        return Expr::call('json:parse', new Arry ([null, self::get($url, $fields, new Map([ 'Accept' => 'Accept: application/json' ]), $method)]));
    }

    /**
    **	Forwards the parameters to Http::post(), parses the JSON result and returns a Map or Arry.
    */
    public static function fetchPost ($url, $data, $method='POST')
    {
        return Expr::call('json:parse', new Arry ([null, self::post($url, $data, new Map([ 'Accept' => 'Accept: application/json' ]), $method)]));
    }

};

Http::init();

/* ****************** */
/* http:init */

Expr::register('http:clear', function ($args)
{
    Http::clear();
    return null;
});

/* ****************** */
/* http:get <url> [<fields>*] */

Expr::register('http:get', function ($args)
{
    $fields = new Map();

    for ($i = 2; $i < $args->length; $i++)
    {
        $data = $args->get($i);

        if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    return Http::get($args->get(1), $args->length == 2 ? '' : $fields, null);
});

/* ****************** */
/* http:head <url> [<fields>*] */

Expr::register('http:head', function ($args)
{
    $fields = new Map();

    for ($i = 2; $i < $args->length; $i++)
    {
        $data = $args->get($i);

        if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    return Http::post($args->get(1), $args->length == 2 ? '' : $fields, null, 'HEAD');
});

/* ****************** */
/* http:status <url> [<fields>*] */

Expr::register('http:status', function ($args)
{
    $fields = new Map();

    for ($i = 2; $i < $args->length; $i++)
    {
        $data = $args->get($i);

        if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    Http::post($args->get(1), $args->length == 2 ? '' : $fields, null, 'HEAD');
    return Http::getCode();
});

/* ****************** */
/* http:post <url> [<fields>*] */

Expr::register('http:post', function ($args)
{
    $fields = new Map();

    for ($i = 2; $i < $args->length; $i++)
    {
        $data = $args->get($i);

        if (\Rose\typeOf($data, true) == 'string')
        {
            $fields = $data;
            break;
        }

        if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    return Http::post($args->get(1), $args->length == 2 ? '' : $fields);
});

/* ****************** */
/* http:put <url> [<fields>*] */

Expr::register('http:put', function ($args)
{
    $fields = new Map();

    for ($i = 2; $i < $args->length; $i++)
    {
        $data = $args->get($i);

        if (\Rose\typeOf($data, true) == 'string')
        {
            $fields = $data;
            break;
        }

        if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    return Http::post($args->get(1), $args->length == 2 ? '' : $fields, null, 'PUT');
});


/* ****************** */
/* http:delete <url> [<fields>*] */

Expr::register('http:delete', function ($args)
{
    $fields = new Map();

    for ($i = 2; $i < $args->length; $i++)
    {
        $data = $args->get($i);

        if (\Rose\typeOf($data, true) == 'string')
        {
            $fields = $data;
            break;
        }

        if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    return Http::post($args->get(1), $args->length == 2 ? '' : $fields, null, 'DELETE');
});


/* ****************** */
/* http:fetch [<method>] <url> [<fields>] */

Expr::register('http:fetch', function ($args)
{
    $fields = new Map();

    $j = 1;

    switch (Text::toUpperCase($args->get($j)))
    {
        case 'GET': case 'PUT': case 'POST': case 'DELETE': case 'HEAD':
            Http::$method = Text::toUpperCase($args->get($j++));
            break;
    }

    for ($i = $j+1; $i < $args->length; $i++)
    {
        $data = $args->get($i);

        if (\Rose\typeOf($data, true) == 'string')
        {
            $fields = $data;
            break;
        }

        if (!$data || \Rose\typeOf($data) != 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    return Http::fetch($args->get($j), $fields);
});

/* ****************** */
/* http:header [header-line] */

Expr::register('http:header', function ($args)
{
    Http::header($args->get(1));
    return null;
});

/* ****************** */
/* http:headers [array|map] */

Expr::register('http:headers', function ($args)
{
    if ($args->length == 1)
        return Http::$responseHeaders;

    if (\Rose\typeOf($args->get(1)) === 'Rose\Map') {
        $args->get(1)->forEach(function ($value, $key) {
            Http::header($key.':'.$value);
        });
    }
    else {
        $args->get(1)->forEach(function ($value) {
            Http::header($value);
        });
    }

    return null;
});

/* ****************** */
/* http:method method */

Expr::register('http:method', function ($args)
{
    Http::$method = Text::toUpperCase($args->get(1));
    return null;
});


/* ****************** */
/* http:debug value */

Expr::register('http:debug', function ($args)
{
    Http::$debug = \Rose\bool($args->get(1));
    return null;
});

/* ****************** */
/* http:verify value */

Expr::register('http:verify', function ($args)
{
    Http::$verify_ssl = \Rose\bool($args->get(1));
    return null;
});


/* ****************** */
/* http:auth basic <username> <password> */
/* http:auth basic <username> */
/* http:auth bearer <token> */
/* http:auth <token> */
/* http:auth false */

Expr::register('http:auth', function ($args)
{
    if ($args->length == 2)
    {
        if (!$args->get(1) || $args->get(1) === 'false')
        {
            Http::auth (false);
            return null;
        }

        Http::auth ('value', $args->get(1));
        return null;
    }

    Http::auth ($args->get(1), $args->get(2), $args->{3});
    return null;
});

/* ****************** */
Expr::register('http:code', function ($args)
{
    if ($args->length == 2)
    {
        http_response_code(~~$args->get(1));
        return null;
    }

    return Http::getCode();
});

Expr::register('http:content-type', function ($args)
{
    return Http::getContentType();
});

Expr::register('http:data', function ($args)
{
    return Http::getData();
});
