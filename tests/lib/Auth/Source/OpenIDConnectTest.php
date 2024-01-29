<?php

namespace Test\SimpleSAML\Auth\Source;

use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Utils\HTTP;
use Test\SimpleSAML\MockOAuth2Provider;
use Test\SimpleSAML\MockOpenIDConnectProvider;
use Test\SimpleSAML\RedirectException;

/**
 * Test authentication to OAuth2.
 */
class OpenIDConnectTest extends OAuth2Test
{
    public const AUTH_ID = 'openidconnect';
    protected function getInstance(array $config): OpenIDConnect
    {
        $info = ['AuthId' => self::AUTH_ID];
        return new OpenIDConnect($info, $config);
    }

    public static function setUpBeforeClass(): void
    {
            // Some of the constructs in this test cause a Configuration to be created prior to us
            // setting the one we want to use for the test.
            Configuration::clearInternalState();
    }

    public function finalStepsDataProvider(): array
    {
        return [
            [
                [
                    'providerClass' => MockOAuth2Provider::class,
                    'attributePrefix' => 'test.',
                    'retryOnError' => 1,
                    'clientId' => 'test client id',
                ],
                // phpcs:disable
                new AccessToken([
                    'access_token' => 'stubToken',
                    'id_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjJ9.emHrAifV1IyvmTXh3lYX0oAFqqZInhDlclIlTUumut0',
                ]),
                // phpcs:enable
                [
                    'test.name' => ['Bob'],
                    'test.id_token.sub' => ['1234567890'],
                    'test.id_token.iat' => [1516239022],
                    'test.id_token.aud' => ['test client id'],
                ],
            ]
        ];
    }

    public function finalStepsDataProviderWithAuthenticatedApiRequest(): array
    {
        return [
            [
                [
                    'providerClass' => MockOAuth2Provider::class,
                    'attributePrefix' => 'test.',
                    'retryOnError' => 1,
                    'authenticatedApiRequests' => ['https://mock.com/v1.0/me/memberOf'],

                ],
                // phpcs:disable
                new AccessToken([
                    'access_token' => 'stubToken',
                    'id_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjJ9.emHrAifV1IyvmTXh3lYX0oAFqqZInhDlclIlTUumut0',

                ]),
                // phpcs:enable
                [
                    'test.name' => ['Bob'],
                    'test.additionalResource' => ['info'],
                    'test.id_token.sub' => ['1234567890'],
                    'test.id_token.iat' => [1516239022],
                    'test.id_token.aud' => ['test client id'],
                ],
            ]
        ];
    }

    public function authenticateDataProvider(): array
    {
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
                    State::ID => 'stateId',
                    'ForceAuthn' => true,
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?prompt=login&state=authoauth2%7CstateId&scope=openid%20profile&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
            [
                $config,
                [
                    State::ID => 'stateId',
                    'isPassive' => true,
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?prompt=none&state=authoauth2%7CstateId&scope=openid%20profile&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
            [
                $config,
                [
                    State::ID => 'stateId',
                    'oidc:acr_values' => 'Level4 Level3',
                    'oidc:display' => 'popup',
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?acr_values=Level4%20Level3&display=popup&state=authoauth2%7CstateId&scope=openid%20profile&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
        ];
    }

    public function authprocTokenProvider(): array
    {
        return [
            [
                new AccessToken([
                    'access_token' => 'stubToken',
                    'id_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjJ9.emHrAifV1IyvmTXh3lYX0oAFqqZInhDlclIlTUumut0',
                ]),
            ]
        ];
    }

    public function testLogoutNoEndpointConfigured()
    {
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

    public function testLogoutNoIDTokenInState()
    {
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

    public function testLogoutRedirects()
    {
        //phpcs:ignore Generic.Files.LineLength.TooLong
        $expectedUrl = 'https://example.org/logout?id_token_hint=myidtoken&post_logout_redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Floggedout.php&state=authoauth2-stateId';
        // Override redirect behavior
        $http = $this->createMock(HTTP::class);
        $http->method('redirectTrustedURL')
            ->with($expectedUrl)
            ->willThrowException(
                new RedirectException('redirectTrustedURL', $expectedUrl)
            );

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
        $as->setHttp($http);
        $state = [
            'id_token' => 'myidtoken',
            State::ID => 'stateId',
        ];
        try {
            $this->assertNull($as->logout($state));
            $this->fail("Redirect expected");
        } catch (RedirectException $e) {
            $this->assertEquals('redirectTrustedURL', $e->getMessage());
            $this->assertEquals($expectedUrl, $e->getUrl());
        }
    }
}
