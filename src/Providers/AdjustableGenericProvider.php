<?php

namespace SimpleSAML\Module\authoauth2\Providers;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Logger;
use SimpleSAML\Utils\HTTP;

class AdjustableGenericProvider extends GenericProvider
{
    /**
     * The fields (and corresponding query param names) in the token response that should get added
     * to the resource owner query. For example ['uid' => 'user'] would add the value of 'uid' from
     * the token response json as the query param 'user' to the resource owner details endpoint
     */
    protected array $tokenFieldsToUserDetailsUrl = [];

    protected function getConfigurableOptions()
    {
        return array_merge(
            parent::getConfigurableOptions(),
            ['tokenFieldsToUserDetailsUrl']
        );
    }


    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $url = parent::getResourceOwnerDetailsUrl($token);
        $toAdd = [];
        // Use the array rather than ->getValues() since it has more components
        $responseValues = $token->jsonSerialize();
        if ($this->tokenFieldsToUserDetailsUrl) {
            foreach ($this->tokenFieldsToUserDetailsUrl as $field => $param) {
                if (!is_string($param)) {
                    throw new \Exception('Query param for field ' . $field . ' must be a string');
                }
                if (array_key_exists($field, $responseValues)) {
                    $toAdd[$param] = $responseValues[$field];
                } else {
                    Logger::debug("authoauth2: Token response missing field '$field'");
                }
            }
        }
        if ($toAdd) {
            $url = (new HTTP())->addURLParameters($url, $toAdd);
        }
        return $url;
    }
}
