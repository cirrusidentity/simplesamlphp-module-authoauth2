<?php

namespace Test\SimpleSAML\Auth\Source;

use CirrusIdentity\SSP\Test\Capture\RedirectException;
use CirrusIdentity\SSP\Test\MockHttp;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Error\AuthSource;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use Test\SimpleSAML\MockOAuth2Provider;

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
        $config = [
            'urlAuthorize' => 'https://example.com/auth',
            'urlAccessToken' => 'https://example.com/token',
            'urlResourceOwnerDetails' => 'https://example.com/userinfo',
            'clientId' => 'test client id',
        ];
        return [
            [
                $config,
                [
                    \SimpleSAML\Auth\State::ID => 'stateId',
                    'ForceAuthn' => true,
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?prompt=login&state=authoauth2%7CstateId&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
            [
                $config,
                [
                    \SimpleSAML\Auth\State::ID => 'stateId',
                    'isPassive' => true,
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?prompt=none&state=authoauth2%7CstateId&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
            [
                $config,
                [
                    \SimpleSAML\Auth\State::ID => 'stateId',
                    'oidc:acr_values' => 'Level4 Level3',
                    'oidc:display' => 'popup',
                ],
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'https://example.com/auth?acr_values=Level4%20Level3&display=popup&state=authoauth2%7CstateId&response_type=code&approval_prompt=auto&redirect_uri=http%3A%2F%2Flocalhost%2Fmodule.php%2Fauthoauth2%2Flinkback.php&client_id=test%20client%20id'
            ],
        ];
    }

    public function idTokenErrorDataProvider() {
        return [
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoiZXZpbCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6Im5pY2VpZHAifQ.T4JQmtmeES1r6On0KnBdJC3f7eFTPd8x_B5EM9c43RXaZHWaq_qpdcyyJzEYJ5er5YXe_hjaLmSybv0NqoVVfg',
                "Error with authentication source 'openidconnect': ID token has incorrect audience"
            ],
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6ImV2aWxpZHAifQ.NPAT8409vdVaQhh5OebxCPM6SxSNRdai3JoGo3cIabtYbjxf83jP-lj0thsbF_nD67QBCJhaz25Tjaw0anuhkw',
                "Error with authentication source 'openidconnect': ID token has incorrect issuer"
            ],
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6Im5pY2VpZHAifQ.D_g5KWCPuYMFBSFEix1zKv-hQ_QrU8LVAIzaLGn8JeCLF74DB0kCMLx0c4Clo0ZB4oc6kdVC0oCp2IeqQsGEGW',
                "Error with authentication source 'openidconnect': ID token validation failed: Signature verification failed"
            ],
        ];
    }

    /**
     * @dataProvider idTokenErrorDataProvider
     * @param $idToken
     * @param $expectedMessage
     */
    public function testIdTokenValidationFails($idToken, $expectedMessage)
    {
        $this->expectException(AuthSource::class);
        $this->expectExceptionMessage($expectedMessage);

        // given: A mock Oauth2 provider
        $code = 'theCode';
        $config = [
            'providerClass' => MockOAuth2Provider::class,
            'attributePrefix' => 'test.',
            'retryOnError' => 0,
            'clientId' => 'test client id',
            'issuer' => 'niceidp',
            'keys' => [ 'mykey' => file_get_contents(getenv('SIMPLESAMLPHP_CONFIG_DIR') . '/jwks-cert.pem') ],
        ];
        $state = [\SimpleSAML\Auth\State::ID => 'stateId'];

        /** @var $mock AbstractProvider|\PHPUnit_Framework_MockObject_MockObject*/
        $mock = $this->getMockBuilder(AbstractProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $accessToken = new AccessToken([
            'access_token' => 'stubToken',
            'id_token' => $idToken,
        ]);
        $mock->method('getAccessToken')
            ->with('authorization_code', ['code' => $code])
            ->willReturn($accessToken);

        $attributes = ['name' => 'Bob'];
        $user = new GenericResourceOwner($attributes, 'userId');
        $mock->method('getResourceOwner')
            ->with($accessToken)
            ->willReturn($user);


        MockOAuth2Provider::setDelegate($mock);

        // when: turning a code into a token and then into a resource owner attributes
        $authOAuth2 = $this->getInstance($config);
        $authOAuth2->finalStep($state, $code);
    }

    public function testLogoutNoEndpointConfigured() {
        $as = $this->getInstance([]);
        $state = [];
        $this->assertNull($as->logout($state));
    }

    public function testLogoutNoIDTokenInState() {
        $as = $this->getInstance([
            'urlEndSession' => 'https://example.org/logout',
        ]);
        $state = [];
        $this->assertNull($as->logout($state));
    }

    public function testLogoutRedirects() {
        // Override redirect behavior
        MockHttp::throwOnRedirectTrustedURL();

        $as = $this->getInstance([
            'urlEndSession' => 'https://example.org/logout',
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
