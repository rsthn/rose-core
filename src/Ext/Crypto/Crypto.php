<?php

namespace Rose\Ext;

use Rose\Expr;
use Rose\Arry;

// @title Crypto

/**
 * Returns a list of available hash algorithms.
 * @code (`crypto:hash-list`)
 * @example
 * (crypto:hash-list)
 * ; ["md2","md4","md5","sha1","sha224", ...]
 */
Expr::register('crypto:hash-list', function($args) {
    return Arry::fromNativeArray(hash_algos());
});

/**
 * Returns the hash of a string (hexadecimal).
 * @code (`crypto:hash` <algorithm> <data>)
 * @example
 * (crypto:hash "md5" "Hello, World!")
 * ; 65a8e27d8879283831b664bd8b7f0ad4
 */
Expr::register('crypto:hash', function($args) {
    return hash($args->get(1), $args->get(2));
});

/**
 * Returns the hash of a string (binary).
 * @code (`crypto:hash-bin` <algorithm> <data>)
 * @example
 * (crypto:hash-bin "md5" "Hello, World!")
 * ; binary data
 */
Expr::register('crypto:hash-bin', function($args) {
    return hash($args->get(1), $args->get(2), true);
});

/**
 * Returns the HMAC of a string (hexadecimal).
 * @code (`crypto:hmac` <algorithm> <secret-key> <data>)
 * @example
 * (crypto:hmac "sha256" "secret" "Hello, World!")
 * ; fcfaffa7fef86515c7beb6b62d779fa4ccf092f2e61c164376054271252821ff
 */
Expr::register('crypto:hmac', function($args) {
    return hash_hmac($args->get(1), $args->get(3), $args->get(2));
});

/**
 * Returns the HMAC of a string (binary).
 * @code (`crypto:hmac-binary` <algorithm> <secret-key> <data>)
 * @example
 * (crypto:hmac-binary "sha256" "secret" "Hello, World!")
 * ; binary data
 */
Expr::register('crypto:hmac-bin', function($args) {
    return hash_hmac($args->get(1), $args->get(3), $args->get(2), true);
});
