<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Module\authoauth2\Codebooks\RoutesEnum;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\AuthSource;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Error\Exception;
use SimpleSAML\Error\NoState;
use SimpleSAML\Error\UserAborted;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Codebooks\LegacyRoutesEnum;
use SimpleSAML\Module\authoauth2\Codebooks\Oauth2ErrorsEnum;
use SimpleSAML\Module\authoauth2\Controller\Traits\ErrorTrait;
use SimpleSAML\Module\authoauth2\Controller\Traits\RequestTrait;
use SimpleSAML\Module\authoauth2\locators\HTTPLocator;
use SimpleSAML\Module\authoauth2\locators\SourceServiceLocator;
use Symfony\Component\HttpFoundation\Request;

class Oauth2Controller
{
    use HTTPLocator;
    use SourceServiceLocator;
    use RequestTrait;
    use ErrorTrait;

    public function __construct()
    {
    }

    /**
     * @throws NoState
     * @throws BadRequest
     * @throws Exception
     */
    public function linkback(Request $request): void
    {
        Logger::debug('authoauth2: linkback request=' . var_export($request->query->all(), true));

        $this->parseRequest($request);

        // Required for psalm
        \assert($this->source instanceof  OAuth2);
        \assert(\is_array($this->state));
        \assert(\is_string($this->sourceId));

        // Handle Identify Provider error
        if (!$request->query->has('code') || empty($request->query->get('code'))) {
            $this->handleError($this->source, $this->state, $request);
        }

        try {
            $this->source->finalStep($this->state, (string)$request->query->get('code'));
        } catch (IdentityProviderException $e) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            Logger::error("authoauth2: error in '$this->sourceId' msg '{$e->getMessage()}' body '" . var_export($e->getResponseBody(), true) . "'");
            State::throwException(
                $this->state,
                new AuthSource($this->sourceId, 'Error on oauth2 linkback endpoint.', $e)
            );
        } catch (\Exception $e) {
            Logger::error("authoauth2: error in '$this->sourceId' '" . get_class($e) . "' msg '{$e->getMessage()}'");
            State::throwException(
                $this->state,
                new AuthSource($this->sourceId, 'Error on oauth2 linkback endpoint.', $e)
            );
        }

        $this->getSourceService()->completeAuth($this->state);
    }

    /**
     * @throws Exception
     */
    protected function handleError(OAuth2 $source, array $state, Request $request): void
    {
        // Errors can be pretty inconsistent
        // XXX We do not have the ability to parse hash parameters in the backend, for example
        //     https://example.com/ssp/module.php/authoauth2/linkback#error=invalid_scope
        /** @var string $error */
        /** @var string $error_description */
        [$error, $error_description] = $this->parseError($request);
        $oauth2ErrorsValues = array_column(Oauth2ErrorsEnum::cases(), 'value');
        if (\in_array($error, $oauth2ErrorsValues, true)) {
            $msg = 'authoauth2: Authsource '
                . $source->getAuthId()
                . ' User denied access: '
                . $error
                . ' Msg: '
                . $error_description;
            Logger::debug($msg);
            if ($source->getConfig()->getOptionalBoolean('useConsentErrorPage', true)) {
                $consentErrorRoute = $source->getConfig()->getOptionalBoolean('useLegacyRoutes', false) ?
                    LegacyRoutesEnum::LegacyConsentError->value : RoutesEnum::ConsentError->value;
                $consentErrorPageUrl = Module::getModuleURL("authoauth2/$consentErrorRoute");
                $this->getHttp()->redirectTrustedURL($consentErrorPageUrl);
                // We should never get here. This is to facilitate testing. If we do get here then
                // something bad happened
                return;
            } else {
                $e = new UserAborted();
                State::throwException($state, $e);
            }
        }

        $errorMsg = 'Authentication failed: [' . $error . '] ' . $error_description;
        Logger::debug("authoauth2: Authsource '" . $source->getAuthId() . "' return error $errorMsg");
        $e = new AuthSource($source->getAuthId(), $errorMsg);
        State::throwException($state, $e);
    }
}
