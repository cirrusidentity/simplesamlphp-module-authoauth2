









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
composer require cirrusidentity/simplesamlphp-module-authoauth2 patrickbussmann/oauth2-apple



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