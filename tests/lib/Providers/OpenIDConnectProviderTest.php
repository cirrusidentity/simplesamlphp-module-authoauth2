<?php

namespace Test\SimpleSAML\Providers;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use SimpleSAML\Module\authoauth2\Providers\OpenIDConnectProvider;
use Test\SimpleSAML\MockOpenIDConnectProvider;

class OpenIDConnectProviderTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(__DIR__)) . '/config');
    }

    public function idTokenErrorDataProvider() {
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
    }

    /**
     * @dataProvider idTokenErrorDataProvider
     * @param $idToken
     * @param $expectedMessage
     */
    public function testIdTokenValidationFails($idToken, $expectedMessage)
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage($expectedMessage);

        MockOpenIDConnectProvider::setSigningKeys([ 'mykey' => file_get_contents(getenv('SIMPLESAMLPHP_CONFIG_DIR') . '/jwks-cert.pem') ]);
        $provider = new MockOpenIDConnectProvider([
            'issuer' => 'niceidp',
            'clientId' => 'test client id',
        ]);
        $provider->verifyIDToken($idToken);
    }
}
