<?php

namespace Test\SimpleSAML\Auth\Source;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SimpleSAML\Module\authoauth2\Auth\Source\MicrosoftHybridAuth;
use Test\SimpleSAML\MockOAuth2Provider;

class MicrosoftHybridAuthTest extends TestCase
{
    /**
     * @dataProvider combineOidcAndGraphProfileProvider
     * @param ?string $idToken The id_token response from the server
     * @param array $expectedAttributes The expected attributes
     */
    public function testCombineOidcAndGraphProfile(
        ?string $idToken,
        array $authenticatedRequestAttributes,
        array $expectedAttributes
    ) {
        // given: A mock Oauth2 provider
        $code = 'theCode';
        $info = ['AuthId' => 'oauth2'];
        $config = [
            'template' => 'MicrosoftGraphV1',
            'providerClass' => MockOAuth2Provider::class,
            'authenticatedApiRequests' => ['https://mock.com/v1.0/me/memberOf'],
        ];
        $state = [\SimpleSAML\Auth\State::ID => 'stateId'];

        /**
         * @var AbstractProvider|MockObject $mock
         */
        $mock = $this->getMockBuilder(AbstractProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token = new AccessToken(['access_token' => 'stubToken', 'id_token' => $idToken]);
        $mock->method('getAccessToken')
            ->with('authorization_code', ['code' => $code])
            ->willReturn($token);

        // graph api seems to return null for email
        $attributes = ['id' => 'a76d6a7a097c1e9d', 'mail' => null];
        $user = new GenericResourceOwner($attributes, 'userId');

        $mock->method('getResourceOwner')
            ->with($token)
            ->willReturn($user);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mock->method('getAuthenticatedRequest')
            ->with('GET', 'https://mock.com/v1.0/me/memberOf', $token)
            ->willReturn($mockRequest);

        $mock->method('getParsedResponse')
            ->with($mockRequest)
            ->willReturn($authenticatedRequestAttributes);

        MockOAuth2Provider::setDelegate($mock);

        // when: turning a code into a token and then into a resource owner attributes
        $authOAuth2 = new MicrosoftHybridAuth($info, $config);
        $authOAuth2->finalStep($state, $code);

        // then: The attributes should be returned based on the getResourceOwner call
        $this->assertEquals($expectedAttributes, $state['Attributes']);
    }


    public function combineOidcAndGraphProfileProvider()
    {
        $expectedGraphAttributes = ['microsoft.id' => ['a76d6a7a097c1e9d'],
            'microsoft.@odata.context' => ['https://graph.microsoft.com/v1.0/$metadata#directoryObjects'],
            'microsoft.value.0.@odata.type' => ['#microsoft.graph.group'],
            'microsoft.value.0.id' => ['11111111-1111-1111-1111-111111111111']];
        // A Graph Id token. note: only the payload is valid. Header and signature are not
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $validIdToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6IjFMVE16YWtpaGlSbGFfOHoyQkVKVlhlV01xbyJ9.eyJ2ZXIiOiIyLjAiLCJpc3MiOiJodHRwczovL2xvZ2luLm1pY3Jvc29mdG9ubGluZS5jb20vOTE4ODA0MGQtNmM2Ny00YzViLWIxMTItMzZhMzA0YjY2ZGFkL3YyLjAiLCJzdWIiOiJBQUFBQUFBQUFBQUFBQUFBQUFBQUFORHBFcUNHa3lPVVFDTXpHOHRGYUUiLCJhdWQiOiI5ZTdkZTIyZS0zYTE3LTQ0ZmQtODdjNy1jNmVjZWIxYmVlMGUiLCJleHAiOjE1Mzk5NjUwNDUsImlhdCI6MTUzOTg3ODM0NSwibmJmIjoxNTM5ODc4MzQ1LCJuYW1lIjoiU3RldmUgU3RyYXR1cyIsInByZWZlcnJlZF91c2VybmFtZSI6InN0ZXZlLnN0cmF0dXNAb3V0bG9vay5jb20iLCJvaWQiOiIwMDAwMDAwMC0wMDAwLTAwMDAtYTc2ZDZhN2EwOTdjMWU5ZCIsImVtYWlsIjoic3RldmUuc3RyYXR1c0BvdXRsb29rLmNvbSIsInRpZCI6IjkxODgwNDBkLTZjNjctNGM1Yi1iMTEyMzZhMzA0YjY2ZGFkIiwiYWlvIjoiRGI1YmRMSHBaSkdla0h3czlxaHlkUkFHSGR1cSFvUDdpS1cxYzFFQkd2dWhDWnZXS2luS0FoVnFZV3NtYSEwT3ZiRTFmV1J2TUF3NHFLUVBud3N6akQwKkd2N1RsbFpOY2FxcDQ0eTM0ZyJ9.SjNeBS11Qa2eXKLhxSApShFMLQ9nDjTXT27JZm3cctM';
        $authenticatedRequestAttributes = [
            '@odata.context' => 'https://graph.microsoft.com/v1.0/$metadata#directoryObjects',
            'value' => [
                0 => [
                    '@odata.type' => '#microsoft.graph.group',
                    'id' => '11111111-1111-1111-1111-111111111111',
                ],
            ],
        ];
        $conflictedRequestAttributes = [
            'id' => ['11111111'],
            'name' => ['Steve Stratus'],
            'mail' => ['steve.stratus@outlook.com'],
            '@odata.context' => 'https://graph.microsoft.com/v1.0/$metadata#directoryObjects',
            'value' => [
                0 => [
                    '@odata.type' => '#microsoft.graph.group',
                    'id' => '11111111-1111-1111-1111-111111111111',
                ],
            ],
        ];
        return [
            // jwt, expected attributes
            ['invalidJwt', $authenticatedRequestAttributes, $expectedGraphAttributes],
            ['', $authenticatedRequestAttributes, $expectedGraphAttributes],
            [null, $authenticatedRequestAttributes, $expectedGraphAttributes],
            ['blah.abc.egd', $authenticatedRequestAttributes, $expectedGraphAttributes],
            [$validIdToken, $authenticatedRequestAttributes,
                [
                    'microsoft.name' => ['Steve Stratus'],
                    'microsoft.mail' => ['steve.stratus@outlook.com'],
                    'microsoft.id' => ['a76d6a7a097c1e9d'],
                    'microsoft.@odata.context' => ['https://graph.microsoft.com/v1.0/$metadata#directoryObjects'],
                    'microsoft.value.0.@odata.type' => ['#microsoft.graph.group'],
                    'microsoft.value.0.id' => ['11111111-1111-1111-1111-111111111111'],
                ]
            ],
            [$validIdToken, $conflictedRequestAttributes, [
                'microsoft.name' => ['Steve Stratus'],
                'microsoft.mail' => ['steve.stratus@outlook.com'],
                'microsoft.id' => ['11111111'],
                'microsoft.@odata.context' => ['https://graph.microsoft.com/v1.0/$metadata#directoryObjects'],
                'microsoft.value.0.@odata.type' => ['#microsoft.graph.group'],
                'microsoft.value.0.id' => ['11111111-1111-1111-1111-111111111111'],
            ]
            ],

        ];
    }
}
