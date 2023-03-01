<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Logger;

/**
 * Bitbucket's api requires a 2nd call to determine email address.
 *
 * @author nikosev<nikos.ev@hotmail.com>
 * @package SimpleSAMLphp
 */
class BitbucketAuth extends OAuth2
{
    public function __construct(array $info, array $config)
    {
        // Set some defaults
        if (!array_key_exists('template', $config)) {
            $config['template'] = 'Bitbucket';
        }
        parent::__construct($info, $config);
    }


    /**
     * Query Bitbucket's email endpoint if needed.
     * Public for testing
     * @param AccessToken $accessToken
     * @param AbstractProvider $provider
     * @param array $state
     */
    public function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, array &$state): void
    {
        if (!in_array('email', $this->config->getArray('scopes'))) {
            // We didn't request email scope originally
            return;
        }
        $emailUrl = $this->getConfig()->getString('urlResourceOwnerEmail');
        $request = $provider->getAuthenticatedRequest('GET', $emailUrl, $accessToken);
        try {
            $response = $this->retry(
            /**
             * @return mixed
             */
                function () use ($provider, $request) {
                    return $provider->getParsedResponse($request);
                }
            );
        } catch (Exception $e) {
            // not getting email shouldn't fail the authentication
            Logger::error(
                'BitbucketAuth: ' . $this->getLabel() . ' exception email query response ' . $e->getMessage()
            );
            return;
        }

        // if the user has multiple email addresses, pick the primary one
        if (is_array($response) && isset($response["size"])) {
            for ($i = 0; $i < $response["size"]; $i++) {
                if ($response["values"][$i]["is_primary"] == "true" && $response["values"][$i]["type"] == "email") {
                    $prefix = $this->getAttributePrefix();
                    $state['Attributes'][$prefix . 'email'] = [$response["values"][$i]["email"]];
                }
            }
        } else {
            Logger::error(
                'BitbucketAuth: ' . $this->getLabel() . ' invalid email query response ' . var_export($response, true)
            );
        }
    }
}
