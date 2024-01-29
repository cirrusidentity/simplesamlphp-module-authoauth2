<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\Source;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\authoauth2\AttributeManipulator;
use SimpleSAML\Module\authoauth2\ConfigTemplate;
use SimpleSAML\Module\authoauth2\locators\HTTPLocator;
use SimpleSAML\Module\authoauth2\Providers\AdjustableGenericProvider;
use SimpleSAML\Session;

/**
 * Authenticate using Oauth2.
 *
 */
class OAuth2 extends Source
{
    use HTTPLocator;

    /** String used to identify our states. */
    public const STAGE_INIT = 'authouath2:init';

    /** Key of AuthId field in state. */
    public const AUTHID = 'authouath2:AuthId';

    /** Used to aid migrating other Oauth2 SSP libraries to this one */
    public const STATE_PREFIX = 'authoauth2';

    /**
     *  The Guzzle log message formatter to use.
     * @see MessageFormatter
     */
    // phpcs:ignore
    public const DEBUG_LOG_FORMAT = "{method} {uri} {code} {req_headers_Authorization} >>>>'{req_body}' <<<<'{res_body}'";

    private const PKCE_SESSION_NAMESPACE = 'authoauth2_pkce';
    private const PKCE_SESSION_KEY = 'pkceCode';

    protected static string $defaultProviderClass = AdjustableGenericProvider::class;

    protected Configuration $config;

    /** @var string an identifier for the server. Used in place we need to record the oauth2 server */
    private string $oauth2ServerIdentifier = 'oauth2-server-id-not-set';


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
            $newUrl = $this->getHttp()->addURLParameters(
                $config['urlResourceOwnerDetails'],
                $config['urlResourceOwnerOptions']
            );
            $config['urlResourceOwnerDetails'] = $newUrl;
        }
        $this->config = Configuration::loadFromArray($config, 'authsources:oauth2');
    }

    /**
     * Return a label for the OAuth2 provider that can be used in log statements, etc
     * @return string
     */
    protected function getLabel(): string
    {
        return $this->config->getOptionalString('label', '');
    }

    /**
     * Retrieve request token.
     *
     * @param array $state
     */
    public function authenticate(array &$state): void
    {
        $provider = $this->getProvider($this->config);

        // We are going to need the authId in order to retrieve this authentication source later, in the callback
        $state[self::AUTHID] = $this->getAuthId();

        $stateID = State::saveState($state, self::STAGE_INIT);

        $providerLabel = $this->getLabel();
        Logger::debug("authoauth2: $providerLabel saved state with stateID=$stateID");

        $options = $this->config->getOptionalArray('urlAuthorizeOptions', []);
        $options = array_merge($options, $this->getAuthorizeOptionsFromState($state));
        // Add a prefix to tell we are the intended recipient for a redirect URI if the redirect URI was customized
        $options['state'] = self::STATE_PREFIX . '|' . $stateID;
        $authorizeURL = $provider->getAuthorizationUrl($options);
        Logger::debug("authoauth2: $providerLabel redirecting to authorizeURL=$authorizeURL");

        // save the pkce code in the current session, so it can be retrieved later for verification
        $this->saveCodeChallengeFromProvider($provider);

        $this->getHttp()->redirectTrustedURL($authorizeURL);
    }

    /**
     * Convert values from the state parameter of the authenticate call into options to the authorization request.
     *
     * Could be overridden in subclasses, base implementation does nothing
     *
     * @param array $state
     * @return array
     */
    protected function getAuthorizeOptionsFromState(array &$state): array
    {
        return [];
    }

    /**
     * Get the provider to use to talk to the OAuth2 server.
     * Only visible for testing
     *
     * Since SSP may serialize Auth modules we don't assign the potentially unserializable provider to a field.
     * @param Configuration $config
     * @return AbstractProvider
     */
    public function getProvider(Configuration $config): AbstractProvider
    {
        $providerLabel = $this->getLabel();

        $collaborators = [];
        if ($config->getOptionalBoolean('logHttpTraffic', false) === true) {
            $format = $config->getOptionalString('logMessageFormat', self::DEBUG_LOG_FORMAT);
            Logger::debug('authoauth2: Enable traffic logging');
            $handlerStack = HandlerStack::create();
            $handlerStack->push(
                Middleware::log(
                    new \SAML2\Compat\Ssp\Logger(),
                    new MessageFormatter("authoauth2: $providerLabel $format")
                ),
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
                    throw new InvalidArgumentException("The OAuth2 provider '$providerClass' does not extend " . AbstractProvider::class);
                }
                /**
                 * @psalm-suppress UnsafeInstantiation
                 */
                $provider = new $providerClass($config->toArray(), $collaborators);
            } else {
                throw new InvalidArgumentException("No OAuth2 provider class found for '$providerClass'.");
            }
        }
        if (!isset($provider)) {
            /**
             * @var AbstractProvider $provider
             * @psalm-suppress InvalidStringClass,UnsafeInstantiation
             */
            $provider = new static::$defaultProviderClass($config->toArray(), $collaborators);
        }
        /** @psalm-suppress MixedArgument, MixedMethodCall psalm is confused about baseAuthzUrl */
        $this->oauth2ServerIdentifier = $config->getOptionalString('issuer', $provider->getBaseAuthorizationUrl());
        return $provider;
    }

    /**
     * Retrieve access token and lookup resource owner profile
     *
     * @param array $state
     * @param string $oauth2Code
     *
     */
    public function finalStep(array &$state, string $oauth2Code): void
    {
        $start = microtime(true);
        $providerLabel = $this->getLabel();

        $provider = $this->getProvider($this->config);

        // load the pkce code from the session, so the server can validate it
        // when exchanging the authorization code for an access token.
        $this->loadCodeChallengeIntoProvider($provider);

        /**
         * @var AccessToken $accessToken
         */
        $accessToken = $this->retry(
            function () use ($provider, $oauth2Code) {
                return $provider->getAccessToken('authorization_code', [
                    'code' => $oauth2Code
                ]);
            }
        );

        if (
            $this->config->getOptionalBoolean('logIdTokenJson', false) &&
            array_key_exists('id_token', $accessToken->getValues())
        ) {
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
            }
        );

        $attributes = $resourceOwner->toArray();

        $authenticatedApiRequests = $this->config->getOptionalArray('authenticatedApiRequests', []);
        foreach ($authenticatedApiRequests as $apiUrl) {
            try {
                $apiAttributes = $this->retry(
                /**
                 * @return array
                 */
                    function () use ($provider, $accessToken, $apiUrl) {

                    /** @var RequestInterface $request */
                        $apiRequest = $provider->getAuthenticatedRequest(
                            'GET',
                            $apiUrl,
                            $accessToken
                        );
                        return $provider->getParsedResponse($apiRequest);
                    }
                );
                if (!empty($apiAttributes)) {
                    $attributes = array_replace_recursive($attributes, $apiAttributes);
                }
            } catch (Exception $e) {
                // not retrieving additional resources, should not fail the authentication
                Logger::error(
                    'OAuth2: ' . $this->getLabel() . ' exception authenticatedApiRequests ' . $e->getMessage()
                );
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
        Logger::debug('authoauth2: ' . $providerLabel . ' finished authentication in ' . $time . ' seconds');

        // Auth procs may redirect the user and processing may finish in another request/method
        $this->runAuthProcs($state);
    }

    /**
     * Take the array of users attributes from the Oauth2 provider and convert them into a form usable by SSP.
     * The default implementation attempts to flatten the user attribute structure and prefix the attribute names
     * @param array $resourceOwnerAttributes The array of attributes from the OAuth2/OIDC provider
     * @param string $prefix A string to put in front of all attribute names
     * @return array The SSP attributes, in form suitable to assign to $state['Attributes']
     */
    protected function convertResourceOwnerAttributes(array $resourceOwnerAttributes, string $prefix): array
    {
        $attributeManipulator = new AttributeManipulator();
        return $attributeManipulator->prefixAndFlatten($resourceOwnerAttributes, $prefix);
    }

    /**
     * Retry token and user info endpoints in event of network errors.
     * @param callable $function the function to try
     * @param ?int $retries number of attempts to try
     * @param int $delay The time to delay between tries.
     * @return mixed the result of the function
     */
    protected function retry(callable $function, ?int $retries = null, int $delay = 1)
    {
        if ($retries === null) {
            $retries = $this->config->getOptionalInteger('retryOnError', 1);
        }
        if ($delay < 0) {
            $delay = 0;
        }
        $providerLabel = $this->getLabel();
        try {
            return $function();
        } catch (ConnectException $e) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            Logger::info('authoauth2: ' . $providerLabel . " Connection error. Retries left $retries. error: {$e->getMessage()}");
            if ($retries > 0) {
                /** @var 0|positive-int $delay */
                sleep($delay);
                return $this->retry($function, $retries - 1, $delay);
            } else {
                Logger::info('authoauth2: ' . $providerLabel . ". Out of retries. Rethrowing error");
                throw $e;
            }
        }
    }

    /**
     * @param ?string $idToken
     * @return string[] id token attributes
     */
    protected function extraIdTokenAttributes(?string $idToken): array
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

    protected function extraAndDecodeJwtPayload(?string $jwt): ?string
    {
        $parts = explode('.', $jwt ?? '');
        if (count($parts) < 3) {
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

    protected function isPkceEnabled(): bool
    {
        return (bool)$this->config->getOptionalValueValidate('pkceMethod', [
            AbstractProvider::PKCE_METHOD_PLAIN,
            AbstractProvider::PKCE_METHOD_S256,
            ''
        ], null);
    }

    /**
     * support saving the providers PKCE code in the session for later verification.
     * We store in the session rather in the $state since the $provider generates
     * the pkce after it has been configured with the $state id, which we get after
     * saving the $state.
     */
    protected function saveCodeChallengeFromProvider(AbstractProvider $provider): void
    {
        if ($this->isPkceEnabled()) {
            Session::getSessionFromRequest()
                ->setData(
                    self::PKCE_SESSION_NAMESPACE,
                    self::PKCE_SESSION_KEY,
                    $provider->getPkceCode()
                );
        }
    }

    /**
     * support retrieving the PKCE code from the session for verification.
     */
    protected function loadCodeChallengeIntoProvider(AbstractProvider $provider): void
    {
        if ($this->isPkceEnabled()) {
            $pkceCode = (string)Session::getSessionFromRequest()
                ->getData(
                    self::PKCE_SESSION_NAMESPACE,
                    self::PKCE_SESSION_KEY
                );
            if ($pkceCode) {
                $provider->setPkceCode($pkceCode);
            }
        }
    }

    /**
     * Allow subclasses to invoked any additional methods, such as extra API calls
     * @param AccessToken $accessToken The user's access token
     * @param AbstractProvider $provider The Oauth2 provider
     * @param array $state The current state
     */
    protected function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, array &$state): void
    {
    }

    /**
     * Get the configuration used for this filter
     * @return Configuration
     */
    public function getConfig(): Configuration
    {
        return $this->config;
    }

    protected function getAttributePrefix(): string
    {
        return $this->config->getOptionalString('attributePrefix', '');
    }

    /**
     * Run authproc filters with the processing chain
     * @param array $state
     * @return void
     * @throws \SimpleSAML\Error\Exception
     * @throws \SimpleSAML\Error\UnserializableException
     */
    protected function runAuthProcs(array &$state): void
    {
        $idpMetadata = [
           'entityid' => $this->getOAuth2ServerIdentifier()
        ];
        $spMetadata = [
            'entityid' => $this->config->getOptionalString('clientId', 'unknown-clientid'),
            'authproc' => $this->config->getOptionalArray('authproc', [])
        ];
        $pc = new ProcessingChain($idpMetadata, $spMetadata, 'oauth2');

        $state['ReturnCall'] = [OAuth2::class, 'authProcessingComplete'];
        $state['Destination'] = $spMetadata;
        $state['Source'] = $idpMetadata;

        $pc->processState($state);
    }

    /**
     * Allow processing chain to finish the authentication if authprocs require user interaction.
     * @param array $state
     * @return void
     */
    public static function authProcessingComplete(array $state): void
    {
            Source::completeAuth($state);
    }

    /**
     * Attempt to return an identifier for the OAuth2 server.
     * @return string
     */
    public function getOAuth2ServerIdentifier(): string
    {
        return $this->oauth2ServerIdentifier;
    }
}
