<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Logger;

/**
 * Microsoft seems to return some attributes in the ID token and some attributes in user profile call.
 * This module combines the two
 */
class MicrosoftHybridAuth extends OAuth2
{
    /**
     * MicrosoftHybridAuth constructor.
     */
    public function __construct(array $info, array $config)
    {
        // Set some defaults
        if (!array_key_exists('template', $config)) {
            $config['template'] = 'MicrosoftGraphV1';
        }
        parent::__construct($info, $config);
    }

    /**
     * Extract some additional data from the id token and add it to the attributes
     * @param AccessToken $accessToken
     * @param AbstractProvider $provider
     * @param array $state
     */
    protected function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, array &$state): void
    {
        if (!array_key_exists('id_token', $accessToken->getValues())) {
            Logger::error('mshybridauth: ' . $this->getLabel() . ' no id_token returned');
            return;
        }

        $idTokenData = $this->extraIdTokenAttributes($accessToken->getValues()['id_token']);
        $prefix = $this->getAttributePrefix();

        if (array_key_exists('email', $idTokenData)) {
            $state['Attributes'][$prefix . 'mail'] = [$idTokenData['email']];
        }
        if (array_key_exists('name', $idTokenData)) {
            $state['Attributes'][$prefix . 'name'] = [$idTokenData['name']];
        }
    }
}
