<?php

namespace Rose\Ext;

use Rose\Expr;
use Rose\Arry;

Expr::register('utils::hashes', function($args) // DEPRECATED
{
    return Arry::fromNativeArray(hash_algos());
});

Expr::register('crypto:hashlist', function($args) {
    return Arry::fromNativeArray(hash_algos());
});

Expr::register('utils::hash', function($args) {  // DEPRECATED
    return hash($args->get(1), $args->get(2));
});

Expr::register('crypto:hash', function($args) {
    return hash($args->get(1), $args->get(2));
});

Expr::register('utils::hash-binary', function($args) {  // DEPRECATED
    return hash($args->get(1), $args->get(2), true);
});

Expr::register('crypto:hash-bin', function($args) {
    return hash($args->get(1), $args->get(2), true);
});

/**
 * Returns the HMAC of a string (hexadecimal).
 * @code (utils::hmac <algorithm> <secret-key> <data>)
 */
Expr::register('utils::hmac', function($args) {  // DEPRECATED
    return hash_hmac($args->get(1), $args->get(3), $args->get(2));
});

Expr::register('crypto:hmac', function($args) {
    return hash_hmac($args->get(1), $args->get(3), $args->get(2));
});

/**
 * Returns the HMAC of a string (binary).
 * @code (utils::hmac-binary <algorithm> <secret-key> <data>)
 */
Expr::register('utils::hmac-binary', function($args) {  // DEPRECATED
    return hash_hmac($args->get(1), $args->get(3), $args->get(2), true);
});

Expr::register('crypto:hmac-bin', function($args) {
    return hash_hmac($args->get(1), $args->get(3), $args->get(2), true);
});
