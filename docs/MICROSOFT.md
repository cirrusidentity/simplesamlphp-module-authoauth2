<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Microsoft as an AuthSource](#microsoft-as-an-authsource)
- [Usage](#usage)
  - [Recommended Config](#recommended-config)
  - [Gotchas](#gotchas)
- [Creating Microsoft Converged app](#creating-microsoft-converged-app)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Microsoft as an AuthSource

Microsoft provides several APIs for logging users in. There is Graph v1 and v2, OpenID Connect and Live Connect.
Live Connect is being deprecated. 
The Graph apis allow you to specify if any user (both Consumer or Azure AD) can log in, just Consumer, just Azure AD or
just a specific Azure AD tenant.


# Usage
## Recommended Config

We ended up creating a sub class of the generic `authsource` called `MicrosoftHybridAuth`. This is because the OIDC `id_token`
and the response from the graph api contain different sets of attributes. For example for consumer users (e.g. hotmail or outlook.com)
the `id_token` will provide email but not first name and last name, while the graph api will provide first name and last name
but not email. The subclass uses the profile data from the graph api and the email and full name from the OIDC `id_token`



```php
   //authsources.php
   'microsoft' => [
       'authoauth2:MicrosoftHybridAuth',
       'clientId' => 'my-client',
       'clientSecret' => 'eyM-mysecret'
   ],
```

and if are using this with a SAML IdP then you can map the OIDC attributes to regular friendly names in your `authproc` section of `saml20-idp-hosted.php`.

```php
    // saml20-idp-hosted.php
$metadata['myEntityId'] = array(			
    'authproc' => array(
        // Convert oidc names to ldap friendly names
        90 => array('class' => 'core:AttributeMap',  'authoauth2:microsoft2name'),
    ),
   // other IdP config options
)
```
## Gotchas

* Azure AD only seems to return an email address if the user has an O365 subscription.
* The Graph OIDC user info endpoint only returns a targeted `sub` id. The `id_token` has
to be inspected to find the email address.


# Creating Microsoft Converged app

Visit https://apps.dev.microsoft.com and add a converged app.



