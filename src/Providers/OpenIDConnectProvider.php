<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Providers;

use Firebase\JWT;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

class OpenIDConnectProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public const CONFIGURATION_PATH = '/.well-known/openid-configuration';
    public const ERROR_FIELD = 'error';

    /**
     * @var string
     */
    protected string $issuer;

    /**
     * @var string
     */
    protected string $discoveryUrl;

    /**
     * @var ?string
     */
    protected ?string $pkceMethod = null;

    /**
     * @var ?Configuration
     */
    private ?Configuration $openIdConfiguration = null;

    /**
     * @var string
     */
    private string $responseError = 'error';

    /**
     * @var array
     */
    private array $defaultScopes;

    /**
     * @var bool
     */
    protected bool $validateIssuer = false;

    protected ?string $urlResourceOwnerDetails = null;

    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        $optionsConfig = Configuration::loadFromArray($options);
        $this->issuer = $optionsConfig->getString('issuer');
        $this->discoveryUrl = $optionsConfig->getOptionalString(
            'discoveryUrl',
            rtrim($this->issuer, '/') . self::CONFIGURATION_PATH
        );
        $this->defaultScopes = $optionsConfig->getOptionalArray('scopes', ['openid', 'profile']);
        $this->validateIssuer = $optionsConfig->getOptionalBoolean('validateIssuer', true);
        $this->urlResourceOwnerDetails = $optionsConfig->getOptionalString('urlResourceOwnerDetails', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * @return array
     */
    protected function getDefaultScopes(): array
    {
        return $this->defaultScopes;
    }

    /**
     * @param   ResponseInterface  $response
     * @param                      $data
     *
     * @return void
     * @throws IdentityProviderException
     * @psalm-suppress MissingParamType
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        /** @var string $error */
        /** @var array|string $data */
        $error = null;
        if (!empty($data[$this->responseError])) {
            if (\is_string($data[$this->responseError])) {
                $error = $data[$this->responseError];
            } else {
                $error = var_export($data[$this->responseError], true);
            }
        }
        if ($error || $response->getStatusCode() >= 400) {
            throw new IdentityProviderException($error ?? '', 0, $data);
        }
    }

    /**
     * @param   array        $response
     * @param   AccessToken  $token
     *
     * @return GenericResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner($response, 'id');
    }

    /**
     * Do any required verification of the id token and return an array of decoded claims
     *
     * @param   string  $id_token  Raw id token as string
     *
     * @throws IdentityProviderException
     */
    public function verifyIdToken(string $id_token): void
    {
        try {
            $keysRaw = $this->getSigningKeys();
            $keys = [];
            // Be explicit about key algorithms to avoid bug reports of key confusion.
            foreach ($keysRaw as $kid => $key) {
                $keys[$kid] = new JWT\Key($key, 'RS256');
            }
            // Once firebase/php-jwt 5.5 support is dropped we can move to firebase's parsing
            //JWT\JWK::parseKeySet($keys, 'RS256');
            $claims = JWT\JWT::decode($id_token, $keys);
            $aud = is_array($claims->aud) ? $claims->aud : [$claims->aud];

            if (!in_array($this->clientId, $aud)) {
                throw new IdentityProviderException("ID token has incorrect audience", 0, $claims->aud);
            }
            // When working with Azure the issuer is tenant specific, but the discovery metadata can be for all tenants
            if ($this->validateIssuer && $claims->iss !== $this->issuer) {
                throw new IdentityProviderException(
                    "ID token has incorrect issuer. Expected '{$this->issuer}' recieved '{$claims->iss}'",
                    0,
                    $claims->iss
                );
            }
        } catch (\UnexpectedValueException $e) {
            throw new IdentityProviderException("ID token validation failed", 0, $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType superClass has phpdoc doesn't align with parameter type
     */
    protected function prepareAccessTokenResponse(array $result)
    {
        $result = parent::prepareAccessTokenResponse($result);
        $this->verifyIdToken((string)$result['id_token']);
        return $result;
    }

    /**
     * @return string
     */
    public function getDiscoveryUrl(): string
    {
        return $this->discoveryUrl;
    }

    /**
     * @return Configuration
     * @throws IdentityProviderException
     */
    protected function getOpenIDConfiguration(): Configuration
    {
        if (isset($this->openIdConfiguration)) {
            return $this->openIdConfiguration;
        }

        $req = $this->getRequest('GET', $this->getDiscoveryUrl());
        /** @var array $config */
        $config = $this->getParsedResponse($req);
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
            throw new \UnexpectedValueException(
                'OpenID Configuration data contains unexpected issuer: ' .
                (string)$config['issuer'] . ' expected: ' . $this->issuer);
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
        $this->openIdConfiguration =  Configuration::loadFromArray($config);
        return $this->openIdConfiguration;
    }

    /**
     * @param string $input
     * @return false|string
     */
    protected static function base64urlDecode(string $input): false|string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * @throws IdentityProviderException
     * @return array<string, string> $keys
     *
     * The response will get us a mixed value back. As a result we suppress the MixedAssignment error
     * @psalm-suppress MixedAssignment
     */
    protected function getSigningKeys(): array
    {
        $url = $this->getOpenIDConfiguration()->getString('jwks_uri');
        /** @var array $jwks */
        $jwks = $this->getParsedResponse($this->getRequest('GET', $url));
        $keys = [];
        foreach ($jwks['keys'] as $key) {
            /** @psalm-var array<string, string> $key */
            $kid = $key['kid'];
            if (\array_key_exists('x5c', $key)) {
                /** @var array $x5c */
                $x5c = $key['x5c'];
                $keys[$kid] = "-----BEGIN CERTIFICATE-----\n" . (string)$x5c[0] . "\n-----END CERTIFICATE-----";
            } elseif ($key['kty'] === 'RSA') {
                $e = self::base64urlDecode($key['e']);
                $n = self::base64urlDecode($key['n']);
                if (!$n || !$e) {
                    Logger::warning('Failed to base64 decode key data for key id: ' . $kid);
                    continue;
                }
                $keys[$kid] = \RobRichards\XMLSecLibs\XMLSecurityKey::convertRSA($n, $e);
            } else {
                Logger::warning('Failed to load key data for key id: ' . $kid);
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
     * @throws IdentityProviderException
     */
    public function getBaseAuthorizationUrl(): string
    {
        return $this->getOpenIDConfiguration()->getString("authorization_endpoint");
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param   array  $params
     *
     * @return string
     * @throws IdentityProviderException
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->getOpenIDConfiguration()->getString('token_endpoint');
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->urlResourceOwnerDetails ?? $this->getOpenIDConfiguration()->getString("userinfo_endpoint");
    }

    /**
     * @return string|null
     * @throws IdentityProviderException
     */
    public function getEndSessionEndpoint(): ?string
    {
        $config = $this->getOpenIDConfiguration();
        return $config->getOptionalString('end_session_endpoint', null);
    }

    /**
     * @return string|null
     */
    protected function getPkceMethod(): ?string
    {
        return $this->pkceMethod ?: parent::getPkceMethod();
    }
}
