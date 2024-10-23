<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller\Trait;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\Source;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Controller\Traits\RequestTrait;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use Symfony\Component\HttpFoundation\Request;

class GenericController
{
    use RequestTrait;

    /**
     * @var string
     */
    protected string $expectedStageState = OAuth2::STAGE_INIT;

    /**
     * @var string
     */
    protected string $expectedPrefix = OAuth2::STATE_PREFIX . '|';

    public function __construct()
    {
    }

    public function setState(array $state): void
    {
        $this->state = $state;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class RequestTraitTest extends TestCase
{
    use RequestTrait;

    private Request $request;
    private GenericController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request();
        $this->expectedStateAuthId =  OAuth2::AUTHID;
        $this->controller = $this->getMockBuilder(GenericController::class)
            ->onlyMethods(['loadState', 'getSourceService'])
            ->getMock();
    }

    public function testStateIsValidWithMissingState(): void
    {
        $this->assertFalse($this->controller->stateIsValid($this->request));
    }

    public function testStateIsValidWithEmptyState(): void
    {
        $this->request->query->set('state', '');
        $this->assertFalse($this->controller->stateIsValid($this->request));
    }

    public function testStateIsValidWithValidState(): void
    {
        $this->request->query->set('state', OAuth2::STATE_PREFIX . '|example');
        $this->assertTrue($this->controller->stateIsValid($this->request));
    }

    public function testStateIsValidWithInvalidState(): void
    {
        $this->request->query->set('state', 'invalid|example');
        $this->assertFalse($this->controller->stateIsValid($this->request));
    }

    public function testParseRequestWithInvalidState(): void
    {
        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('An error occured');
        $this->request->attributes->set('_route', 'invalid_route');
        $this->controller->parseRequest($this->request);
    }

    public function testParseRequestWithEmptyState(): void
    {
        $this->request->query->set('state', '');
        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('An error occured');
        $this->request->attributes->set('_route', 'invalid_route');
        $this->controller->parseRequest($this->request);
    }

    public function testParseRequestWithValidState(): void
    {
        $this->request->query->set('state', OAuth2::STATE_PREFIX . '|valid_state');
        $this->request->attributes->set('_route', 'valid_route');

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller->method('loadState')->willReturn([OAuth2::AUTHID => 'test_authsource_id']);

        $mockSourceService = $this->getMockBuilder(SourceService::class)->getMock();
        $mockSource = $this->getMockBuilder(Source::class)->disableOriginalConstructor()->getMock();
        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller->method('getSourceService')->willReturn($mockSourceService);

        $mockSourceService->method('getById')->willReturn($mockSource);

        $this->controller->parseRequest($this->request);

        $this->assertEquals('test_authsource_id', $this->controller->getSourceId());
    }

    public function testParseRequestWithNoStateAuthId(): void
    {
        $this->request->query->set('state', OAuth2::STATE_PREFIX . '|valid_state_with_missing_authid');
        $this->request->attributes->set('_route', 'valid_route');

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller->method('loadState')->willReturn(null);

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('No authsource id data in state for ' . OAuth2::AUTHID);

        $this->controller->parseRequest($this->request);
    }

    public function testParseRequestWithEmptyAuthSourceId(): void
    {
        $this->request->query->set('state', OAuth2::STATE_PREFIX . '|valid_state_with_empty_authid');
        $this->request->attributes->set('_route', 'valid_route');

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller->method('loadState')->willReturn([OAuth2::AUTHID => '']);

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('Source ID is undefined');

        $this->controller->parseRequest($this->request);
    }


    public function testParseRequestWithInvalidSource(): void
    {
        $this->request->query->set('state', OAuth2::STATE_PREFIX . '|valid_state_with_invalid_source');
        $this->request->attributes->set('_route', 'valid_route');

        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller->method('loadState')->willReturn([OAuth2::AUTHID => 'invalid_source_id']);

        $mockSourceService = $this->getMockBuilder(SourceService::class)->getMock();
        /** @psalm-suppress UndefinedMethod,MixedMethodCall */
        $this->controller->method('getSourceService')->willReturn($mockSourceService);
        $mockSourceService->method('getById')->willReturn(null);

        $this->expectException(BadRequest::class);
        $this->expectExceptionMessage('Could not find authentication source with id invalid_source_id');

        $this->controller->parseRequest($this->request);
    }
}
