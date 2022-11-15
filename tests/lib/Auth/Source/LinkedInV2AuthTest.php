<?php

namespace Test\SimpleSAML\Auth\Source;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module\authoauth2\Auth\Source\LinkedInV2Auth;

class LinkedInV2AuthTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
         // When all tests are run at once, sometimes a Configuration is created prior to us
        // setting the one we want to use for the test.
        Configuration::clearInternalState();
    }

    /**
     * Confirms linkedIn's attribute structure gets converted correctly
     * @dataProvider attributeConversionProvider
     * @param array $userAttributes The attributes from the linkedIn endpoint
     * @param array $expectedAttributes The expected attributes
     */
    public function testAttributeConversion(array $userAttributes, array $expectedAttributes)
    {
        $linkedInAuth = new LinkedInV2Auth(['AuthId' => 'linked'], []);
        $attributes = $linkedInAuth->convertResourceOwnerAttributes($userAttributes, 'linkedin.');
        $this->assertEquals($expectedAttributes, $attributes);
    }

    public function attributeConversionProvider(): array
    {
        return [
            [["id" => "abc"], ["linkedin.id" => ["abc"]]],
            [
                [
                    "id" => "abc",
                    "firstName" => ["localized" => ["en_US" => "Jon", "en_CA" => "John"]],
                    "lastName" => ['not-used']
                ],
                ["linkedin.id" => ["abc"], 'linkedin.firstName' => ["Jon"]]
            ],
            [
                [
                    "id" => "abc",
                    "firstName" => ["localized" => ["en_US" => "Jon", "en_CA" => "John"]],
                    "lastName" => ["localized" => ["en_CA" => "Smith"]],
                ],
                ["linkedin.id" => ["abc"], 'linkedin.firstName' => ["Jon"], 'linkedin.lastName' => ["Smith"]]
            ],
        ];
    }

    public function testNoEmailCallIfNotRequested(): void
    {
        $linkedInAuth = new LinkedInV2Auth(['AuthId' => 'linked'], ['scopes' => ['r_liteprofile']]);
        $state = [];
        /**
         * @var AbstractProvider|MockObject $mock
         */
        $mock = $this->getMockBuilder(AbstractProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->never())
            ->method('getAuthenticatedRequest');
        $linkedInAuth->postFinalStep(new AccessToken(['access_token' => 'abc']), $mock, $state);

        $this->assertEquals([], $state, "State array not changed");
    }

    /**
     * @dataProvider getEmailProvider
     * @param array $emailResponse The response from the email endpoint
     * @param array $expectedAttributes What the SSP attributes are expected to be
     */
    public function testGettingEmail(array $emailResponse, array $expectedAttributes)
    {
        $linkedInAuth = new LinkedInV2Auth(['AuthId' => 'linked'], []);
        $state = [
            'Attributes' => [
                'linkedin.id' => ['abc']
            ]
        ];

        $token = new AccessToken(['access_token' => 'abc']);
        /**
         * @var AbstractProvider|MockObject $mock
         */
        $mock = $this->getMockBuilder(AbstractProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRequest = $this->createMock(RequestInterface::class);
        $mock->method('getAuthenticatedRequest')
            ->with('GET', 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', $token)
            ->willReturn($mockRequest);

        $mock->method('getParsedResponse')
            ->with($mockRequest)
            ->willReturn($emailResponse);
        $linkedInAuth->postFinalStep($token, $mock, $state);

        $this->assertEquals(
            $expectedAttributes,
            $state['Attributes'],
            "mail should be added"
        );
    }

    public function getEmailProvider()
    {
        return [
            [
                // valid email response
                [
                    "elements" => [
                        [
                            "handle" => "urn:li:emailAddress:5266785132",
                            "handle~" => [
                                "emailAddress" => "testuser@cirrusidentity.com"
                            ]
                        ]
                    ]
                ],
                // email added
                ['linkedin.id' => ['abc'], 'linkedin.emailAddress' => ['testuser@cirrusidentity.com']]
            ],
            [
                [
                    'someerror' => 'errormessage'
                ],
                // email not added
                ['linkedin.id' => ['abc']],
            ],
        ];
    }
}
