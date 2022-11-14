<?php

namespace Test\SimpleSAML\Providers;

use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Providers\AdjustableGenericProvider;
use SimpleSAML\Module\authoauth2\Providers\OpenIDConnectProvider;
use Symfony\Component\HttpFoundation\Request;

class AdjustableGenericProviderTest extends TestCase
{
    private array $requiredProviderConifg = [
        'urlAuthorize' => 'https://www.facebook.com/dialog/oauth',
        'urlAccessToken' => 'https://graph.facebook.com/oauth/access_token',
        'urlResourceOwnerDetails' => 'https://graph.facebook.com/me?fields=123',
    ];

    /**
     * @dataProvider adjustProvider
     * @param array $tokenResponse
     * @param $expectedQueryString
     */
    public function testAdjustingResourceOwnerUrl(array $tokenResponse, $expectedQueryString): void
    {

        $token = new AccessToken($tokenResponse);
        $config = $this->requiredProviderConifg + [
                'tokenFieldsToUserDetailsUrl' => [
                    'uid' => 'uid',
                    'rename' => 'newname',
                    'access_token' => 'access_token'
                ]
            ];
        $provider = new AdjustableGenericProvider($config);
        $url = $provider->getResourceOwnerDetailsUrl($token);
        $query = parse_url($url, PHP_URL_QUERY);
        $this->assertEquals($expectedQueryString, $query);
    }

    public function adjustProvider(): array
    {
        return [
            [
                ['uid' => 'abc', 'rename' => '123', 'ignore' => 'ig', 'access_token' => 'secret'],
                'fields=123&uid=abc&newname=123&access_token=secret'
            ],
            [['access_token' => 'secret'], 'fields=123&access_token=secret'],
        ];
    }

    /**
     * Test only adjusting the url if configured
     */
    public function testNoAdjustmentsToUrl()
    {
        $provider = new AdjustableGenericProvider($this->requiredProviderConifg);
        $token = new AccessToken(['access_token' => 'abc', 'someid' => 123]);
        $url = $provider->getResourceOwnerDetailsUrl($token);
        $this->assertEquals('https://graph.facebook.com/me?fields=123', $url);
    }

    /**
     * Confirm scope can be set with scopes or authoricationUrl.scope
     */
    public function testSetScopes()
    {
        $provider = new AdjustableGenericProvider($this->requiredProviderConifg);
        $url = $provider->getAuthorizationUrl();
        $request = Request::create($url);
        $this->assertFalse($request->query->has('scope'), 'no default scopes');

        $url = $provider->getAuthorizationUrl(['scope' => 'otherscope']);
        $request = Request::create($url);
        $this->assertEquals('otherscope', $request->query->get('scope'));

        $provider = new AdjustableGenericProvider($this->requiredProviderConifg + ['scopes' => ['openid']]);

        $url = $provider->getAuthorizationUrl();
        $request = Request::create($url);
        $this->assertEquals('openid', $request->query->get('scope'));
    }
}
