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
}