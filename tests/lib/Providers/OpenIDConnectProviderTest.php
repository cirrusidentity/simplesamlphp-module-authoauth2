<?php

declare(strict_types=1);

namespace Test\SimpleSAML\Providers;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Providers\OpenIDConnectProvider;
use Symfony\Component\HttpFoundation\Request;
use Test\SimpleSAML\MockOpenIDConnectProvider;

class OpenIDConnectProviderTest extends TestCase
{
    public static function idTokenErrorDataProvider(): array
    {
        // phpcs:disable
        return [
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoiZXZpbCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6Im5pY2VpZHAifQ.GJaMKCvWGW3kDc_T4D2IyGclsu0OqYDUox7Xdw_7VZm3PoRrv6wvm6QyJ6PswS1Tu7sJxBfVulchaHlgWoISU_NnMX496gO6RqZ717Co8S6QNbj44NCd_eY3ql3mQfdafUFq1U9iP3D8zGPKbjRvKiZJNw2_LIk_Lo-g_5vWE6BaVHmSBxsRAS5ezcLGXl5ZmdPoW3VlY3CsACh1zjvfS4HCtFFTmsi1kr0jnDU_oNTbBJbUJpWVT2aIUa3il_2sChOqdKyoJozSYM6na8-8Sx6fYAcnWksoSi6fz4s578MsawQIwMwrsQsgyXzoXrVwxDdjyHDJ0zdoJ4Cm0Jg4jQ',
                "ID token has incorrect audience"
            ],
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6ImV2aWxpZHAifQ.LRYfGNzL_MHC75GPuio7Hl8hPRTWVnTtvzK2MaoVBz1EViBDO_66_Az1wppM6zK7RJLLzxFWbsrsiza9AcfKsZhstg3DBwGMCsTN4VR8Nv4vs36x1jJ42-di-nwrGHmpIjEl3sivTFux_nLiFAfSqFBQCSII9IslbdXkkgaMua3Kti_qxqx_yMhHGZxJB3ToGl8NBhFe4Bre9Dw3mPicoTAcWjys2wpOh7i5PGNyGnyDto8oJwlzHngf7sXXPEB_vDeC2HjlTHLMD-C3vXab1gQL9FVwfKuQtratioD6ZSJ4tcbhGTu_BtZvs3p2vqQKIJCCYz4MpUQr1vngKksXvA',
                "ID token has incorrect issuer"
            ],
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6Im5pY2VpZHAifQ.D_g5KWCPuYMFBSFEix1zKv-hQ_QrU8LVAIzaLGn8JeCLF74DB0kCMLx0c4Clo0ZB4oc6kdVC0oCp2IeqQsGEGW',
                "ID token validation failed"
            ],
        ];
        // phpcs:enable
    }

    /**
     * @param string $idToken
     * @param string $expectedMessage
     */
    #[DataProvider('idTokenErrorDataProvider')]
    public function testIdTokenValidationFails(string $idToken, string $expectedMessage): void
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage($expectedMessage);

        $configDir = !empty(getenv('SIMPLESAMLPHP_CONFIG_DIR')) ? (string)getenv('SIMPLESAMLPHP_CONFIG_DIR') : '';
        MockOpenIDConnectProvider::setSigningKeys([
            'mykey' => file_get_contents($configDir . '/jwks-cert.pem')
                                                  ]);
        $provider = new MockOpenIDConnectProvider([
            'issuer' => 'niceidp',
            'clientId' => 'test client id',
        ]);
        $provider->verifyIDToken($idToken);
    }

    /**
     * Confirm scope can be set with scopes or authoricationUrl.scope
     */
    public function testSetScopes(): void
    {
        $provider = new OpenIDConnectProvider(
            ['issuer' => 'https://accounts.google.com']
        );
        $url = $provider->getAuthorizationUrl();
        $request = Request::create($url);
        $this->assertEquals('openid profile', $request->query->get('scope'));

        $url = $provider->getAuthorizationUrl(['scope' => 'otherscope']);
        $request = Request::create($url);
        $this->assertEquals('otherscope', $request->query->get('scope'));

        $provider = new OpenIDConnectProvider(
            ['issuer' => 'https://accounts.google.com', 'scopes' => ['openid']]
        );
        $url = $provider->getAuthorizationUrl();
        $request = Request::create($url);
        $this->assertEquals('openid', $request->query->get('scope'));
    }

    public function testConfiguringDiscoveryUrl(): void
    {
        $provider = new OpenIDConnectProvider(
            ['issuer' => 'https://accounts.example.com']
        );
        $this->assertEquals(
            'https://accounts.example.com/.well-known/openid-configuration',
            $provider->getDiscoveryUrl()
        );

        $provider = new OpenIDConnectProvider(
            [
                'issuer' => 'https://accounts.example.com',
                'discoveryUrl' => 'https://otherhost.example.com/path/path2/.well-known/openid-configuration'
            ]
        );
        $this->assertEquals(
            'https://otherhost.example.com/path/path2/.well-known/openid-configuration',
            $provider->getDiscoveryUrl()
        );
    }

    public function testGetPkceMethodGetsSetFromConfig(): void
    {
        $provider = new OpenIDConnectProvider(
            ['issuer' => 'https://accounts.example.com']
        );
        // make the protected getPkceMethod available
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getPkceMethod');
        $method->setAccessible(true);
        $this->assertNull($method->invoke($provider));

        $provider = new OpenIDConnectProvider([
            'issuer' => 'https://accounts.example.com',
            'pkceMethod' => 'S256'
        ]);
        // make the protected getPkceMethod available
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getPkceMethod');
        $method->setAccessible(true);
        $this->assertEquals('S256', $method->invoke($provider));
    }
}
