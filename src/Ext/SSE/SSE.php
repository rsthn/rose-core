<?php

namespace Rose\Ext;

use Rose\Ext\Wind;

// @title Server-Sent Events (SSE)
// @short SSE

/**
 * Initializes server-sent events (SSE) by setting several HTTP headers in the response and configuring the Gateway
 * to persist and disable any kind of output buffering.
 * @code (`sse:init`)
 * @example
 * (sse:init)
 * ; Content-Type: text/event-stream; charset=utf-8
 * ; Transfer-Encoding: identity
 * ; Content-Encoding: identity
 * ; Cache-Control: no-store
 * ; X-Accel-Buffering: no
 */
Expr::register('sse:init', function(...$args) {
    return Wind::enableEvents();
});

/**
 * Sends the specified data to the browser as a server-sent event. If no event name is specified, the default event
 * name `message` will be used.
 * @code (`sse:send` [event-name] <data>)
 * @example
 * (sse:send "message" "Hello, World!")
 * ; event: message
 * ; data: Hello, World!
 * 
 * (sse:send "info" (& list (# 1 2 3)))
 * ; event: info
 * ; data: {"list":[1,2,3]}
 */
Expr::register('sse:send', function(...$args) {
    return Wind::sendEvent(...$args);
});

/**
 * Sends a comment line `:alive` to the browser to keep the connection alive if the last message sent was more than
 * 30 seconds ago. Returns `false` if the connection was already closed by the browser.
 * @code (`sse:alive`)
 * @example
 * (when (sse:alive)
 *     ; Do something ...
 *     (sys:sleep 1)
 * )
 */
Expr::register('sse:alive', function(...$args) {
    return Wind::eventsAlive();
});
