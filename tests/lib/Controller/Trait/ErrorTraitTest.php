<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller\Trait;

use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\InputBag;
use SimpleSAML\Module\authoauth2\Controller\Traits\ErrorTrait;

class ErrorTraitTest extends TestCase
{
    use ErrorTrait;

    public function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testParseErrorWithNoError(): void
    {
        // Mocking Request object
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(InputBag::class);

        // Stubbing the method has() and get() of InputBag
        $request->query->method('has')
            ->willReturnOnConsecutiveCalls(false, false);

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['', ''], $result);
    }

    public function testParseErrorWithErrorAndDescription(): void
    {
        // Mocking Request object
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(InputBag::class);

        // Stubbing the method has() and get() of InputBag
        $request->query->method('has')
            ->willReturnOnConsecutiveCalls(true, true);
        $request->query->method('get')
            ->willReturnOnConsecutiveCalls('sample_error', 'This is a sample error description');

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['sample_error', 'This is a sample error description'], $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testParseErrorWithErrorOnly(): void
    {
        // Mocking Request object
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(InputBag::class);

        $request->query
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, false);

        // Stubbing the method has() and get() of InputBag
        $request->query
            ->method('get')
            ->willReturn('sample_error');

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['sample_error', ''], $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testParseErrorWithDescriptionOnly(): void
    {
        // Mocking Request object
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(InputBag::class);

        // Stubbing the method has() and get() of InputBag
        $request->query
            ->method('has')
            ->willReturnOnConsecutiveCalls(false, true);

        $request->query
            ->method('get')
            ->willReturn('This is a sample error description');

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['', 'This is a sample error description'], $result);
    }
}
