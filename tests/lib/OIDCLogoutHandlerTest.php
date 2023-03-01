<?php

namespace Test\SimpleSAML;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Auth\Source\OAuth2;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Module\authoauth2\locators\SourceService;
use SimpleSAML\Module\authoauth2\OIDCLogoutHandler;
use SimpleSAML\Auth\State;
use SimpleSAML\Session;
use SimpleSAML\Utils\HTTP;

class OIDCLogoutHandlerTest extends TestCase
{
    /**
     * @var OIDCLogoutHandler
     */
    private OIDCLogoutHandler $logoutHandler;

    private string $validStateValue = 'authoauth2-validStateId';

    /**
     * @var MockObject|OpenIDConnect
     */
    private $mockAuthSource;

    /**
     * @var MockObject|SourceService
     */
    private $mockSourceService;

    protected function setUp(): void
    {

        $this->logoutHandler = new OIDCLogoutHandler();

        $this->mockAuthSource = $this->createMock(OpenIDConnect::class);
        $this->mockAuthSource->method('getAuthId')->willReturn('mockAuthSource');

        $this->mockSourceService = $this->createMock(SourceService::class);
        $this->mockSourceService->method('getById')
            ->with('mockAuthSource', OpenIDConnect::class)
            ->willReturn($this->mockAuthSource);
        $this->logoutHandler->setSourceService($this->mockSourceService);
    }

    /**
     * Confirm checking if response can be handled by this module
     */
    public function testCanHandle()
    {
        $this->assertFalse($this->logoutHandler->canHandleResponseFromRequest([]));
        $this->assertFalse($this->logoutHandler->canHandleResponseFromRequest(['wrongParams' => 'value']));
        $this->assertFalse($this->logoutHandler->canHandleResponseFromRequest(['wrongParams' => 'value']));
        $this->assertFalse($this->logoutHandler->canHandleResponseFromRequest(['state' => 'wrong-prefix']));

        $this->assertTrue($this->logoutHandler->canHandleResponseFromRequest(['state' => 'authoauth2-']));
        $this->assertTrue($this->logoutHandler->canHandleResponseFromRequest(['state' => $this->validStateValue]));
        $this->assertTrue($this->logoutHandler->canHandleResponseFromRequest([
            'state' => 'authoauth2-otherstate',
            'other' => 'param'
        ]));
    }

    /**
     * If we can't handle the state param throw an exception
     */
    public function testUnhandleableResponse()
    {
        $this->expectException(\SimpleSAML\Error\BadRequest::class);
        $this->logoutHandler->handleResponseFromRequest(['state' => 'wrong-prefix']);
    }

    /**
     * Test behavior if state not found in session. User session could have expired during login.
     */
    public function testNoStateFoundInSession()
    {
        $this->expectException(\SimpleSAML\Error\NoState::class);
        Session::clearInternalState();
        $request = [
            'state' => $this->validStateValue,
        ];

        $this->logoutHandler->handleResponseFromRequest($request);
    }

    public function testValidResponse()
    {
        // given: a valid response
        $request = [
            'state' => $this->validStateValue,
            'code' => 'authCode'
        ];

        $this->mockSourceService->expects($this->exactly(1))
        ->method('completeLogout');
        $stateValue = serialize([
            State::ID => 'validStateId',
            State::STAGE => 'authouath2:logout',
            'authouath2:AuthId' => 'mockAuthSource',
            'LogoutCompletedHandler' => ['\SimpleSAML\Auth\Simple', 'logoutCompleted'],
            'ReturnTo' => '/',
        ]);

        Session::getSessionFromRequest()->setData('\SimpleSAML\Auth\State', 'validStateId', $stateValue);
        // when: handling the response
        $this->logoutHandler->handleResponseFromRequest($request);
        // earlier we required that the mocked completeLogout is called
    }
}
