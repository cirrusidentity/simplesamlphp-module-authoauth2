<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Tests\Codebooks;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\authoauth2\Codebooks\RoutesEnum;

class RoutesEnumTest extends TestCase
{
    public function testLinkbackEnum(): void
    {
        $this->assertEquals('linkback', RoutesEnum::Linkback->value);
    }

    public function testLogoutEnum(): void
    {
        $this->assertEquals('logout', RoutesEnum::Logout->value);
    }

    public function testLoggedOutEnum(): void
    {
        $this->assertEquals('loggedout', RoutesEnum::LoggedOut->value);
    }

    public function testConsentErrorEnum(): void
    {
        $this->assertEquals('errors/consent', RoutesEnum::ConsentError->value);
    }

    public function testAllEnumCases(): void
    {
        $expected = [
            'Linkback' => 'linkback',
            'Logout' => 'logout',
            'LoggedOut' => 'loggedout',
            'ConsentError' => 'errors/consent',
        ];

        foreach (RoutesEnum::cases() as $case) {
            $this->assertSame($expected[$case->name], $case->value);
        }
    }
}
