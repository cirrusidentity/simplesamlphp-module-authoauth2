<?php

/**
 * Oauth2 module routes file.
 */

declare(strict_types=1);

use SimpleSAML\Module\authoauth2\Codebooks\RoutesEnum;
use SimpleSAML\Module\authoauth2\Controller\Oauth2Controller;
use SimpleSAML\Module\authoauth2\Controller\OIDCLogoutController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/** @psalm-suppress InvalidArgument */
return function (RoutingConfigurator $routes): void {

    $routes->add(RoutesEnum::Callback->name, RoutesEnum::Callback->value)
        ->controller([Oauth2Controller::class, 'callback']);
    $routes->add(RoutesEnum::Logout->name, RoutesEnum::Logout->value)
        ->controller([OIDCLogoutController::class, 'logout']);
//    $routes->add(RoutesEnum::LoggedOut->name, RoutesEnum::LoggedOut->value)
//        ->controller([AccessTokenController::class, 'token']);
};
