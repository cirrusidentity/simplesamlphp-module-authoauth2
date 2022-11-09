<?php

namespace Test\SimpleSAML;


use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Module\authoauth2\OIDCLogoutHandler;
use SimpleSAML\Auth\State;

use SimpleSAML\Session;

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

    public static function setUpBeforeClass(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(__DIR__) . '/config');
    }

    protected function setUp(): void
    {

        $this->logoutHandler = new OIDCLogoutHandler();

        $this->mockAuthSource = $this->createMock(OpenIDConnect::class);
        $this->mockAuthSource->method('getAuthId')->willReturn('mockAuthSource');
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
        // Override redirect behavior
        MockHttp::throwOnRedirectTrustedURL();

        // given: a valid response
        $request = [
            'state' => $this->validStateValue,
            'code' => 'authCode'
        ];

        $stateValue = serialize([
            State::ID => 'validStateId',
            State::STAGE => 'authouath2:logout',
            'authouath2:AuthId' => 'mockAuthSource',
            'LogoutCompletedHandler' => ['\SimpleSAML\Auth\Simple', 'logoutCompleted'],
            'ReturnTo' => '/',
        ]);

        Session::getSessionFromRequest()->setData('\SimpleSAML\Auth\State', 'validStateId', $stateValue);
        // when: handling the response
        try {
            $this->logoutHandler->handleResponseFromRequest($request);
            $this->fail("Redirect expected");
        } catch (RedirectException $e) {
            $this->assertEquals('redirectTrustedURL', $e->getMessage());
            $this->assertEquals(
                '/',
                $e->getUrl(),
                "First argument should be the redirect url"
            );
            $this->assertEquals([], $e->getParams(), "query params are already added into url");
        }
    }
}
