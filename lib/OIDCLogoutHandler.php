<?php
/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 12/21/17
 * Time: 3:26 PM
 */

namespace SimpleSAML\Module\authoauth2;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Utils\HTTP;

class OIDCLogoutHandler
{
    private $expectedStageState = OpenIDConnect::STAGE_LOGOUT;
    private $expectedStateAuthId = OAuth2::AUTHID;

    private $expectedPrefix = OAuth2::STATE_PREFIX . '-';

    /**
     * Look at the state parameter returned by the OpenID Connect server and determine if we can handle it;
     * @return bool true if response can be handled by this module
     */
    public function canHandleResponseFromRequest(array $request)
    {
        return strpos(@$request['state'], $this->expectedPrefix) === 0;
    }

    /**
     * Handle an OAuth2 response.
     */
    public function handleResponse()
    {
        $this->handleResponseFromRequest($_REQUEST);
    }

    public function handleResponseFromRequest(array $request)
    {
        Logger::debug('authoauth2: logout request=' . var_export($request, true));

        if (!$this->canHandleResponseFromRequest($request)) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            throw new \SimpleSAML\Error\BadRequest('Either missing state parameter on OpenID Connect logout callback, or cannot be handled by authoauth2');
        }
        $stateIdWithPrefix = $request['state'];
        $stateId = substr($stateIdWithPrefix, strlen($this->expectedPrefix));
        //TODO: decide how no-state errors should be handled?
        // Likely cause is user clicked back button (state was already consumed and removed) or session expired
        $state = \SimpleSAML\Auth\State::loadState($stateId, $this->expectedStageState);

        // Find authentication source
        if (!array_key_exists($this->expectedStateAuthId, $state)) {
            throw new \SimpleSAML\Error\BadRequest('No authsource id data in state for ' . $this->expectedStateAuthId);
        }
        $sourceId = $state[$this->expectedStateAuthId];

        /**
         * @var OAuth2 $source
         */
        $source = \SimpleSAML\Auth\Source::getById($sourceId, OpenIDConnect::class);
        if ($source === null) {
            throw new \SimpleSAML\Error\BadRequest('Could not find authentication source with id ' . $sourceId);
        }

        \SimpleSAML\Auth\Source::completeLogout($state);
        // @codeCoverageIgnoreStart
    }

    public function handleRequest()
    {
        $this->handleRequestFromRequest($_REQUEST);
    }

    public function handleRequestFromRequest(array $request)
    {
        Logger::debug('authoauth2: logout request=' . var_export($request, true));
        $config = \SimpleSAML\Configuration::getInstance();
        $sourceId = $request['authSource'];
        $as = new \SimpleSAML\Auth\Simple($sourceId);
        $as->logout([
            'oidc:localLogout' => true,
            'ReturnTo' => $config->getBasePath().'logout.php',
        ]);
    }
}
