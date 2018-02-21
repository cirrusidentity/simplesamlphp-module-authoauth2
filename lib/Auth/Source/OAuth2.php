<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SAML2\Utils;
use SimpleSAML\Logger;
use SimpleSAML\Module\authoauth2\AttributeManipulator;
use SimpleSAML\Utils\Arrays;
use SimpleSAML\Utils\HTTP;

/**
 * Authenticate using Oauth2.
 *
 */
class OAuth2 extends \SimpleSAML_Auth_Source
{
    /**
     * Retrieve request token.
     *
     * @param array $state
     */
    public function authenticate(&$state)
    {
        //TODO: redirect to social provider
    }

    /**
     * Retrieve access token and lookup resource owner profile
     *
     * @param array $state
     * @param string $oauth2Code
     *
     */
    public function finalStep(array &$state, $oauth2Code)
    {
       //TODO: exchange for oauth2 token and user info

    }

}