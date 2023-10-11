<?php

namespace Test\SimpleSAML\Strategy;

use PHPUnit\Framework\TestCase;
use League\OAuth2\Client\Provider\AbstractProvider;
use SimpleSAML\Module\authoauth2\Strategy\PkceSessionStrategy;
use SimpleSAML\Session;

class PkceSessionStrategyTest extends TestCase
{
    public function testSaveCodeChallengeFromProvider(): void
    {
        $codeChallenge = 'some_code_challenge';

        $providerMock = $this->createMock(AbstractProvider::class);
        $providerMock->expects($this->once())
            ->method('getPkceCode')
            ->willReturn($codeChallenge);

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('setData')
            ->with(
                PkceSessionStrategy::SESSION_NAMESPACE,
                PkceSessionStrategy::PKCE_SESSION_KEY,
                $codeChallenge
            );

        $strategy = new PkceSessionStrategy($sessionMock);
        $state = [];
        $strategy->saveCodeChallengeFromProvider($providerMock, $state);
    }

    public function testLoadCodeChallengeIntoProvider(): void
    {
        $codeChallenge = 'some_code_challenge';

        $providerMock = $this->createMock(AbstractProvider::class);
        $providerMock->expects($this->once())
            ->method('setPkceCode')
            ->with($codeChallenge);

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('getData')
            ->with(PkceSessionStrategy::SESSION_NAMESPACE, PkceSessionStrategy::PKCE_SESSION_KEY)
            ->willReturn($codeChallenge);

        $strategy = new PkceSessionStrategy($sessionMock);
        $state = [];
        $strategy->loadCodeChallengeIntoProvider($providerMock, $state);
    }

    public function testLoadCodeChallengeIntoProviderNoCode(): void
    {
        $providerMock = $this->createMock(AbstractProvider::class);
        $providerMock->expects($this->never())  // Ensure that setPkceCode() is not called
        ->method('setPkceCode');

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('getData')
            ->with(PkceSessionStrategy::SESSION_NAMESPACE, PkceSessionStrategy::PKCE_SESSION_KEY)
            ->willReturn(null);

        $strategy = new PkceSessionStrategy($sessionMock);
        $state = [];
        $strategy->loadCodeChallengeIntoProvider($providerMock, $state);
    }
}
