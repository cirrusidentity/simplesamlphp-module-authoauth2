<?php

$config = array(

    'genericFacebookTest' => array(
        'authoauth2:OAuth2',
        // *** Required for all integrations ***
        'urlAuthorize' => 'https://www.facebook.com/dialog/oauth',
        'urlAccessToken' => 'https://graph.facebook.com/oauth/access_token',
        'urlResourceOwnerDetails' => 'https://graph.facebook.com/me?fields=id,name,first_name,last_name,email',
        // *** Required for facebook ***
        // Test App
        'clientId' => '133972730583345',
        'clientSecret' => '36aefb235314bad5df075363b79cbbcd',
        // *** Optional ***
        // Custom query parameters to add to authorize request
        'urlAuthorizeOptions' => [
            'auth_type' => 'reauthenticate',
            // request email access
            'req_perm' => 'email',
        ],
    ),

    'genericAmazonTest' => array(
        'authoauth2:OAuth2',
        // *** Required for all***
        'urlAuthorize' => 'https://www.amazon.com/ap/oa',
        'urlAccessToken' => 'https://api.amazon.com/auth/o2/token',
        'urlResourceOwnerDetails' => 'https://api.amazon.com/user/profile',
        // *** required for amazon ***
        // Test App.
        'clientId' => 'amzn1.application-oa2-client.94d04152358d4f989473fecdf8553e25',
        'clientSecret' => '8681bdd290df87fe1eea2d821d7dadc39fd4f89e599dfaa8a50c5656aae16980',
        'scopes' => 'profile',
        // *** Optional ***
        // Allow changing the default redirectUri
        'redirectUri' => 'https://abc.tutorial.stack-dev.cirrusidentity.com:8732/module.php/authoauth2/linkback.php',
    ),

    'genericGoogleTest' => array(
        'authoauth2:OAuth2',
        // *** Required for all***
        'urlAuthorize' => 'https://accounts.google.com/o/oauth2/auth',
        'urlAccessToken' => 'https://accounts.google.com/o/oauth2/token',
        'urlResourceOwnerDetails' => 'https://www.googleapis.com/plus/v1/people/me/openIdConnect',
        // userinfo doesn't need need Google Plus API access
//        'urlResourceOwnerDetails' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        //'urlResourceOwnerDetails' => 'https://www.googleapis.com/plus/v1/people/me?fields=id,name',
        // *** required for google ***
        // Test App.
       'clientId' => '685947170891-0fcfnkkt6q0veqhvlpbr7a98i29p8rlf.apps.googleusercontent.com',
       'clientSecret' => 'wV0FdFs_KEF1oY7XcBGq2TzM',
         'scopes' =>  array(
            'openid',
            'email',
            'profile'
        ),
        'scopeSeparator' => ' ',
        // *** Optional ***
        // Allow changing the default redirectUri
    ),

   'googleTempate' => [
       'authoauth2:OAuth2',
       'template' => 'GoogleOIDC',
       // Client with Google Plus API access disabled
       'clientId' => '815042564757-2ek814rm61bjtih4tpar8qh0pkrciifc.apps.googleusercontent.com',
       'clientSecret' => 'eyM-J6cOa3FlhIeKtyd4nDX9'
   ],

    'googleTest' => array(
        // Must install correct provider with: composer require league/oauth2-google
        'authoauth2:OAuth2',
        'providerClass' => 'League\OAuth2\Client\Provider\Google',
        // *** required for google ***
        // Test App with Google Plus access
        'clientId' => '685947170891-0fcfnkkt6q0veqhvlpbr7a98i29p8rlf.apps.googleusercontent.com',
        'clientSecret' => 'wV0FdFs_KEF1oY7XcBGq2TzM',
    ),
    //OpenID Connect provider https://accounts.google.com
    'https://accounts.google.com' => array(
        'authoauth2:OpenIDConnect',

        // Scopes to request, should include openid
        'scopes' => ['openid', 'profile'],

        // Configured client id and secret
        'clientId' => '685947170891-0fcfnkkt6q0veqhvlpbr7a98i29p8rlf.apps.googleusercontent.com',
        'clientSecret' => 'wV0FdFs_KEF1oY7XcBGq2TzM',

        'scopeSeparator' => ' ',
        'issuer' => 'https://accounts.google.com',
        'urlAuthorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'urlAccessToken' => 'https://oauth2.googleapis.com/token',
        'urlResourceOwnerDetails' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'keys' => array (
  'df8d9ee403bcc7185ad51041194bd3433742d9aa' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnQgOafNApTMwKerFuGXD
j8HZ7hUSFPUV4/SzYj79SF5giP0IfF6Ksnb5Jy0pQ/MXQ6XNuh6eZqCfAPXUwHto
xE29jpe6L6DGKPLTr8RTbNhdIsorc1yXiPcail58gftq1fmegZw0KO6QtBpKYnBW
oZw4PJkuP8ZdGanA0btsZRRRYVmSOKuYDNHfVJlcrD4cqAOL3BPjWQIrZszwTVmw
0FjiU9KfGtU0rDYnas+mZv1qfetZkTA3YPTqSspCNZDbGCVXpJnr4pai0E7lxFgD
NDN2IDk955Pf8eG8oNCfqkHXfnWDrTlXP7SSrYmEaBPcmMKOHdjyrYPk0lWI8+ur
XwIDAQAB
-----END PUBLIC KEY-----',
  'f6f80f37f21c23e61f2be4231e27d269d6695329' => '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8Q+bsTm7MfrGQsnigd+0
ix9EYUesUEJWGpK6jRjArdphVkE7xHqrHbIGQcFrRKOeatHDCXtBKDWTbVOJugCc
5EC8CeH+q54VU5YxunooUCK4jTQW1piLq0BpOKM0dbHxpEQtGRwA6Yu52ZKafswG
64BYo44kX0pPgi4sssUSn0dz0fIrcA8MSa8iffICPKfe757I3en7XTypKFs5BCPo
PAhYHoCqrQnOoRh7ieVvAQUeiaKASjngGSo+5GWpsMzQO05+2J3vId01f0oRUTJY
trKppNS8LxXr8BXSp66SBwgXZEhFLOcmnM9zZEAPt/DMd3IQZUaOF3w5h3ZUHMXc
zwIDAQAB
-----END PUBLIC KEY-----',
    )
    ),

    // ORCID OpenID Connect Provider
    'orcidOIDCTest' => array_merge(\SimpleSAML\Module\authoauth2\ConfigTemplate::OrcidOIDC, [
        'clientId' => 'APP-PRIZEPSDX1RMMI34',
        'clientSecret' => '7a91a2a0-f118-447d-8401-71ba07815eb7',
        // *** Optional ***
        // Allow changing the default redirectUri
        'redirectUri' => 'https://abc.tutorial.stack-dev.cirrusidentity.com:8732/module.php/authoauth2/linkback.php',
    ]),

    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ),

);
