<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Codebooks;

enum LegacyRoutesEnum: string
{
    case LegacyLinkback = 'linkback.php';
    case LegacyLogout = 'logout.php';
    case LegacyLoggedOut = 'loggedout.php';
    case LegacyConsentError = 'errors/consent.php';
}
