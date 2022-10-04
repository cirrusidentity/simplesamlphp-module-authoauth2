<?php

$attributemap = array(
    // Use the global user identifier `oid` from the id_token
    'oidc.oid'  => 'uid',
    'oidc.sub'  => 'urn:oasis:names:tc:SAML:attribute:pairwise-id',
    'oidc.family_name'   => 'sn',
    'oidc.given_name'   => 'givenName',
    'oidc.name'        => 'cn',
    'oidc.preferred_username' => 'displayName',
    'oidc.email'       => 'mail',
);