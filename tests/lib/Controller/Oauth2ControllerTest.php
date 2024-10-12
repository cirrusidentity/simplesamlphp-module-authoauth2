<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller;

use DG\BypassFinals;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\Source;
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
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

// Unless we declare the class here, it is not recognized by phpcs
class Oauth2ControllerMock extends Oauth2Controller
{
    use HTTPLocator;
    use SourceServiceLocator;
    use RequestTrait;
    use ErrorTrait;

    public function handleError(OAuth2 $source, array $state, Request $request): void
    {
        parent::handleError($source, $state, $request);
    }

    public function setSource(Source $source): void
    {
        $this->source = $source;
    }

    public function getSource(): ?Source
    {
        return $this->source;
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
class Oauth2ControllerTest extends TestCase
{
    /** @var Oauth2ControllerMock */
    private $controller;
    /** @var Request */
    private $requestMock;
    /** @var HTTP  */
    private $httpMock;
    /** @var \PHPUnit\Framework\MockObject\MockObject|(OAuth2&\PHPUnit\Framework\MockObject\MockObject) */
    private $oauth2Mock;
    private array $stateMock;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);

        $this->requestMock = $this->getMockBuilder(Request::class)->getMock();
        $this->oauth2Mock = $this->getMockBuilder(OAuth2::class)->disableOriginalConstructor()->getMock();
        $this->httpMock = $this->getMockBuilder(HTTP::class)->getMock();
        $this->stateMock = ['state' => 'testState'];
    }

    public function testExpectedConstVariables(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'handleError', 'getHttp', 'parseError']);
        $this->assertEquals(OAuth2::STAGE_INIT, $this->controller->getExpectedStageState());
        $this->assertEquals(OAuth2::STATE_PREFIX . '|', $this->controller->getExpectedPrefix());
    }

    public function testLinkbackValidCode(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'handleError', 'getHttp', 'parseError']);
        $this->requestMock->query = $this->createQueryMock(['code' => 'validCode']);

        $this->oauth2Mock->expects($this->once())
            ->method('finalStep')
            ->with($this->stateMock, 'validCode');

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller
            ->method('getSourceService')
            ->willReturn($this->createMock(SourceService::class));

        $this->controller->linkback($this->requestMock);

        // Assertions for the success scenario can be done here
    }

    public function testLinkbackWithNoCode(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'handleError', 'getHttp', 'parseError']);
        $this->requestMock->query = $this->createQueryMock([]);

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller
            ->expects($this->once())
            ->method('handleError');

        $this->controller->linkback($this->requestMock);
    }

    public function testLinkbackWithIdentityProviderException(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'handleError', 'getHttp', 'parseError']);
        $this->requestMock->query = $this->createQueryMock(['code' => 'validCode']);

        $this->oauth2Mock->expects($this->once())
            ->method('finalStep')
            ->willThrowException(new IdentityProviderException('Error Message', 0, ['body' => 'error body']));

        $this->expectException(AuthSource::class);

        $this->controller->linkback($this->requestMock);
    }

    /**
     * @throws Exception
     */
    public function testHandleErrorWithConsentedError(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'getHttp', 'parseError']);
        $this->controller->getSource()->method('getAuthId')->willReturn('authId');
        $this->controller
            ->getSource()
            ->method('getConfig')
            ->willReturn(new class (['useConsentErrorPage' => true], '') extends Configuration {
                public function getOptionalBoolean($name, $default): bool
                {
                    if (!$this->hasValue($name) && isset($default)) {
                        return filter_var($default, FILTER_VALIDATE_BOOLEAN);
                    }
                    return filter_var($this->getValue($name), FILTER_VALIDATE_BOOLEAN);
                }
            });

        $this->requestMock->query = $this->createQueryMock(
            ['error' => 'invalid_scope', 'error_description' => 'Invalid scope']
        );

        $this->controller->method('parseError')
            ->with($this->requestMock)
            ->willReturn(['invalid_scope', 'Invalid scope']);

        $this->controller->method('getHttp')->willReturn($this->httpMock);

        $this->httpMock->expects($this->once())
            ->method('redirectTrustedURL')
            ->with('http://localhost/module.php/authoauth2/errors/consent');

        $this->controller->handleError($this->controller->getSource(), $this->stateMock, $this->requestMock);
    }

    public function testHandleErrorWithConsentErrorAtLegacyRoute(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'getHttp', 'parseError']);
        $this->controller->getSource()->method('getAuthId')->willReturn('authId');
        $this->controller
            ->getSource()
            ->method('getConfig')
            ->willReturn(new class ([
                'useConsentErrorPage' => true,
                'useLegacyRoutes' => true
            ], '') extends Configuration {
                public function getOptionalBoolean($name, $default): bool
                {
                    if (!$this->hasValue($name) && isset($default)) {
                        return filter_var($default, FILTER_VALIDATE_BOOLEAN);
                    }
                    return filter_var($this->getValue($name), FILTER_VALIDATE_BOOLEAN);
                }
            });

        $this->requestMock->query = $this->createQueryMock(
            ['error' => 'invalid_scope', 'error_description' => 'Invalid scope']
        );

        $this->controller->method('parseError')
            ->with($this->requestMock)
            ->willReturn(['invalid_scope', 'Invalid scope']);

        $this->controller->method('getHttp')->willReturn($this->httpMock);

        $this->httpMock->expects($this->once())
            ->method('redirectTrustedURL')
            ->with('http://localhost/module.php/authoauth2/errors/consent.php');

        $this->controller->handleError($this->controller->getSource(), $this->stateMock, $this->requestMock);
    }

    public function testHandleErrorWithUserAborted(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'getHttp', 'parseError']);
        $this->controller->getSource()->method('getAuthId')->willReturn('authId');
        $this->controller
            ->getSource()
            ->method('getConfig')
            ->willReturn(new class (['useConsentErrorPage' => false], '') extends Configuration {
                public function getOptionalBoolean($name, $default): bool
                {
                    if (!$this->hasValue($name) && isset($default)) {
                        return filter_var($default, FILTER_VALIDATE_BOOLEAN);
                    }
                    return filter_var($this->getValue($name), FILTER_VALIDATE_BOOLEAN);
                }
            });

        $this->requestMock->query = $this->createQueryMock(
            ['error' => 'invalid_scope', 'error_description' => 'Invalid scope']
        );

        $this->controller->method('parseError')
            ->with($this->requestMock)
            ->willReturn(['invalid_scope', 'Invalid scope']);

        $this->controller->method('getHttp')->willReturn($this->httpMock);

        $this->expectException(UserAborted::class);

        $this->controller->handleError($this->controller->getSource(), $this->stateMock, $this->requestMock);
    }


    public function testHandleErrorWithAuthSourceException(): void
    {
        $this->createControllerMock(['parseRequest', 'getSourceService', 'getHttp', 'parseError']);
        $this->controller->getSource()->method('getAuthId')->willReturn('authId');

        $this->requestMock->query = $this->createQueryMock(
            ['error' => 'invalid_error', 'error_description' => 'Invalid Error']
        );

        $this->controller->method('parseError')
            ->with($this->requestMock)
            ->willReturn(['invalid_error', 'Invalid Error']);

        $this->controller->method('getHttp')->willReturn($this->httpMock);

        $this->expectException(AuthSource::class);

        $this->controller->handleError($this->controller->getSource(), $this->stateMock, $this->requestMock);
    }

    private function createControllerMock(array $methods): void
    {
        $this->controller = $this->getMockBuilder(Oauth2ControllerMock::class)
            ->onlyMethods($methods)
            ->getMock();

        // Stubbing dependencies
        $this->controller->setSource($this->oauth2Mock);
        $this->controller->setState($this->stateMock);
        $this->controller->setSourceId('testSourceId');
    }

    private function createQueryMock(array $params): InputBag
    {
        $queryMock = $this->getMockBuilder(InputBag::class)->getMock();
        $queryMock->method('has')->willReturnCallback(
            function (string $key) use ($params) {
                return array_key_exists($key, $params);
            }
        );
        $queryMock->method('get')->willReturnCallback(
            function (string $key) use ($params) {
                return $params[$key] ?? null;
            }
        );
        return $queryMock;
    }
}
