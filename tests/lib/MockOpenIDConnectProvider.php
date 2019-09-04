<?php

namespace Test\SimpleSAML;

use SimpleSAML\Module\authoauth2\Providers\OpenIDConnectProvider;

class MockOpenIDConnectProvider extends OpenIDConnectProvider
{
    /**
     * @var array
     */
    private static $config, $keys;

    public static function setConfig(array $config)
    {
        self::$config = $config;
    }

    public static function setSigningKeys(array $keys)
    {
        self::$keys = $keys;
    }

    protected function getOpenIDConfiguration()
    {
        return self::$config;
    }

    protected function getSigningKeys()
    {
        return self::$keys;
    }
}
