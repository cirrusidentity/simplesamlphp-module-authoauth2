<?php

namespace Test\SimpleSAML\Providers;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Providers\OpenIDConnectProvider;
use Symfony\Component\HttpFoundation\Request;
use Test\SimpleSAML\MockOpenIDConnectProvider;

class OpenIDConnectProviderTest extends TestCase
{
    public function idTokenErrorDataProvider(): array
    {
        // phpcs:disable
        return [
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoiZXZpbCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6Im5pY2VpZHAifQ.T4JQmtmeES1r6On0KnBdJC3f7eFTPd8x_B5EM9c43RXaZHWaq_qpdcyyJzEYJ5er5YXe_hjaLmSybv0NqoVVfg',
                "ID token has incorrect audience"
            ],
            [
                'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Im15a2V5In0.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXVkIjoidGVzdCBjbGllbnQgaWQiLCJpYXQiOjE1MTYyMzkwMjIsImlzcyI6ImV2aWxpZHAifQ.NPAT8409vdVaQhh5OebxCPM6SxSNRdai3JoGo3cIabtYbjxf83jP-lj0thsbF_nD67QBCJhaz25Tjaw0anuhkw',
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
     * @dataProvider idTokenErrorDataProvider
     * @param $idToken
     * @param $expectedMessage
     */
    public function testIdTokenValidationFails($idToken, $expectedMessage): void
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage($expectedMessage);

        MockOpenIDConnectProvider::setSigningKeys([
            'mykey' => file_get_contents(getenv('SIMPLESAMLPHP_CONFIG_DIR') . '/jwks-cert.pem')
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
