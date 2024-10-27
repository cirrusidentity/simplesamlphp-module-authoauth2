<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller;

use DG\BypassFinals;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error\AuthSource;
use SimpleSAML\Error\Exception;
use SimpleSAML\Error\UserAborted;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Controller\Oauth2Controller;
use SimpleSAML\Module\authoauth2\Controller\Traits\ErrorTrait;
use SimpleSAML\Module\authoauth2\Controller\Traits\RequestTrait;
use SimpleSAML\Module\authoauth2\locators\HTTPLocator;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use SimpleSAML\Module\authoauth2\locators\SourceServiceLocator;
use SimpleSAML\Utils\HTTP;
use Symfony\Component\HttpFoundation\Request;

// Unless we declare the class here, it is not recognized by phpcs
class Oauth2ControllerMock extends Oauth2Controller
{
    use HTTPLocator;
    use SourceServiceLocator;
    use RequestTrait;
    use ErrorTrait;

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
class Oauth2ControllerTest extends TestCase
{
    /** @var Oauth2ControllerMock */
    private $controller;
    /** @var HTTP  */
    private $httpMock;
    /** @var \PHPUnit\Framework\MockObject\MockObject|(OAuth2&\PHPUnit\Framework\MockObject\MockObject) */
    private $oauth2Mock;
    /** @var SourceService */
    private $sourceServiceMock;
    private array $stateMock;
    private array $parametersMock;

    protected function setUp(): void
    {
        $this->oauth2Mock = $this->getMockBuilder(OAuth2::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['finalStep', 'getConfig'])
            ->getMock();
        $this->sourceServiceMock = $this->getMockBuilder(SourceService::class)
            ->onlyMethods(['getById', 'completeAuth'])
            ->getMock();


        $this->httpMock = $this->getMockBuilder(HTTP::class)->getMock();
        $this->parametersMock = ['state' => OAuth2::STATE_PREFIX . '|statefoo'];
        $this->stateMock = [OAuth2::AUTHID => 'testSourceId'];
    }

    public function testExpectedConstVariables(): void
    {
        $this->createControllerMock(['getSourceService', 'loadState']);
        $this->assertEquals(OAuth2::STAGE_INIT, $this->controller->getExpectedStageState());
        $this->assertEquals(OAuth2::STATE_PREFIX . '|', $this->controller->getExpectedPrefix());
    }

    public static function requestMethod(): array
    {
        return [
            'GET' => ['GET'],
            'POST' => ['POST'],
        ];
    }

    #[DataProvider('requestMethod')]
    public function testLinkbackValidCode(string $requestMethod): void
    {
        $this->createControllerMock(['getSourceService', 'loadState', 'getHttp']);
        $parameters = [
            'code' => 'validCode',
            ...$this->parametersMock,
        ];

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: $requestMethod,
            parameters: $parameters,
        );

        $this->oauth2Mock->expects($this->once())
            ->method('finalStep')
            ->with($this->stateMock, 'validCode');

        $this->sourceServiceMock->expects($this->once())
            ->method('completeAuth')
            ->with($this->stateMock);

        $this->controller->linkback($request);
    }

    #[DataProvider('requestMethod')]
    public function testLinkbackWithNoCode(string $requestMethod): void
    {
        $this->createControllerMock(['getSourceService', 'loadState', 'handleError']);

        $parameters = [
            ...$this->parametersMock,
        ];

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: $requestMethod,
            parameters: $parameters,
        );

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller
            ->expects($this->once())
            ->method('handleError');

        $this->controller->linkback($request);
    }

    #[DataProvider('requestMethod')]
    public function testLinkbackWithIdentityProviderException(string $requestMethod): void
    {
        $this->createControllerMock(['getSourceService', 'loadState', 'getHttp']);

        $parameters = [
            'code' => 'validCode',
            ...$this->parametersMock,
        ];

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: $requestMethod,
            parameters: $parameters,
        );

        $this->oauth2Mock->expects($this->once())
            ->method('finalStep')
            ->willThrowException(new IdentityProviderException('Error Message', 0, ['body' => 'error body']));

        $this->expectException(AuthSource::class);

        $this->controller->linkback($request);
    }

    public static function configuration(): array
    {
        return [ //datasets
            'useConsentPage' => [ // dataset 0
                [
                    ['useConsentErrorPage' => true],
                ],
            ],
            'useConsentPage & legacyRoute' => [ // data set 1
                [
                    ['useConsentErrorPage' => true, 'useLegacyRoutes' => true],
                ],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    #[DataProvider('configuration')]
    public function testHandleErrorWithConsentedError(array $configuration): void
    {
        $this->createControllerMock(['getSourceService', 'loadState', 'getHttp']);

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: 'POST',
            parameters: [
                     ...$this->parametersMock,
                     'error' => 'invalid_scope',
                     'error_description' => 'Invalid scope',
                 ],
        );

        $this->oauth2Mock
            ->method('getConfig')
            ->willReturn(new Configuration($configuration, 'test'));

        $this->controller->method('getHttp')->willReturn($this->httpMock);

        $this->httpMock->expects($this->once())
            ->method('redirectTrustedURL')
            ->with('http://localhost/module.php/authoauth2/errors/consent');

        $this->controller->linkback($request);
    }

    public static function oauth2errors(): array
    {
        return [
          'oauth2 valid error code' => [
              [
                  'error' => 'invalid_scope',
                  'error_description' => 'Invalid scope'
              ],
              UserAborted::class
          ],
          'oauth2 invalid error code' => [
              [
                  'error' => 'invalid_error',
                  'error_description' => 'Invalid error'
              ],
              AuthSource::class
          ]
        ];
    }

    #[DataProvider('oauth2errors')]
    public function testHandleErrorThrowException(array $errorResponse, string $className): void
    {
        $this->createControllerMock(['getSourceService', 'loadState', 'getHttp']);

        $request = Request::create(
            uri: 'https://localhost/auth/authorize',
            method: 'GET',
            parameters: [
                     ...$this->parametersMock,
                     ...$errorResponse,
                 ],
        );
        $configArray = ['useConsentErrorPage' => false];

        $this->oauth2Mock
            ->method('getConfig')
            ->willReturn(new Configuration($configArray, 'test'));

        $this->controller->method('getHttp')->willReturn($this->httpMock);

        $this->expectException($className);

        $this->controller->linkback($request);
    }

    protected function createControllerMock(array $methods): void
    {
        $this->controller = $this->getMockBuilder(Oauth2ControllerMock::class)
            ->onlyMethods($methods)
            ->getMock();

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller
            ->method('getSourceService')
            ->willReturn($this->sourceServiceMock);

        $this->sourceServiceMock
            ->method('getById')
            ->with('testSourceId', OAuth2::class)
            ->willReturn($this->oauth2Mock);

        $this->controller
            ->method('loadState')
            ->willReturn($this->stateMock);
    }
}
