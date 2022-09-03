<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Logger;

/**
 * ORCID api requires a 2nd call to determine email address.
 */
class OrcidOIDCAuth extends OpenIDConnect
{
    public function __construct(array $info, array $config)
    {
        // Set some defaults
        if (!array_key_exists('template', $config)) {
            $config['template'] = 'OrcidOIDC';
        }
        parent::__construct($info, $config);
    }

    /**
     * Query ORCID's email endpoint and add it to the attributes
     * Public for testing
     * @param AccessToken $accessToken
     * @param AbstractProvider $provider
     * @param array $state
     */
    public function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, &$state)
    {
        // initialize attributes from id token
        parent::postFinalStep($accessToken, $provider, $state);

        $prefix = $this->getAttributePrefix();

        $emailUrl = $this->getConfig()->getString('urlResourceOwnerEmail');
        $request = $provider->getAuthenticatedRequest(
            'GET',
            strtr($emailUrl, ['@orcid' => $state['Attributes'][$prefix . 'sub'][0]]),
            $accessToken,
            ['headers' => ['Accept' => 'application/json']]
        );
        try {
            $response = $this->retry(
                function () use ($provider, $request) {
                    return $provider->getParsedResponse($request);
                },
                $this->config->getInteger('retryOnError', 1)
            );
        } catch (\Exception $e) {
            // not getting email shouldn't fail the authentication
            Logger::error(
                'OrcidOIDCAuth: ' . $this->getLabel() . ' exception email query response ' . $e->getMessage()
            );
            return;
        }

        if (is_array($response) && isset($response["email"])) {
            /**
             * A valid response for email lookups is:
             * {
             * "last-modified-date" : {
             *     "value" : 1662154866754
             * },
             * "email" : [ {
             *     "created-date" : {
             *     "value" : 1487980758777
             *     },
             *     "last-modified-date" : {
             *     "value" : 1662154866754
             *     },
             *     "source" : {
             *     "source-orcid" : {
             *         "uri" : "https://orcid.org/0000-0002-0385-1674",
             *         "path" : "0000-0002-0385-1674",
             *         "host" : "orcid.org"
             *     },
             *     "source-client-id" : null,
             *     "source-name" : {
             *         "value" : "Gary Windham"
             *     },
             *     "assertion-origin-orcid" : null,
             *     "assertion-origin-client-id" : null,
             *     "assertion-origin-name" : null
             *     },
             *     "email" : "windhamg@ecirrusidentity.com",
             *     "path" : null,
             *     "visibility" : "public",
             *     "verified" : true,
             *     "primary" : true,
             *     "put-code" : null
             * }, {
             *     "created-date" : {
             *     "value" : 1661900382942
             *     },
             *     "last-modified-date" : {
             *     "value" : 1661904239469
             *     },
             *     "source" : {
             *     "source-orcid" : {
             *         "uri" : "https://orcid.org/0000-0002-0385-1674",
             *         "path" : "0000-0002-0385-1674",
             *         "host" : "orcid.org"
             *     },
             *     "source-client-id" : null,
             *     "source-name" : {
             *         "value" : "Gary Windham"
             *     },
             *     "assertion-origin-orcid" : null,
             *     "assertion-origin-client-id" : null,
             *     "assertion-origin-name" : null
             *     },
             *     "email" : "windhamg@gmail.com",
             *     "path" : null,
             *     "visibility" : "public",
             *     "verified" : true,
             *     "primary" : false,
             *     "put-code" : null
             * } ],
             * "path" : "/0000-0002-0385-1674/email"
             * }
             *
             * ORCID allows multiple email addresses, with only one being marked as "primary". Email addresses
             * can also me restricted from public visibility -- so the "primary" email address may not be released.
             * Use the first email address in array marked primary (if any), else use first email address.
             */
            $email = null;
            foreach ($response["email"] as $e) {
                if ($email === null || $e["primary"] === true) {
                    $email = $e["email"];
                }
            }
            if ($email !== null) {
                $state['Attributes'][$prefix . 'email'] = [$email];
            }
        } else {
            Logger::error(
                'OrcidOIDCAuth: ' . $this->getLabel() . ' invalid email query response ' . var_export($response, true)
            );
        }
    }
}
