# PKCE support

PKCE (Proof Key for Code Exchange) is an extension to the OAuth2 protocol that is used to secure the
authorization code flow against CSRF (cross site request forgery) and code injection attacks.
PKCE is recommended in almost all OAuth use cases. Some servers or operators require the clients to use PKCE.

## Usage

Enable PKCE by setting the `pkceMethod` configuration key to a valid method (only `S256` is recommended).
Note: `plain` is also a valid method, but not recommended, see the link to 'thephpleague/oauth2-client' below for details.

### Example configuration

Below is an example demonstrating how to configure the `authoauth2` module for PKCE:

```php
// config/authsources.php

$config = [
    'my-oidc-auth-source' => [
        'authoauth2:OpenIDConnect',

        'issuer' => 'https://my-issuer',
        'clientId' => 'client-id',
        'clientSecret' => 'client-secret',
        
        // activate PKCE with the S256 method
        'pkceMethod' => 'S256',
    ]
];
```

## Links

- See https://github.com/thephpleague/oauth2-client/blob/master/docs/usage.md#authorization-code-grant-with-pkce
  for implementation notes of the underlying library.
- RFC 7636 for PKCE: https://datatracker.ietf.org/doc/html/rfc7636
