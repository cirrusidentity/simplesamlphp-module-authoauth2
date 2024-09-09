<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller;

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorController
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /**
     * Controller constructor.
     *
     * It initializes the global configuration and session for the controllers implemented here.
     *
     */
    public function __construct()
    {
        $this->config = SimpleSAML\Configuration::getInstance();
    }

    /**
     * Show warning.
     *
     * @param   Request  $request
     * @return Response
     * @throws \Exception
     */
    public function consent(Request $request): Response
    {
        return new Template($this->config, 'authoauth2:errors/consent.twig');
    }
}
