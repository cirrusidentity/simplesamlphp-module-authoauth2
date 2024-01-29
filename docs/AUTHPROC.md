# AUTHPROC support

In SimpleSAMLphp (SSP) there is an API where you can do something after authentication is complete.
Authentication processing filters (AuthProc filters) postprocess authentication information received from the
authentication sources.

SSP provides built in support for running authproc on SAML SP and IdPs, while other protocols must add their own
support.

The `authoauth2` module provides a way to postprocess the authentication information
similar to regular SAML SPs. The module provides two ways to configure it:
 * Via 'authproc.oauth2' configuration option in `config.php`. These will run for all OAuth2 authsources
 * Via `authproc` configuration option on a oauth2 authsource. These filters will just run for that authsource.

## Supported AuthProc features

* Attribute manipulation
* User interaction (via redirects and authproc flow resumption)
* Generally any filter that does not require SAML metadata.

## Limitations

Some AuthProc filters rely on SAML metadata to function and are unlikely to work as expected.
For example, `saml:FilterScopes` looks at the allowed scopes in the SAML IdP's metadata and filters
attributes and this would not work in this module.

## Usage

Add `authproc` to you authsource or add the 'authproc.oauth2' config option to `config.php` to enable
for all OAuth2 authsources. See SSP's [Auth Proc documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-authproc.html).

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

        'authproc' => [
            20 => [
                'class' => 'preprodwarning:Warning'
            ],
            25 => [
                'class' => 'core:AttributeAdd',
                '%replace',
                'groups' => ['users', 'members'],
            ],
            // The authproc are run in order by key, not by order defined,
            // which means this authproc will run first and have its output overwritten by the
            // above authproc (number 25)
            15 => [
                'class' => 'core:AttributeAdd',
                '%replace',
                'groups' => ['should', 'be', 'replaced'],
            ],
        ]
    ]
];
```

## Links

- See https://simplesamlphp.org/docs/stable/simplesamlphp-authproc.html for the regular SSP authproc documentation.
