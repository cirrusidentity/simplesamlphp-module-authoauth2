<?php

namespace Test\SimpleSAML\Auth\Source;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module\authoauth2\Auth\Source\OrcidOIDCAuth;

class OrcidOIDCAuthTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
         // When all tests are run at once, sometimes a Configuration is created prior to us
        // setting the one we want to use for the test.
        Configuration::clearInternalState();
    }

    public function emailResponseProvider()
    {
        return [
            // no email addresses
            [json_decode('{"last-modified-date":null,"email":[],"path":"/0000-0000-0000-0000/email"}', true), null],
            // no primary, but one non-primary
            [json_decode('{"last-modified-date":{"value":1664233445808},"email":[{"created-date":{"value":1661900382942},"last-modified-date":{"value":1664233445808},"source":{"source-orcid":{"uri":"https://orcid.org/0000-0000-0000-0000","path":"0000-0000-0000-0000","host":"orcid.org"},"source-client-id":null,"source-name":{"value":"Test User"},"assertion-origin-orcid":null,"assertion-origin-client-id":null,"assertion-origin-name":null},"email":"non-primary@example.org","path":null,"visibility":"public","verified":true,"primary":false,"put-code":null}],"path":"/0000-0000-0000-0000/email"}', true), 'non-primary@example.org'],
            // primary and non-primary
            [json_decode('{"last-modified-date":{"value":1664233809699},"email":[{"created-date":{"value":1487980758777},"last-modified-date":{"value":1664233809699},"source":{"source-orcid":{"uri":"https://orcid.org/0000-0000-0000-0000","path":"0000-0000-0000-0000","host":"orcid.org"},"source-client-id":null,"source-name":{"value":"Test User"},"assertion-origin-orcid":null,"assertion-origin-client-id":null,"assertion-origin-name":null},"email":"non-primary@example.org","path":null,"visibility":"public","verified":true,"primary":false,"put-code":null},{"created-date":{"value":1661900382942},"last-modified-date":{"value":1664233445808},"source":{"source-orcid":{"uri":"https://orcid.org/0000-0000-0000-0000","path":"0000-0000-0000-0000","host":"orcid.org"},"source-client-id":null,"source-name":{"value":"Test User"},"assertion-origin-orcid":null,"assertion-origin-client-id":null,"assertion-origin-name":null},"email":"primary@example.org","path":null,"visibility":"public","verified":true,"primary":true,"put-code":null}],"path":"/0000-0000-0000-0000/email"}', true), 'primary@example.org'],
            // only primary
            [json_decode('{"last-modified-date":{"value":1664233445808},"email":[{"created-date":{"value":1661900382942},"last-modified-date":{"value":1664233445808},"source":{"source-orcid":{"uri":"https://orcid.org/0000-0000-0000-0000","path":"0000-0000-0000-0000","host":"orcid.org"},"source-client-id":null,"source-name":{"value":"Test User"},"assertion-origin-orcid":null,"assertion-origin-client-id":null,"assertion-origin-name":null},"email":"primary@example.org","path":null,"visibility":"public","verified":true,"primary":true,"put-code":null}],"path":"/0000-0000-0000-0000/email"}', true), 'primary@example.org']
        ];
    }

    /**
     * @dataProvider emailResponseProvider
     * @param array $emailResponse The JSON response from the email endpoint
     * @param array $expectedEmail What the resolved email address should be
     */
    public function testEmailResolution(array $emailResponse, ?string $expectedEmail)
    {
        $orcidAuth = new OrcidOIDCAuth(['AuthId' => 'orcid'], []);
        $state = [
            'Attributes' => [
                'oidc.sub' => ['abc']
            ]
        ];
        $token = new AccessToken(['access_token' => 'abc']);
        /**
         * @var $mock AbstractProvider|MockObject
         */
        $mock = $this->getMockBuilder(AbstractProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRequest = $this->createMock(RequestInterface::class);
        $mock->method('getAuthenticatedRequest')
            ->with('GET', strtr('https://pub.orcid.org/v3.0/@orcid/email', ['@orcid' => $state['Attributes']['oidc.sub'][0]]), $token, ['headers' => ['Accept' => 'application/json']])
            ->willReturn($mockRequest);

        $mock->method('getParsedResponse')
            ->with($mockRequest)
            ->willReturn($emailResponse);
        $email = $orcidAuth->parseEmailLookupResponse($emailResponse);

        $this->assertEquals(
            $expectedEmail,
            $email
        );
    }
}
