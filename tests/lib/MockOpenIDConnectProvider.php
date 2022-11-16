<?php

namespace Test\SimpleSAML;

use SimpleSAML\Configuration;
use SimpleSAML\Module\authoauth2\Providers\OpenIDConnectProvider;

class MockOpenIDConnectProvider extends OpenIDConnectProvider
{
    private static Configuration $config;

    private static array $keys;

    public static function setConfig(array $config)
    {
        self::$config = Configuration::loadFromArray($config);
    }

    public static function setSigningKeys(array $keys)
    {
        self::$keys = $keys;
    }

    protected function getOpenIDConfiguration(): Configuration
    {
        return self::$config;
    }

    protected function getSigningKeys(): array
    {
        return self::$keys;
    }
}
