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
use SimpleSAML\Utils\HTTP;

class OAuth2ResponseHandler
{
    private $expectedStageState = OAuth2::STAGE_INIT;
    private $expectedStateAuthId = OAuth2::AUTHID;

    private $expectedPrefix = OAuth2::STATE_PREFIX . '|';

    /**
     * 'access_denied' is OAuth2 standard. Some AS made up their own codes, so support the common ones.
     * @var string[]
     */
    private $errorsUserConsent = [
        'access_denied',
        'user_denied',
        'user_cancelled_authorize',
        'consent_required',
        'user_cancelled_login'
    ];

    /**
     * Look at the state parameter returned by the OAuth2 server and determine if we can handle it;
     * @return bool true if response can be handled by this module
     */
    public function canHandleResponse()
    {
        return $this->canHandleResponseFromRequest($_REQUEST);
    }

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
        Logger::debug('authoauth2: linkback request=' . var_export($request, true));

        if (!$this->canHandleResponseFromRequest($request)) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            throw new \SimpleSAML\Error\BadRequest('Either missing state parameter on OAuth2 login callback, or cannot be handled by authoauth2');
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
        $source = \SimpleSAML\Auth\Source::getById($sourceId, OAuth2::class);
        if ($source === null) {
            throw new \SimpleSAML\Error\BadRequest('Could not find authentication source with id ' . $sourceId);
        }
        if (!array_key_exists('code', $request)) {
            $this->handleErrorResponse($source, $state, $request);
        }

        try {
            $source->finalStep($state, $request['code']);
        } catch (IdentityProviderException $e) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            Logger::error("authoauth2: error in '$sourceId' msg '{$e->getMessage()}' body '" . var_export($e->getResponseBody(), true) . "'");
            \SimpleSAML\Auth\State::throwException(
                $state,
                new \SimpleSAML\Error\AuthSource($sourceId, 'Error on oauth2 linkback endpoint.', $e)
            );
        } catch (\Exception $e) {
            Logger::error("authoauth2: error in '$sourceId' '" . get_class($e) . "' msg '{$e->getMessage()}'");
            \SimpleSAML\Auth\State::throwException(
                $state,
                new \SimpleSAML\Error\AuthSource($sourceId, 'Error on oauth2 linkback endpoint.', $e)
            );
        }

        \SimpleSAML\Auth\Source::completeAuth($state);
    }

    private function handleErrorResponse(OAuth2 $source, array $state, array $request)
    {
        // Errors can be pretty inconsistent
        $error = @$request['error'];
        if (in_array($error, $this->errorsUserConsent)) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            Logger::debug("authoauth2: Authsource '" . $source->getAuthId() . "' User denied access: $error. Msg: " .  @$request['error_description']);
            if ($source->getConfig()->getBoolean('useConsentErrorPage', true)) {
                $consentErrorPageUrl = Module::getModuleURL('authoauth2/errors/consent.php');
                HTTP::redirectTrustedURL($consentErrorPageUrl);
            } else {
                $e = new \SimpleSAML\Error\UserAborted();
                \SimpleSAML\Auth\State::throwException($state, $e);
            }
        }

        $errorMsg = 'Authentication failed: [' . $error . '] ' . @$request['error_description'];
        Logger::debug("authoauth2: Authsource '" . $source->getAuthId() . "' return error $errorMsg");
        $e = new \SimpleSAML\Error\AuthSource($source->getAuthId(), $errorMsg);
        \SimpleSAML\Auth\State::throwException($state, $e);
    }
}
