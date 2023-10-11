# PKCE support

PKCE (Proof Key for Code Exchange) is an extension to the OAuth2 protocol that is used to secure the authorization code flow against CSRF (cross site request forgery) and code injection attacks.
PKCE is recommended in almost all OAuth use cases. Some servers or operators require the clients to use PKCE.

## Usage

Activating PKCE with the SSP authoauth2 module involves the subsequent steps:
- enable PKCE by setting the `pkceMethod` configuration key to a valid value (`S256` or `plain` (not recommended))
  - you could use the constants from the underlying `league/oauth2-client` library:
    - `League\OAuth2\Client\Provider\AbstractProvider::PKCE_METHOD_S256` or
    - `League\OAuth2\Client\Provider\AbstractProvider::PKCE_METHOD_PLAIN`
- define a PkceStrategy class, that is responsible for storing and retrieving the proof key. The default implementation stores the proof key in the session. You can use the `PkceSessionStrategy` class for this approach.

### Example configuration

Below is an example demonstrating how to configure the `authoauth2` module for PKCE with the `S256` method and session storage strategy:

```php
// config/authsources.php

$config = [
    'my-oidc-auth-source' => [
        'authoauth2:OpenIDConnect',

        'issuer' => 'https://my-issuer',
        'clientId' => 'client-id',
        'clientSecret' => 'client-secret',
        
        // activate PKCE with the S256 method and session storage strategy
        'pkceMethod' => 'S256',
        'pkceStrategyClass' => 'authoauth2:PkceSessionStrategy',
    ]
];
```

## Links

- See https://github.com/thephpleague/oauth2-client/blob/master/docs/usage.md#authorization-code-grant-with-pkce
  for implementation notes of the underlying library.
- RFC 7636 for PKCE: https://datatracker.ietf.org/doc/html/rfc7636
