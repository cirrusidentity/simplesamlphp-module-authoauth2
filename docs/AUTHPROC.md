# AUTHPROC support

In SimpleSAMLphp (SSP) there is an API where you can do something after authentication is complete.
Authentication processing filters (AuthProc filters) postprocess authentication information received from the
authentication sources.

However, if an authsource is not a regular SAML SP (or IdP), the authproc filters are not executed.

The `authoauth2` module provides a way to postprocess the authentication information
similar to regular SAML SPs. The module provides its own 'authproc.oauth2' configuration option for that purpose.
Its implementation is influenced by the SSP OIDC module.

The primary use case focuses on attribute manipulation following authentication to ensure that the attributes
of an OAuth 2.0/OpenID Connect (OIDC) login mirror those of a regular SAML Service Provider (SP).
This consistency allows users to log in using different authentication sources while maintaining
uniform attributes across the board.

## Limitations

Currently, the 'authoauth2' module only supports basic filter use cases like attribute handling, logging or similar.
Filters that add additional authentication steps, e.g. by showing a page to the user, are not supported.

## Usage

Add the 'authproc.oauth2' config option in the same way as described in the
[Auth Proc documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-authproc.html).
In addition you need to activate authproc filtering by setting the `authProcStrategyClass`.
The default implementation would be `authoauth2:AuthProcStrategy`

## Example configuration

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
        
        'authProcStrategyClass' => 'authoauth2:AuthProcStrategy',
        'authproc.oauth2' => [
            10 => [
                'class' => 'core:AttributeAdd',
                'groups' => ['user', 'members'],
            ],
        ]
    ]
];
```

## Links

- See https://simplesamlphp.org/docs/stable/simplesamlphp-authproc.html for the regular SSP authproc documentation.
- authproc.oidc implementation: https://github.com/simplesamlphp/simplesamlphp-module-oidc#auth-proc-filters
