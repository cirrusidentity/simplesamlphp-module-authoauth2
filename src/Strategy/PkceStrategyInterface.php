<?php

namespace SimpleSAML\Module\authoauth2\Strategy;

use League\OAuth2\Client\Provider\AbstractProvider;

interface PkceStrategyInterface extends StrategyInterface
{
    /**
     * support saving the providers PKCE code in the session for later verification.
     *
     * Note: the state is not the same as in `loadCodeChallengeIntoProvider`
     */
    public function saveCodeChallengeFromProvider(AbstractProvider $provider, array &$state): void;

    /**
     * support retrieving the PKCE code from tne session for verification.
     *
     * Note: the state is not the same as in `saveCodeChallengeFromProvider`
     */
    public function loadCodeChallengeIntoProvider(AbstractProvider $provider, array &$state): void;
}
