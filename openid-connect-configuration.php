<?php

require_once('./vendor/autoload.php');

const CONFIGURATION_PATH = '.well-known/openid-configuration';
$configurationPathLength = strlen(CONFIGURATION_PATH);

function usage() {
    echo "Usage: $argv[0] <provider url>\n";
    exit;
}

function base64url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
}

function process_jwks($url) {
    $jwksData = file_get_contents($url);
    if (!$jwksData) {
        error_log("Failed to get jwks data from $url");
        return;
    }
    $jwks = json_decode($jwksData, true);
    if (!$jwks) {
        error_log("Failed to json decode jwks data: " . $jwksData);
        return;
    }
    $keys = [];
    foreach ($jwks['keys'] as $key) {
        $kid = $key['kid'];
        if (array_key_exists('x5c', $key)) {
            $x5c = $key['x5c'];
            $keys[$kid] = "-----BEGIN CERTIFICATE-----\n" . $x5c[0] . "\n-----END CERTIFICATE-----";
        } else if ($key['kty'] === 'RSA') {
            $e = base64url_decode($key['e']);
            $n = base64url_decode($key['n']);
            $keys[$kid] = \RobRichards\XMLSecLibs\XMLSecurityKey::convertRSA($n, $e);
        } else {
            error_log("Failed to load key data for key id: " . $kid);
        }
    }
    return $keys;
}

if (sizeof($argv) < 2) {
    usage();
}

if ($argv[1] === '-h' || $argv[1] === '--help') {
    usage();
}

$url = $argv[1];
if (substr($url, -$configurationPathLength) !== CONFIGURATION_PATH) {
    if (substr($url, -1) !== '/') {
        $url .= '/';
    }
    $url .= CONFIGURATION_PATH;
}

$data = file_get_contents($url);
if (!$data) {
    error_log("Failed to get configuration data from $url");
    exit;
}
$conf = json_decode($data);
if (!$conf) {
    error_log("Failed to json decode configuration data: " . $data);
    exit;
}

echo <<<SNIP1
    //OpenID Connect provider $conf->issuer
    '$conf->issuer' => array(
        'authoauth2:OpenIDConnect',

        // Scopes to request, should include openid
        'scopes' => ['openid', 'profile'],

        // Configured client id and secret
        'clientId' => '<client id>',
        'clientSecret' => '<client secret>',

        'scopeSeparator' => ' ',
        'issuer' => '$conf->issuer',
        'urlAuthorize' => '$conf->authorization_endpoint',
        'urlAccessToken' => '$conf->token_endpoint',
        'urlResourceOwnerDetails' => '$conf->userinfo_endpoint',

SNIP1;
if (isset($conf->end_session_endpoint)) {
    echo "        'urlEndSession' => '$conf->end_session_endpoint',\n";
}
$keys = process_jwks($conf->jwks_uri);
if ($keys) {
    echo "        'keys' => " . var_export($keys, true);
} else {
    error_log("Couldn't get or parse jwks data, generated config without id token verification");
}
echo <<<SNIP2

    ),

SNIP2;
