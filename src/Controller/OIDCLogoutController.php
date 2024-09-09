<?php

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

    private string $expectedStageState = OpenIDConnect::STAGE_LOGOUT;
    private string $expectedStateAuthId = OAuth2::AUTHID;

    private string $expectedPrefix = OAuth2::STATE_PREFIX . '-';

    public function __construct()
    {
    }

    /**
     * @throws NoState
     * @throws BadRequest
     */
    public function loggedout(Request $request): void
    {
        Logger::debug('authoauth2: logout request=' . var_export($request->request->all(), true));

        $this->loadState($request);

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
        $config = Configuration::getInstance();
        // Find the authentication source
        if (!$request->query->has('authSource')) {
            throw new BadRequest('No authsource in the request');
        }
        $sourceId = $request->query->get('authSource');
        $as = new Simple($sourceId);
        $as->logout([
                        'oidc:localLogout' => true,
                        'ReturnTo' => $config->getBasePath() . 'logout.php',
                    ]);
    }
}
