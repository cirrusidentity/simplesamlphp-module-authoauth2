<?php

namespace SimpleSAML\Module\authoauth2\Strategy;

use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use SimpleSAML\Session;

class PkceSessionStrategy extends AbstractStrategy implements PkceStrategyInterface
{
    public const SESSION_NAMESPACE = 'authoauth2_pkce';
    public const PKCE_SESSION_KEY = 'pkceCode';

    private ?Session $session;

    public function __construct(?Session $session = null)
    {
        $this->session = $session;
    }

    /**
     * @throws Exception
     */
    public function saveCodeChallengeFromProvider(AbstractProvider $provider, array &$state): void
    {
        $this->getSession()->setData(self::SESSION_NAMESPACE, self::PKCE_SESSION_KEY, $provider->getPkceCode());
    }

    /**
     * @throws Exception
     */
    public function loadCodeChallengeIntoProvider(AbstractProvider $provider, array &$state): void
    {
        $code = (string)$this->getSession()->getData(self::SESSION_NAMESPACE, self::PKCE_SESSION_KEY);
        if ($code) {
            $provider->setPkceCode($code);
        }
    }

    /**
     * @throws Exception
     */
    private function getSession(): Session
    {
        return $this->session ?: Session::getSessionFromRequest();
    }
}
