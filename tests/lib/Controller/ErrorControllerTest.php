<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\authoauth2\Controller\ErrorController;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

class ErrorControllerTest extends TestCase
{
    /** @var ErrorController */
    private $controller;
    /** @var Configuration */
    private $config;

    protected function setUp(): void
    {
        $this->config = Configuration::loadFromArray([
           'baseurlpath' => '/',
        ]);

        $this->controller = new ErrorController($this->config);
    }

    public function testConsent(): void
    {
        $request = Request::create('/consent', 'GET');
        $response = $this->controller->consent($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertEquals('authoauth2:errors/consent.twig', $response->getTemplateName());
    }
}
