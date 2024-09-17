<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\Source;
use SimpleSAML\Error\AuthSource;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Controller\Oauth2Controller;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class Oauth2ControllerMock extends Oauth2Controller {
    public function handleError(OAuth2 $source, array $state, Request $request): void
    {
        parent::handleError($source, $state, $request);
    }

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

class Oauth2ControllerTest extends TestCase
{
    private $controller;
    private $requestMock;
    private $oauth2Mock;
    private array $stateMock;

    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(Oauth2ControllerMock::class)
            ->onlyMethods(['parseRequest', 'handleError', 'getSourceService'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(Request::class)->getMock();
        $this->oauth2Mock = $this->getMockBuilder(OAuth2::class)->disableOriginalConstructor()->getMock();
        $this->stateMock = ['state' => 'testState'];

        // Stubbing dependencies
        $this->controller->setSource($this->oauth2Mock);
        $this->controller->setState($this->stateMock);
        $this->controller->setSourceId('testSourceId');
    }

    public function testLinkbackValidCode(): void
    {
        $this->requestMock->query = $this->createQueryMock(['code' => 'validCode']);

        $this->oauth2Mock->expects($this->once())
            ->method('finalStep')
            ->with($this->stateMock, 'validCode');

        $this->controller
            ->method('getSourceService')
            ->willReturn($this->createMock(SourceService::class));

        $this->controller->linkback($this->requestMock);

        // Assertions for the success scenario can be done here
    }

    public function testLinkbackWithNoCode(): void
    {
        $this->requestMock->query = $this->createQueryMock([]);

        $this->controller->expects($this->once())
            ->method('handleError');

        $this->controller->linkback($this->requestMock);
    }

    public function testLinkbackWithIdentityProviderException(): void
    {
        $this->requestMock->query = $this->createQueryMock(['code' => 'validCode']);

        $this->oauth2Mock->expects($this->once())
            ->method('finalStep')
            ->willThrowException(new IdentityProviderException('Error Message', 0, ['body' => 'error body']));

        $this->expectException(AuthSource::class);

        $this->controller->linkback($this->requestMock);
    }

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
}