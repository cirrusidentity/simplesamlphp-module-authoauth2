<?php

/**
 * Oauth2 module routes file.
 */

declare(strict_types=1);

use SimpleSAML\Module\authoauth2\Codebooks\RoutesEnum;
use SimpleSAML\Module\authoauth2\Codebooks\LegacyRoutesEnum;
use SimpleSAML\Module\authoauth2\Controller\Oauth2Controller;
use SimpleSAML\Module\authoauth2\Controller\OIDCLogoutController;
use SimpleSAML\Module\authoauth2\Controller\ErrorController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/** @psalm-suppress InvalidArgument */
return function (RoutingConfigurator $routes): void {

    // We support both the new and the legacy routes
    // New Routes
    $routes->add(RoutesEnum::Linkback->name, RoutesEnum::Linkback->value)
        ->controller([Oauth2Controller::class, 'linkback']);
    $routes->add(RoutesEnum::Logout->name, RoutesEnum::Logout->value)
        ->controller([OIDCLogoutController::class, 'logout']);
    $routes->add(RoutesEnum::LoggedOut->name, RoutesEnum::LoggedOut->value)
        ->controller([OIDCLogoutController::class, 'loggedout']);
    $routes->add(RoutesEnum::ConsentError->name, RoutesEnum::ConsentError->value)
        ->controller([ErrorController::class, 'consent']);

    // Legacy Routes
    $routes->add(LegacyRoutesEnum::LegacyLinkback->name, LegacyRoutesEnum::LegacyLinkback->value)
        ->controller([Oauth2Controller::class, 'linkback']);
    $routes->add(LegacyRoutesEnum::LegacyLogout->name, LegacyRoutesEnum::LegacyLogout->value)
        ->controller([OIDCLogoutController::class, 'logout']);
    $routes->add(LegacyRoutesEnum::LegacyLoggedOut->name, LegacyRoutesEnum::LegacyLoggedOut->value)
        ->controller([OIDCLogoutController::class, 'loggedout']);
    $routes->add(LegacyRoutesEnum::LegacyConsentError->name, LegacyRoutesEnum::LegacyConsentError->value)
        ->controller([ErrorController::class, 'consent']);
};
