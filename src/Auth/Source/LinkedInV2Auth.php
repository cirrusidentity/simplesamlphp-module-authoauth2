<?php

/**
 * DEPRECATED -- see docs/LINKEDIN.md
 */

/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 10/16/18
 * Time: 1:34 PM
 */

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Logger;

/**
 * LinkedIn's v2 api requires a 2nd call to determine email address.
 */
class LinkedInV2Auth extends OAuth2
{
    public function __construct(array $info, array $config)
    {
        // Set some defaults
        if (!array_key_exists('template', $config)) {
            $config['template'] = 'LinkedInV2';
        }
        parent::__construct($info, $config);
    }

    public function convertResourceOwnerAttributes(array $resourceOwnerAttributes, string $prefix): array
    {
        /** Sample response from LinkedIn
         * {
         * "lastName":{
         * "localized":{
         * "en_US":"Lasty"
         * },
         * "preferredLocale":{
         * "country":"US",
         * "language":"en"
         * }
         * },
         * "firstName":{
         * "localized":{
         * "en_US":"Firsty"
         * },
         * "preferredLocale":{
         * "country":"US",
         * "language":"en"
         * }
         * },
         * "id":"t7xZ7s4F02"
         * }
         *  With 'preferredLocale' being optional
         */
        $attributes = [
            $prefix . "id" => [$resourceOwnerAttributes["id"]]
        ];
        foreach (['firstName', 'lastName'] as $attributeName) {
            $value = $this->getFirstValueFromMultiLocaleString($attributeName, $resourceOwnerAttributes);
            if (!empty($value)) {
                $attributes[$prefix . $attributeName] = [$value];
            }
        }


        return $attributes;
    }

    /**
     * LinkedIn's attribute values are complex subobjects per
     * https://docs.microsoft.com/en-us/linkedin/shared/references/v2/object-types#multilocalestring
     * @param string $attributeName The multiLocalString attribute to check
     * @param array $attributes All the linkedIn attributes
     * @return string|false|null Return the first value or null/false if there is no value
     */
    private function getFirstValueFromMultiLocaleString(string $attributeName, array $attributes)
    {
        if (isset($attributes[$attributeName]['localized'])) {
            // reset gives us the first value from the multi valued associate localized array
            return reset($attributes[$attributeName]['localized']);
        }
        return null;
    }


    /**
     * Query LinkedIn's email endpoint if needed.
     * Public for testing
     * @param AccessToken $accessToken
     * @param AbstractProvider $provider
     * @param array $state
     */
    public function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, array &$state): void
    {
        if (!in_array('r_emailaddress', $this->config->getArray('scopes'))) {
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
                'linkedInv2Auth: ' . $this->getLabel() . ' exception email query response ' . $e->getMessage()
            );
            return;
        }

        if (is_array($response) && isset($response["elements"][0]["handle~"]["emailAddress"])) {
            /**
             * A valid response for email lookups is:
             * {
             * "elements" : [ {
             * "handle" : "urn:li:emailAddress:5266785132",
             * "handle~" : {
             * "emailAddress" : "patrick+testuser@cirrusidentity.com"
             * }
             * } ]
             * }
             */
            $prefix = $this->getAttributePrefix();
            $state['Attributes'][$prefix . 'emailAddress'] = [$response["elements"][0]["handle~"]["emailAddress"]];
        } else {
            Logger::error(
                'linkedInv2Auth: ' . $this->getLabel() . ' invalid email query response ' . var_export($response, true)
            );
        }
    }
}
