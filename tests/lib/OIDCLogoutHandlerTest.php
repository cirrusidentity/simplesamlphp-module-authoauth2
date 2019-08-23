<?php

namespace Test\SimpleSAML;

use CirrusIdentity\SSP\Test\Auth\MockAuthSource;
use CirrusIdentity\SSP\Test\Capture\RedirectException;
use CirrusIdentity\SSP\Test\MockHttp;
use PHPUnit_Framework_MockObject_MockObject;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Module\authoauth2\OIDCLogoutHandler;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\AuthSource;
use SimpleSAML\Error\UserAborted;
use SimpleSAML\Session;

use AspectMock\Test as test;

class OIDCLogoutHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OIDCLogoutHandler
     */
    private $logoutHandler;

    private $validStateValue = 'authoauth2-validStateId';

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|OpenIDConnect
     */
    private $mockAuthSource;

    public static function setUpBeforeClass()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(__DIR__) . '/config');
    }

    protected function setUp()
    {
        test::clean();
        MockAuthSource::clearInternalState();
        $this->logoutHandler = new OIDCLogoutHandler();

        $this->mockAuthSource = $this->createMock(OpenIDConnect::class);
        $this->mockAuthSource->method('getAuthId')->willReturn('mockAuthSource');
        MockAuthSource::getById($this->mockAuthSource, 'mockAuthSource');
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
