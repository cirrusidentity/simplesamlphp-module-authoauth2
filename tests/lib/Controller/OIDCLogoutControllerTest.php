<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller;

use DG\BypassFinals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Module\authoauth2\Codebooks\RoutesEnum;
use SimpleSAML\Module\authoauth2\Controller\OIDCLogoutController;
use SimpleSAML\Module\authoauth2\Controller\Traits\RequestTrait;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use Symfony\Component\HttpFoundation\Request;

// Unless we declare the class here, it is not recognized by phpcs
class OIDCLogoutControllerMock extends OIDCLogoutController
{
    use RequestTrait;

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
    /** @var SourceService */
    private $sourceServiceMock;
    /** @var \PHPUnit\Framework\MockObject\MockObject|(OAuth2&\PHPUnit\Framework\MockObject\MockObject) */
    private $oauth2Mock;
    /** @var \PHPUnit\Framework\MockObject\MockObject|(Simple&\PHPUnit\Framework\MockObject\MockObject) */
    private $simpleMock;
    private array $stateMock;
    private array $parametersMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->parametersMock = ['state' => OAuth2::STATE_PREFIX . '-statefoo'];
        $this->stateMock = [OAuth2::AUTHID => 'testSourceId'];

        // Create the mock controller
        $this->createControllerMock(['getSourceService', 'loadState', 'getAuthSource']);
    }

    public function testExpectedConstVariables(): void
    {
        $this->assertEquals(OpenIDConnect::STAGE_LOGOUT, $this->controller->getExpectedStageState());
        $this->assertEquals(OAuth2::STATE_PREFIX . '-', $this->controller->getExpectedPrefix());
    }

    public static function requestMethod(): array
    {
        return [
            'GET' => ['GET'],
            'POST' => ['POST'],
        ];
    }

    #[DataProvider('requestMethod')]
    public function testLoggedOutSuccess(string $requestMethod): void
    {
        $parameters = [
            ...$this->parametersMock,
        ];

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: $requestMethod,
            parameters: $parameters,
        );

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->sourceServiceMock
            ->expects($this->once())
            ->method('completeLogout')
            ->with($this->stateMock);

        $this->controller->loggedout($request);
    }

    #[DataProvider('requestMethod')]
    public function testLogoutWithoutAuthSourceThrowsBadRequest(string $requestMethod): void
    {
        $parameters = [
            ...$this->parametersMock,
        ];

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: $requestMethod,
            parameters: $parameters,
        );

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('No authsource in the request');

        $this->controller->logout($request);
    }

    #[DataProvider('requestMethod')]
    public function testLogoutWithInvalidAuthSourceThrowsBadRequest(string $requestMethod): void
    {
        $parameters = [
            'authSource' => ['INVALID SOURCE ID'],
            ...$this->parametersMock,
        ];

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: $requestMethod,
            parameters: $parameters,
        );

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('Authsource ID invalid');

        $this->controller->logout($request);
    }

    #[DataProvider('requestMethod')]
    public function testSuccessfullLogout(string $requestMethod): void
    {
        $parameters = [
            'authSource' => 'authsourceid',
            ...$this->parametersMock,
        ];

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: $requestMethod,
            parameters: $parameters,
        );

        $logoutConfig = [
            'oidc:localLogout' => true,
            'ReturnTo' => '/' . RoutesEnum::Logout->value
        ];

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->simpleMock
            ->expects($this->once())
            ->method('logout')
            ->with($logoutConfig);

        $this->controller->logout($request);
    }

    // Mock helper function
    private function createControllerMock(array $methods): void
    {
        $this->oauth2Mock = $this->getMockBuilder(OAuth2::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->simpleMock = $this->getMockBuilder(Simple::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['logout'])
            ->getMock();

        $this->sourceServiceMock = $this->getMockBuilder(SourceService::class)
            ->onlyMethods(['completeLogout', 'getById'])
            ->getMock();

        $this->controller = $this->getMockBuilder(OIDCLogoutControllerMock::class)
            ->onlyMethods($methods)
            ->getMock();

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller
            ->method('getSourceService')
            ->willReturn($this->sourceServiceMock);

        $this->controller
            ->method('getAuthSource')
            ->willReturn($this->simpleMock);

        $this->sourceServiceMock
            ->method('getById')
            ->with('testSourceId', OAuth2::class)
            ->willReturn($this->oauth2Mock);

        $this->controller
            ->method('loadState')
            ->willReturn($this->stateMock);
    }
}
