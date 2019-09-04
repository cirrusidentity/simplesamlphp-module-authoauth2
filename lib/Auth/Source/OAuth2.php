<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\authoauth2\AttributeManipulator;
use SimpleSAML\Module\authoauth2\ConfigTemplate;
use SimpleSAML\Module\authoauth2\Providers\AdjustableGenericProvider;
use SimpleSAML\Module\authoauth2\PsrLogBridge;
use SimpleSAML\Utils\HTTP;

/**
 * Authenticate using Oauth2.
 *
 */
class OAuth2 extends \SimpleSAML\Auth\Source
{


    /** String used to identify our states. */
    const STAGE_INIT = 'authouath2:init';

    /** Key of AuthId field in state. */
    const AUTHID = 'authouath2:AuthId';

    /** Used to aid migrating other Oauth2 SSP libraries to this one */
    const STATE_PREFIX = 'authoauth2';

    /**
     *  The Guzzle log message formatter to use.
     * @see MessageFormatter
     */
    const DEBUG_LOG_FORMAT = "{method} {uri} {code} {req_headers_Authorization} >>>>'{req_body}' <<<<'{res_body}'";

    protected static $defaultProviderClass = AdjustableGenericProvider::class;

    /**
     * @var \SimpleSAML\Configuration
     */
    protected $config;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info Information about this authentication source.
     * @param array $config Configuration.
     */
    public function __construct(array $info, array $config)
    {

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);
        if (array_key_exists('template', $config)) {
            $template = $config['template'];
            if (is_string($template)) {
                $templateArray = constant(ConfigTemplate::class . '::' . $template);
                // Remove the class name
                unset($templateArray[0]);
                $config = array_merge($templateArray, $config);
            }
        }
        if (!array_key_exists('redirectUri', $config)) {
            $config['redirectUri'] = Module::getModuleURL('authoauth2/linkback.php');
        }
        if (!array_key_exists('timeout', $config)) {
            $config['timeout'] = 3;
        }
        // adjust config to add resource owner query parameters.
        if (array_key_exists('urlResourceOwnerOptions', $config)) {
            $newUrl = HTTP::addURLParameters($config['urlResourceOwnerDetails'], $config['urlResourceOwnerOptions']);
            $config['urlResourceOwnerDetails'] = $newUrl;
        }
        $this->config = \SimpleSAML\Configuration::loadFromArray($config, 'authsources:oauth2');
    }

    /**
     * Return a label for the OAuth2 provider that can be used in log statements, etc
     * @return string
     */
    protected function getLabel()
    {
        return $this->config->getString('label', '');
    }

    /**
     * Retrieve request token.
     *
     * @param array $state
     */
    public function authenticate(&$state)
    {
        $provider = $this->getProvider($this->config);

        // We are going to need the authId in order to retrieve this authentication source later, in the callback
        $state[self::AUTHID] = $this->getAuthId();

        $stateID = \SimpleSAML\Auth\State::saveState($state, self::STAGE_INIT);

        $providerLabel = $this->getLabel();
        Logger::debug("authoauth2: $providerLabel saved state with stateID=$stateID");

        $options = $this->config->getArray('urlAuthorizeOptions', []);
        $options = array_merge($options, $this->getAuthorizeOptionsFromState($state));
        // Add a prefix to tell we are the intended recipient for a redirect URI if the redirect URI was customized
        $options['state'] = self::STATE_PREFIX . '|' . $stateID;
        $authorizeURL = $provider->getAuthorizationUrl($options);
        Logger::debug("authoauth2: $providerLabel redirecting to authorizeURL=$authorizeURL");

        HTTP::redirectTrustedURL($authorizeURL);
    }

    /**
     * Convert values from the state parameter of the authenticate call into options to the authorization request.
     *
     * Could be overridden in subclasses, base implementation does nothing
     *
     * @param array $state
     * @return array
     */
    protected function getAuthorizeOptionsFromState(&$state)
    {
        return [];
    }

    /**
     * Get the provider to use to talk to the OAuth2 server.
     * Only visible for testing
     *
     * Since SSP may serialize Auth modules we don't assign the potentially unserializable provider to a field.
     * @param \SimpleSAML\Configuration $config
     * @return \League\OAuth2\Client\Provider\AbstractProvider
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
                Middleware::log(new PsrLogBridge(), new MessageFormatter("authoauth2: $providerLabel $format")),
                'logHttpTraffic'
            );
            $clientConfig = $config->toArray();
            $clientConfig['handler'] = $handlerStack;
            $client = new Client($clientConfig);
            $collaborators['httpClient'] = $client;
        }
        if ($config->hasValue('providerClass')) {
            $providerClass = $config->getString('providerClass');
            if (class_exists($providerClass)) {
                if (!is_subclass_of($providerClass, AbstractProvider::class)) {
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    throw new \InvalidArgumentException("The OAuth2 provider '$providerClass' does not extend " . AbstractProvider::class);
                }
                return new $providerClass($config->toArray(), $collaborators);
            } else {
                throw new \InvalidArgumentException("No OAuth2 provider class found for '$providerClass'.");
            }
        }
        return new static::$defaultProviderClass($config->toArray(), $collaborators);
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
        $start = microtime(true);
        $providerLabel = $this->getLabel();

        $provider = $this->getProvider($this->config);

        $accessToken = $this->retry(
            function () use ($provider, $oauth2Code) {
                return $provider->getAccessToken('authorization_code', [
                    'code' => $oauth2Code
                ]);
            },
            $this->config->getInteger('retryOnError', 1)
        );

        if ($this->config->getBoolean('logIdTokenJson', false) &&
            array_key_exists('id_token', $accessToken->getValues())) {
            $idToken = $accessToken->getValues()['id_token'];
            $decodedIdToken = base64_decode(
                explode('.', $idToken)[1]
            );
            Logger::debug('authoauth2: ' . $providerLabel . ' id_token json: ' . $decodedIdToken);
        }

        /** @var ResourceOwnerInterface $resourceOwner */
        $resourceOwner = $this->retry(
            function () use ($provider, $accessToken) {
                return $provider->getResourceOwner($accessToken);
            },
            $this->config->getInteger('retryOnError', 1)
        );

        $attributes = $resourceOwner->toArray();
        $prefix = $this->getAttributePrefix();
        $state['Attributes'] = $this->convertResourceOwnerAttributes($attributes, $prefix);
        $this->postFinalStep($accessToken, $provider, $state);
        Logger::debug(
            'authoauth2: ' . $providerLabel . ' attributes: ' . implode(", ", array_keys($state['Attributes']))
        );
        // Track time spent calling out to oauth2 server. This can often be a source of slowness.
        $time = microtime(true) - $start;
        Logger::debug('authoauth2: ' . $providerLabel . ' finished authentication in ' . $time . ' seconds');
    }

    /**
     * Take the array of users attributes from the Oauth2 provider and convert them into a form usable by SSP.
     * The default implementation attempts to flatten the user attribute structure and prefix the attribute names
     * @param array $resourceOwnerAttributes The array of attributes from the OAuth2/OIDC provider
     * @param string $prefix A string to put in front of all attribute names
     * @return array The SSP attributes, in form suitable to assign to $state['Attributes']
     */
    protected function convertResourceOwnerAttributes(array $resourceOwnerAttributes, $prefix)
    {
        $attributeManipulator = new AttributeManipulator();
        return $attributeManipulator->prefixAndFlatten($resourceOwnerAttributes, $prefix);
    }

    /**
     * Retry token and user info endpoints in event of network errors.
     * @param callable $function the function to try
     * @param int $retries number of attempts to try
     * @param int $delay The time to delay between tries.
     * @return mixed the result of the function
     */
    protected function retry(callable $function, $retries = 1, $delay = 1)
    {
        $providerLabel = $this->getLabel();
        try {
            return $function();
        } catch (ConnectException $e) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            Logger::info('authoauth2: ' . $providerLabel . " Connection error. Retries left $retries. error: {$e->getMessage()}");
            if ($retries > 0) {
                sleep($delay);
                return $this->retry($function, $retries - 1, $retries);
            } else {
                Logger::info('authoauth2: ' . $providerLabel . ". Out of retries. Rethrowing error");
                throw $e;
            }
        }
    }

    /**
     * @param $idToken
     * @return string[] id token attributes
     */
    protected function extraIdTokenAttributes($idToken)
    {
        // We don't need to verify the signature on the id token since it was the token returned directly from
        // the OP over TLS
        $decoded = $this->extraAndDecodeJwtPayload($idToken);
        if ($decoded == null) {
            return [];
        }
        $data = json_decode($decoded, true);
        //TODO: spec recommends checking that aud matches us and issuer is as expected.
        if ($data == null) {
            Logger::warning("authoauth2: '$decoded' payload can't be decoded to json.");
            return [];
        }
        return $data;
    }

    protected function extraAndDecodeJwtPayload($jwt)
    {
        $parts = explode('.', $jwt);
        if ($parts === false || count($parts) < 3) {
            Logger::warning("authoauth2: idToken '$jwt' is in unexpected format.");
            return null;
        }
        // payload is b64url encode
        $decoded = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($decoded === false) {
            Logger::warning("authoauth2: idToken '$jwt' payload can't be decoded.");
            return null;
        }
        return $decoded;
    }

    /**
     * Allow subclasses to invoked any additional methods, such as extra API calls
     * @param AccessToken $accessToken The user's access token
     * @param AbstractProvider $provider The Oauth2 provider
     * @param array $state The current state
     */
    protected function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, &$state)
    {
    }

    /**
     * Get the configuration used for this filter
     * @return \SimpleSAML\Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    protected function getAttributePrefix()
    {
        return $this->config->getString('attributePrefix', '');
    }
}
