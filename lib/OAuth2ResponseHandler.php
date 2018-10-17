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
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;

class OAuth2ResponseHandler
{
    private $expectedStageState = OAuth2::STAGE_INIT;
    private $expectedStateAuthId = OAuth2::AUTHID;

    private $expectedPrefix = OAuth2::STATE_PREFIX . '|';

    /**
     * Look at the state parameter returned by the OAuth2 server and determine if we can handle it;
     * @return bool true if response can be handled by this module
     */
    public function canHandleResponse() {
        return $this->canHandleResponseFromRequest($_REQUEST);
    }

    public function canHandleResponseFromRequest(array $request) {
        return strpos(@$request['state'], $this->expectedPrefix) === 0;
    }

    /**
     * Handle an OAuth2 response.
     */
    public function handleResponse()
    {
        $this->handleResponseFromRequest($_REQUEST);
    }
    public function handleResponseFromRequest(array $request) {
        //TODO: use some error checking
        Logger::debug('authoauth2 : linkback request=' . var_export($request, true));

        if (!$this->canHandleResponseFromRequest($request)) {
            throw new \SimpleSAML_Error_BadRequest('Either missing state parameter on OAuth2 login callback, or cannot be handled by authoauth2');
        }
        $stateIdWithPrefix = $request['state'];
        $stateId = substr($stateIdWithPrefix, strlen($this->expectedPrefix));
        //TODO: decide how no-state errors should be handled? Likely cause is user clicked back button (state was already consumed and removed)
        // or session expired
        $state = \SimpleSAML_Auth_State::loadState($stateId, $this->expectedStageState);

        // Find authentication source
        if (!array_key_exists($this->expectedStateAuthId, $state)) {
            throw new \SimpleSAML_Error_BadRequest('No authsource id data in state for ' . $this->expectedStateAuthId);
        }
        $sourceId = $state[$this->expectedStateAuthId];

        /**
         * @var OAuth2 $source
         */
        $source = \SimpleSAML_Auth_Source::getById($sourceId, OAuth2::class);
        if ($source === NULL) {
            throw new \SimpleSAML_Error_BadRequest('Could not find authentication source with id ' . $sourceId);
        }
        if (!array_key_exists('code', $request)) {
            $this->handleErrorResponse($source, $state, $request);
        }

        try {
            $source->finalStep($state, $request['code']);
        } catch (IdentityProviderException $e) {
            Logger::error("authoauth2: error in '$sourceId' msg '{$e->getMessage()}' body '" . var_export($e->getResponseBody(), true) . "'");
            \SimpleSAML_Auth_State::throwException($state, new \SimpleSAML_Error_AuthSource($sourceId, 'Error on oauth2 linkback endpoint.', $e));
        } catch (\Exception $e) {
            Logger::error("authoauth2: error in '$sourceId' msg '{$e->getMessage()}'");
            \SimpleSAML_Auth_State::throwException($state, new \SimpleSAML_Error_AuthSource($sourceId, 'Error on oauth2 linkback endpoint.', $e));
        }

        \SimpleSAML_Auth_Source::completeAuth($state);
    }

    private function handleErrorResponse(\SimpleSAML_Auth_Source $source,  array $state, array $request) {
        // Errors can be pretty inconsistent
        $error = @$request['error'];
        // 'access_denied' is OAuth2 standard. Some AS made up their own codes, so support the common ones.
        if ($error === 'access_denied' || $error === 'user_denied') {
            Logger::debug("Authsource '" . $source->getAuthId() . "' User denied access: $error");
            $e = new \SimpleSAML_Error_UserAborted();
            \SimpleSAML_Auth_State::throwException($state, $e);
        }

        $errorMsg = 'Authentication failed: ['. $error.'] '. @$request['error_description'];
        Logger::debug("Authsource '" . $source->getAuthId() . "' return error $errorMsg");
        $e = new \SimpleSAML_Error_AuthSource($source->getAuthId(), $errorMsg );
        \SimpleSAML_Auth_State::throwException($state, $e);

    }

}