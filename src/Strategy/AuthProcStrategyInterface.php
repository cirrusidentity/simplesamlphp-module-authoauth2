<?php

namespace SimpleSAML\Module\authoauth2\Strategy;

interface AuthProcStrategyInterface extends StrategyInterface
{
    public function processState(array &$state): array;
}
