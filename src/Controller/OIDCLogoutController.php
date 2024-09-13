<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller;

use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Error\NoState;
use SimpleSAML\Logger;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Module\authoauth2\Controller\Traits\RequestTrait;
use SimpleSAML\Module\authoauth2\locators\SourceServiceLocator;
use Symfony\Component\HttpFoundation\Request;

class OIDCLogoutController
{
    use SourceServiceLocator;
    use RequestTrait;

    /**
     * @var string
     */
    private string $expectedStageState = OpenIDConnect::STAGE_LOGOUT;
    /**
     * @var string
     */
    private string $expectedStateAuthId = OAuth2::AUTHID;

    /**
     * @var string
     */
    private string $expectedPrefix = OAuth2::STATE_PREFIX . '-';

    /**
     * @var Configuration
     */
    protected Configuration $config;

    /**
     *  Controller constructor.
     *
     *  It initializes the global configuration for the controllers implemented here.
     *
     * @param   Configuration|null  $config
     */
    public function __construct(Configuration $config = null)
    {
        $this->config = $config ?? Configuration::getInstance();
    }

    /**
     * @throws NoState
     * @throws BadRequest
     */
    public function loggedout(Request $request): void
    {
        Logger::debug('authoauth2: logout request=' . var_export($request->request->all(), true));

        $this->parseRequest($request);

        assert(is_array($this->state));

        $this->getSourceService()->completeLogout($this->state);
        // @codeCoverageIgnoreStart
    }

    /**
     * @throws BadRequest
     * @throws CriticalConfigurationError
     * @throws \Exception
     */
    public function logout(Request $request): void
    {
        Logger::debug('authoauth2: logout request=' . var_export($request->request->all(), true));
        // Find the authentication source
        if (!$request->query->has('authSource')) {
            throw new BadRequest('No authsource in the request');
        }
        $sourceId = $request->query->get('authSource');
        if (empty($sourceId) || !is_string($sourceId)) {
            throw new BadRequest('Authsource ID invalid');
        }
        $this->getAuthSource($sourceId)
            ->logout([
                         'oidc:localLogout' => true,
                         'ReturnTo' => $this->config->getBasePath() . 'logout.php',
                     ]);
    }

    /**
     * Create and return an instance with the specified authsource.
     *
     * @param   string  $authSource  The id of the authentication source.
     *
     * @return Simple The authentication source.
     */
    public function getAuthSource(string $authSource): Simple
    {
        return new Simple($authSource);
    }
}
