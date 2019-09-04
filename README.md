<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Installation](#installation)
  - [Changelog](#changelog)
- [Usage](#usage)
  - [Redirect URI](#redirect-uri)
  - [Provider specific Tips](#provider-specific-tips)
  - [Generic Usage](#generic-usage)
  - [Provider Specific Usage](#provider-specific-usage)
  - [Template Specific Usage](#template-specific-usage)
  - [Samples](#samples)
    - [Generic Facebook](#generic-facebook)
    - [Generic Amazon](#generic-amazon)
    - [Generic Google](#generic-google)
    - [Provider Specific Google](#provider-specific-google)
- [Debugging](#debugging)
  - [HTTP Logging](#http-logging)
  - [Using Curl](#using-curl)
- [Migrating from an existing auth module](#migrating-from-an-existing-auth-module)
  - [Calling OAuth2ResponseHandler](#calling-oauth2responsehandler)
- [Development](#development)
  - [Code style](#code-style)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

[![Build Status](https://travis-ci.org/cirrusidentity/simplesamlphp-module-authoauth2.svg?branch=master)](https://travis-ci.org/cirrusidentity/simplesamlphp-module-authoauth2)
SimpleSAMLphp OAuth2 Authentication Source Module

This is a generic module for authentication against an OAuth2 or OIDC server. It performs the `authorization_code` flow
and then uses the resulting access token to query an endpoint to get the user's attributes. It is a wrapper around the
excellent [PHP League OAuth2 Client](http://oauth2-client.thephpleague.com/).

# Installation

The module can be installed with composer.

    composer require cirrusidentity/simplesamlphp-module-authoauth2

Or you can install the latest from master

    composer require cirrusidentity/simplesamlphp-module-authoauth2:dev-master

If you install into a tar ball distribution of SSP then composer, by default, will also install all `dev` dependencies for SSP and this module. This can be a long list.
If you prefer not having dev dependencies installed, then you can use.

    composer require --no-update cirrusidentity/simplesamlphp-module-authoauth2 && composer update --no-dev cirrusidentity/simplesamlphp-module-authoauth2

## Changelog

[View the change log](CHANGELOG.md)

# Usage

The generic OAuth2 client is configured with 
* the OAuth2 server endpoints
* the client secret/id 
* optional parameters for scope
* optional query parameters for the authorization url

## Redirect URI

Almost all OAuth2/OIDC providers will require you to register a redirect URI. Use a url of the form below, and set hostname, SSP_PATH and optionally port to the correct values.

    https://hostname/SSP_PATH/module.php/authoauth2/linkback.php

## Provider specific Tips

 * [Bitbucket](/docs/BITBUCKET.md)
 * [Google](/docs/GOOGLE.md)
 * [LinkedIn](/docs/LINKEDIN.md)
 * [Microsoft](/docs/MICROSOFT.md)

## Generic Usage

Generic usage provides enough configuration parameters to use with any OAuth2 or OIDC server.
```php
       'oauth2' => array(
              'authoauth2:OAuth2',
              // *** Required for all integrations ***
              'urlAuthorize' => 'https://www.example.com/oauth2/authorize',
              'urlAccessToken' => 'https://www.example.com/oauth2/token',
              'urlResourceOwnerDetails' => 'https://api.example.com/userinfo',
              // You can add query params directly to urlResourceOwnerDetails or use urlResourceOwnerOptions.
              'urlResourceOwnerOptions' => [
                 'fields' => 'id,name,first_name,last_name,email'
              ],
              // allow fields from token response to be query params on resource owne details request
              'tokenFieldsToUserDetailsUrl' => [
                   'fieldName' => 'queryParamName',
                   'access_token' => 'access_token',
                   'user_id' > 'user_id',
              ],
              // *** Required for most integrations ***
              // Test App.
              'clientId' => '133972730583345',
              'clientSecret' => '36aefb235314bad5df075363b79cbbcd',
              // *** Optional ***
              // Custom query parameters to add to authorize request
              'urlAuthorizeOptions' => [
                  'prompt' => 'always',
              ],
              // Scopes to request
              'scopes' = ['email', 'profile'],
              'scopeSeparator' => ' ',
              // Customize redirect, if you don't want to use the standard /module.php/authoauth2/linkback.php
              'redirectUri' => 'https://myapp.example.com/callback',
              // See League\OAuth2\Client\Provider\GenericProvider for more options
              
              // Guzzle HTTP config
              // Wait up to 3.4 seconds for Oauth2 servers to respond
              //http://docs.guzzlephp.org/en/stable/request-options.html#timeout 
              'timeout' => 3.4,
              // http://docs.guzzlephp.org/en/stable/request-options.html#proxy
              'proxy' => [
              ],
              
              // All attribute keys will have this prefix
              'attributePrefix' => 'someprefix.'
              // Enable logging of request/response. This *will* leak you client secret and tokens into the logs
              'logHttpTraffic' => true, //default is false
              'logMessageFormat' => 'A Guzzle MessageFormatter format string', // default setting is sufficient for most debugging
              'logIdTokenJson' => true, //default false. Log the json in the ID token.
              
          ),
```

## OpenID Connect Usage

For providers that support OpenID Connect discovery protocol the configuration can be simplified a bit. Only the issuer url, client id and client secret are required..
```php
       'openidconnect' => array(
              'authoauth2:OpenIDConnect',
              // *** Required for all integrations ***
              'issuer' => 'https://www.example.com', # e.g https://accounts.google.com
              'clientId' => '133972730583345',
              'clientSecret' => '36aefb235314bad5df075363b79cbbcd',

              // Most Optional settings for OAuth2 above can be used
              // *** New Optional ***
              // Customize post logout redirect, if you don't want to use the standard /module.php/authoauth2/loggedout.php
              'postLogoutRedirectUri' => 'https://myapp.example.com/loggedout'
          ),
```

If your OP supports front channel single logout, you can configure `https://hostname/SSP_PATH/module.php/authoauth2/logout.php?authSource=AUTHSOURCE` where `AUTHSOURCE` is the id of your authsource in the authsources configuration (`openidconnect` in the example above)

## Provider Specific Usage

There are numerous [Offical](http://oauth2-client.thephpleague.com/providers/league/) and [Third-Party](http://oauth2-client.thephpleague.com/providers/thirdparty/) providers
that you can use instead of the generic OAuth2 provider. Using one of those providers can simplify the configurations.

To use a provider you must first install it `composer require league/oauth2-some-provider`
and then configure it.

```php
    'providerExample' => array(
        // Must install correct provider with: composer require league/oauth2-example
        'authoauth2:OAuth2',
        'providerClass' => 'League\OAuth2\Client\Provider\SomeProvider',
        'clientId' => 'my_id',
        'clientSecret' => 'my_secret',
    ),

```

## Template Specific Usage

For some OAuth2 providers the generic endpoint configurations are already defined in `ConfigTemplate`. You can reference
this to reduce the amount of typing needed in your authsource

```php
    'google' => array_merge(\SimpleSAML\Module\authoauth2\ConfigTemplate::GoogleOIDC, [
        'clientId' => '3473456456-qqh0n4b5rrt7vkapgk3e4osre40.apps.googleusercontent.com',
        'clientSecret' => 'shhh',
        'urlAuthorizeOptions' => [
            'hd' => 'example.com',
        ],
    ]),
```

or by using the template option

```php
    'providerExample' => array(
        'authoauth2:OAuth2',
        'template' => 'GoogleOIDC',
        'clientId' => 'my_id',
        'clientSecret' => 'my_secret',
    ),
```

## Samples

Several of these samples show how to configure the generic endpoint to authenticate against Facebook, Amazon and Google, etc. 
In a lot of cases you can use a template from `ConfigTemplate` to make the configuration cleaner or you can use a provider specific implementations of the base OAuth2 client.

### Generic Facebook

You can use the Facebook template `'template' => 'Facebook',` and then provide just the `clientId` and `clientSecret` to
have a cleaner looking config

```php
    'genericFacebookTest' => array(
        'authoauth2:OAuth2',
        // *** Facebook endpoints ***
        'urlAuthorize' => 'https://www.facebook.com/dialog/oauth',
        'urlAccessToken' => 'https://graph.facebook.com/oauth/access_token',
        // Add requested attributes as fields
        'urlResourceOwnerDetails' => 'https://graph.facebook.com/me?fields=id,name,first_name,last_name,email',
        // *** My application ***
        'clientId' => '13397273example',
        'clientSecret' => '36aefb235314baexample',
        'scopes' => 'email',
        // *** Optional ***
        // Custom query parameters to add to authorize request
        'urlAuthorizeOptions' => [
            // Force use to reauthenticate
            'auth_type' => 'reauthenticate',
        ],
    ),
```

### Generic Amazon
```php
    'genericAmazonTest' => array(
        'authoauth2:OAuth2',
        // *** Amazon Endpoints ***
        'urlAuthorize' => 'https://www.amazon.com/ap/oa',
        'urlAccessToken' => 'https://api.amazon.com/auth/o2/token',
        'urlResourceOwnerDetails' => 'https://api.amazon.com/user/profile',
        // *** My application ***
        'clientId' => 'amzn1.application-oa2-client.94d04152358dexample',
        'clientSecret' => '8681bdd290df87example',
        'scopes' => 'profile',
    ),
```

### Generic Google

View [full Google](/docs/GOOGLE.md) instructions.


```php
'genericGoogleTest' => array(
        'authoauth2:OAuth2',
        // *** Google Endpoints ***
        'urlAuthorize' => 'https://accounts.google.com/o/oauth2/auth',
        'urlAccessToken' => 'https://accounts.google.com/o/oauth2/token',
        'urlResourceOwnerDetails' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        // *** My application ***
        'clientId' => '685947170891-exmaple.apps.googleusercontent.com',
        'clientSecret' => 'wV0FdFs_example',
        'scopes' =>  array(
            'openid',
            'email',
            'profile'
        ),
        'scopeSeparator' => ' ',
    ),
 ```
 
 ### Provider Specific Google
 
 View [full Google](/docs/GOOGLE.md) instructions.

 
 ```php
    'googleProvider' => array(
        // Must install correct provider with: composer require league/oauth2-google
        'authoauth2:OAuth2',
        'providerClass' => 'League\OAuth2\Client\Provider\Google',
        'clientId' => 'client_id',
        'clientSecret' => 'secret',
    ),
```

# Debugging

## HTTP Logging

You can enable http logging with the `logHttpTraffic` flag and optionally customize the 
format with `logMessageFormat`. See Guzzle's `MessageFormatter` class for how to define a
format string.

**security note:** enabling http logging will make your client secret and any returned access
or id tokens to appear in your logs.

## Using Curl

You can use `curl` on the command line to interact with the OAuth2/OIDC server.

**Get Code**

The first step is get a valid code AND ensure the module doesn't try to use it. 
This can be done by constructing an authorize url with an invalid state value but with all other params correct

https://as.example.com/openid/authorize?state=invalid-state&scope=openid&response_type=code&redirect_uri=https%3A%2F%2Fmyapp.example.com%2Fsimplesaml%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=my_client

If you log in with the above it will redirect you back to your SSP instance with an `authorization_code` (which you should capture) 
and then your SSP instance will report an error about an invalid state parameter. The state check happens prior to consuming the `authorization_code` so you know SSP hasn't used it.

**Get Access Token**

Now you can use the code and try to get an access token

```bash
curl -d "code=REPLACE" \
    -d "client_secret=my_secret" \
    -d "client_id=my_client" \
    -d "redirect_uri=https://myapp.example.com/simplesaml/module.php/authoauth2/linkback.php" \
    -d 'grant_type=authorization_code' \
   https://as.example.com/openid/token
```

**Get User Info**

Take the access token from above and call the user info endpoint

    curl -H "Authorization: Bearer $ACCESS_TOKEN" https://as.example.com/userInfo
 

# Migrating from an existing auth module

If you are migrating away from an existing auth module, such as `authfacebook` you will need to one of the following:
 * add this module's `authoauth2` redirect URI to the facebook app, or
 * override the `authoauth2` authsource's redirect URI to match the `authfacebook` uri (`https://myserver.com/module.php/authfacebook/linkback.php)` AND do one of the following
   * edit `/modules/authfacebook/www/linkback.php` to conditionally call `OAuth2ResponseHandler` (see below)
   * configure an Apache rewrite rule to change '/module.php/authfacebook/linkback.php' to '/module.php/authoauth2/linkback.php'
   * symlink or edit `/modules/authfacebook/www/linkback.php` to invoke the `/modules/authoauth2/www/linkback.php`
   

Some social providers support multiple login protocols and older SSP modules may use the non-OAuth2 version for login.
To migrate to this module you may need to make some application changes.
For example 
* the LinkedIn module uses oauth1 and you may need to make adjustments to your app to move to their oauth2 API
* moving from OpenID 2 Yahoo logins to OIDC Yahoo logins requires creating a Yahoo app.

## Calling OAuth2ResponseHandler

To migrate from an existing module you can adjust that modules `linkback`/redirect handler to conditionally use the `OAuth2ResponseHandler`
In the below example the code will use the `authoauth2` if that is what initiated the process else use the existing handler

```php
$handler = new \SimpleSAML\Module\authoauth2\OAuth2ResponseHandler();
if ($handler->canHandleResponse()) {
   $handler->handleResponse();
   return;
 } 
```

# Development

To perform some integration tests you can run the embedded
webserver. You may need to run `composer install` and then `phpunit` one time first to correctly
link the project into SSP's modules directory

```bash
export SIMPLESAMLPHP_CONFIG_DIR=$PWD/tests/config/
mkdir -p /tmp/ssp-log/
php -S 0.0.0.0:8732 -t $PWD/vendor/simplesamlphp/simplesamlphp/www &

```

Then visit http://abc.tutorial.stack-dev.cirrusidentity.com:8732/

Note: `*.tutorial.stack-dev.cirrusidentity.com` resolves to your local host.

`authsources.php` contains some preconfigured clients. The generic facebook and google ones should work as is.
The generic amazon one requires `https`. You can run use it and when Amazon redirects back to `https` change the url to
`http` to proceed. The google provider authsource requires you to install the google auth module first.

## Code style

Run `phpcs` to check code style

    ./vendor/bin/phpcs --standard=PSR2 lib/