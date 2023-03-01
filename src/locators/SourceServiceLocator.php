<?php

namespace SimpleSAML\Module\authoauth2\locators;

use SimpleSAML\Auth\Source;
use SimpleSAML\Utils\HTTP;

/**
 * Indicates that class will use getHttp() rather than `new` to get an instance of
 * HTTP, allowing a mock to be used in its place
 */
trait SourceServiceLocator
{
    private ?SourceService $sourceService = null;

    /**
     * @return SourceService
     */
    public function getSourceService(): SourceService
    {
        if (!isset($this->sourceService)) {
            $this->sourceService = new SourceService();
        }
        return $this->sourceService;
    }

    /**
     * @param SourceService $sourceService
     */
    public function setSourceService(SourceService $sourceService): void
    {
        $this->sourceService = $sourceService;
    }
}
