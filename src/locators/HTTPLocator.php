<?php

namespace SimpleSAML\Module\authoauth2\locators;

use SimpleSAML\Utils\HTTP;

/**
 * Indicates that class will use getHttp() rather than `new` to get an instance of
 * HTTP, allowing a mock to be used in its place
 */
trait HTTPLocator
{
    private ?HTTP $http = null;

    /**
     * Used to allow tests to override
     * @return HTTP
     */
    public function getHttp(): HTTP
    {
        if (!isset($this->http)) {
            $this->http = new HTTP();
        }
        return $this->http;
    }

    /**
     * @param ?HTTP $http
     */
    public function setHttp(?HTTP $http): void
    {
        $this->http = $http;
    }
}
