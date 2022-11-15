<?php

namespace SimpleSAML\Module\authoauth2\locators;

use SimpleSAML\Auth\Source;

class SourceService
{
    /**
     * Pass through to static Source methods. Allows for easier mocking
     * @see Source::completeAuth()
     */
    public function completeAuth(array &$state): void
    {
        Source::completeAuth($state);
    }

    /**
     * Pass through to static Source methods. Allows for easier mocking
     * @see Source::getById()
     */
    public function getById(string $authId, ?string $type = null): ?Source
    {
        return Source::getById($authId, $type);
    }

    /**
     * Pass through to static Source methods. Allows for easier mocking
     * @see Source::completeLogout()
     */
    public function completeLogout(array &$state): void
    {
        Source::completeLogout($state);
    }
}
