<?php

namespace Test\SimpleSAML;

use AspectMock\Test as test;
use CirrusIdentity\SSP\Test\SanityChecker;
use PHPUnit\Framework\TestCase;

class AspectMockConfiguredTest extends TestCase
{

    protected function tearDown(): void
    {
        test::clean(); // remove all registered test doubles
    }

    /**
     * Test to confirm AspectMock is configured correctly. phpunit bootstrap.php has the configuration for AspectMock
     * and that is where you can tell it which classes to do its AOP magic on.
     */
    public function testAspectMockConfigured()
    {
        // Ensure mocks are configured for SSP classes
        $httpDouble = SanityChecker::confirmAspectMockConfigured();

        // We can also validate the that a method was called.
        $httpDouble->verifyInvokedOnce('getAcceptLanguage');

        $this->assertTrue(true, 'No exception thrown above');
    }
}
