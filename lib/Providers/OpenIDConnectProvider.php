<?php

namespace SimpleSAML\Module\authoauth2\Providers;

use Firebase\JWT;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use SimpleSAML\Logger;
use SimpleSAML\Utils\HTTP;

class OpenIDConnectProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const CONFIGURATION_PATH = '/.well-known/openid-configuration';
    const ERROR_FIELD = 'error';

    /**
     * @var string
     */
    protected $issuer;

    /**
     * @var array
     */
    private $openIdConfiguration;

    /**
     * @var string
     */
    private $responseError = 'error';

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if (!array_key_exists('issuer', $options)) {
            throw new \InvalidArgumentException(
                'Required options not defined: issuer'
            );
        }
        $this->issuer = $options['issuer'];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function getDefaultScopes()
    {
        return 'openid profile';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        $error = null;
        if (!empty($data[$this->responseError])) {
            $error = $data[$this->responseError];
            if (!is_string($error)) {
                $error = var_export($error, true);
            }
        }
        if ($error || $response->getStatusCode() >= 400) {
            throw new IdentityProviderException($error, 0, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner($response, 'id');
    }

    /**
     * Do any required verification of the id token and return an array of decoded claims
     *
     * @param string $id_token Raw id token as string
     * @return array associative array of claims decoded from the id token
     */
    public function verifyIdToken($id_token)
    {
        try {
            $keys = $this->getSigningKeys();
            $claims = JWT\JWT::decode($id_token, $keys, ['RS256']);
            if ($claims->aud !== $this->clientId) {
                throw new IdentityProviderException("ID token has incorrect audience", 0, $claims->aud);
            }
            if ($claims->iss !== $this->issuer) {
                throw new IdentityProviderException("ID token has incorrect issuer", 0, $claims->iss);
            }
        } catch (\UnexpectedValueException $e) {
            throw new IdentityProviderException("ID token validation failed", 0, $e->getMessage());
        }
    }

    protected function prepareAccessTokenResponse(array $result)
    {
        $result = parent::prepareAccessTokenResponse($result);
        $this->verifyIdToken($result['id_token']);
        return $result;
    }

    protected function getOpenIDConfiguration()
    {
        if (isset($this->openIdConfiguration)) {
            return $this->openIdConfiguration;
        }

        $config = $this->getParsedResponse($this->getRequest('GET', $this->issuer . self::CONFIGURATION_PATH));
        $requiredEndPoints = [ "authorization_endpoint", "token_endpoint", "jwks_uri", "issuer", "userinfo_endpoint" ];
        foreach ($requiredEndPoints as $key) {
            if (!array_key_exists($key, $config)) {
                throw new \UnexpectedValueException("OpenID Configuration data misses required key: " . $key);
            }
            if (!is_string($config[$key])) {
                throw new \UnexpectedValueException("OpenID Configuration data for key: " . $key . " is not a string");
            }
            if (substr($config[$key], 0, 8) !== 'https://') {
                throw new \UnexpectedValueException("OpenID Configuration data for key " . $key .
                                                    " should be url. Got: " . $config[$key]);
            }
        }
        if ($config['issuer'] !== $this->issuer) {
            throw new \UnexpectedValueException("OpenID Configuration data contains unexpected issuer: " .
                                                $config['issuer'] . " expected: " . $this->issuer);
        }
        $optionalEndPoints = ['end_session_endpoint'];
        foreach ($optionalEndPoints as $key) {
            if (array_key_exists($key, $config)) {
                if (!is_string($config[$key])) {
                    throw new \UnexpectedValueException("OpenID Configuration data for key: " . $key .
                                                        " is not a string");
                }
                if (substr($config[$key], 0, 8) !== 'https://') {
                    throw new \UnexpectedValueException("OpenID Configuration data for key " . $key .
                                                        " should be url. Got: " . $config[$key]);
                }
            }
        }
        $this->openIdConfiguration = $config;
        return $config;
    }

    protected static function base64urlDecode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    protected function getSigningKeys()
    {
        $url = $this->getOpenIDConfiguration()['jwks_uri'];
        $jwks = $this->getParsedResponse($this->getRequest('GET', $url));
        $keys = [];
        foreach ($jwks['keys'] as $key) {
            $kid = $key['kid'];
            if (array_key_exists('x5c', $key)) {
                $x5c = $key['x5c'];
                $keys[$kid] = "-----BEGIN CERTIFICATE-----\n" . $x5c[0] . "\n-----END CERTIFICATE-----";
            } elseif ($key['kty'] === 'RSA') {
                $e = self::base64urlDecode($key['e']);
                $n = self::base64urlDecode($key['n']);
                $keys[$kid] = \RobRichards\XMLSecLibs\XMLSecurityKey::convertRSA($n, $e);
            } else {
                Logger::warning("Failed to load key data for key id: " . $kid);
            }
        }
        return $keys;
    }

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->getOpenIDConfiguration()["authorization_endpoint"];
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getOpenIDConfiguration()["token_endpoint"];
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getOpenIDConfiguration()["userinfo_endpoint"];
    }

    public function getEndSessionEndpoint()
    {
        $config = $this->getOpenIDConfiguration();
        if (array_key_exists("end_session_endpoint", $config)) {
            return $config["end_session_endpoint"];
        }
        return null;
    }
}
