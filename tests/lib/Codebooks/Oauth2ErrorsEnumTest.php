<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Codebooks;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Codebooks\Oauth2ErrorsEnum;

final class Oauth2ErrorsEnumTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('access_denied', Oauth2ErrorsEnum::AccessDenied->value);
        $this->assertSame('consent_required', Oauth2ErrorsEnum::ConsentRequired->value);
        $this->assertSame('invalid_scope', Oauth2ErrorsEnum::InvalidScope->value);
        $this->assertSame('user_cancelled_authorize', Oauth2ErrorsEnum::UserCancelledAuthorize->value);
        $this->assertSame('user_cancelled_login', Oauth2ErrorsEnum::UserCancelledLogin->value);
        $this->assertSame('user_denied', Oauth2ErrorsEnum::UserDenied->value);
    }

    public function testEnumKeys(): void
    {
        $this->assertSame(Oauth2ErrorsEnum::AccessDenied, Oauth2ErrorsEnum::from('access_denied'));
        $this->assertSame(Oauth2ErrorsEnum::ConsentRequired, Oauth2ErrorsEnum::from('consent_required'));
        $this->assertSame(Oauth2ErrorsEnum::InvalidScope, Oauth2ErrorsEnum::from('invalid_scope'));
        $this->assertSame(Oauth2ErrorsEnum::UserCancelledAuthorize, Oauth2ErrorsEnum::from('user_cancelled_authorize'));
        $this->assertSame(Oauth2ErrorsEnum::UserCancelledLogin, Oauth2ErrorsEnum::from('user_cancelled_login'));
        $this->assertSame(Oauth2ErrorsEnum::UserDenied, Oauth2ErrorsEnum::from('user_denied'));
    }
}
