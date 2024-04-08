[&laquo; Go Back](./README.md)
# Crypto


### (`crypto:hash-list`)
Returns a list of available hash algorithms.
```lisp
(crypto:hash-list)
; ["md2","md4","md5","sha1","sha224", ...]
```

### (`crypto:hash` \<algorithm> \<data>)
Returns the hash of a string (hexadecimal).
```lisp
(crypto:hash "md5" "Hello, World!")
; 65a8e27d8879283831b664bd8b7f0ad4
```

### (`crypto:hash-bin` \<algorithm> \<data>)
Returns the hash of a string (binary).
```lisp
(crypto:hash-bin "md5" "Hello, World!")
; binary data
```

### (`crypto:hmac` \<algorithm> \<secret-key> \<data>)
Returns the HMAC of a string (hexadecimal).
```lisp
(crypto:hmac "sha256" "secret" "Hello, World!")
; fcfaffa7fef86515c7beb6b62d779fa4ccf092f2e61c164376054271252821ff
```

### (`crypto:hmac-binary` \<algorithm> \<secret-key> \<data>)
Returns the HMAC of a string (binary).
```lisp
(crypto:hmac-binary "sha256" "secret" "Hello, World!")
; binary data
```

### (`crypto:unique` \<length> [charset])
Generates a unique code using a cryptographically secure random number generator.
```lisp
(crypto:unique 16)
; If1uIctc_61vluui

(crypto:unique 16 "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@$")
; QjE5SbH8z1OBliBS
```
