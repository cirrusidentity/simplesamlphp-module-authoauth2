<?php

declare(strict_types=1);

$attributemap = [
    // http://openid.net/specs/openid-connect-core-1_0.html#Claims
    'orcid.id' => 'eduPersonOrcid', // URI with a 16-digit number
    'orcid.sub'  => 'uid',
    'orcid.family_name'   => 'sn',
    'orcid.given_name'   => 'givenName',
    'orcid.name'        => 'cn',
    'orcid.preferred_username' => 'displayName',
    'orcid.email'       => 'mail',
];