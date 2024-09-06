<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\AuthSource;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Error\Exception;
use SimpleSAML\Error\NoState;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\locators\HTTPLocator;
use SimpleSAML\Module\authoauth2\locators\SourceServiceLocator;
use SimpleSAML\Module\authoauth2\Controller\Traits\RequestTrait;
use Symfony\Component\HttpFoundation\Request;

class Oauth2Controller
{
    use HTTPLocator;
    use SourceServiceLocator;
    use RequestTrait;

    private string $expectedStageState = OAuth2::STAGE_INIT;
    private string $expectedStateAuthId = OAuth2::AUTHID;

    private string $expectedPrefix = OAuth2::STATE_PREFIX . '|';

    /**
     * 'access_denied' is OAuth2 standard. Some AS made up their own codes, so support the common ones.
     * @var string[]
     */
    private array $errorsUserConsent = [
        'access_denied',
        'user_denied',
        'user_cancelled_authorize',
        'consent_required',
        'user_cancelled_login'
    ];

    public function __construct()
    {
    }


    /**
     * @throws NoState
     * @throws BadRequest
     * @throws Exception
     */
    public function callback(Request $request): void
    {
        Logger::debug('authoauth2: linkback request=' . var_export($request->query->all(), true));

        $this->loadState($request);

        if (!$request->query->has('code')) {
            $this->handleError($this->source, $this->state, $request->query->all());
        }

        try {
            $this->source->finalStep($this->state, $request->query->get('code'));
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

    private function handleError(OAuth2 $source, array $state, array $request): void
    {
        // Errors can be pretty inconsistent
        $error = @$request['error'];
        if (in_array($error, $this->errorsUserConsent)) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            Logger::debug("authoauth2: Authsource '" . $source->getAuthId() . "' User denied access: $error. Msg: " .  @$request['error_description']);
            if ($source->getConfig()->getOptionalBoolean('useConsentErrorPage', true)) {
                $consentErrorPageUrl = Module::getModuleURL('authoauth2/errors/consent.php');
                $this->getHttp()->redirectTrustedURL($consentErrorPageUrl);
            } else {
                $e = new \SimpleSAML\Error\UserAborted();
                State::throwException($state, $e);
            }
        }

        $errorMsg = 'Authentication failed: [' . $error . '] ' . @$request['error_description'];
        Logger::debug("authoauth2: Authsource '" . $source->getAuthId() . "' return error $errorMsg");
        $e = new AuthSource($source->getAuthId(), $errorMsg);
        State::throwException($state, $e);
    }
}