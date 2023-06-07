<?php

namespace SimpleSAML\Module\authoauth2;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class ConfigTemplate
{
    public const Amazon = [
        'authoauth2:OAuth2',
        // *** Amazon endpoints ***
        'urlAuthorize' => 'https://www.amazon.com/ap/oa',
        'urlAccessToken' => 'https://api.amazon.com/auth/o2/token',
        'urlResourceOwnerDetails' => 'https://api.amazon.com/user/profile',
        'scopes' => 'profile',
        // Prefix attributes so we can use the amazon2name
        'attributePrefix' => 'amazon.',
        // Improve log lines
        'label' => 'amazon'
    ];

    public const AppleLeague = [
        'authoauth2:OAuth2',
        'attributePrefix' => 'apple.',
        // Improve log lines
        'label' => 'apple',
        'logIdTokenJson' => true,
        // You must install composer require patrickbussmann/oauth2-apple:~0.2.10
        'providerClass' => 'League\OAuth2\Client\Provider\Apple',
        // You must set these four settings
        //'teamId' => $appleTeamId,
        //'clientId' => $apiKey,
        // 'keyFileId' => $privateKeyId,
        // 'keyFilePath' => $privateKeyPath
    ];

    public const Facebook = [
        'authoauth2:OAuth2',
        // *** Facebook endpoints ***
        'urlAuthorize' => 'https://www.facebook.com/dialog/oauth',
        'urlAccessToken' => 'https://graph.facebook.com/oauth/access_token',
        // Add requested attributes as fields
        'urlResourceOwnerDetails' => 'https://graph.facebook.com/me',
        'urlResourceOwnerOptions' => [
            'fields' => 'id,name,first_name,last_name,email'
        ],
        'scopes' => 'email',
        // Prefix attributes so we can use the facebook2name
        'attributePrefix' => 'facebook.',

        // Improve log lines
        'label' => 'facebook'
    ];

    public const GoogleOIDC = [
        'authoauth2:OAuth2',
        // *** Google Endpoints ***
        'urlAuthorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'urlAccessToken' => 'https://oauth2.googleapis.com/token',
        'urlResourceOwnerDetails' => 'https://openidconnect.googleapis.com/v1/userinfo',

        'scopes' => array(
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

    // Deprecated
    public const LinkedIn = [
        'authoauth2:OAuth2',
        // *** LinkedIn Endpoints ***
        'urlAuthorize' => 'https://www.linkedin.com/oauth/v2/authorization',
        'urlAccessToken' => 'https://www.linkedin.com/oauth/v2/accessToken',
        // phpcs:ignore Generic.Files.LineLength.TooLong
        'urlResourceOwnerDetails' => 'https://api.linkedin.com/v1/people/~:(id,first-name,last-name,email-address)?format=json',
        //scopes are the default ones configured for your application
        'attributePrefix' => 'linkedin.',
        'scopeSeparator' => ' ',
        // Improve log lines
        'label' => 'linkedin'
    ];

    public const LinkedInV2 = [
        'authoauth2:LinkedInV2Auth',
        // *** LinkedIn Endpoints ***
        'urlAuthorize' => 'https://www.linkedin.com/oauth/v2/authorization',
        'urlAccessToken' => 'https://www.linkedin.com/oauth/v2/accessToken',
        'urlResourceOwnerDetails' => 'https://api.linkedin.com/v2/me',
        'urlResourceOwnerEmail' => 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))',
        //scopes are the default ones configured for your application
        'attributePrefix' => 'linkedin.',
        'scopes' => [
            'r_liteprofile',
            // This requires additional api call to the urlResourceOwnerEmail url
            'r_emailaddress',
        ],
        'scopeSeparator' => ' ',
        // Improve log lines
        'label' => 'linkedin'
    ];

    //https://learn.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/sign-in-with-linkedin-v2
    public const LinkedInOIDC = [
        'authoauth2:OAuth2',
        // *** LinkedIn oidc Endpoints ***
        'urlAuthorize' => 'https://www.linkedin.com/oauth/v2/authorization',
        'urlAccessToken' => 'https://www.linkedin.com/oauth/v2/accessToken',
        'urlResourceOwnerDetails' => 'https://api.linkedin.com/v2/userinfo',
        'attributePrefix' => 'oidc.',
        'scopes' => ['openid', 'email', 'profile'],
        'scopeSeparator' => ' ',

        // Improve log lines
        'label' => 'linkedin'
    ];

    //https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-protocols-oidc
    //https://login.microsoftonline.com/common/v2.0/.well-known/openid-configuration
    // WARNING: The OIDC user resource endpoint only returns sub, which is a targeted id.
    // You must decode the id token instead to determine user attributes. There you will
    // find oid which is the ID you are probably expecting if you are moving from the live apis.
    public const MicrosoftOIDC = [
        'authoauth2:OAuth2',
        // *** Microsoft oidc Endpoints ***
        'urlAuthorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
        'urlAccessToken' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
        'urlResourceOwnerDetails' => 'https://graph.microsoft.com/oidc/userinfo',
        'attributePrefix' => 'oidc.',
        'scopes' => ['openid', 'email', 'profile'],
        'scopeSeparator' => ' ',

        // Improve log lines
        'label' => 'microsoft'
    ];

    public const MicrosoftGraphV1 = [
        'authoauth2:MicrosoftHybridAuth',
        // *** Microsoft graph Endpoints ***
        'urlAuthorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
        'urlAccessToken' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
        'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me/',
        'attributePrefix' => 'microsoft.',
        // graph v1 requires user.read
        'scopes' => ['openid', 'email', 'profile', 'user.read'],
        'scopeSeparator' => ' ',

        // Improve log lines
        'label' => 'microsoft'
    ];

    public const YahooOIDC = [
        'authoauth2:OAuth2',
        // *** Yahoo Endpoints ***
        'urlAuthorize' => 'https://api.login.yahoo.com/oauth2/request_auth',
        'urlAccessToken' => 'https://api.login.yahoo.com/oauth2/get_token',
        'urlResourceOwnerDetails' => 'https://api.login.yahoo.com/openid/v1/userinfo',
        'scopes' => array(
            'openid',
// Yahoo doesn't support standard OIDC claims, like email and profile
//          'email',
//          'profile',
// Yahoo prefers the sdpp-w scope for getting acess to user's email, however it prompts user for write access.
// Leaving it out makes things work fine IF you picked being able to edit private profile when creating your app
//            'sdpp-w',
        ),
        'scopeSeparator' => ' ',
        // Prefix attributes so we can use the standard oidc2name attributemap
        'attributePrefix' => 'oidc.',

        // Improve log lines
        'label' => 'yahoo'
    ];

    // TODO: weibo is work in progress
    public const Weibo = [
        'authoauth2:OAuth2',
        // *** Weibo Endpoints ***
        'urlAuthorize' => 'https://api.weibo.com/oauth2/authorize',
        'urlAccessToken' => 'https://api.weibo.com/oauth2/access_token',
        'urlResourceOwnerDetails' => 'https://api.weibo.com/2/users/show.json',
        'attributePrefix' => 'weibo.',
        'scopeSeparator' => ' ',
        // Improve log lines
        'label' => 'weibo',
        // uid attribute from token response needs to be included in user details call
        'tokenFieldsToUserDetailsUrl' => ['uid' => 'uid', 'access_token' => 'access_token'],
    ];

    public const Bitbucket = [
        'authoauth2:BitbucketAuth',
        // *** Bitbucket Endpoints ***
        'urlAuthorize' => 'https://bitbucket.org/site/oauth2/authorize',
        'urlAccessToken' => 'https://bitbucket.org/site/oauth2/access_token',
        'urlResourceOwnerDetails' => 'https://api.bitbucket.org/2.0/user',
        'urlResourceOwnerEmail' => 'https://api.bitbucket.org/2.0/user/emails',
        //scopes are the default ones configured for your application
        'attributePrefix' => 'bitbucket.',
        'scopes' => ['account', 'email'],
        'scopeSeparator' => ' ',
        // Improve log lines
        'label' => 'bitbucket'
    ];

    public const OrcidOIDC = [
        'authoauth2:OrcidOIDCAuth',
        // *** ORCID support OpenID Connect discovery protocol ***
        'issuer' => 'https://orcid.org',
        // email requires a separate API call
        'urlResourceOwnerEmail' => 'https://pub.orcid.org/v3.0/@orcid/email',
        // Prefix attributes so we can use the standard oidc2name attributemap
        'attributePrefix' => 'oidc.',
    ];
}
// phpcs:enable
