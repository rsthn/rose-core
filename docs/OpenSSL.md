[&laquo; Go Back](./README.md)
# OpenSSL


### (`pem:encode` \<label> \<data>)
Wraps the given buffer in a PEM encoded block with the specified label.

### (`openssl:new` \<algorithm> \<key-type> [bits])
Creates a new private key using the specified algorithm and key type.

### (`openssl:import-private` \<data>)
Loads a private key (PEM format) from the specified data buffer.

### (`openssl:import-public` \<data>)
Loads a public key (PEM format) from the specified data buffer.

### (`openssl:export-private` \<pkey>)
Export the private key as a PEM encoded string.

### (`openssl:export-public` \<pkey>)
Export the public key as a PEM encoded string.

### (`openssl:get-der` \<pem-string>)
Converts a PEM encoded key to DER format.

### (`openssl:get-raw` \<pem-string> [\<int-size=0>])
Converts a DER string to raw DER format.

### (`openssl:sign` \<algorithm> \<private-key> \<data>)
Signs a data block using a private key and returns a signature in DER format.

### (`openssl:random-bytes` \<length>)
Generates a pseudo-random string of bytes.

### (`openssl:derive` \<private-key> \<public-key> [key-length])
Generates a derived key from a shared secret.

### (`openssl:encrypt` \<algorithm> \<secret> \<nonce> \<data>)
Generates a derived key from a shared secret.
