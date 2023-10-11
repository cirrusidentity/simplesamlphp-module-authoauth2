<?php

namespace SimpleSAML\Module\authoauth2\locators;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module;

class SspProcessingFilterResolver implements ProcessingFilterResolverInterface
{
    public function instantiate(string $sspClassString, array $filterConfig): ProcessingFilter
    {
        /** @var class-string<ProcessingFilter> $className */
        $className = Module::resolveClass(
            $sspClassString,
            'Auth\Process',
            ProcessingFilter::class
        );

        /** @psalm-suppress UnsafeInstantiation */
        return new $className($filterConfig, null);
    }
}
