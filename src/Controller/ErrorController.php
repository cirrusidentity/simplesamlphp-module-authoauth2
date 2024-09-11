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
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param   Configuration|null  $config
     */
    public function __construct(Configuration $config = null)
    {
        $this->config = $config ?? SimpleSAML\Configuration::getInstance();
    }

    /**
     * Show error consent view.
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
