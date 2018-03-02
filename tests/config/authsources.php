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

    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ),

);
