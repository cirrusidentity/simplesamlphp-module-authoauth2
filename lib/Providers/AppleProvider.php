<?php

namespace SimpleSAML\Module\authoauth2\Providers;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class AppleProvider extends AdjustableGenericProvider
{
    use BearerAuthorizationTrait;


    /**
     * Constructs an OAuth 2.0 service provider.
     *
     * @param array $options An array of options to set on this provider.
     *     Options include `clientId`, `clientSecret`, `redirectUri`, and `state`.
     *     Individual providers may introduce more options, as needed.
     * @param array $collaborators An array of collaborators that may be used to
     *     override this provider's default behavior. Collaborators include
     *     `grantFactory`, `requestFactory`, and `httpClient`.
     *     Individual providers may introduce more collaborators, as needed.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
        $this->tokenFieldsToUserDetailsUrl = ['sub', 'email', 'email_verified', 'is_private_email'];
    }

    /**
     * Returns all options that are required.
     *
     * @return array
     */
    protected function getRequiredOptions()
    {
        return [
            'urlAuthorize',
            'urlAccessToken',
        ];
    }

    /**
     * Get the string used to separate scopes.
     *
     * @return string
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    public function getDefaultScopes()
    {
        return 'email';
    }

    public function getTokenFieldsToUserDetailsUrl() {
        return $this->tokenFieldsToUserDetailsUrl;
    }

    /**
     * Change response mode when scope requires it
     *
     * @param array $options
     *
     * @return array
     */
    protected function getAuthorizationParameters(array $options)
    {
        $options = parent::getAuthorizationParameters($options);
        $options['grant_type'] = 'authorization_code';
        if (strpos($options['scope'], 'name') !== false || strpos($options['scope'], 'email') !== false) {
            $options['response_mode'] = 'form_post';
        }
        return $options;
    }

    /**
     * Builds the access token URL's query string.
     *
     * @param  array $params Query parameters
     * @return string Query string
     */
    protected function getAccessTokenQuery(array $params)
    {
        $params['grant_type'] = 'authorization_code';
        return $this->buildQueryString($params);
    }

}
