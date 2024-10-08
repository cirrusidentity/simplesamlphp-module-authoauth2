<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Codebooks;

enum RoutesEnum: string
{
    case Linkback = 'linkback';
    case Logout = 'logout';
    case LoggedOut = 'loggedout';
    case ConsentError = 'errors/consent';
}
