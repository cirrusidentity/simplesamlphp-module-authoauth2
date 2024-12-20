<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller\Traits;

use SimpleSAML\Auth\Source;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Error\NoState;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Codebooks\LegacyRoutesEnum;
use SimpleSAML\Module\authoauth2\Codebooks\RoutesEnum;
use SimpleSAML\Module\authoauth2\Lib\RequestUtilities;
use SimpleSAML\Module\authoauth2\locators\SourceServiceLocator;
use Symfony\Component\HttpFoundation\Request;

trait RequestTrait
{
    use SourceServiceLocator;

    /**
     * @var array|null
     */
    protected ?array $state;
    /**
     * @var Source|null
     */
    protected ?Source $source;
    /**
     * @var string|null
     */
    protected ?string $sourceId;

    /**
     * @var string
     */
    protected string $expectedStateAuthId = OAuth2::AUTHID;

    /** @var array  */
    protected array $requestParams = [];

    /**
     * @param   Request  $request
     *
     * @return bool
     */
    public function stateIsValid(Request $request): bool
    {
        // Parse the request parameters
        $this->parseRequestParamsSingleton($request);
        if (!isset($this->requestParams['state'])) {
            return false;
        }
        /** @var ?string $stateId */
        $stateId = $this->requestParams['state'];
        if (empty($stateId)) {
            return false;
        }
        return str_starts_with($stateId, $this->expectedPrefix);
    }

    /**
     * @throws NoState
     * @throws BadRequest
     */
    public function parseRequest(Request $request): void
    {
        if (!$this->stateIsValid($request)) {
            $message = match ($request->attributes->get('_route')) {
                LegacyRoutesEnum::LegacyLogout->name,
                // phpcs:ignore Generic.Files.LineLength.TooLong
                RoutesEnum::Logout->name   => 'Either missing state parameter on OpenID Connect logout callback, or cannot be handled by authoauth2',
                LegacyRoutesEnum::LegacyLinkback->name,
                // phpcs:ignore Generic.Files.LineLength.TooLong
                RoutesEnum::Linkback->name => 'Either missing state parameter on OAuth2 login callback, or cannot be handled by authoauth2',
                default => 'An error occured'
            };
            throw new BadRequest($message);
        }
        $stateIdWithPrefix = (string)($this->requestParams['state'] ?? '');
        $stateId = substr($stateIdWithPrefix, \strlen($this->expectedPrefix));

        $this->state = $this->loadState($stateId, $this->expectedStageState);

        // Find the authentication source
        if (
            $this->state === null
            || !\array_key_exists($this->expectedStateAuthId, $this->state)
        ) {
            throw new BadRequest('No authsource id data in state for ' . $this->expectedStateAuthId);
        }

        if (empty($this->state[$this->expectedStateAuthId])) {
            throw new BadRequest('Source ID is undefined');
        }

        $this->sourceId = (string)$this->state[$this->expectedStateAuthId];
        $this->source = $this->getSourceService()->getById($this->sourceId, OAuth2::class);
        if ($this->source === null) {
            throw new BadRequest('Could not find authentication source with id ' . $this->sourceId);
        }
    }

    /**
     *  Retrieve saved state.
     *
     * @param   string  $id
     * @param   string  $stage
     * @param   bool    $allowMissing
     *
     * @return array|null
     * @throws NoState
     */
    public function loadState(string $id, string $stage, bool $allowMissing = false): ?array
    {
        return State::loadState($id, $stage, $allowMissing);
    }

    /**
     * @param   Request  $request
     */
    public function parseRequestParamsSingleton(Request $request): void
    {
        if (empty($this->requestParams)) {
            $this->requestParams = RequestUtilities::getRequestParams($request);
        }
    }
}
