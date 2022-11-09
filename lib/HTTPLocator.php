<?php

namespace SimpleSAML\Module\authoauth2;

use SimpleSAML\Utils\HTTP;

/**
 * Indicates that class will use getHttp() to get an instance of
 * HTTP, allowing a mock to be used in it's place
 */
trait HTTPLocator
{

    private HTTP $http;

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
     * @param HTTP $http
     */
    public function setHttp(HTTP $http): void
    {
        $this->http = $http;
    }
}