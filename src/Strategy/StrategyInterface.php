<?php

namespace SimpleSAML\Module\authoauth2\Strategy;

interface StrategyInterface
{
    public function initWithConfig(array $config): void;
}
