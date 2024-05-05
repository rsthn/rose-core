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
use Rose\JSON;
use Rose\Math;

// @title HTTP Requests
// @short Request

class Http
{
    private static $curl_last_error = null;
    private static $curl_last_info = null;
    private static $curl_last_data = null;

    public static $output_handler = null;
    public static $input_handler = null;
    public static $progress_handler = null;

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
    public static $headers = null;

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

    /**
     * Returns the HTTP code of the last request.
     */
    public static function getCode() {
        return !self::$curl_last_info ? 0 : self::$curl_last_info['http_code'];
    }

    /**
     * Returns the content-type of the last request.
     */
    public static function getContentType() {
        return !self::$curl_last_info ? null : self::$curl_last_info['content_type'];
    }

    /**
     * Returns the last error message.
     */
    public static function getError() {
        return self::$curl_last_error;
    }

    /**
     * Returns the data returned by the last request.
     */
    public static function getData() {
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
                throw new Error('GET operation requires a map, not ' . \Rose\typeOf($fields, true));

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
        if ($ch === '?' || $ch === '&') $url = Text::substring($url, 0, -1);

        curl_setopt ($c, CURLOPT_URL, $url);
        curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($c, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt ($c, CURLOPT_HTTPHEADER, $headers->values()->__nativeArray);

        curl_setopt ($c, CURLINFO_HEADER_OUT, true);
        curl_setopt ($c, CURLOPT_HEADERFUNCTION, function($curl, $header) {
            self::header ($header, true);
            return Text::length($header);
        });

        if (!self::$verify_ssl) {
            curl_setopt ($c, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt ($c, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $output_handler = self::$output_handler;
        $output_handler_params = null;
        $output_ready = null;
        if ($output_handler)
        {
            self::$output_handler = null;
            $output_handler_params = new Arry([null, null]);
            curl_setopt($c, CURLOPT_WRITEFUNCTION, function($curl, $data) use(&$output_handler, &$output_handler_params, &$output_ready)
            {
                if ($output_ready === null)
                    $output_ready = curl_getinfo($curl)['http_code'] == 200;

                if (!$output_ready)
                    return strlen($data);

                $output_handler_params->set(1, $data);
                $output_handler($output_handler_params, null, null);
                return strlen($data);
            });
        }

        if (self::$progress_handler)
        {
            $progress_handler = self::$progress_handler;
            $progress_handler_params = new Arry([null, null, null]);
            self::$progress_handler = null;
            curl_setopt($c, CURLOPT_NOPROGRESS, false);
            curl_setopt($c, CURLOPT_PROGRESSFUNCTION, function($curl, $down_total, $down_curr) use (&$progress_handler, &$progress_handler_params) {
                if (!$down_total) return;
                $progress_handler_params->set(1, Math::fixed(100.0 * $down_curr / $down_total, 2));
                $progress_handler_params->set(2, $down_total);
                $progress_handler($progress_handler_params, null, null);
            });
        }

        $data = curl_exec($c);

        if ($output_handler) {
            $data = $output_ready;
            if ($data) {
                $output_handler_params->set(1, '');
                $output_handler($output_handler_params, null, null);
            }
        }

        self::$curl_last_error = curl_error($c);
        self::$curl_last_info = curl_getinfo($c);
        self::$curl_last_data = $data;

        if (self::$debug)
        {
            \Rose\trace($method . ' ' . $url);
            \Rose\trace('HTTP ' . json_encode(self::$curl_last_info['http_code']));

            \Rose\trace(Text::split("\n", curl_getinfo($c, CURLINFO_HEADER_OUT))
                ->forEach(function(&$value) { $value = '> ' . Text::trim($value); })
                ->removeAll("/^> $/")->slice(1)->join("\n"))
                ;
            \Rose\trace('');

            \Rose\trace(self::$responseHeaders->values()
                ->forEach(function(&$value) { $value = '< ' . Text::trim($value); })
                ->removeAll("/^< $/")->slice(1)->join("\n"))
                ;
            \Rose\trace('');

            try { \Rose\trace("RESPONSE-DATA\n" . $data); } catch (\Throwable $e) { }
        }

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

        if (\Rose\typeOf($data) === 'Rose\Map')
        {
            $data->forEach(function($value, $name) use(&$fields, &$tempFiles, &$useFormData, &$debugFields)
            {
                $value2 = $value;

                if (\Rose\typeOf($value) === 'Rose\Map')
                {
                    if ($value->has('path'))
                    {
                        $path = Path::resolve($value->get('path'));

                        if (!Path::exists($path))
                            throw new Error ('File for field `'.$name.'` not found');

                        if (!Path::isFile($path))
                            throw new Error ('Path specified for field `'.$name.'` is not a file');

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

            if (!$useFormData && (!$headers->has('content-type') || $headers->get('content-type') === 'content-type: application/x-www-form-urlencoded'))
            {
                $temp = $fields->map(function ($value, $name) {
                    return urlencode($name) . '=' . urlencode($value ?? '');
                });

                $temp = $temp->values()->join('&');

                if (Text::length($temp) > 2048) {
                    $headers->set('content-type', 'content-type: multipart/form-data');
                    $fields = $fields->__nativeArray;
                }
                else {
                    $headers->set('content-type', 'content-type: application/x-www-form-urlencoded');
                    $fields = $temp;
                }
            }
            else {
                $headers->set('content-type', 'content-type: multipart/form-data');
                $fields = $fields->__nativeArray;
            }
        }
        else
        {
            if (!$headers->has('content-type') && $data !== '')
                $headers->set('content-type', 'content-type: ' . ($data[0] === '<' ? 'text/xml' : ($data[0] === '[' || $data[0] === '{' ? 'application/json' : 'application/octet-stream')));
            $fields = $data;
        }

        curl_setopt ($c, CURLOPT_URL, $url);
        curl_setopt ($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'HEAD')
            curl_setopt ($c, CURLOPT_NOBODY, true);

        if ($method === 'POST')
            curl_setopt ($c, CURLOPT_POST, true);
        else
            curl_setopt ($c, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt ($c, CURLOPT_HTTPHEADER, $headers->values()->__nativeArray);
        curl_setopt ($c, CURLOPT_POSTFIELDS, $fields ? $fields : '');

        curl_setopt ($c, CURLINFO_HEADER_OUT, true);
        curl_setopt ($c, CURLOPT_HEADERFUNCTION, function($curl, $header) {
            self::header ($header, true);
            return Text::length($header);
        });

        if (!self::$verify_ssl) {
            curl_setopt ($c, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt ($c, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $output_handler = self::$output_handler;
        $output_handler_params = null;
        $output_ready = null;
        if ($output_handler)
        {
            self::$output_handler = null;
            $output_handler_params = new Arry([null, null]);

            curl_setopt($c, CURLOPT_WRITEFUNCTION, function($curl, $data) use(&$output_handler, &$output_handler_params, &$output_ready)
            {
                if ($output_ready === null)
                    $output_ready = curl_getinfo($curl)['http_code'] == 200;

                if (!$output_ready)
                    return strlen($data);

                $output_handler_params->set(1, $data);
                $output_handler($output_handler_params, null, null);
                return strlen($data);
            });
        }

        $input_handler = self::$input_handler;
        $input_handler_params = null;
        if ($input_handler)
        {
            self::$input_handler = null;
            $input_handler_params = new Arry([null, null]);

            curl_setopt($c, CURLOPT_UPLOAD, true);
            curl_setopt($c, CURLOPT_READFUNCTION, function($curl, $_, $max_bytes) use(&$input_handler, &$input_handler_params) {
                $input_handler_params->set(1, $max_bytes);
                return $input_handler($input_handler_params, null, null);
            });
        }

        $data = curl_exec($c);

        if ($output_handler) {
            $data = $output_ready;
            if ($data) {
                $output_handler_params->set(1, '');
                $output_handler($output_handler_params, null, null);
            }
        }

        if ($input_handler) {
            $input_handler_params->set(1, 0);
            $input_handler($input_handler_params, null, null);
        }

        self::$curl_last_error = curl_error($c);
        self::$curl_last_info = curl_getinfo($c);
        self::$curl_last_data = $data;

        if (self::$debug)
        {
            \Rose\trace($method . ' ' . $url);
            \Rose\trace('HTTP ' . json_encode(self::$curl_last_info['http_code']));

            \Rose\trace(Text::split("\n", curl_getinfo($c, CURLINFO_HEADER_OUT))
                ->forEach(function(&$value) { $value = '> ' . Text::trim($value); })
                ->removeAll("/^> $/")->slice(1)->join("\n"))
                ;
            \Rose\trace('');

            \Rose\trace(self::$responseHeaders->values()
                ->forEach(function(&$value) { $value = '< ' . Text::trim($value); })
                ->removeAll("/^< $/")->slice(1)->join("\n"))
                ;
            \Rose\trace('');

            \Rose\trace("REQUEST-DATA\n" . (\Rose\isArray($fields) ? $debugFields : $fields));
            try { \Rose\trace("RESPONSE-DATA\n" . $data); } catch (\Throwable $e) { }
        }

        $tempFiles->forEach(function($path) { File::remove($path); });

        curl_close($c);
        return $data;
    }

    /**
     * Forwards the parameters to fetchGet or fetchPost (based on current method), parses the JSON result and returns a Map or Arry.
     */
    public static function fetch ($url, $fields)
    {
        $method = self::$method;
        self::$method = 'GET';

        return ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')
            ? self::fetchPost($url, $fields, $method) 
            : self::fetchGet($url, $fields, $method)
            ;
    }

    /**
     * Forwards the parameters to Http::get(), parses the JSON result and returns a Map or Arry.
     */
    public static function fetchGet ($url, $fields, $method='GET') {
        return JSON::parse(self::get($url, $fields, new Map([ 'Accept' => 'Accept: application/json' ]), $method));
    }

    /**
     * Forwards the parameters to Http::post(), parses the JSON result and returns a Map or Arry.
     */
    public static function fetchPost ($url, $data, $method='POST') {
        return JSON::parse(self::post($url, $data, new Map([ 'Accept' => 'Accept: application/json' ]), $method));
    }

};

Http::init();

/**
 * Returns a map or a string with the fields to be sent in the request.
 */
function getFields ($args, $i=2)
{
    $fields = new Map();

    for (; $i < $args->length; $i++)
    {
        $data = $args->get($i);
        $type = \Rose\typeOf($data, true);

        if ($type === 'string') {
            $fields = $data;
            break;
        }

        if (!$data || $type !== 'Rose\\Map')
            continue;

        $fields->merge($data, true);
    }

    return $fields;
}

/**
 * Executes a GET request and returns the response data.
 * @code (`request:get` <url> [fields...])
 * @example
 * (request:get "http://example.com/api/currentTime")
 * ; 2024-12-31T23:59:59
 */
Expr::register('request:get', function ($args) {
    return Http::get($args->get(1), $args->length == 2 ? '' : getFields($args), null);
});

/**
 * Executes a HEAD request and returns the HTTP status code. Response headers will be available using `request:response-headers`.
 * @code (`request:head` <url> [fields...])
 * @example
 * (request:head "http://example.com/api/currentTime")
 * ; 200
 */
Expr::register('request:head', function ($args) {
    Http::post($args->get(1), $args->length == 2 ? '' : getFields($args), null, 'HEAD');
    return Http::getCode();
});

/**
 * Executes a POST request and returns the response data.
 * @code (`request:post` <url> [fields...])
 * @example
 * (request:post "http://example.com/api/login" (& "username" "admin" "password" "admin"))
 * ; { "token": "eyJhbGciOiJIUzI" }
 */
Expr::register('request:post', function ($args) {
    return Http::post($args->get(1), $args->length == 2 ? '' : getFields($args));
});

/**
 * Executes a PUT request and returns the response data.
 * @code (`request:put` <url> [fields...])
 * @example
 * (request:put "http://example.com/api/user/1" (& "name" "John Doe"))
 * ; { "id": 1, "name": "John Doe" }
 */
Expr::register('request:put', function ($args) {
    return Http::post($args->get(1), $args->length == 2 ? '' : getFields($args), null, 'PUT');
});

/**
 * Executes a DELETE request and returns the response data.
 * @code (`request:delete` <url> [fields...])
 * @example
 * (request:delete "http://example.com/api/user/1")
 * ; { "id": 1, "name": "John Doe" }
 */
Expr::register('request:delete', function ($args) {
    return Http::post($args->get(1), $args->length == 2 ? '' : getFields($args), null, 'DELETE');
});

/**
 * Executes a PATCH request and returns the response data.
 * @code (`request:patch` <url> [fields...])
 * @example
 * (request:patch "http://example.com/api/user/1")
 * ; { "id": 1, "name": "John Doe" }
 */
Expr::register('request:patch', function ($args) {
    return Http::post($args->get(1), $args->length == 2 ? '' : getFields($args), null, 'PATCH');
});

/**
 * Executes a fetch request using the specified method and returns a parsed JSON response. Default method is `GET`.
 * @code (`request:fetch` [method] <url> [fields...])
 * @example
 * (request:fetch "http://example.com/api/currentTime")
 * ; { "currentTime": "2024-12-31T23:59:59" }
 */
Expr::register('request:fetch', function ($args)
{
    $fields = new Map();
    $j = 1;

    switch (Text::toUpperCase($args->get($j))) {
        case 'GET': case 'PUT': case 'POST': case 'DELETE': case 'HEAD': case 'PATCH':
            Http::$method = Text::toUpperCase($args->get($j++));
            break;
    }

    return Http::fetch($args->get($j), getFields($args, $j+1));
});

/**
 * Returns the current headers or sets one or more headers for the next request.
 * @code (`request:headers` [header-line|array])
 * @example
 * (request:headers "Authorization: Bearer MyToken")
 * ; true
 * (request:headers)
 * ; ["Authorization: Bearer MyToken"]
 */
Expr::register('request:headers', function ($args)
{
    $value = $args->{1};
    if ($value === null)
        return Http::$headers->values();

    if (\Rose\typeOf($value) === 'Rose\Arry')
        $value->forEach(function($header) { Http::header($header); });
    else
        Http::header($value);
    return true;
});

/**
 * Returns the response headers of the last request or a single header (if exists).
 * @code (`request:response-headers` [header])
 * @example
 * (request:response-headers)
 * ; { "content-type": "application/json", "content-length": "123" }
 *
 * (request:response-headers "content-type")
 * ; application/json
 */
Expr::register('request:response-headers', function ($args)
{
    if ($args->length === 2)
        return Http::$responseHeaders->get($args->get(1));

    return Http::$responseHeaders;
});

/**
 * Enables or disables request debugging. When enabled request data will be output to the log file.
 * @code (`request:debug` <value>)
 * @example
 * (request:debug true)
 * ; true
 */
Expr::register('request:debug', function ($args) {
    Http::$debug = \Rose\bool($args->get(1));
    return true;
});

/**
 * Enables or disables SSL verification for requests.
 * @code (`request:verify` <value>)
 * @example
 * (request:verify false)
 * ; true
 */
Expr::register('request:verify', function ($args)
{
    Http::$verify_ssl = \Rose\bool($args->get(1));
    return true;
});


/**
 * Sets the HTTP Authorization header for the next request.
 * @code (`request:auth` "basic" <username> <password>)
 * @code (`request:auth` "basic" <username>)
 * @code (`request:auth` "bearer" <token>)
 * @code (`request:auth` <token>)
 * @code (`request:auth` false)
 * @example
 * (request:auth "basic" "admin" "admin")
 * ; true
 */
Expr::register('request:auth', function ($args)
{
    if ($args->length == 2)
    {
        if (!$args->get(1) || $args->get(1) === 'false') {
            Http::auth(false);
            return true;
        }

        Http::auth('value', $args->get(1));
        return true;
    }

    Http::auth($args->get(1), $args->get(2), $args->{3});
    return true;
});

/**
 * Returns the HTTP status code of the last request.
 * @code (`request:status`)
 * @example
 * (request:status)
 * ; 200
 */
Expr::register('request:status', function ($args) {
    return Http::getCode();
});

/**
 * Returns the last error message.
 * @code (`request:error`)
 * @example
 * (request:error)
 * ; Could not resolve host
 */
Expr::register('request:error', function ($args) {
    return Http::getError();
});

/**
 * Returns the content-type of the last request. Shorthand for `(request:headers "content-type")` without the charset.
 * @code (`request:content-type`)
 * @example
 * (request:content-type)
 * ; text/html
 */
Expr::register('request:content-type', function ($args) {
    return Http::getContentType();
});

/**
 * Returns the raw data returned by the last request.
 * @code (`request:data`)
 * @example
 * (request:data)
 * ; HelloWorld
 */
Expr::register('request:data', function ($args) {
    return Http::getData();
});

/**
 * Clears the current headers, response headers and response data.
 * @code (`request:clear`)
 */
Expr::register('request:clear', function ($args) {
    Http::clear();
    return null;
});

/**
 * Sets the output handler for the next request.
 * @code (`request:output-handler` <func>)
 * @example
 * (request:output-handler (fn data (echo (data))))
 * ; true
 */
Expr::register('request:output-handler', function ($args) {
    Http::$output_handler = $args->get(1);
    return true;
});

/**
 * Sets the output file for the next request.
 * @code (`request:output-file` <file-path>)
 * @example
 * (request:output-file "output.txt")
 * ; true
 */
Expr::register('request:output-file', function ($args) {
    $fp = $args->get(1);
    Http::$output_handler = function($data, $_, $__) use(&$fp)
    {
        if (\Rose\isString($fp)) $fp = fopen($fp, 'w');
        if (!$fp) return;

        $data = $data->get(1);
        if ($data === '') {
            fclose($fp);
            return;
        }

        fwrite($fp, $data);
    };

    return true;
});

/**
 * Sets the input handler for the next request.
 * @code (`request:input-handler` <func>)
 * @example
 * (request:input-handler (fn max_bytes (ret "....")))
 * ; true
 */
Expr::register('request:input-handler', function ($args) {
    Http::$input_handler = $args->get(1);
    return true;
});

/**
 * Sets the input file for the next request.
 * @code (`request:input-file` <file-path>)
 * @example
 * (request:input-file "sample.jpg")
 * ; true
 */
Expr::register('request:input-file', function ($args) {
    $fp = $args->get(1);
    Http::$input_handler = function($data, $_, $__) use(&$fp)
    {
        if (\Rose\isString($fp)) $fp = fopen($fp, 'r');
        if (!$fp) return '';

        $count = $data->get(1);
        if (!$count) {
            fclose($fp);
            return '';
        }

        return fread($fp, $count);
    };

    return true;
});

/**
 * Sets the progress handler for the next request.
 * @code (`request:progress-handler` <func>)
 * @example
 * (request:progress-handler (fn total_bytes curr_bytes ...))
 * ; true
 */
Expr::register('request:progress-handler', function ($args) {
    Http::$progress_handler = $args->get(1);
    return true;
});
