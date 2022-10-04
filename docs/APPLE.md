
# Apple:
* only returns name on first login
* includes email in the id_token
  * Apple for School accounts may not have email at all.
* `sub` (user id) is tied to your application (or the apps you group together in Apple's developer console)

# Testing with Apple
To test authenticating as if it is your first login:
1. Visit https://appleid.apple.com/account/manage
2. Click 'Sign in with Apple'
3. Click the app you want to 'Stop using Sign in with Apple'
4. On your next login to that app you will be prompted to choose what email to release and your name
will be release on initial login.

# Configuration

Apple integration uses the provider from this package `patrickbussmann/oauth2-apple:~0.2.10`.
You must install it, and provide these 4 settings

```php
        'authoauth2:OAuth2',
        'template' => 'AppleLeague',
        'teamId' => $appleTeamId,
        'clientId' => $apiKey,
        'keyFileId' => $privateKeyId,
        'keyFilePath' => $privateKeyPath,

        // Other settings that are provider specific (like logging) may or may not work on the Apple provider
```

If you are using this with a SAML IdP then you can map the Apple attributes to regular friendly names in your `authproc` section of `saml20-idp-hosted.php`.

```php
    // saml20-idp-hosted.php
$metadata['myEntityId'] = array(
    'authproc' => array(
        // Convert oidc names to ldap friendly names
        90 => array('class' => 'core:AttributeMap',  'authoauth2:apple2name'),
    ),
   // other IdP config options
)
```


# POC Testing

Testing locally with a docker image to prove out configuration. You won't need this for your setup.

```
# Run ssp image
docker run --name ssp-apple-oidc \
  --mount type=bind,source="$(pwd)/samples/apple/authsources.php",target=/var/simplesamlphp/config/authsources.php,readonly \
  --mount type=bind,source="$(pwd)/.test-secrets/apple.p8",target=/var/simplesamlphp/cert/apple.p8,readonly \
  -e SSP_ADMIN_PASSWORD=secret1 \
  -e SSP_LOG_LEVEL=7 \
   -p 443:443 cirrusid/simplesamlphp

# Then get shell on image to install some stuff
docker exec -it ssp-apple-oidc bash
cd /var/simplesamlphp/
# Use a fork of the oauth2-apple module to get `sub` in the resource owner.
composer config repositories.apple vcs https://github.com/pradtke/oauth2-apple.git
composer require cirrusidentity/simplesamlphp-module-authoauth2 patrickbussmann/oauth2-apple:dev-owner_to_array_fix



# In theory composer can be handle at run time, but for me composer was complaining of a full disk if it ran during container init
docker run --name ssp-apple-oidc \
  --mount type=bind,source="$(pwd)/samples/apple/authsources.php",target=/var/simplesamlphp/config/authsources.php,readonly \
  --mount type=bind,source="$(pwd)/.test-secrets/apple.p8",target=/var/simplesamlphp/cert/apple.p8,readonly \
  -e SSP_ADMIN_PASSWORD=secret1 \
  -e COMPOSER_REQUIRE="cirrusidentity/simplesamlphp-module-authoauth2 patrickbussmann/oauth2-apple" \
  -e SSP_ENABLED_MODULES="authoauth2" \
   -p 443:443 cirrusid/simplesamlphp
```

Edit your `/etc/hosts` file to make `apple.test.idpproxy.illinois.edu` route to local host and then visit
 `https://apple.test.idpproxy.illinois.edu/simplesaml/module.php/core/authenticate.php?as=appleTest` to
initiate a login to Apple. Non-secret values such as keyId and teamId