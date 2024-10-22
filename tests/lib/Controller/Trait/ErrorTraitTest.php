<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Controller\Trait;

use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use SimpleSAML\Module\authoauth2\Controller\Traits\ErrorTrait;

class ErrorTraitTest extends TestCase
{
    use ErrorTrait;

    public function testParseErrorWithNoError(): void
    {
        // Create a request object
        $request = Request::create(uri: 'localhost');

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['', ''], $result);
    }

    public function testParseErrorWithErrorAndDescription(): void
    {
        $request = Request::create(uri: 'localhost', parameters: [
            'error' => 'sample_error',
            'error_description' => 'This is a sample error description'
        ]);

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['sample_error', 'This is a sample error description'], $result);
    }


    public function testParseErrorWithErrorOnly(): void
    {
        $request = Request::create(uri: 'localhost', parameters: [
            'error' => 'sample_error'
        ]);

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['sample_error', ''], $result);
    }

    public function testParseErrorWithDescriptionOnly(): void
    {
        $request = Request::create(uri: 'localhost', parameters: [
            'error_description' => 'This is a sample error description'
        ]);

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['', 'This is a sample error description'], $result);
    }

    public function testWillNotParseUnrecognizedQueryParam(): void
    {
        $request = Request::create(uri: 'localhost', parameters: [
            'error2' => 'This is a sample error description'
        ]);

        // Test
        $result = $this->parseError($request);
        $this->assertEquals(['', ''], $result);
    }
}
