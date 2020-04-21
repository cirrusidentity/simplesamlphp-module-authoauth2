<?php
namespace SimpleSAML\Module\authoauth2\Auth\Source;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use League\OAuth2\Client\Token\AccessTokenInterface;
use SimpleSAML\Logger;
use SimpleSAML\Module\authoauth2\Providers\AppleProvider;

class AppleAuth extends OAuth2
{

    protected static $defaultProviderClass = AppleProvider::class;

    public function __construct(array $info, array $config)
    {
        // Set some defaults
        if (!array_key_exists('template', $config)) {
            $config['template'] = 'Apple';
        }
        parent::__construct($info, $config);
    }


    /**
     * Get the provider to use to talk to the OAuth2 server.
     * Only visible for testing
     *
     * Since SSP may serialize Auth modules we don't assign the potentially unserializable provider to a field.
     * @param \SimpleSAML\Configuration $config
     * @return AppleProvider
     */
    public function getProvider(\SimpleSAML\Configuration $config)
    {
        $providerLabel = $this->getLabel();

        $collaborators = [];
        if ($config->getBoolean('logHttpTraffic', false) === true) {
            $format = $config->getString('logMessageFormat', self::DEBUG_LOG_FORMAT);
            Logger::debug('authoauth2: Enable traffic logging');
            $handlerStack = HandlerStack::create();
            $handlerStack->push(
                Middleware::log(new \SAML2\Compat\Ssp\Logger(), new MessageFormatter("authoauth2: $providerLabel $format")),
                'logHttpTraffic'
            );
            $clientConfig = $config->toArray();
            $clientConfig['handler'] = $handlerStack;
            $client = new Client($clientConfig);
            $collaborators['httpClient'] = $client;
        }

        return new AppleProvider($config->toArray(), $collaborators);
    }

    public function finalStep(array &$state, $oauth2Code)
    {
        $start = microtime(true);
        $providerLabel = $this->getLabel();
        $provider = $this->getProvider($this->config);

        /**
         * @var AccessTokenInterface $accessToken
         */
        $accessToken = $this->retry(
            function () use ($provider, $oauth2Code) {
                return $provider->getAccessToken('authorization_code', [
                    'code' => $oauth2Code,
                    'grant_type' => 'authorization_code',
                ]);
            },
            $this->config->getInteger('retryOnError', 1)
        );
        $tokenAttributes = [];
        if (array_key_exists('id_token', $accessToken->getValues())) {
            $idToken = $accessToken->getValues()['id_token'];
            $decodedIdToken = base64_decode(
                explode('.', $idToken)[1]
            );
            $tokenAttributes = json_decode($decodedIdToken, true);
        }

        $attributes = [];
        $fields = $provider->getTokenFieldsToUserDetailsUrl();
        foreach ($fields as $field) {
            if (isset($tokenAttributes[$field])) {
                $attributes[$field] = $tokenAttributes[$field];
            }
        }

        $prefix = $this->getAttributePrefix();
        $state['Attributes'] = $this->convertResourceOwnerAttributes($attributes, $prefix);
        $this->postFinalStep($accessToken, $provider, $state);
        Logger::debug(
            'authoauth2: ' . $providerLabel . ' attributes: ' . implode(", ", array_keys($state['Attributes']))
        );
        // Track time spent calling out to oauth2 server. This can often be a source of slowness.
        $time = microtime(true) - $start;
        Logger::debug('authoauth2: ' . $providerLabel . ' finished authentication in ' . $time . ' seconds');    }
}
