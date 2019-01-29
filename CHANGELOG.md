# simplesamlphp-module-authoauth2  Changelog

## Unreleased

_Release: 2019-?
* Nothing yet!

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