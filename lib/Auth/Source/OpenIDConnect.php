<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr6CacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Auth;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\authoauth2\Providers\OpenIDConnectProvider;
use SimpleSAML\Utils\HTTP;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Authentication source to authenticate with a generic OpenID Connect idp
 *
 * @package SimpleSAML\Module\authoauth2
 */
class OpenIDConnect extends \SimpleSAML\Module\authoauth2\Auth\Source\OAuth2
{

    /** String used to identify our states. */
    const STAGE_LOGOUT = 'authouath2:logout';
    protected static $defaultProviderClass = OpenIDConnectProvider::class;

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
        $provider = parent::getProvider($config);
        $httpClient = $provider->getHttpClient();
        $handler = $httpClient->getConfig('handler');
        if (!($handler instanceof HandlerStack)) {
            $newhandler = HandlerStack::create();
            $newhandler->push($handler);
            $httpClieng->getConfig()['handler'] = $newhandler;
            $handler = $newhandler;
        }
        $cacheDir = \SimpleSAML\Configuration::getInstance()->getString('tempdir') . "/oidc-cache";
        $handler->push(
            new CacheMiddleware(
                new PrivateCacheStrategy(
                    new Psr6CacheStorage(
                        new FilesystemAdapter('', 0, $cacheDir)
                    )
                ),
                'cache'
            )
        );
        return $provider;
    }

    /**
     * Convert values from the state parameter of the authenticate call into options to the authorization request.
     *
     * Any parameter prefixed with oidc: are added (without the prefix), in
     * addition isPassive and ForceAuthn are converted into prompt=none and
     * prompt=login respectively
     *
     * @param array $state
     * @return array
     */
    protected function getAuthorizeOptionsFromState(&$state)
    {
        $result = [];
        foreach ($state as $key => $value) {
            if (strpos($key, 'oidc:') === 0) {
                $result[substr($key, 5)] = $value;
            }
        }
        if (array_key_exists('ForceAuthn', $state) && $state['ForceAuthn']) {
            $result['prompt'] = 'login';
        }
        if (array_key_exists('isPassive', $state) && $state['isPassive']) {
            $result['prompt'] = 'none';
        }
        return $result;
    }


    /**
     * This method is overriding the default empty implementation to parse attributes received in the id_token, and
     * place them into the attributes array.
     *
     * @inheritdoc
     */
    protected function postFinalStep(AccessToken $accessToken, AbstractProvider $provider, &$state)
    {
        $prefix = $this->getAttributePrefix();
        $id_token = $accessToken->getValues()['id_token'];
        $id_token_claims = $this->extraIdTokenAttributes($id_token);
        $state['Attributes'] = array_merge($this->convertResourceOwnerAttributes(
            $id_token_claims,
            $prefix . 'id_token' . '.'
        ), $state['Attributes']);
        $state['id_token'] = $id_token;
        $state['PersistentAuthData'][] = 'id_token';
        $state['LogoutState'] = ['id_token' => $id_token];
    }

    /**
     * Log out from upstream idp if possible
     *
     * @param array &$state Information about the current logout operation.
     * @return void
     */
    public function logout(&$state)
    {
        $providerLabel = $this->getLabel();
        if (array_key_exists('oidc:localLogout', $state) && $state['oidc:localLogout'] === true) {
            Logger::debug("authoauth2: $providerLabel OP initiated logout");
            return;
        }
        $endSessionEndpoint = $this->getProvider($this->config)->getEndSessionEndpoint();
        if (!$endSessionEndpoint) {
            Logger::debug("authoauth2: $providerLabel OP does not provide an 'end_session_endpoint',".
                          " not doing anything for logout");
            return;
        }

        if (!array_key_exists('id_token', $state)) {
            Logger::debug("authoauth2: $providerLabel No id_token in state, not doing anything for logout");
            return;
        }
        $id_token = $state['id_token'];

        $postLogoutUrl = $this->config->getString('postLogoutRedirectUri', null);
        if (!$postLogoutUrl) {
            $postLogoutUrl = Module::getModuleURL('authoauth2/loggedout.php');
        }

        // We are going to need the authId in order to retrieve this authentication source later, in the callback
        $state[self::AUTHID] = $this->getAuthId();

        $stateID = \SimpleSAML\Auth\State::saveState($state, self::STAGE_LOGOUT);
        $endSessionURL = HTTP::addURLParameters($endSessionEndpoint, [
            'id_token_hint' => $id_token,
            'post_logout_redirect_uri' => $postLogoutUrl,
            'state' => self::STATE_PREFIX . '-' . $stateID,
        ]);
        HTTP::redirectTrustedURL($endSessionURL);
        // @codeCoverageIgnoreStart
    }
}
