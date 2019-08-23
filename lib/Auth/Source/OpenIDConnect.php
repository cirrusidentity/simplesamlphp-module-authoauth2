<?php

namespace SimpleSAML\Module\authoauth2\Auth\Source;

use Firebase\JWT;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Auth;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

/**
 * Authentication source to authenticate with a generic OpenID Connect idp
 *
 * @package SimpleSAML\Module\authoauth2
 */
class OpenIDConnect extends \SimpleSAML\Module\authoauth2\Auth\Source\OAuth2
{

    /** String used to identify our states. */
    const STAGE_LOGOUT = 'authouath2:logout';


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
     * Do any required verification of the id token and return an array of decoded claims
     *
     * @param string $id_token Raw id token as string
     * @return array associative array of claims decoded from the id token
     */
    protected function verifyIdToken($id_token) {
        $keys = $this->config->getArray('keys', null);
        if ($keys) {
            try {
                JWT\JWT::decode($id_token, $keys, ['RS256']);
            } catch (\UnexpectedValueException $e) {
                $e2 = new \SimpleSAML\Error\AuthSource(
                    $this->getAuthId(),
                    "ID token validation failed: " . $e->getMessage()
                );
                \SimpleSAML\Auth\State::throwException($state, $e2);
            }
        }
        $id_token_claims = $this->extraIdTokenAttributes($id_token);
        if ($id_token_claims['aud'] !== $this->config->getString('clientId')) {
            $e = new \SimpleSAML\Error\AuthSource($this->getAuthId(), "ID token has incorrect audience");
            \SimpleSAML\Auth\State::throwException($state, $e);
        }
        $issuer = $this->config->getString('issuer', null);
        if ($issuer && $id_token_claims['iss'] !== $issuer) {
            $e = new \SimpleSAML\Error\AuthSource($this->getAuthId(), "ID token has incorrect issuer");
            \SimpleSAML\Auth\State::throwException($state, $e);
        }
        return $id_token_claims;
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
        $id_token_claims = $this->verifyIdToken($id_token);
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
        $endSessionEndpoint = $this->config->getString('urlEndSession', null);
        if (!$endSessionEndpoint) {
            Logger::debug("authoauth2: $providerLabel No urlEndSession configured, not doing anything for logout");
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
