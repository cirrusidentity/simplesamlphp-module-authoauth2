<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Google as an AuthSource](#google-as-an-authsource)
- [Recommended Config](#recommended-config)
- [Resitricting home domain](#resitricting-home-domain)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Google as an AuthSource

Google provides both OIDC and Google Plus endpoints for learning about
a user.  The OIDC endpoints require fewer client API permissions and
return data in a standardized format. The Google Plus endpoints can
return more data about a user but require Goolge Plus permissions and
return data in a Google specific format.

You can also choose between using the generic OAuth/OIDC implementation or using
a [Google specific library](https://github.com/thephpleague/oauth2-google/).

# Recommended Config

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

# Resitricting home domain

If you want to restrict the home domain of a user you can pass the
`hd` query parameter to Google.  You **must** ensure the `hd` value
returned from Google matches what you expect - a user could remove the
`hd` from the browser flow and login with any account.

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


