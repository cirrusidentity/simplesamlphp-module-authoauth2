<?php

namespace SimpleSAML\Module\authoauth2;

class ConfigTemplate {

    const GoogleOIDC = [
        'authoauth2:OAuth2',
        // *** Google Endpoints ***
        'urlAuthorize' => 'https://accounts.google.com/o/oauth2/auth',
        'urlAccessToken' => 'https://accounts.google.com/o/oauth2/token',
        'urlResourceOwnerDetails' => 'https://www.googleapis.com/oauth2/v3/userinfo',

        'scopes' =>  array(
            'openid',
            'email',
            'profile'
        ),
        'scopeSeparator' => ' ',
        // Prefix attributes so we can use the standard oidc2name attributemap
        'attributePrefix' => 'oidc.',

        // Improve log lines
        'label' => 'google'
    ];

    const YahooOIDC = [
        'authoauth2:OAuth2',
        // *** Yahoo Endpoints ***
        'urlAuthorize' => 'https://api.login.yahoo.com/oauth2/request_auth',
        'urlAccessToken' => 'https://api.login.yahoo.com/oauth2/get_token',
        'urlResourceOwnerDetails' => 'https://api.login.yahoo.com/openid/v1/userinfo',
        'scopes' =>  array(
            'openid',
// Yahoo doesn't support standard OIDC claims, like email and profile
//          'email',
//          'profile',
// Yahoo prefers the sdpp-w scope for getting acess to user's email, however it prompts user for write access. Leaving it
// out makes things work fine IF you picked being able to edit private profile when creating your app
//            'sdpp-w',
        ),
        'scopeSeparator' => ' ',
        // Prefix attributes so we can use the standard oidc2name attributemap
        'attributePrefix' => 'oidc.',

        // Improve log lines
        'label' => 'yahoo'
    ];
}