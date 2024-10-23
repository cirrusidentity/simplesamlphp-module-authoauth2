<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller;

use DG\BypassFinals;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\Source;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Module\authoauth2\Controller\OIDCLogoutController;
use SimpleSAML\Module\authoauth2\Controller\Traits\RequestTrait;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

// Unless we declare the class here, it is not recognized by phpcs
class OIDCLogoutControllerMock extends OIDCLogoutController
{
    use RequestTrait;

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

    public function getExpectedStageState(): string
    {
        return $this->expectedStageState;
    }

    public function getExpectedPrefix(): string
    {
        return $this->expectedPrefix;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class OIDCLogoutControllerTest extends TestCase
{
    /** @var OIDCLogoutControllerMock */
    private $controller;
    /** @var Request */
    private $requestMock;
    /** @var Configuration */
    private $configMock;
    private array $stateMock;
    /** @var SourceService */
    private $sourceServiceMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);

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

    public function testExpectedConstVariables(): void
    {
        $this->assertEquals(OpenIDConnect::STAGE_LOGOUT, $this->controller->getExpectedStageState());
        $this->assertEquals(OAuth2::STATE_PREFIX . '-', $this->controller->getExpectedPrefix());
    }

    public function testLoggedOutSuccess(): void
    {
        $this->controller->setState($this->stateMock);

        $this->requestMock->request = $this->createRequestMock([]);

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
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
    private function createQueryMock(array $params): InputBag
    {
        $queryMock = $this->getMockBuilder(InputBag::class)->getMock();
        $queryMock->method('has')->willReturnCallback(
            function (string $key) use ($params) {
                return array_key_exists($key, $params);
            }
        );

        $queryMock->method('get')->willReturnCallback(
            function (?string $key) use ($params) {
                return $params[$key] ?? null;
            }
        );
        return $queryMock;
    }

    // Mock helper function
    private function createRequestMock(array $params): InputBag
    {
        $queryMock = $this->getMockBuilder(InputBag::class)->getMock();

        $queryMock->method('get')->willReturnCallback(
            function (?string $key) use ($params) {
                return $params[$key] ?? null;
            }
        );

        $queryMock->method('all')->willReturnCallback(
            function (?string $key) use ($params) {
                return $params[$key] ?? [];
            }
        );
        return $queryMock;
    }
}
