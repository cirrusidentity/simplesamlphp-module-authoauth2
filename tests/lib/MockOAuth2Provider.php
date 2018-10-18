<?php

namespace Test\SimpleSAML;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\AbstractProvider;

class MockOAuth2Provider extends GenericProvider implements \SimpleSAML\Utils\ClearableState
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

    public function getResourceOwner(\League\OAuth2\Client\Token\AccessToken $token)
    {
        return self::$delegate->getResourceOwner($token);
    }

    public static function setDelegate(AbstractProvider $delegate)
    {
        self::$delegate = $delegate;
    }


    /**
     * Clear any cached internal state.
     */
    public static function clearInternalState()
    {
        self::$delegate = null;
    }
}
