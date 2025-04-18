<?php

use Rose\Errors\Error;
use Rose\Expr;
use Rose\Map;
use Rose\Arry;
use Rose\Regex;
use Rose\Text;

// @title OpenSSL

function get_der ($value) {
    return base64_decode(Regex::_replace ('/-+BEGIN\s.+?-+|-+END\s.+?-+|\r|\n|\s|\t/', '', $value));
}

function parse_der ($data)
{
    $n = strlen($data);

    $state = 0;
    $next = 0;
    $a = 0;

    $der = new Map();
    $der->set('int', new Arry());
    $der->set('bits', new Arry());
    $der->set('octets', new Arry());

    for ($i = 0; $i < $n; $i++)
    {
        $val = ord($data[$i]);
        switch($state)
        {
            case 0: // Expecting: main sequence
                if ($val !== 0x30)
                    throw new Error('[Offset '.$i.'] Expected main DER sequence');

                $state = 100;
                $next = 1;
                break;

            case 1: // Length of main sequence ready
                if ($a+$i !== $n)
                    throw new Error('[Offset '.$i.'] Invalid main sequence length ' . ($a+$i) . ' expected ' . $n);

                $state = 2;
                $i--;
                break;

            case 2: // Expecting: integer, bit-string, octet-string, sequence
                if ($val === 0x02) { // integer
                    $state = 100;
                    $next = 20;
                    break;
                }

                if ($val === 0x03) { // bit-string
                    $state = 100;
                    $next = 21;
                    break;
                }

                if ($val === 0x04) { // octet-string
                    $state = 100;
                    $next = 22;
                    break;
                }

                if ($val === 0x30 || $val === 0xA1) {
                    $state = 100;
                    $next = 3;
                    break;
                }

                throw new Error('[Offset '.$i.'] Undefined DER structure: ' . Text::lpad(dechex($val), 2, '0') . 'h');

            case 3: // Skip element
                $i += $a - 1;
                $state = 2;
                break;

            case 20: // Length of integer ready
                $val = Text::substring($data, $i, $a);
                $der->get('int')->push($val);
                $state = 2;
                $i += $a - 1;
                break;

            case 21: // Length of bit-string ready
                $val = Text::substring($data, $i, $a);
                $extra_bits = ord($val[0]);
                // TODO: Do something with this?
                $der->get('bits')->push(Text::substring($val, 1));
                $state = 2;
                $i += $a - 1;
                break;

            case 22: // Length of octet-string ready
                $val = Text::substring($data, $i, $a);
                $der->get('octets')->push($val);
                $state = 2;
                $i += $a - 1;
                break;

            case 100: // Read length
                if ($val == 0x81) {
                    $state = 101;
                    break;
                }

                $state = $next;
                $a = $val;
                break;

            case 101: // Read length byte
                $state = $next;
                $a = $val;
                break;
        }
    }

    return $der;
}

function asn1_encode ($code, $data)
{
    $out = $data;
    $len = strlen($out);

    if ($len < 128)
        $out = chr($len) . $out;
    else if ($len < 256)
        $out = chr(0x81) . chr($len) . $out;
    else
        throw new Error('Invalid length');

    return chr($code) . $out;
}

function asn1_int ($val)
{
    $tmp = '';
    while ($val > 0) {
        $tmp = chr($val & 0xFF) . $tmp;
        $val = $val >> 8;
    }

    return $tmp === '' ? chr(0) : $tmp;
}

function asn1_int7 ($val)
{
    $tmp = '';
    while ($val > 0) {
        $tval = $val & 0x7F;
        if ($tmp !== '')
            $tval = $tval | 0x80;
        $val = $val >> 7;
        $tmp = chr($tval) . $tmp;
    }

    return $tmp === '' ? chr(0) : $tmp;
}

function asn1_int7_array ($values, $pos=0)
{
    $n = count($values);
    $out = '';
    for ($i = $pos; $i < $n; $i++)
        $out .= asn1_int7((int)$values[$i]);
    return $out;
}

function get_signing_algorithm ($name)
{
    switch(Text::toUpperCase($name))
    {
        case 'DSS1': return OPENSSL_ALGO_DSS1;
        case 'SHA1': return OPENSSL_ALGO_SHA1;
        case 'SHA224': return OPENSSL_ALGO_SHA224;
        case 'SHA256': return OPENSSL_ALGO_SHA256;
        case 'SHA384': return OPENSSL_ALGO_SHA384;
        case 'SHA512': return OPENSSL_ALGO_SHA512;
        case 'RMD160': return OPENSSL_ALGO_RMD160;
        case 'MD5': return OPENSSL_ALGO_MD5;
        case 'MD4': return OPENSSL_ALGO_MD4;
        case 'MD2 ': return OPENSSL_ALGO_MD2;
        default:
            throw new Error('Invalid signature algorithm: ' . $name);
    }
}

/**
 * Returns the version of the OpenSSL library.
 * @code (`openssl:version`)
 * @example
 * (openssl:version)
 * ; "OpenSSL 3.0.13 30 Jan 2024"
 */
Expr::register('openssl:version', function($args) {
    return OPENSSL_VERSION_TEXT;
});

/**
 * Wraps the given buffer in a PEM encoded block with the specified label.
 * @code (`pem:encode` <label> <data>)
 */
Expr::register('pem:encode', function($args) {
    $label = $args->get(1);
    $value = base64_encode($args->get(2));
    return "-----BEGIN ".$label."-----\n" . wordwrap($value, 64, "\n", true) . "\n-----END ".$label."-----\n";
});

/**
 * Returns a list of supported curves.
 * @code (`openssl:curves`)
 * @example
 * (openssl:curves)
 * ; ["prime192v1","secp224r1","prime256v1",...]
 */
Expr::register('openssl:curves', function($args) {
    return new Arry(openssl_get_curve_names());
});

/**
 * Returns a list of supported ciphers.
 * @code (`openssl:ciphers`)
 * @example
 * (openssl:ciphers)
 * ; ["prime192v1","secp224r1","prime256v1",...]
 */
Expr::register('openssl:ciphers', function($args) {
    return new Arry(openssl_get_cipher_methods());
});

/**
 * Generates a pseudo-random string of bytes.
 * @code (`openssl:random-bytes` <length>)
 * @example
 * (openssl:random-bytes 16)
 * ; (binary data)
 */
Expr::register('openssl:random-bytes', function($args) {
    return openssl_random_pseudo_bytes($args->get(1));
});

/**
 * Creates a new private key of the specified type. Returns `pkey` object. Note that when using EC keys, the curve name is
 * required, see `openssl:curves` for a list of supported curves.
 * @code (`openssl:create` <DSA|DH|RSA|EC> [curve-name] [bits])
 * @example
 * (openssl:create "EC" "prime256v1")
 * ; (pkey)
 */
Expr::register('openssl:create', function($args)
{
    $type = $args->get(1);
         if ($type === 'DSA') $type = OPENSSL_KEYTYPE_DSA;
    else if ($type === 'DH')  $type = OPENSSL_KEYTYPE_DH;
    else if ($type === 'RSA') $type = OPENSSL_KEYTYPE_RSA;
    else if ($type === 'EC')  $type = OPENSSL_KEYTYPE_EC;
    else throw new Error('Invalid key type: ' . $type);

    $config = [
        'private_key_type' => $type
    ];

    $val = $args->{2};
    if ($type === OPENSSL_KEYTYPE_EC) {
        if ($val === null)
            throw new Error('Curve name is required for EC keys');
        $config['curve_name'] = $val;
    }
    else {
        if ($val !== null && !\Rose\isInteger($val))
            throw new Error('Invalid key size: ' . $val);

        if ($val !== null)
            $config['private_key_bits'] = $val;
    }

    $key = openssl_pkey_new($config);
    if ($key === false)
        throw new Error(openssl_error_string());
    return $key;
});

/**
 * Returns the number of bits in the key.
 * @code (`openssl:bits` <pkey>)
 * @example
 * (openssl:bits (pkey))
 * ; 4096
 */
Expr::register('openssl:bits', function($args) {
    $details = openssl_pkey_get_details($args->get(1));
    return $details['bits'];
});

/**
 * Export the private key as a PEM encoded string.
 * @code (`openssl:export-private` <pkey>)
 * @example
 * (openssl:export-private (pkey))
 * ; "-----BEGIN ...
 */
Expr::register('openssl:export-private', function($args) {
    return openssl_pkey_export($args->get(1), $output) ? $output : null;
});

/**
 * Export the public key as a PEM encoded string.
 * @code (`openssl:export-public` <pkey>)
 * @example
 * (openssl:export-public (pkey))
 * ; "-----BEGIN ...
 */
Expr::register('openssl:export-public', function($args) {
    $details = openssl_pkey_get_details($args->get(1));
    return $details['key'];
});

/**
 * Loads a private key (PEM format) from the specified data buffer.
 * @code (`openssl:import-private` <pem-data>)
 * @example
 * (openssl:import-private "-----BEGIN ...")
 * ; (pkey)
 */
Expr::register('openssl:import-private', function($args) {
    $result = openssl_pkey_get_private($args->get(1));
    if ($result === false)
        throw new Error(openssl_error_string());
    return $result;
});

/**
 * Loads a public key (PEM format) from the specified data buffer.
 * @code (`openssl:import-public` <pem-data>)
 * @example
 * (openssl:import-public "-----BEGIN ...")
 * ; (pkey)
 */
Expr::register('openssl:import-public', function($args) {
    $result = openssl_pkey_get_public($args->get(1));
    if ($result === false) {
        $s = '';
        while ($msg = openssl_error_string())
            $s .= $msg . "\n";
        throw new Error($s);
    }
    return $result;
});

/**
 * Returns the last error message (if any) or empty string.
 * @code (`openssl:error`)
 * @example
 * (openssl:error)
 * ; "error:0D07207B:asn1 encoding routines:ASN1_get_object:header too long"
 */
Expr::register('openssl:error', function($args) {
    $s = '';
    while ($msg = openssl_error_string())
        $s .= $msg . "\n";
    return $s;
});

/**
 * Signs a data block using a private key and returns a signature in DER format.
 * Supported signing algorithms are: DSS1, SHA1, SHA224, SHA256, SHA384, SHA512, RMD160, MD5, MD4, and MD2.
 * @code (`openssl:sign` <private-key> <algorithm> <data>)
 * @example
 * (openssl:sign (priv-key) "SHA256" "hello")
 * ; (binary data)
 */
Expr::register('openssl:sign', function($args) {
	if (!openssl_sign($args->get(3), $signature, $args->get(1), get_signing_algorithm($args->get(2))))
        throw new Error(openssl_error_string());
    return $signature;
});

/**
 * Verifies a signature (DER format) of a data block using a public key. See `openssl:sign` for supported signing algorithms.
 * @code (`openssl:verify` <public-key> <algorithm> <signature> <data>)
 * @example
 * (openssl:verify (pub-key) "SHA256" (signature) "hello")
 * ; true
 */
Expr::register('openssl:verify', function($args) {
    $res = openssl_verify($args->get(4), $args->get(3), $args->get(1), get_signing_algorithm($args->get(2)));
	if ($res === false || $res === -1)
        throw new Error(openssl_error_string());
    return $res == 1;
});

/**
 * Encrypts a data block with a public key. Use `openssl:private-decrypt` to decrypt the data.
 * @code (`openssl:public-encrypt` <public-key> <data>)
 */
Expr::register('openssl:public-encrypt', function($args) {
    if (!openssl_public_encrypt($args->get(2), $output, $args->get(1)))
        throw new Error(openssl_error_string());
    return $output;
});

/**
 * Decrypts a data block with a private key. Use `openssl:public-encrypt` to encrypt the data.
 * @code (`openssl:private-decrypt` <private-key> <encrypted-data>)
 */
Expr::register('openssl:private-decrypt', function($args) {
    if (!openssl_private_decrypt($args->get(2), $output, $args->get(1)))
        throw new Error(openssl_error_string());
    return $output;
});

/**
 * Encrypts a data block with a private key. Use `openssl:public-decrypt` to decrypt the data.
 * @code (`openssl:private-encrypt` <private-key> <data>)
 */
Expr::register('openssl:private-encrypt', function($args) {
    if (!openssl_private_encrypt($args->get(2), $output, $args->get(1)))
        throw new Error(openssl_error_string());
    return $output;
});

/**
 * Decrypts a data block with a public key. Use `openssl:private-encrypt` to encrypt the data.
 * @code (`openssl:public-decrypt` <public-key> <encrypted-data>)
 */
Expr::register('openssl:public-decrypt', function($args) {
    if (!openssl_public_decrypt($args->get(2), $output, $args->get(1)))
        throw new Error(openssl_error_string());
    return $output;
});

/**
 * Generates a shared secret for public value of remote and local DH or ECDH key.
 * @code (`openssl:derive` <private-key> <public-key> [key-length])
 * @example
 * (openssl:derive (priv-key) (pub-key))
 * ; (binary data)
 */
Expr::register('openssl:derive', function($args) {
    $shared = openssl_pkey_derive($args->get(2), $args->get(1), $args->{3} ?? 0);
    if ($shared === false)
        throw new Error(openssl_error_string());
    return $shared;
});

/**
 * @code (`openssl:encrypt` <cipher-algorithm> <secret> <iv> <data>)
 */
Expr::register('openssl:encrypt', function($args) {
    $algo = $args->get(1);
    if (!in_array($algo, openssl_get_cipher_methods()))
        throw new Error('Invalid cipher algorithm: ' . $algo);
    $output = openssl_encrypt($args->get(4), $algo, $args->get(2), OPENSSL_RAW_DATA, $args->get(3), $tag);
    if ($output === false)
        throw new Error(openssl_error_string());
    return new Map([ 'tag' => $tag, 'data' => $output ]);
});


/**
 * Extracts fields from a DER encoded string.
 * @code (`der:extract` <type='int'|'bits'|'octets'> <der-string|pem-string> [<int-size=0>])
 */
Expr::register('der:extract', function($args) {

    $value = $args->get(2);
    if (Text::startsWith($value, '-----BEGIN'))
        $value = get_der($value);
    $value = parse_der($value);

    $int_size = $args->{3} ?? 0;
    $out = '';

    // TODO: Try to check the authn stuff to add here integer padding appropriately.
    // https://stackoverflow.com/questions/55357924/asn-1-der-encoding-of-integers
    // https://luca.ntop.org/Teaching/Appunti/asn1.html
    // https://www.strozhevsky.com/free_docs/asn1_by_simple_words.pdf

    foreach ($value->get($args->get(1))->__nativeArray as $item) {
        if ($int_size != 0)
            $out .= Text::substring($item, -$int_size);
        else
            $out .= $item;
    }

    return $out;
});

/**
 * Converts a PEM encoded key to DER format.
 * @code (`der:get` <pem-string>)
 */
Expr::register('der:get', function($args) {
    return get_der($args->get(1));
});

/**
 * Parses a DER encoded data and returns a map with 'int', 'bits', 'octets' fields.
 * @code (`der:parse` <der-string|pem-string>)
 */
Expr::register('der:parse', function($args) {
    $value = $args->get(1);
    if (Text::startsWith($value, '-----BEGIN'))
        $value = get_der($value);
    return parse_der($value);
});






Expr::register('asn1:int', function($args) {
    $out = '';
    for ($i = 1; $i < $args->length; $i++)
        $out .= asn1_encode(0x02, asn1_int($args->get($i)));
    return $out;
});

Expr::register('asn1:octets', function($args) {
    $out = '';
    for ($i = 1; $i < $args->length; $i++)
        $out .= asn1_encode(0x04, $args->get($i));
    return $out;
});

Expr::register('asn1:bits', function($args) {
    $out = '';
    $out .= asn1_encode(0x03, chr(Text::length($args->get(2))*8 - $args->get(1)).$args->get(2));
    return $out;
});

Expr::register('asn1:seq', function($args) {
    return asn1_encode(0x30, $args->slice(1)->join(''));
});

Expr::register('asn1:ctx', function($args) {
    return asn1_encode(0xA0, $args->slice(1)->join(''));
});

Expr::register('asn1:obj', function($args) {
    $out = '';
    for ($i = 1; $i < $args->length; $i++)
        $out .= asn1_encode(0x06, $args->get($i));
    return $out;
});

Expr::register('asn1:arr', function($args) {
    return asn1_encode(0x30, $args->slice(1)->join(''));
});

Expr::register('asn1:oid', function($args) {
    $a = (int)($args->get(1));
    $b = (int)($args->get(2));
    $data = chr( $a*40 + $b );
    $data .= asn1_int7_array($args->__nativeArray, 3);
    return asn1_encode(0x06, $data);
});
