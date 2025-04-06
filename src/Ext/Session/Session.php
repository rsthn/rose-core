<?php

namespace Rose\Ext;

use Rose\Session;
use Rose\Expr;

// @title Session

/**
 * Object with the current session data.
 * @code (`session`)
 * @example
 * (session)
 * ; {"key1": "value1", "key2": "value2"}
 */
Expr::register('session', function ($args) { return Session::$data; });

/**
 * Attempts to open an existing session or creates a new one (if `createSession` is `true`). The cookie name and other
 * configuration fields are obtained from the `Session` configuration section.
 * @code (`session:open` [createSession=true])
 * @example
 * (session:open)
 * ; true
 */
Expr::register('session:open', function ($args) {
    return Session::open($args->length == 2 ? \Rose\bool($args->get(1)) : true);
});

/**
 * Closes the current session and writes the session data to permanent storage (file system or database). If the `activityOnly` 
 * parameter is `true` only the session's last activity field will be written to storage.
 * @code (`session:close` [activityOnly=false])
 */
Expr::register('session:close', function ($args) {
    return Session::close($args->length == 2 ? \Rose\bool($args->get(1)) : false);
});

/**
 * Attempts to open an existing session and if exists its data will be loaded and then will be immediately closed. Only
 * the `last_activity` field will be updated. Useful to prevent session blocking. Use `session:save` to save the session data.
 * @code (`session:load` [createSession=true])
 * @example
 * (session:load)
 * ; true
 */
Expr::register('session:load', function ($args) {
    $result = Session::open($args->length == 2 ? \Rose\bool($args->get(1)) : true);
    Session::close(true);
    return $result;
});

/**
 * Attempts to save the data to the session if it exists.
 * @code (`session:save`)
 * @example
 * (session:save)
 * ; true
 */
Expr::register('session:save', function ($args) {
    Session::write(false);
    return true;
});

/**
 * Destroys the current session, removes all session data including the session's cookie.
 * @code (`session:destroy`)
 */
Expr::register('session:destroy', function ($args) {
    return Session::destroy();
});

/**
 * Clears the session data and keeps the same cookie name.
 * @code (`session:clear`)
 */
Expr::register('session:clear', function ($args) {
    return Session::clear();
});

/**
 * Returns the name of the session cookie, default ones comes from the `Session` configuration section.
 * @code (`session:name`)
 * @example
 * (session:name)
 * ; "MySession"
 */
Expr::register('session:name', function ($args) {
    return Session::$sessionName;
});

/**
 * Returns or sets the current session ID.
 * @code (`session:id` [newSessionID])
 * @code (`session:id`)
 * @example
 * (session:id)
 * ; "oldSessionID"
 *
 * (session:id "newSessionID")
 * ; "newSessionID"
 */
Expr::register('session:id', function ($args)
{
    if ($args->length == 2)
        Session::$sessionId = $args->get(1);

    return Session::$sessionId;
});

/**
 * Returns boolean indicating if the session is open.
 * @code (`session:is-open`)
 * @example
 * (session:is-open)
 * ; true
 */
Expr::register('session:is-open', function ($args) {
    return Session::$sessionOpen;
});
