<?php

namespace SimpleSAML\Module\authoauth2\Controller;

use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
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
    public function logout(Request $request): void
    {
        Logger::debug('authoauth2: logout request=' . var_export($request, true));

        $this->loadState($request);

        $this->getSourceService()->completeLogout($this->state);
        // @codeCoverageIgnoreStart
    }

    public function handleRequestFromRequest(array $request): void
    {
        Logger::debug('authoauth2: logout request=' . var_export($request, true));
        $config = Configuration::getInstance();
        $sourceId = $request['authSource'];
        $as = new Simple($sourceId);
        $as->logout([
                        'oidc:localLogout' => true,
                        'ReturnTo' => $config->getBasePath() . 'logout.php',
                    ]);
    }
}
