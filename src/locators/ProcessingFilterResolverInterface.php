<?php

namespace SimpleSAML\Module\authoauth2\locators;

use SimpleSAML\Auth\ProcessingFilter;

interface ProcessingFilterResolverInterface
{
    public function instantiate(string $sspClassString, array $filterConfig): ProcessingFilter;
}
