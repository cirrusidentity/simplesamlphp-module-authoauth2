<?php

namespace SimpleSAML\Module\authoauth2\Strategy;

use SimpleSAML\Module\authoauth2\Strategy\StrategyInterface;

class AbstractStrategy implements StrategyInterface
{
    protected array $config = [];
    public function initWithConfig(array $config): void
    {
        $this->config = $config;
    }
}
