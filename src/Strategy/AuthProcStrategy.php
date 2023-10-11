<?php

namespace SimpleSAML\Module\authoauth2\Strategy;

use Exception;
use InvalidArgumentException;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\authoauth2\locators\SspProcessingFilterResolver;
use SimpleSAML\Module\authoauth2\locators\ProcessingFilterResolverInterface;

class AuthProcStrategy extends AbstractStrategy implements AuthProcStrategyInterface
{
    private const AUTH_PROC_KEY = 'authproc.oauth2';

    private ProcessingFilterResolverInterface $filterResolver;

    /**
     * @var array<ProcessingFilter> Filters to be applied to current state.
     */
    private array $filters = [];

    public function __construct(?ProcessingFilterResolverInterface $filterResolver = null)
    {
        $this->filterResolver = $filterResolver ?: new SspProcessingFilterResolver();
    }

    /**
     * @throws Exception
     */
    public function initWithConfig(array $config): void
    {
        if (isset($config[self::AUTH_PROC_KEY]) && is_array($config[self::AUTH_PROC_KEY])) {
            $this->filters = $this->parseFilterList($config[self::AUTH_PROC_KEY]);
        }
    }

    public function processState(array &$state): array
    {
        foreach ($this->filters as $filter) {
            $filter->process($state);
        }

        return $state;
    }

    /**
     * Parse an array of authentication processing filters.
     * @see \SimpleSAML\Auth\ProcessingChain::parseFilterList for original implementation
     *
     * @param array<mixed|string|array{class: string}> $filterSrc Array with filter configuration.
     *
     * @return ProcessingFilter[]  Array of ProcessingFilter objects.
     *
     * @throws Exception
     */
    private function parseFilterList(array $filterSrc): array
    {
        /** @var ProcessingFilter[] $parsedFilters */
        $parsedFilters = [];

        foreach ($filterSrc as $priority => $filterConfig) {
            if (is_string($filterConfig)) {
                $filterConfig = ['class' => $filterConfig];
            }

            /** @psalm-suppress DocblockTypeContradiction */
            if (!is_array($filterConfig)) {
                throw new InvalidArgumentException('Invalid authentication processing filter configuration: ' .
                    'One of the filters was not a string or an array.');
            }

            if (!array_key_exists('class', $filterConfig)) {
                throw new InvalidArgumentException('Authentication processing filter without name given.');
            }
            $sspClassString = (string)$filterConfig['class'];
            unset($filterConfig['class']);
            $filterConfig['%priority'] = $priority;

            $parsedFilters[(int)$priority] = $this->filterResolver->instantiate($sspClassString, $filterConfig);
        }

        // sort the filters by priority
        ksort($parsedFilters);

        return $parsedFilters;
    }
}
