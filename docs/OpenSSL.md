[&laquo; Go Back](./README.md)
# OpenSSL


### (`pem:encode` \<label> \<data>)
Wraps the given buffer in a PEM encoded block with the specified label.

### (`openssl:curves`)
Returns a list of supported curves.
```lisp
(openssl:curves)
; ["prime192v1","secp224r1","prime256v1",...]
```

### (`openssl:ciphers`)
Returns a list of supported ciphers.
```lisp
(openssl:ciphers)
; ["prime192v1","secp224r1","prime256v1",...]
```

### (`openssl:random-bytes` \<length>)
Generates a pseudo-random string of bytes.
```lisp
(openssl:random-bytes 16)
; (binary data)
```

### (`openssl:create` \<DSA|DH|RSA|EC> [curve-name] [bits])
Creates a new private key of the specified type. Returns `pkey` object. Note that when using EC keys, the curve name is
required, see `openssl:curves` for a list of supported curves.
```lisp
(openssl:create "EC" "prime256v1")
; (pkey)
```

### (`openssl:bits` \<pkey>)
Returns the number of bits in the key.
```lisp
(openssl:bits (pkey))
; 4096
```

### (`openssl:export-private` \<pkey>)
Export the private key as a PEM encoded string.
```lisp
(openssl:export-private (pkey))
; "-----BEGIN ...
```

### (`openssl:export-public` \<pkey>)
Export the public key as a PEM encoded string.
```lisp
(openssl:export-public (pkey))
; "-----BEGIN ...
```

### (`openssl:import-private` \<pem-data>)
Loads a private key (PEM format) from the specified data buffer.
```lisp
(openssl:import-private "-----BEGIN ...")
; (pkey)
```

### (`openssl:import-public` \<pem-data>)
Loads a public key (PEM format) from the specified data buffer.
```lisp
(openssl:import-public "-----BEGIN ...")
; (pkey)
```

### (`openssl:error`)
Returns the last error message (if any) or empty string.
```lisp
(openssl:error)
; "error:0D07207B:asn1 encoding routines:ASN1_get_object:header too long"
```

### (`openssl:sign` \<private-key> \<algorithm> \<data>)
Signs a data block using a private key and returns a signature in DER format.
<br/>Supported signing algorithms are: DSS1, SHA1, SHA224, SHA256, SHA384, SHA512, RMD160, MD5, MD4, and MD2.
```lisp
(openssl:sign (priv-key) "SHA256" "hello")
; (binary data)
```

### (`openssl:verify` \<public-key> \<algorithm> \<signature> \<data>)
Verifies a signature (DER format) of a data block using a public key. See `openssl:sign` for supported signing algorithms.
```lisp
(openssl:verify (pub-key) "SHA256" (signature) "hello")
; true
```

### (`openssl:public-encrypt` \<public-key> \<data>)
Encrypts a data block with a public key. Use `openssl:private-decrypt` to decrypt the data.

### (`openssl:private-decrypt` \<private-key> \<encrypted-data>)
Decrypts a data block with a private key. Use `openssl:public-encrypt` to encrypt the data.

### (`openssl:private-encrypt` \<private-key> \<data>)
Encrypts a data block with a private key. Use `openssl:public-decrypt` to decrypt the data.

### (`openssl:public-decrypt` \<public-key> \<encrypted-data>)
Decrypts a data block with a public key. Use `openssl:private-encrypt` to encrypt the data.

### (`openssl:derive` \<private-key> \<public-key> [key-length])
Generates a shared secret for public value of remote and local DH or ECDH key.
```lisp
(openssl:derive (priv-key) (pub-key))
; (binary data)
```

### (`openssl:encrypt` \<cipher-algorithm> \<secret> \<iv> \<data>)

### (`der:extract` \<type='int'|'bits'|'octets'> \<der-string|pem-string> [\<int-size=0>])
Extracts fields from a DER encoded string.

### (`der:get` \<pem-string>)
Converts a PEM encoded key to DER format.

### (`der:parse` \<der-string|pem-string>)
Parses a DER encoded data and returns a map with 'int', 'bits', 'octets' fields.
