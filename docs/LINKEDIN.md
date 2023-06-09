<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [LinkedIn as authsource](#linkedin-as-authsource)
  - [Enabling OIDC in your LinkedIn App](#enabling-oidc-in-your-linkedin-app)
- [Usage](#usage)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# LinkedIn as authsource

The `LinkedInV2Auth` authsource has been deprecated, and we now recommend the use of OIDC, which is enabled in the LinkedIn developer portal via their [Sign In with LinkedIn V2](https://learn.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/sign-in-with-linkedin-v2#what-is-openid-connect) product. Use of OIDC facilitates the use of standard configuration patterns and claims endpoints.

## Enabling OIDC in your LinkedIn App

OIDC can be enabled in your existing LinkedIn App by simply adding **Sign In with LinkedIn v2** to your app's products. See the [Cirrus Identity Blog article](https://blog.cirrusidentity.com/enabling-linkedins-oidc-authentication) for details.

# Usage

```php
   'linkedin' => [
        'authoauth2:OAuth2',
        'template' => 'LinkedInOIDC',
        'clientId' => $apiKey,
        'clientSecret' =>  $apiSecret,
        // Adjust the scopes: default is to request 'openid' (required),
        // 'profile' and 'email'
        // 'scopes' => ['openid', 'profile'],
   ]
```
