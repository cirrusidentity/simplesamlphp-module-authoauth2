<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Google as an AuthSource](#google-as-an-authsource)
- [Usage](#usage)
  - [Recommended Config](#recommended-config)
  - [Resitricting hosted domain](#resitricting-hosted-domain)
- [Creating Google OIDC Client](#creating-google-oidc-client)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Google as an AuthSource

Google provides both OIDC and Google Plus endpoints for learning about
a user.  The OIDC endpoints require fewer client API permissions and
return data in a standardized format. The Google Plus endpoints can
return more data about a user but require Goolge Plus permissions and
return data in a Google specific format.

You can also choose between using the generic OAuth/OIDC implementation or using
a [Google specific library](https://github.com/thephpleague/oauth2-google/).

# Usage
## Recommended Config

We recommend using the OIDC configuration with the generic implementation. This
requires the least configuration.


```php
   //authsources.php
   'google' => [
       'authoauth2:OAuth2',
       'template' => 'GoogleOIDC',
       'clientId' => 'myclient.apps.googleusercontent.com',
       'clientSecret' => 'eyM-mysecret'
   ],
```

and then you can map the ODIC attributes to regular friendly names in your `authprocs`.

```php
    'authproc' => array(
        // Convert oidc names to ldap friendly names
        90 => array('class' => 'core:AttributeMap',  'authoauth2:oidc2name'),
    ),
```

## Resitricting hosted domain

If you want to restrict the hosted domain of a user you can pass the
`hd` query parameter to Google.  You **must** ensure the `hd` value
returned from Google matches what you expect - a user could remove the
`hd` from the browser flow and login with any account.

TODO: Once https://github.com/thephpleague/oauth2-google/pull/54 is accepted into the oauth2-google project then
this check would be done automatically. This example would then need to be updated to use that project

```php
   // Using the generic provider
   'google' => [
       'authoauth2:OAuth2',
       'template' => 'GoogleOIDC',
       'clientId' => 'myclient.apps.googleusercontent.com',
       'clientSecret' => 'eyM-mysecret'
       'urlAuthorizeOptions' => [
           'hd' => 'cirrusidentity.com',
       ],
    ],
```

# Creating Google OIDC Client

Google provides [documentation](https://developers.google.com/identity/protocols/OpenIDConnect#appsetup). Follow the section related to 'Setting up OAuth 2.0' to setup an API project and create an OAuth2 client. If you intend to use the Google Plus API (instead of OIDC) than you must enable it from the API library in Google's developer console.

The section in the documentation about accessing the service, authentication and server flows are performed by this module.

You will need to add the correct redirect URI to your OAuth2 client in the Google console. Use a url of the form below, and set hostname, SSP_PATH and optionally port to the correct values.

    https://hostname/SSP_PATH/module.php/authoauth2/linkback.php

