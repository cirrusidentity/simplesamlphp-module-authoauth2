<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use SimpleSAML\Module\authoauth2\Controller\Traits\ErrorTrait;

class ErrorTraitTest extends TestCase
{
    use ErrorTrait;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testParseErrorWithNoError(): void
    {
        // Mocking Request object
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(ParameterBag::class);

        // Stubbing the method has() and get() of ParameterBag
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
        $request->query = $this->createMock(ParameterBag::class);

        // Stubbing the method has() and get() of ParameterBag
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
        $request->query = $this->createMock(ParameterBag::class);

        $request->query
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, false);

        // Stubbing the method has() and get() of ParameterBag
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
        $request->query = $this->createMock(ParameterBag::class);

        // Stubbing the method has() and get() of ParameterBag
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