<?php

$config = array(

    /** Test facebook template */
    'templateFacebook' => [
        'authoauth2:OAuth2',
        'template' => 'Facebook',
        // App is in development mode and can be used to login as a test user
        'clientId' => '1223209798230151',
        'clientSecret' => '61cb2fdddc5a16998924360c1a9a726f',
        /**
         * This the app's test user that can be used to authenticate:
         *  email: open_nzwvghb_user@tfbnw.net
         *  password: SSPisMyFavorite2022
         */
    ],

    'templateAuthProcFacebook' => [
        'authoauth2:OAuth2',
        'template' => 'Facebook',
        // App is in development mode and can be used to login as a test user
        'clientId' => '1223209798230151',
        'clientSecret' => '61cb2fdddc5a16998924360c1a9a726f',
        /**
         * This the app's test user that can be used to authenticate:
         *  email: open_nzwvghb_user@tfbnw.net
         *  password: SSPisMyFavorite2022
         */
        'authproc' => [
            20 => [
                'class' => 'preprodwarning:Warning'
            ],
            25 => [
                'class' => 'core:AttributeAdd',
                '%replace',
                'groups' => ['users', 'members'],
            ],
            // The authproc should be run in order by key, not by order defined,
            // which means this authproc will run first and have its output overwritten by the
            // above authproc
            15 => [
                'class' => 'core:AttributeAdd',
                '%replace',
                'groups' => ['should', 'be', 'replaced'],
            ],
        ]
    ],

    'templateMicrosoft' => [
        'authoauth2:OAuth2',
        'template' => 'MicrosoftGraphV1',
        'clientId' => 'f579dc6e-58f5-41a8-8bbf-96d54eacfe8d',
        'clientSecret' => 'GXc8Q~mgI7kTBllrvpBthUEioeARdjrRYORSyda4',
    ],

    /** Test using Google OIDC but with config explicitly define rather than pulled from .well-know */
    'templateGoogle' => [
        'authoauth2:OAuth2',
        'template' => 'GoogleOIDC',
        'clientId' => '105348996343-6jb2828gnlo07mop7b08gjse1ms77bm0.apps.googleusercontent.com',
        'clientSecret' => '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',
    ],

    /** Test using the OpenIDConnect functionality to interact with Google. This configures itself from `.well-known/openid-configuration` */
    'googleOIDCSource' => [
        'authoauth2:OpenIDConnect',
        'issuer' => 'https://accounts.google.com',

        'clientId' => '105348996343-6jb2828gnlo07mop7b08gjse1ms77bm0.apps.googleusercontent.com',
        'clientSecret' => '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',
        /**
         * This the app's test user that can be used to authenticate:
         *  email: open_nzwvghb_user@tfbnw.net
         *  password: SSPisMyFavorite2022
         */
    ],

    /** Using the OIDC authsource for MS logins */
    'microsoftOIDCSource' => [
        'authoauth2:OpenIDConnect',
        'issuer' => 'https://sts.windows.net/{tenantid}/',
        // When using the 'common' discovery endpoint it allows any Azure user to authenticate, however
        // the token issuer is tenant specific and will not match what is in the common discovery document.
        'validateIssuer' => false,  // issuer is just used to confirm correct discovery endpoint loaded
        'discoveryUrl' => 'https://login.microsoftonline.com/common/.well-known/openid-configuration',
        'clientId' => 'f579dc6e-58f5-41a8-8bbf-96d54eacfe8d',
        'clientSecret' => 'GXc8Q~mgI7kTBllrvpBthUEioeARdjrRYORSyda4',
    ],

    'microsoftOIDCPkceSource' => [
        'authoauth2:OpenIDConnect',
        'issuer' => 'https://sts.windows.net/{tenantid}/',
        // When using the 'common' discovery endpoint it allows any Azure user to authenticate, however
        // the token issuer is tenant specific and will not match what is in the common discovery document.
        'validateIssuer' => false,  // issuer is just used to confirm correct discovery endpoint loaded
        'discoveryUrl' => 'https://login.microsoftonline.com/common/.well-known/openid-configuration',
        'clientId' => 'f579dc6e-58f5-41a8-8bbf-96d54eacfe8d',
        'clientSecret' => 'GXc8Q~mgI7kTBllrvpBthUEioeARdjrRYORSyda4',
        'pkceMethod' => 'S256',
    ],


    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ),

);
