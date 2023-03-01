<?php

namespace Test\SimpleSAML;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use SimpleSAML\Module\authoauth2\OAuth2ResponseHandler;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\AuthSource;
use SimpleSAML\Error\UserAborted;
use SimpleSAML\Session;
use SimpleSAML\Utils\HTTP;

class OAuth2ResponseHandlerTest extends TestCase
{
    /**
     * @var OAuth2ResponseHandler
     */
    private $responseHandler;

    private $validStateValue = 'authoauth2|validStateId';

    /**
     * @var MockObject|OAuth2
     */
    private $mockAuthSource;

    /**
     * @var MockObject|SourceService
     */
    protected $mockSourceService;

    protected function setUp(): void
    {
        $this->responseHandler = new OAuth2ResponseHandler();

        $this->mockAuthSource = $this->createMock(OAuth2::class);
        $this->mockAuthSource->method('getAuthId')->willReturn('mockAuthSource');
        $this->mockSourceService = $this->createMock(SourceService::class);
        $this->mockSourceService->method('getById')
            ->with('mockAuthSource', OAuth2::class)
            ->willReturn($this->mockAuthSource);
        $this->responseHandler->setSourceService($this->mockSourceService);
    }

    /**
     * Confirm checking if response can be handled by this module
     */
    public function testCanHandle(): void
    {
        $this->assertFalse($this->responseHandler->canHandleResponseFromRequest([]));
        $this->assertFalse($this->responseHandler->canHandleResponseFromRequest(['wrongParams' => 'value']));
        $this->assertFalse($this->responseHandler->canHandleResponseFromRequest(['wrongParams' => 'value']));
        $this->assertFalse($this->responseHandler->canHandleResponseFromRequest(['state' => 'wrong-prefix']));

        $this->assertTrue($this->responseHandler->canHandleResponseFromRequest(['state' => 'authoauth2|']));
        $this->assertTrue($this->responseHandler->canHandleResponseFromRequest(['state' => $this->validStateValue]));
        $this->assertTrue($this->responseHandler->canHandleResponseFromRequest([
            'state' => 'authoauth2|otherstate',
            'other' => 'param'
        ]));
    }

    /**
     * If we can't handle the state param throw an exception
     */
    public function testUnhandleableResponse()
    {
        $this->expectException(\SimpleSAML\Error\BadRequest::class);
        $this->responseHandler->handleResponseFromRequest(['state' => 'wrong-prefix']);
    }

    /**
     * Test behavior if state not found in session. User session could have expired during login.
     */
    public function testNoStateFoundInSession()
    {

        $this->expectException(\SimpleSAML\Error\NoState::class);
        $request = [
            'state' => $this->validStateValue,
        ];

        $this->responseHandler->handleResponseFromRequest($request);
    }

    /**
     * @dataProvider noCodeDataProvider
     * @param $queryParams
     * @param $expectedException
     * @param $expectedMessage
     */
    public function testNoCodeInResponse($queryParams, $expectedException, $expectedMessage)
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $request = array_merge([
            'state' => $this->validStateValue,
        ], $queryParams);

        $stateValue = serialize([
            State::ID => 'validStateId',
            State::STAGE => 'authouath2:init',
            'authouath2:AuthId' => 'mockAuthSource',
        ]);
        $this->mockAuthSource->method('getConfig')->willReturn(
            new \SimpleSAML\Configuration(['useConsentErrorPage' => false], 'authsources:oauth2')
        );

        Session::getSessionFromRequest()->setData('\SimpleSAML\Auth\State', 'validStateId', $stateValue);
        $this->responseHandler->handleResponseFromRequest($request);
    }

    public function noCodeDataProvider()
    {
        return [
            // OAuth2 AS did not return error code or a authz code
            [
                [],
                AuthSource::class,
                "Error with authentication source 'mockAuthSource': Authentication failed: []"
            ],
            // OAuth2 AS says client not allowed
            [
                ['error' => 'unauthorized_client'],
                AuthSource::class,
                "Error with authentication source 'mockAuthSource': Authentication failed: [unauthorized_client]"
            ],
            // OAuth2 AS has custom error code
            [
                ['error' => 'special_code', 'error_description' => 'Closed'],
                AuthSource::class,
                "Error with authentication source 'mockAuthSource': Authentication failed: [special_code] Closed"
            ],
            // OAuth2 AS says users denied access
            [
                ['error' => 'access_denied', 'error_description' => 'User declined'],
                UserAborted::class,
                "USERABORTED"
            ],
            // OAuth2 AS says users denied access, no description
            [
                ['error' => 'access_denied'],
                UserAborted::class,
                "USERABORTED"
            ],
            // LinkedIn uses their own error code
            [
                ['error' => 'user_cancelled_authorize', 'error_description' => 'The user cancelled the authorization'],
                UserAborted::class,
                "USERABORTED"
            ],

        ];
    }

    public function testValidResponse()
    {

        // given: a valid response
        $request = [
            'state' => $this->validStateValue,
            'code' => 'authCode'
        ];

        $stateValue = serialize([
            State::ID => 'validStateId',
            State::STAGE => 'authouath2:init',
            'authouath2:AuthId' => 'mockAuthSource',
        ]);

        // Confirm completeAuth is called later
        $this->mockSourceService->expects($this->once())
            ->method('completeAuth')
            ->with(
                $this->callback(function (array $state) {
                    $this->assertEquals([
                        '\SimpleSAML\Auth\State.id' => 'validStateId',
                        '\SimpleSAML\Auth\State.stage' => 'authouath2:init',
                        'authouath2:AuthId' => 'mockAuthSource'
                    ], $state);
                    return true;
                })
            );

        // phpunit mock to confirm authsource called
        $this->mockAuthSource->expects($this->once())
            ->method('finalStep')
            ->with(
                // Check state was deserialized and passed in
                $this->arrayHasKey('authouath2:AuthId'),
                // Check OAuth2 auth code was passed in
                $this->equalTo('authCode')
            );

        Session::getSessionFromRequest()->setData('\SimpleSAML\Auth\State', 'validStateId', $stateValue);
        // when: handling the response
        $this->responseHandler->handleResponseFromRequest($request);
    }

    /**
     * Confirm mock verification is working.
     */
    public function testSanityCheckMocks()
    {
        $myState = [];
        $this->mockAuthSource->expects($this->once())
            ->method('finalStep')
            ->with(
                $myState,
                'code'
            );
        $this->mockAuthSource->finalStep($myState, 'code');
    }

    public function testUserCancelledErrorPage()
    {
        $expectedUrl = 'http://localhost/module.php/authoauth2/errors/consent.php';
        $http = $this->createMock(HTTP::class);
        $http->method('redirectTrustedURL')
            ->with($expectedUrl)
            ->willThrowException(
                new RedirectException('redirectTrustedURL', $expectedUrl)
            );
        $this->responseHandler->setHttp($http);
        // Use an empty config to test defaults
        $this->mockAuthSource->method('getConfig')->willReturn(
            new \SimpleSAML\Configuration([], 'authsources:oauth2')
        );
        $request = [
            'state' => $this->validStateValue,
            'error' => 'access_denied',
        ];

        $stateValue = serialize([
            State::ID => 'validStateId',
            State::STAGE => 'authouath2:init',
            'authouath2:AuthId' => 'mockAuthSource',
        ]);

        Session::getSessionFromRequest()->setData('\SimpleSAML\Auth\State', 'validStateId', $stateValue);
        try {
            $this->responseHandler->handleResponseFromRequest($request);
            $this->fail("Redirect expected");
        } catch (RedirectException $e) {
            $this->assertEquals('redirectTrustedURL', $e->getMessage());
            $this->assertEquals($expectedUrl, $e->getUrl());
        }
    }
}
