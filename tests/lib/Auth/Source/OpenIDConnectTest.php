<?php

namespace Test\SimpleSAML\Auth\Source;

use CirrusIdentity\SSP\Test\Capture\RedirectException;
use CirrusIdentity\SSP\Test\MockHttp;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use Test\SimpleSAML\MockOAuth2Provider;
use Test\SimpleSAML\MockOpenIDConnectProvider;

/**
 * Test authentication to OAuth2.
 */
class OpenIDConnectTest extends OAuth2Test
{
    const AUTH_ID = 'openidconnect';
    protected function getInstance(array $config)
    {
        $info = ['AuthId' => self::AUTH_ID];
        return new OpenIDConnect($info, $config);
    }

    public function finalStepsDataProvider() {
        return [
            [
                [
                    'providerClass' => MockOAuth2Provider::class,
                    'attributePrefix' => 'test.',
                    'retryOnError' => 1,
                    'clientId' => 'test client id',
                ],
                new AccessToken([
                    'access_token' => 'stubToken',
                    'id_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjJ9.emHrAifV1IyvmTXh3lYX0oAFqqZInhDlclIlTUumut0',
                ]),
                [
                    'test.name' => ['Bob'],
                    'test.id_token.sub' => ['1234567890'],
                    'test.id_token.iat' => [1516239022],
                    'test.id_token.aud' => ['test client id'],
                ],
            ]
        ];
    }


    public function authenticateDataProvider() {
        MockOpenIDConnectProvider::setConfig([
            'authorization_endpoint' => 'https://example.com/auth',
            'token_endpoint' => 'https://example.com/token',
            'userinfo_endpoint' => 'https://example.com/userinfo',

        ]);
        $config = [
            'issuer' => 'https://example.com',
            'clientId' => 'test client id',
            'providerClass' => MockOpenIDConnectProvider::class,
        ];
        return [
            [
                $config,
                [
                    \SimpleSAML\Auth\State::ID => 'stateId',
                    'ForceAuthn' => true,
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?prompt=login&state=authoauth2%7CstateId&scope=openid%20profile&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
            [
                $config,
                [
                    \SimpleSAML\Auth\State::ID => 'stateId',
                    'isPassive' => true,
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?prompt=none&state=authoauth2%7CstateId&scope=openid%20profile&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
            [
                $config,
                [
                    \SimpleSAML\Auth\State::ID => 'stateId',
                    'oidc:acr_values' => 'Level4 Level3',
                    'oidc:display' => 'popup',
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?acr_values=Level4%20Level3&display=popup&state=authoauth2%7CstateId&scope=openid%20profile&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
        ];
    }


    public function testLogoutNoEndpointConfigured() {
        MockOpenIDConnectProvider::setConfig([
            'authorization_endpoint' => 'https://example.com/auth',
            'token_endpoint' => 'https://example.com/token',
            'userinfo_endpoint' => 'https://example.com/userinfo',
        ]);
        $as = $this->getInstance([
            'issuer' => 'https://example.com',
            'providerClass' => MockOpenIDConnectProvider::class,
        ]);
        $state = [];
        $this->assertNull($as->logout($state));
    }

    public function testLogoutNoIDTokenInState() {
        MockOpenIDConnectProvider::setConfig([
            'authorization_endpoint' => 'https://example.com/auth',
            'token_endpoint' => 'https://example.com/token',
            'userinfo_endpoint' => 'https://example.com/userinfo',
            'end_session_endpoint' => 'https://example.org/logout',
        ]);
        $as = $this->getInstance([
            'issuer' => 'https://example.com',
            'providerClass' => MockOpenIDConnectProvider::class,
        ]);
        $state = [];
        $this->assertNull($as->logout($state));
    }

    public function testLogoutRedirects() {
        // Override redirect behavior
        MockHttp::throwOnRedirectTrustedURL();
        MockOpenIDConnectProvider::setConfig([
            'authorization_endpoint' => 'https://example.com/auth',
            'token_endpoint' => 'https://example.com/token',
            'userinfo_endpoint' => 'https://example.com/userinfo',
            'end_session_endpoint' => 'https://example.org/logout',

        ]);

        $as = $this->getInstance([
            'issuer' => 'https://example.com',
            'providerClass' => MockOpenIDConnectProvider::class,
        ]);
        $state = [
            'id_token' => 'myidtoken',
            \SimpleSAML\Auth\State::ID => 'stateId',
        ];
        try {
            $this->assertNull($as->logout($state));
            $this->fail("Redirect expected");
        } catch (RedirectException $e) {
            $this->assertEquals('redirectTrustedURL', $e->getMessage());
            $this->assertEquals(
                'https://example.org/logout?id_token_hint=myidtoken&post_logout_redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Floggedout.php&state=authoauth2-stateId',
                $e->getUrl(),
                "First argument should be the redirect url"
            );
            $this->assertEquals([], $e->getParams(), "query params are already added into url");
        }
    }
}
