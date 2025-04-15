<?php

namespace Rose\Ext;

use Rose\Errors\FalseError;
use Rose\Errors\Error;
use Rose\Errors\MetaError;

use Rose\IO\Directory;
use Rose\IO\Path;
use Rose\IO\File;

use Rose\Gateway;
use Rose\Regex;
use Rose\Text;
use Rose\DateTime;
use Rose\Expr;
use Rose\Arry;
use Rose\Map;
use Rose\Math;

use Rose\Resources;
use Rose\Session;
use Rose\Strings;
use Rose\Configuration;
use Rose\JSON;

use Rose\Ext\Wind\SubReturn;
use Rose\Ext\Wind\WindError;

use Rose\Main;

class WindProxy {
    private $version;
    public function __construct($version) {
        $this->version = $version;
    }

    public function main() {
        Wind::main($this->version);
    }
};

class Wind
{
    private static $base;
    private static $cache;
    public static $data;

    public static $multiResponseMode;
    public static $response;

    public static $callStack;
    private static $version;

    public const R_OK                       = 200; // OK
    public const R_BAD_REQUEST              = 400; // Bad Request
    public const R_UNAUTHORIZED             = 401; // Unauthorized
    public const R_FORBIDDEN                = 403; // Forbidden
    public const R_NOT_FOUND                = 404; // Not Found
    public const R_VALIDATION_ERROR         = 422; // Unprocessable Content
    public const R_CUSTOM_ERROR             = 409; // Conflict

    public static function init()
    {
        Gateway::registerService ('wind', new WindProxy(1));
        Gateway::registerService ('wind-2', new WindProxy(2));
        Gateway::registerService ('wind-3', new WindProxy(3));

        self::$base = Main::$CORE_DIR.'/fn';
        self::$cache = 'volatile/wind';
        self::$callStack = new Arry();
        self::$multiResponseMode = 0;
    }

    public static function flush ($response)
    {
        if (self::$version >= 2 && \Rose\typeOf($response) === 'Rose\\Map')
        {
            if ($response->has('response')) {
                Gateway::status($response->get('response'));
                if (self::$version >= 3)
                    $response->remove('response');
            }
        }

        echo (string)$response;
    }

    public static function prepare ($response)
    {
        if (\Rose\isArray($response))
            $response = new Map ($response);

        if (\Rose\typeOf($response) === 'Rose\\Map' || \Rose\typeOf($response) === 'Rose\\Arry')
        {
            if (\Rose\typeOf($response) === 'Rose\\Arry') {
                $response = new Map([ 'response' => Wind::R_OK, 'data' => $response ], false);
            }
            else
            {
                if (!$response->has('response')) {
                    $tmp = new Map([ 'response' => Wind::R_OK ]);
                    $tmp->merge ($response, true);
                    $response = $tmp;
                }
            }
        }
        else if (\Rose\isString($response)) {
        }
        else {
            $response = $response ? (string)$response : null;
        }

        return $response;
    }

    public static function reply ($response, $isError=false)
    {
        if (self::$data->internal_call != 0) {
            self::$response = $response;
            throw new SubReturn();
        }

        if (Gateway::$contentFlushed)
        {
            if ($isError) {
                $response = self::prepare($response);
                \Rose\trace('[ERROR] ['.(new DateTime()).'] ['.Gateway::getInstance()->remoteAddress.'] '.$response);
                self::flush($response);
            }

            Gateway::exit();
        }

        $response = self::prepare($response);

        if (\Rose\typeOf($response) === 'Rose\Map' || \Rose\typeOf($response) === 'Rose\Arry') {
            if (Gateway::$contentType == null)
                Gateway::$contentType = 'Content-Type: application/json; charset=utf-8';
        }
        else if (\Rose\isString($response) && strlen($response) != 0) {
            if (Gateway::$contentType == null)
                Gateway::$contentType = 'Content-Type: text/plain; charset=utf-8';
        }

        self::$response = $response;

        if (self::$multiResponseMode)
            throw new FalseError();

        if ($response != null) {
            Gateway::header(Gateway::$contentType);
            self::flush($response);
        }

        if (self::$data->internal_call != 0)
            throw new SubReturn();

        Gateway::exit();
    }

    public static function resetContext ()
    {
        self::$data = new Map();
        self::$data->internal_call = 0;
    }

    public static function process ($path, $relative_path=null)
    {
        if ($path[0] === '@')
            $path = self::$callStack->get(self::$callStack->length-1)[0].$path;

        $path1 = Path::append(self::$base, Text::replace('.', '/', $path) . '.fn');
        $path2 = Path::append(self::$cache, $path.'.fn');

        self::$response = null;

        if (Path::exists($path2) && Path::exists($path1) && File::mtime($path2, true) == File::mtime($path1, true))
        {
            $expr = unserialize(File::getContents($path2));
        }
        else if (Path::exists($path1))
        {
            $expr = Expr::clean(Expr::parse(File::getContents($path1)));

            File::setContents($path2, serialize($expr));
            File::touch($path2, File::mtime($path1, true));
        }
        else
        {
            $endpoints = Configuration::getInstance()?->endpoints;
            if ($relative_path && $endpoints)
            {
                $method = Gateway::getInstance()->method;
                $ctx = new Map([
                    'method' => $method,
                    'query' => Gateway::getInstance()->request,
                    'body' => Gateway::getInstance()->input->contentType === null ? null : Gateway::getInstance()->input,
                ]);

                // TODO: Optimize to prevent re-parsing and linear searches.
                foreach ($endpoints->__nativeArray as $endpoint => $handlers)
                {
                    [$endpoint_method, $endpoint_path] = explode(' ', $endpoint);
                    if ($endpoint_method !== '*' && $endpoint_method !== $method)
                        continue;

                    $endpoint_path = '|^'.Regex::_replace('/{([A-Za-z0-9_-]+)}/', '(?<$1>[^/]+)', $endpoint_path).'$|';
                    $vars = Regex::_matchFirst($endpoint_path, $relative_path);
                    if (!$vars->length) continue;
                    $vars->removeAll('/^[0-9]/', true);

                    foreach (explode(' ', $handlers) as $handler)
                    {
                        $handler = explode(':', $handler);
                        if (count($handler) == 1)
                            $handler[] = 'main';

                        Expr::call('import', new Arry(['', $handler[0]]));

                        if (!Expr::getFunction($handler[1])) {
                            throw new WindError ('NotFoundError', [
                                'response' => self::R_NOT_FOUND,
                                'message' => Strings::get('@messages.function_not_found') . ': ' . $handler[1] . ' in ' . $handler[0]
                            ]);
                        }
                        $response = Expr::call($handler[1], new Arry(['', $ctx, $vars ]));
                    }

                    if ($response != null)
                        self::reply($response);

                    exit;
                }

                throw new WindError ('NotFoundError', [ 'response' => self::R_NOT_FOUND, 'message' => Strings::get('@messages.invalid_endpoint') . ': ' . $method . ' ' . $relative_path ]);
            }

            throw new WindError ('NotFoundError', [ 'response' => self::R_BAD_REQUEST, 'error' => Strings::get('@messages.function_not_found') . ': ' . $path ]);
        }

        $tmp = Text::split('.', $path);
        $tmp->pop();
        $tmp = $tmp->join('.').'.';

        self::$callStack->push([ $tmp, $path, $path1 ]);

        Expr::$context->currentPath = Path::resolve(Path::dirname($path1));
        $response = Expr::expand($expr, self::$data, 'last');

        self::$callStack->pop();

        if ($response != null)
            self::reply($response);
    }

    /**
     * Runs a script in CLI-mode.
     */
    public static function run ($path, $data=null)
    {
        self::$data = $data ? $data : new Map();
        self::$data->internal_call = 1;

        self::$response = null;

        if (Path::exists($path))
            $expr = Expr::clean(Expr::parse(File::getContents($path)));
        else
            throw new Error (Strings::get('@messages.file_not_found') . ': ' . $path);

        try {
            Expr::$context->currentPath = Path::resolve(Path::dirname($path));
            $response = Expr::expand($expr, self::$data, 'last');

            if ($response != null)
                self::reply ($response);
        }
        catch (SubReturn $e)
        {
            if (!Gateway::$contentFlushed)
                self::flush(self::$response);
        }
        catch (FalseError $e) {
        }
    }

    public static function main ($version)
    {
        self::$version = $version;

        $gateway = Gateway::getInstance();
        $params = $gateway->request;

        // Handle OPTIONS request.
        if ($gateway->method === 'OPTIONS')
            return;

        // Handle regular requests.
        if ($params->rpkg != null || $params->mreq != null)
        {
            $requests = Text::split(';', $params->rpkg != null ? $params->rpkg : $params->mreq);
            self::$multiResponseMode = 1;

            $r = new Map ();
            $n = 0;

            $originalParams = $params;

            foreach ($requests->__nativeArray as $i)
            {
                $i = Text::trim($i);
                if (!$i) continue;

                $i = Text::split(',', $i);
                if ($i->length != 2) continue;

                try {
                    $gateway->request->clear()->merge($originalParams, true);
                    parse_str(base64_decode($i->get(1)), $requestParams);
                    $gateway->request->__nativeArray = $gateway->request->__nativeArray + $requestParams;
                }
                catch (\Throwable $e) {
                    \Rose\trace('Error: '.$e->getMessage());
                    continue;
                }

                if (++$n > 64) break;

                try
                {
                    self::resetContext();

                    $f = $gateway->request->f;
                    $relative_path = '';
                    if ($gateway->relativePath) {
                        $relative_path = $gateway->relativePath . Text::replace('.', '/', $f);
                        $f = $relative_path;
                    }
                    $f = Text::replace('/', '.', Text::trim($f, '/'));

                    $f = Regex::_extract('/[A-Za-z0-9._-]+/', $f);
                    self::process($f, $relative_path);
                }
                catch (FalseError $e) {
                }
                catch (WindError $e) {
                    self::$response = self::prepare($e->getData());
                }
                catch (MetaError $e)
                {
                    switch ($e->code)
                    {
                        case 'EXPR_YIELD':
                            self::$response = self::prepare($e->value);
                            break;

                        case 'FN_RET':
                            self::$response = self::prepare($e->value);
                            break;
        
                        default:
                            throw $e;
                    }
                }
                catch (\Throwable $e) {
                    self::$response = new Map([ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ]);
                }

                $r->set($i->get(0), self::$response);
            }

            self::$multiResponseMode = 0;
            self::reply($r);
        }

        if ($gateway->relativePath) {
            $gateway->relativePath .= Text::replace('.', '/', $params->f);
            $params->f = $gateway->relativePath;
        }
        $params->f = Text::replace('/', '.', Text::trim($params->f, '/'));

        try
        {
            self::resetContext();

            $f = Regex::_extract ('/[A-Za-z0-9._-]+/', $params->f);
            if (!$f) {
                if (!$params->f) {
                    $banner = Configuration::getInstance()?->Gateway?->banner;
                    if ($banner)
                        self::reply(Expr::eval($banner, null, 'arg'));
                    else
                        throw new WindError ('Response', [ 'response' => self::R_OK, 'framework' => Main::name(), 'version' => Main::version() ]);
                }
                throw new WindError ('NotFoundError', [ 'response' => self::R_BAD_REQUEST, 'message' => Strings::get('@messages.function_not_found') . ': ' . $params->f ]);
            }

            self::process($f, $gateway->relativePath);
        }
        catch (FalseError $e) {
        }
        catch (WindError $e) {
            self::reply ($e->getData(), true);
        }
        catch (MetaError $e)
        {
            switch ($e->code)
            {
                case 'EXPR_YIELD':
                case 'FN_RET':
                    self::reply ($e->value);
                    break;

                default:
                    throw $e;
            }
        }
        catch (\Throwable $e) {
            self::reply ([ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ], true);
        }
    }

    /**
    **	return [<data>]
    */
    public static function _return ($args, $parts, $data)
    {
        self::reply ($args->length > 1 ? $args->get(1) : new Map());
    }

    /**
    **	stop [<object-expr>]
    */
    public static function stop ($args, $parts, $data)
    {
        self::$data->internal_call = 0;

        if ($args->length > 1)
            self::reply ($args->get(1));

        Gateway::exit();
    }

    /**
    **	call <fnname> [:<name> <expr>...]
    */
    public static function _call ($parts, $data)
    {
        self::$data->internal_call = 1 + self::$data->internal_call;
        $response = null;
        $n_args = null;
        $p_args = null;

        try {
            $n_args = Expr::getNamedValues($parts, $data, 2);

            $p_args = self::$data->args;
            self::$data->args = $n_args;

            self::process($name = Expr::expand($parts->get(1), $data));
        }
        catch (SubReturn $e) {
            $response = self::$response;
        }
        catch (FalseError $e) {
            self::$data->args = $p_args;
            throw $e;
        }
        catch (WindError $e) {
            self::$data->internal_call = self::$data->internal_call - 1;
            self::$data->args = $p_args;
            throw $e;
        }
        catch (MetaError $e)
        {
            switch ($e->code)
            {
                case 'EXPR_YIELD':
                case 'FN_RET':
                    $response = $e->value;
                    break;

                default:
                    throw $e;
            }
        }
        catch (\Throwable $e)
        {
            self::$data->internal_call = self::$data->internal_call - 1;
            self::$data->args = $p_args;
            throw new WindError ('Error', [ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ]);
        }

        self::$data->internal_call = self::$data->internal_call - 1;
        self::$data->args = $p_args;

        return $response;
    }

    /**
    **	icall <fnname> [:<name> <expr>...]
    */
    public static function _icall ($parts, $data)
    {
        self::$data->internal_call = 1 + self::$data->internal_call;

        $response = null;
        $p_data = self::$data;
        self::$data = new Map();

        try {
            self::$data->internal_call = $p_data->internal_call;
            self::$data->args = Expr::getNamedValues($parts, $data, 2);
            self::process($name = Expr::expand($parts->get(1), $data));
        }
        catch (SubReturn $e) {
            $response = self::$response;
        }
        catch (FalseError $e) {
            self::$data = $p_data;
            throw $e;
        }
        catch (WindError $e) {
            self::$data = $p_data;
            self::$data->internal_call = self::$data->internal_call - 1;
            throw $e;
        }
        catch (MetaError $e)
        {
            switch ($e->code)
            {
                case 'EXPR_YIELD':
                case 'FN_RET':
                    $response = $e->value;
                    break;

                default:
                    throw $e;
            }
        }
        catch (\Throwable $e)
        {
            self::$data = $p_data;
            self::$data->internal_call = self::$data->internal_call - 1;
            throw new WindError ('Error', [ 'response' => Wind::R_CUSTOM_ERROR, 'error' => $e->getMessage() ]);
        }

        self::$data = $p_data;
        self::$data->internal_call = self::$data->internal_call - 1;

        return $response;
    }

    private static $eventsEnabled = false;
    private static $lastSent = null;

    /**
     * sse:init
     */
    public static function enableEvents ()
    {
        if (self::$eventsEnabled)
            return;

        self::$eventsEnabled = true;
        self::$lastSent = time();

        Gateway::$contentType = 'text/event-stream; charset=utf-8';

        Gateway::header("Content-Type: text/event-stream; charset=utf-8");
        Gateway::header("Transfer-Encoding: identity");
        Gateway::header("Content-Encoding: identity");
        Gateway::header("Cache-Control: no-store");
        Gateway::header("X-Accel-Buffering: no");

        Gateway::persistent();
        Gateway::flush();
    }

    /**
     * sse:send [<event-name>] <data>
     */
    public static function sendEvent ($args, $parts, $data)
    {
        if (!self::$eventsEnabled)
            return false;

        $s = '';
        $i = 2;

        if ($args->length > 2)
            $s = "event: ".$args->get(1)."\n";
        else
            $i = 1;

        $i = $args->get($i);

        if (\Rose\typeOf($i) === 'Rose\\Arry' || \Rose\typeOf($i) === 'Rose\\Map')
            $i = (string)$i;

        $s .= "data: " . $i . "\n\n";
        echo $s;

        self::$lastSent = time();
        return true;
    }

    /**
     * sse:alive
     */
    public static function eventsAlive ()
    {
        if (!self::$eventsEnabled)
            return false;

        $now = time();
        if ($now - self::$lastSent >= 30) {
            echo ": alive\n\n";
            self::$lastSent = time();
        }

        return Gateway::connected();
    }
};

Wind::init();
