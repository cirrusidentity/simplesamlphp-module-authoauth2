<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller\Traits;

use SimpleSAML\Auth\Source;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Error\NoState;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Codebooks\RoutesEnum;
use Symfony\Component\HttpFoundation\Request;

trait RequestTrait
{
    /**
     * @var array
     */
    private array $state;
    /**
     * @var Source
     */
    private Source $source;
    /**
     * @var string
     */
    private string $sourceid;

    /**
     * @param   Request  $request
     *
     * @return bool
     */
    public function stateIsValid(Request $request): bool
    {
        if (!$request->query->has('state')) {
            return false;
        }
        /** @var ?string $stateId */
        $stateId = $request->query->get('state');
        return \is_string($stateId) && str_starts_with($stateId, $this->expectedPrefix);
    }

    /**
     * @throws NoState
     * @throws BadRequest
     */
    public function parseRequest(Request $request): void
    {
        if (!$this->stateIsValid($request)) {
            $message = match ($request->attributes->get('_route')) {
                // phpcs:ignore Generic.Files.LineLength.TooLong
                RoutesEnum::Logout->name   => 'Either missing state parameter on OpenID Connect logout callback, or cannot be handled by authoauth2',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                RoutesEnum::Linkback->name => 'Either missing state parameter on OAuth2 login callback, or cannot be handled by authoauth2',
            };
            throw new BadRequest($message);
        }
        $stateIdWithPrefix = $request->query->get('state');
        $stateId = substr($stateIdWithPrefix, strlen($this->expectedPrefix));

        $this->state = $this->loadState($stateId, $this->expectedStageState);

        // Find the authentication source
        if (!\array_key_exists($this->expectedStateAuthId, $this->state)) {
            throw new BadRequest('No authsource id data in state for ' . $this->expectedStateAuthId);
        }
        $this->sourceId = $this->state[$this->expectedStateAuthId];

        /**
         * @var ?OAuth2 $source
         */
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
}
