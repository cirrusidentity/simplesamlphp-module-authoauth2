<?php

namespace SimpleSAML\Module\authoauth2\Providers;

use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Logger;

class LinkedInV2AuthProvider extends AdjustableGenericProvider
{
    /**
     * The LinkedIn-Version header value to include in the request to the resource owner details
     * endpoint. Must be in YYYYMM format. See https://learn.microsoft.com/en-us/linkedin/marketing/versioning
     * for further information.
     * @var string
     */
    protected $linkedInApiVersion;

    protected function getConfigurableOptions()
    {
        return array_merge(
            parent::getConfigurableOptions(),
            ['linkedInApiVersion']
        );
    }

    /**
     * Returns necessary headers for versioned LinkedIn API endpoints.
     * Uses '202305' as default if specific version is not configured
     * for authsource.
     *
     * @param  string $url
     * @return array
     */
    protected function getLinkedInVersionHeader($url)
    {
        $hdrs = [];
        // Only /rest URIs are versioned
        if (stripos($url, 'https://api.linkedin.com/rest/') === 0) {
            if ($this->linkedInApiVersion) {
                $hdrs['LinkedIn-Version'] = $this->linkedInApiVersion;
            } else {
                $hdrs['LinkedIn-Version'] = '202305';
            }
        }
        return ['headers' => $hdrs];
    }

    /**
     * Requests resource owner details.
     * Includes required LinkedIn-Version header if resource owner details
     * endpoint is versioned.
     *
     * @param  AccessToken $token
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $request = $this->getAuthenticatedRequest(
            self::METHOD_GET,
            $url,
            $token,
            $this->getLinkedInVersionHeader($url)
        );

        Logger::debug("authoauth2: fetching resource owner details from url = $url , headers = " . print_r($request->getHeaders(), true));

        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }
}
