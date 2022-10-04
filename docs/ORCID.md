# ORCID as an AuthSource

ORCID supports both OAuth 2.0 and OpenID Connect 1.0 for logging users in.

# Usage
## Recommended Config

We ended up creating a subclass of the generic `OpenIDConnect` called `OrcidOIDCAuth`. This is because ORCID supports [OpenID Connect Discovery](https://openid.net/specs/openid-connect-discovery-1_0.html) which allows for dynamic configuration of authorization/token endpoints. ORCID provides name attributes via the `id_token`, but the email address must be retrieved via a separate API call (which uses the `urlResourceOwnerEmail` property in the default `OrcidOIDC` config template).


```php
   //authsources.php
   'microsoft' => [
       'authoauth2:OrcidOIDCAuth',
       'clientId' => 'my-client',
       'clientSecret' => 'eyM-mysecret'
   ],
```

If you are using this with a SAML IdP then you can map the standard OIDC attributes to regular friendly names in your `authproc` section of `saml20-idp-hosted.php`.

```php
    // saml20-idp-hosted.php
$metadata['myEntityId'] = array(			
    'authproc' => array(
        // Convert oidc names to ldap friendly names
        90 => array('class' => 'core:AttributeMap',  'authoauth2:oidc2name'),
    ),
   // other IdP config options
)
```


## Gotchas

* ORCID allows users to add multiple email addresses to their user profile, and each of these addresses can be configured to be released publically (or not). This is performed out-of-band via the ORCID website, **not** as part of the OAuth2/OIDC authorization process. Of these email addresses, one may be marked as "primary" (although the primary address does not necessarily have to be released by the user).
* The ORCID AuthSource will attempt to retrieve the primary email address (if visible) and return it in the `oidc.email` attribute. If none of the visible email addresses are marked as "primary", then the first email address returned is used. If no email addresses are visible, the `oidc.email` attribute will not be set.

# Creating ORCID Public API Client

Visit [https://orcid.org/developer-tools](https://orcid.org/developer-tools) to register an ORCID public API client. You must [create an ORCID ID](https://orcid.org/register) before registering a public API client.
