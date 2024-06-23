<?php

namespace Rose;

use Rose\Configuration;
use Rose\Gateway;
use Rose\Text;
use Rose\DateTime;

/**
 * Stores and retrieves persistent system parameters (cookies).
 */

class Cookies
{
    /**
     * Sets a cookie with optional expiration value (TTL, time-to-live, delta from current time).
     */
    private static function setCookieHeader ($name, $value, $ttl=null, $domain=null, $path=null)
    {
        $path = $path == null ? Gateway::getInstance()->root.'/' : $path;
        $domain = $domain == null ? Configuration::getInstance()?->Gateway?->server_name : $domain;

        if ($value === null)
        {
            $ttl = -608400;
            $value = 'deleted';
        }

        $header = $name.'='.$value;

        if ($ttl > 0)
            $header .= '; Expires=' . (new DateTime())->add($ttl, DateTime::SECOND)->format('UTC');
        else if ($ttl < 0)
            $header .= '; MaxAge=-1; Expires=Thu, 01 Jan 1970 00:00:00 GMT';

        if ($domain) $header .= '; Domain='.$domain;
        if ($path) $header .= '; Path='.$path;

        if (Configuration::getInstance()?->Gateway?->same_site)
            $sameSite = Configuration::getInstance()->Gateway->same_site;
        else
            $sameSite = 'None';

        if (Configuration::getInstance()?->Gateway?->allow_origin /*&& Gateway::getInstance()->server->has('HTTP_ORIGIN')*/)
            $header .= '; SameSite='.$sameSite;

        if (Text::toLowerCase($sameSite) === 'none' && Gateway::getInstance()->secure)
            $header .= '; Secure; Partitioned';

        $header .= '; HttpOnly';

        Gateway::header('Set-Cookie: '.$header);
    }

    /**
     * Returns true if the given cookie name exists.
     */
    public static function has ($name)
    {
        return Gateway::getInstance()->cookies->has($name);
    }

    /**
     * Returns the cookie value matching the given name or null if not found.
     */
    public static function get ($name)
    {
        return Gateway::getInstance()->cookies->get($name);
    }

    /**
     * Sets a cookie with optional TTL value.
     */
    public static function set ($name, $value, $ttl=null, $domain=null)
    {
        if ($ttl !== null)
            self::setCookieHeader ($name, $value, $ttl, $domain);
        else
            self::setCookieHeader ($name, $value, 0, $domain);
    }

    /**
     * Removes a cookie given its name.
     */
    public static function remove ($name, $domain=null)
    {
        self::setCookieHeader ($name, null, 0, $domain);
        Gateway::getInstance()->cookies->remove($name);
    }
};
