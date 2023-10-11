<?php

namespace Test\SimpleSAML\Strategy;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Module\authoauth2\locators\ProcessingFilterResolverInterface;
use SimpleSAML\Module\authoauth2\Strategy\AuthProcStrategy;

class AuthProcStrategyTest extends TestCase
{
    /**
     * test that the strategy can be instantiated and that the filters are processed in the correct order.
     */
    public function testProcessStateWithSomeFilters(): void
    {
        // Create a mock filter that expects to process state once
        $mockFilterA = $this->createMock(ProcessingFilter::class);
        $mockFilterA->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (array &$state) {
                    $state['processedA'] = true;
                    $state['lastProcess'] = 'A';
                }
            );
        $mockFilterB = $this->createMock(ProcessingFilter::class);
        $mockFilterB->expects($this->once())
            ->method('process')
            ->willReturnCallback(
                function (array &$state) {
                    $state['processedB'] = true;
                    $state['lastProcess'] = 'B';
                }
            );

        // Mock the filter resolver to return our mock filter when instantiated
        $mockFilterResolver = $this->createMock(ProcessingFilterResolverInterface::class);
        $mockFilterResolver->expects($this->exactly(2))
            ->method('instantiate')
            ->willReturn($mockFilterA, $mockFilterB);

        // Instantiate AuthProcStrategy with our mock filter resolver
        $authProcStrategy = new AuthProcStrategy($mockFilterResolver);

        // Assume valid config
        $config = [
            'authproc.oauth2' => [
                20 => ['class' => 'Some\Filter\Class'],
                10 => ['class' => 'Some\Filter\Class'],
            ],
        ];

        $authProcStrategy->initWithConfig($config);

        $state = [];
        $authProcStrategy->processState($state);

        // Assert that state was processed by our mock filters
        $this->assertTrue($state['processedA']);
        $this->assertTrue($state['processedB']);
        // Test, that the filters ran in a certain order
        $this->assertEquals('A', $state['lastProcess']);
    }
}
