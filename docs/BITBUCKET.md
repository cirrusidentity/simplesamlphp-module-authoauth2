**Table of Contents**

- [Bitbucket as authsource](#bitbucket-as-authsource)
- [Usage](#usage)
- [Creating Bitbucket OAuth Client](#creating-bitbucket-oauth-client)

# Bitbucket as authsource

Bitbucket recommends using OAuth2 and their apis. Bitbucket apis return data in a
json format and require additional API calls to get an email address. You need to use the
`authoauth2:BitbucketAuth` authsource since Bitbucket doesn't conform
the expected OIDC/OAuth pattern.

# Usage

```php
   'bitbucket' => [
        'authoauth2:BitbucketAuth',
        'clientId' => $apiKey,
        'clientSecret' =>  $apiSecret,
        // Adjust the scopes: default is to request email and account
        //'scopes' => ['account', 'email'],
    ],
```

# Creating Bitbucket OAuth Client

Bitbucket provides [documentation](https://confluence.atlassian.com/bitbucket/oauth-on-bitbucket-cloud-238027431.html). Follow the section related to 'Create a consumer' to create an OAuth consumer.
You will need to add the correct Callback URL to your OAuth2 client in the Bitbucket console. Use a URL of the form below, and set hostname, SSP_PATH and optionally port to the correct values.

https://hostname/SSP_PATH/module.php/authoauth2/linkback.php

You will then need to change your `authsource` configuration to match the example usage above.

On your idp side you may need to use `bitbucket2name` attribute mapping from this module.

```php
        // Convert bitbucket names to ldap friendly names
        10 => array(
            'class' => 'core:AttributeMap',
            'authoauth2:bitbucket2name'
        ),
```
