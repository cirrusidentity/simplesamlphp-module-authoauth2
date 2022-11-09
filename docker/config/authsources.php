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


    // This is a authentication source which handles admin authentication.
    'admin' => array(
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ),

);
