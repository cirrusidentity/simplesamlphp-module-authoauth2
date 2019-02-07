<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [LinkedIn as authsource](#linkedin-as-authsource)
- [Usage](#usage)
- [Migrarting from OAuth v1 authlinkedin](#migrarting-from-oauth-v1-authlinkedin)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# LinkedIn as authsource

LinkedIn recommends using OAuth2 and their v2 apis. Their v1 apis and
OAuth1 endpoints are being shutdown. LinkedIn v2 apis return data in a
more complex format (json keys change based on language) and require
additional API calls to get an email address. You need to use the
`authoauth2:LinkedInV2Auth` authsource since LinkedIn doesn't conform
the expected OIDC/OAuth pattern.

# Usage

```php
   'linkedin' => [
        'authoauth2:LinkedInV2Auth',
        'clientId' => $apiKey,
        'clientSecret' =>  $apiSecret,
        // Adjust the scopes: default is to request email and liteprofile
        // 'scopes' => ['r_liteprofile'], 
    ],
```

# Migrating from OAuth v1 authlinkedin

The `authlinkedin` module bundled with most versions of SSP uses
deprecated OAuth v1 and v1 API endpoints.  To migrate to the new
LinkedIn API you will need to add a [redirect URI to your linkedin
application](https://docs.microsoft.com/en-us/linkedin/shared/authentication/authorization-code-flow?context=linkedin/consumer/context). The
redirect URI is

    https://hostname/SSP_PATH/module.php/authoauth2/linkback.php

You will then need to change your `authsource` configuration to match the example usage above.

On your IdP side you may need to use `linkedin2name` from this module rather than `authlinkedin`.

```php
        // Convert linkedin names to ldap friendly names
        10 => array('class' => 'core:AttributeMap',  'authoauth2:linkedin2name'),
```
There are some minor changes in user experience and consent which are outlined in [our blog post](https://blog.cirrusidentity.com/linkedin-user-interaction-changes).
