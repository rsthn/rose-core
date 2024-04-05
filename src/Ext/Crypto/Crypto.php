<?php

namespace Rose\Ext;

use Rose\Errors\ArgumentError;
use Rose\Expr;
use Rose\Arry;
use Rose\Text;

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

/**
 * Generates a unique code using a cryptographically secure random number generator.
 * @code (`crypto:unique` <length> [charset])
 * @example
 * (crypto:unique 16)
 * ; If1uIctc_61vluui
 *
 * (crypto:unique 16 "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@$")
 * ; QjE5SbH8z1OBliBS
 */
Expr::register('crypto:unique', function($args)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz-0123456789';

    if ($args->has(2)) {
        $chars = $args->get(2);
        if (Text::length($chars) != 64)
            throw new ArgumentError('Code charset string should be 64 characters long.');
    }

    $tmp = explode(' ', microtime());
    $tmp[0] = ((int)($tmp[0] * 0x1000000)) & 0xFFFFFF;
    $tmp[1] = ((int)$tmp[1]) & 0xFFFFFFFF;

    $data = [
        (($tmp[1] >> 24) & 0x3F),
        (($tmp[1] >> 0) & 0x3F),
        (($tmp[1] >> 12) & 0x3F),
        (($tmp[1] >> 18) & 0x3F),
        (($tmp[1] >> 6) & 0x3F),
        (($tmp[0] >> 6) & 0x3F),
        (((($tmp[1] >> 30) & 0x03) << 4) | (($tmp[0] >> 12) & 0x0F)),
        (($tmp[0] >> 0) & 0x3F),
    ];

    $n = $args->length > 1 ? (int)$args->get(1) : 0;
    while ($n-- > 8)
        $data[] = ord(random_bytes(1)) & 0x3F;

    $tmp = '';
    for ($i = 0; $i < count($data); $i++)
        $tmp .= $chars[ $data[$i] ^ (ord(random_bytes(1)) & 0x3F) ];

    return $tmp;
});
