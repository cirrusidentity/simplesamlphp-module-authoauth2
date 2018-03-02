<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SAML2\Utils;
use SimpleSAML\Logger;
use SimpleSAML\Module\authoauth2\AttributeManipulator;
use SimpleSAML\Module\authoauth2\ConfigTemplate;
use SimpleSAML\Utils\Arrays;
use SimpleSAML\Utils\HTTP;

/**
 * Authenticate using Oauth2.
 *
 */
class OAuth2 extends \SimpleSAML_Auth_Source
{


    /** String used to identify our states. */
    const STAGE_INIT = 'authouath2:init';

    /** Key of AuthId field in state. */
    const AUTHID = 'authouath2:AuthId';

    /** Used to aid migrating other Oauth2 SSP libraries to this one */
    const STATE_PREFIX = 'authoauth2';

    /**
     * @var \SimpleSAML_Configuration
     */
    private $config;


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
            $config['redirectUri'] = \SimpleSAML\Module::getModuleURL('authoauth2/linkback.php');
        }
        if (!array_key_exists('timeout', $config)) {
            $config['timeout'] = 3;
        }
        $this->config = \SimpleSAML_Configuration::loadFromArray($config, 'authsources:oauth2');


    }

    /**
     * Return a label for the OAuth2 provider that can be used in log statements, etc
     * @return string
     */
    private function getLabel() {
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

        $stateID = \SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);

        $providerLabel = $this->getLabel();
        Logger::debug("authoauth2: $providerLabel saved state with stateID=$stateID");

        $options = $this->config->getArray('urlAuthorizeOptions', []);
        // Add a prefix so we can tell we are the intended recipient for a redirect URI if the redirect URI was customized
        $options['state'] = self::STATE_PREFIX . '|' . $stateID;
        $authorizeURL = $provider->getAuthorizationUrl($options);
        Logger::debug("authoauth2: $providerLabel redirecting to authorizeURL=$authorizeURL");

        HTTP::redirectTrustedURL($authorizeURL);
    }

    /**
     * Get the provider to use to talk to the OAuth2 server.
     *
     * Since SSP may serialize Auth modules we don't assign the potentially unserializable provider to a field.
     * @param \SimpleSAML_Configuration $config
     * @return \League\OAuth2\Client\Provider\AbstractProvider
     */
    protected function getProvider(\SimpleSAML_Configuration $config) {

        if ($config->hasValue('providerClass')) {
            $providerClass = $config->getString('providerClass');
            if (class_exists($providerClass)) {
                if ( !is_subclass_of($providerClass, AbstractProvider::class)) {
                    throw new \InvalidArgumentException("The OAuth2 provider '$providerClass' does not extend " . AbstractProvider::class);
                }
                return new $providerClass($config->toArray());
            } else {
                throw new \InvalidArgumentException("No OAuth2 provider class found for '$providerClass'.");
            }
        }
        return new GenericProvider($config->toArray());
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

        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $oauth2Code
        ]);

        $resourceOwner = $provider->getResourceOwner($accessToken);

        $attributes = $resourceOwner->toArray();
        $prefix = $this->config->getString('attributePrefix', '');
        $attributeManipulator = new AttributeManipulator();
        $state['Attributes'] = $attributeManipulator->prefixAndFlatten($attributes, $prefix);
        $this->postFinalStep($accessToken, $provider, $state);
        Logger::debug('authoauth2: ' . $providerLabel . ' attributes: ' . implode(", ", array_keys($state['Attributes'] )));
        // Track time spent calling out to oauth2 server. This can often be a source of slowness.
        $time = microtime(true) - $start;
        Logger::debug('authoauth2: ' . $providerLabel . ' finished authentication in ' . $time . ' seconds');

    }

    /**
     * Allow subclasses to invoked any additional methods, such as extra API calls
     * @param AccessToken $accessToken The user's access token
     * @param AbstractProvider $provider The Oauth2 provider
     * @param array $state The current state
     */
    protected function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, &$state) {

    }

    /**
     * Get the configuration used for this filter
     * @return \SimpleSAML_Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

}