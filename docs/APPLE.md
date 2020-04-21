**Table of Contents**

- [Apple as authsource](#apple-as-authsource)
- [Usage](#usage)
- [Creating Apple OAuth Client](#creating-apple-oauth-client)

# Apple as authsource

Apple provides own solution for Sign in with Apple, which is very similar to OAuth2, but without /userinfo endpoint
You need to use the `authoauth2:AppleAuth` authsource since Apple doesn't conform
the expected OIDC/OAuth pattern.



# Usage

```php
   'apple' => [
        'authoauth2:AppleAuth',
        'clientId' => 'CLIENT_ID',
        'clientSecret' => 'CLIENT_SECRET',
        'redirectUri' => 'REDIRECT_URI',
        //scopes: Only email is available
    ],
```

# Creating Apple OAuth Client

Apple provides [documentation](https://developer.apple.com/documentation/sign_in_with_apple/).
You will need to add the correct Callback URL to your OAuth2 client in the Apple console. Use a URL of the form below, and set hostname, SSP_PATH and optionally port to the correct values.

https://hostname/SSP_PATH/module.php/authoauth2/linkback.php

You will then need to change your `authsource` configuration to match the example usage above.

On your idp side you may need to use `apple2name` attribute mapping from this module.

```php
        // Convert apple names to ldap friendly names
        // apple.sub => uid, apple.email => mail
        10 => array(
            'class' => 'core:AttributeMap',
            'authoauth2:apple2name'
        ),
```
