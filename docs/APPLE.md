
# Testing with Apple

Apple:
* only returns name on first login
* includes email in the id_token
** Apple for School accounts may not have email at all.
* `sub` (user id) is tied to your application (or the apps you group together in Apple's developer console)

To test authenticating as if it is your first login:
1. Visit https://appleid.apple.com/account/manage
2. Click 'Sign in with Apple'
3. Click the app you want to 'Stop using Sign in with Apple'
4. On your next login to that app you will be prompted to choose what email to release and your name
will be release on initial login.

# POC Testing

Testing locally with a docker image

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