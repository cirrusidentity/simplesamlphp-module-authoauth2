<?php

namespace Test\SimpleSAML;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;
use SimpleSAML\Utils\ClearableState;

class MockOAuth2Provider extends GenericProvider implements ClearableState
{
    /**
     * @var AbstractProvider
     */
    private static $delegate;

    /**
     * MockOAuth2Provider constructor.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $defaultOptions = [
            'urlAuthorize' => 'https://mock.com/authorize',
            'urlAccessToken' => 'https://mock.com/token',
            'urlResourceOwnerDetails' => 'https://mock.com/userInfo',
        ];
        parent::__construct(array_merge($options, $defaultOptions), $collaborators);
    }


    public function getAccessToken($grant, array $options = [])
    {
        return self::$delegate->getAccessToken($grant, $options);
    }

    public function getResourceOwner(AccessToken $token)
    {
        return self::$delegate->getResourceOwner($token);
    }

    public function getAuthenticatedRequest($method, $url, $token, array $options = [])
    {
        return self::$delegate->getAuthenticatedRequest($method, $url, $token, $options);
    }

    public static function setDelegate(AbstractProvider $delegate)
    {
        self::$delegate = $delegate;
    }

    public function getParsedResponse(RequestInterface $request)
    {
        return self::$delegate->getParsedResponse($request);
    }

    public function setPkceCode($pkceCode): void
    {
        self::$delegate->setPkceCode($pkceCode);
    }

    public function getPkceCode()
    {
        return self::$delegate->getPkceCode();
    }

    /**
     * Clear any cached internal state.
     */
    public static function clearInternalState(): void
    {
        self::$delegate = null;
    }
}
