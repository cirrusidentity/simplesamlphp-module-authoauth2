<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\authoauth2\Controller\OIDCLogoutController;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class OIDCLogoutControllerMock extends OIDCLogoutController {
    public function setSource(Source $source): void
    {
        $this->source = $source;
    }

    public function setState(array $state): void
    {
        $this->state = $state;
    }

    public function setSourceId(string $sourceId): void
    {
        $this->sourceId = $sourceId;
    }
}

class OIDCLogoutControllerTest extends TestCase
{
    private $controller;
    private $requestMock;
    private $configMock;
    private array $stateMock;
    private $sourceServiceMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Configuration::class);

        // Initial state setup
        $this->stateMock = ['state' => 'testState'];

        $this->controller = $this->getMockBuilder(OIDCLogoutControllerMock::class)
            ->setConstructorArgs([$this->configMock])
            ->onlyMethods(['parseRequest', 'getSourceService', 'getAuthSource'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Request::class)->getMock();

        $this->controller->method('getSourceService')
            ->willReturn($this->createMock(SourceService::class));

        $this->sourceServiceMock = $this->controller->getSourceService();
    }

    public function testLoggedOutSuccess(): void
    {
        $this->controller->setState($this->stateMock);

        $this->requestMock->request = $this->createRequestMock([]);

        $this->sourceServiceMock
            ->expects($this->once())
            ->method('completeLogout')
            ->with($this->stateMock);

        $this->controller->loggedout($this->requestMock);


        // Assertions to verify behavior
    }

    public function testLogoutWithoutAuthSourceThrowsBadRequest(): void
    {
        $this->requestMock->query = $this->createQueryMock([]);
        $this->requestMock->request = $this->createRequestMock([]);

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('No authsource in the request');

        $this->controller->logout($this->requestMock);
    }

    public function testLogoutWithInvalidAuthSourceThrowsBadRequest(): void
    {
        $this->requestMock->query = $this->createQueryMock(['authSource' => '']);
        $this->requestMock->request = $this->createRequestMock([]);

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('Authsource ID invalid');

        $this->controller->logout($this->requestMock);
    }

    // Mock helper function
    private function createQueryMock(array $params)
    {
        $queryMock = $this->getMockBuilder(ParameterBag::class)->getMock();
        $queryMock->method('has')->willReturnCallback(
            function ($key) use ($params) {
                return array_key_exists($key, $params);
            }
        );

        $queryMock->method('get')->willReturnCallback(
            function ($key) use ($params) {
                return $params[$key] ?? null;
            }
        );
        return $queryMock;
    }

    // Mock helper function
    private function createRequestMock(array $params)
    {
        $queryMock = $this->getMockBuilder(ParameterBag::class)->getMock();

        $queryMock->method('get')->willReturnCallback(
            function ($key) use ($params) {
                return $params[$key] ?? null;
            }
        );

        $queryMock->method('all')->willReturnCallback(
            function ($key) use ($params) {
                return $params[$key] ?? [];
            }
        );
        return $queryMock;
    }
}