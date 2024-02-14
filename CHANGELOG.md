# simplesamlphp-module-authoauth2  Changelog

## v4.1.0-beta.2
_Release: 2024-02-13
* Update consent template to twig

## v4.1.0-beta.1
_Release: 2024-01-29
* Test against php 8.3
* Add support for PKCE
* Add support for running authproc filters
* Require league/oauth2-client ^2.7

## v4.0.0
_Release: 2023-08-04
* No changes from v4.0.0-beta.2

## v4.0.0-beta.2
_Release: 2023-08-04
* LinkedIn OIDC Template
* Deprecate old LinkedIn auth method
* Upgrade `kevinrob/guzzle-cache-middleware` to fix Guzzle promise issue
* Allow more versions of `psr/cache` and `symfony/cache`

## v4.0.0-beta.1
_Release: 2023-03-01
* Move `lib` to `src`
* Move `www` to `public`
* Use ssp2 final release
* firebase/php-jwt 6 support

## v4.0.0-alpha.1

_Release: 2022-11-16
* Make OIDC discovery configrable
* SSP 2 compatability
* Improved psalm code quality
* Better source code typing

## v3.3.0

_Release: 2023-06-12
* LinkedIn OIDC Template
* Deprecate old LinkedIn auth method
* Upgrade `kevinrob/guzzle-cache-middleware` to fix Guzzle promise issue

## v3.2.0

_Release: 2022-10-12
* Amazon template
* Apple template
* Orcid auth source
* OIDC auth source now supports `scopes` setting
* Move to phpunit 8
* Increase min php version

## v3.1.0

_Release: 2020-04-09
* Allow additional authenticated urls to be queried for attributes
* Update dependencies

## v3.0.0

_Release: 2019-12-03
* Bumb min SSP version to 1.17
* Better OIDC support
** Logout
** Query .well-known endpoint
* Bitbucket

## v2.1.0

_Release: 2019-01-29
* LinkedIn V2 authsource
* Make attribute conversion method overridable
* Some code style cleanup

## v2.0.0

_Release: 2018-11-29
* Behavior changes from v1
    * User canceling consent sends them to error page rather than throwing USER_ABORT. Behavior is configurable
    * Automatic retry on network errors. Behavior is configurable
* Option tokenFieldsToUserDetailsUrl to indicate which fields from token response should
be query params on user info request
* If user cancels consent, send them to page saying consent must be provided.
* Perform 1 retry on network errors
* Use ssp 1.16.2 as the dependency
* Add php 7.1 and 7.2 to travis builds
* PSR-2 styling
* Add Microsoft authsource
* Allow logging of id_token json
* Template for YahooOIDC, MicrosoftOIDC, LinkedIn and Facebook
* Add support for enabling http request/response logging
* Add general debug information

## v1.0.0

_Released: 2018-08-21

* Generic OAuth2/OIDC module
* Template for Google OIDC
* OIDC attribute map
* Instructions
* Tips for migrating from old/alternate modules 
