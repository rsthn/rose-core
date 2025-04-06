<?php

namespace Rose\Ext;

use Rose\Expr;
use Rose\Cookies;

// @title Cookies

/**
 * Returns `true` if a cookie with the given name exists.
 * @code (`cookie:exists` <name>)
 * @example
 * (cookie:exists "MyCookie")
 * ; false
 */
Expr::register('cookie:exists', function ($args) {
    return Cookies::has($args->get(1));
});

/**
 * Sets a cookie with the given name and value. Optionally, you can specify the time to live in seconds and the domain.
 * NOTE: By default the cookie will be set to never expire.
 * @code (`cookie:set` <name> <value> [timeToLive] [domain])
 * @example
 * (cookie:set "MyCookie" "hello" 3600)
 * ; null
 */
Expr::register('cookie:set', function ($args) {
    Cookies::set($args->get(1), $args->get(2), $args->{3}, $args->{4});
    return null;
});

/**
 * Returns the value of the cookie with the specified name.
 * @code (`cookie:get` <name>)
 * @example
 * (cookie:get "MyCookie")
 * ; "hello"
 */
Expr::register('cookie:get', function ($args) {
    return Cookies::get($args->get(1));
});

/**
 * Returns all available cookies.
 * @code (`cookie:get-all`)
 * @example
 * (cookie:get-all)
 * ; "hello"
 */
Expr::register('cookie:get-all', function ($args) {
    return Cookies::getAll();
});

/**
 * Removes the cookie with the specified name.
 * @code (`cookie:remove` <name>)
 * @example
 * (cookie:remove "MyCookie" [domain])
 * ; true
 */
Expr::register('cookie:remove', function ($args) {
    return Cookies::remove($args->get(1), $args->{2});
});
