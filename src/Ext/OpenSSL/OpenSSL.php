<?php

use Rose\Expr;
use Rose\Map;
use Rose\Arry;
use Rose\Regex;
use Rose\Text;

function parseDER ($data)
{
    $n = strlen($data);
    
    $state = 0;
    $next = 0;
    $a = 0;

    $der = new Map();
    $der->set('int', new Arry());

    for ($i = 0; $i < $n; $i++)
    {
        $val = ord($data[$i]);

        switch($state)
        {
            case 0: // Expecting: main sequence
                if ($val != 0x30) throw new RuntimeException('Expected main DER sequence');
                $state = 100;
                $next = 1;
                break;

            case 1: // Length of main sequence ready
                if ($a+$i != $n) throw new Exception('Invalid main sequence length ' . ($a+$i) . ' expected ' . $n);
                $state = 2;
                $i--;
                break;

            case 2: // Expecting: integer, sequence
                if ($val == 0x02) {
                    $state = 100;
                    $next = 20;
                    break;
                }

                $val = 2;
                throw new RuntimeException('Undefined DER structure: ' . Text::lpad(dechex($val), 2, '0') . 'h');

            case 20: // Length of integer ready
                $val = Text::substring($data, $i, $a);
                $der->get('int')->push($val);
                $state = 2;
                $i = $i + $a - 1;
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
        throw new RuntimeException('Invalid length');

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

/**
 * Wraps the given buffer in a PEM encoded block with the specified label.
 * @code (pem:encode <label> <data>)
 */
Expr::register('pem:encode', function($args) {
    $label = $args->get(1);
    $value = base64_encode($args->get(2));
    return "-----BEGIN ".$label."-----\n" . wordwrap($value, 64, "\n", true) . "\n-----END ".$label."-----\n";
});


/**
 * Creates a new private key using the specified algorithm and key type.
 * @code (openssl:new <algorithm> <key-type> [<bits>])
 */
Expr::register('openssl:new', function($args)
{
    $type = $args->get(2);
         if ($type === 'DSA') $type = OPENSSL_KEYTYPE_DSA;
    else if ($type === 'DH')  $type = OPENSSL_KEYTYPE_DH;
    else if ($type === 'RSA') $type = OPENSSL_KEYTYPE_RSA;
    else if ($type === 'EC')  $type = OPENSSL_KEYTYPE_EC;
    else throw new \Exception('Invalid key type: ' . $type);

    $config = [
        'curve_name' => $args->get(1),
        'private_key_type' => $type,
    ];

    if ($args->has(3))
        $config['private_key_bits'] = $args->get(3);

    $key = openssl_pkey_new($config);
    if ($key === false)
        throw new \Exception(openssl_error_string());
    return $key;
});

/**
 * Loads a private key (PEM format) from the specified data buffer.
 * @code (openssl:import-private <data>)
 */
Expr::register('openssl:import-private', function($args) {
    $result = openssl_pkey_get_private($args->get(1));
    if ($result === false)
        throw new \Exception(openssl_error_string());
    return $result;
});

/**
 * Loads a public key (PEM format) from the specified data buffer.
 * @code (openssl:import-public <data>)
 */
Expr::register('openssl:import-public', function($args) {
    $result = openssl_pkey_get_public($args->get(1));
    if ($result === false) {
        $s = '';
        while ($msg = openssl_error_string())
            $s .= $msg . "\n";
        throw new \Exception($s);
    }
    return $result;
});

Expr::register('openssl:error', function($args) {
    $s = '';
    while ($msg = openssl_error_string())
        $s .= $msg . "\n";
    return $s;
});

/**
 * Export the private key as a PEM encoded string.
 * @code (openssl:export-private <pkey>)
 */
Expr::register('openssl:export-private', function($args) {
    $output = '';
    return openssl_pkey_export($args->get(1), $output) ? $output : null;
});

/**
 * Export the public key as a PEM encoded string.
 * @code (openssl:export-public <pkey>)
 */
Expr::register('openssl:export-public', function($args) {
    $details = openssl_pkey_get_details($args->get(1));
    return $details['key'];
});

/**
 * Converts a PEM encoded key to DER format.
 * @code (openssl:get-der <pem-string>)
 */
Expr::register('openssl:get-der', function($args) {
    return base64_decode(Regex::_replace ('/-+BEGIN\s.+?-+|-+END\s.+?-+|\r|\n|\s|\t/', '', $args->get(1)));
});

/**
 * Converts a DER string to raw DER format.
 * @code (openssl:get-raw <pem-string> [<int-size=0>])
 */
Expr::register('openssl:get-raw', function($args) {
    $data = parseDER($args->get(1));
    
    $size = $args->has(2) ? $args->get(2) : 0;
    $out = '';

    foreach ($data->get('int')->__nativeArray as $item) {
        if ($size != 0)
            $out .= Text::substring($item, -$size);
        else
            $out .= $item;
    }

    return $out;
});

/**
 * Signs a data block using a private key and returns a signature in DER format.
 * @code (openssl:sign <algorithm> <private-key> <data>)
 */
Expr::register('openssl:sign', function($args) {
    $signature = '';
	if (!openssl_sign($args->get(3), $signature, $args->get(2), $args->get(1)))
        throw new \Exception(openssl_error_string());
    return $signature;
});

/**
 * XXXXXXXXXXXXXXXXXX
 * @code (openssl:parse-der <data>)
 */
Expr::register('openssl:parse-der', function($args) {
    return parseDER($args->get(1));
});

/**
 * Generates a pseudo-random string of bytes.
 * @code (openssl:random-bytes <length>)
 */
Expr::register('openssl:random-bytes', function($args) {
    return openssl_random_pseudo_bytes($args->get(1));
});

/**
 * Generates a derived key from a shared secret.
 * @code (openssl:derive <private-key> <public-key> [<key-length>])
 */
Expr::register('openssl:derive', function($args) {
    $shared = openssl_pkey_derive($args->get(2), $args->get(1), $args->has(3) ? $args->get(3) : 0);
    if ($shared === false)
        throw new \Exception(openssl_error_string());
    return $shared;
});

/**
 * Generates a derived key from a shared secret.
 * @code (openssl:encrypt <algorithm> <secret> <nonce> <data>)
 */
Expr::register('openssl:encrypt', function($args) {
    $tag = '';
    $output = openssl_encrypt($args->get(4), $args->get(1), $args->get(2), OPENSSL_RAW_DATA, $args->get(3), $output);
    return new Map([ 'tag' => $tag, 'data' => $output ]);
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
