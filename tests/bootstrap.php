<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$projectRoot = dirname(__DIR__);
putenv('SIMPLESAMLPHP_CONFIG_DIR=' . __DIR__ . '/config');

// Symlink module into ssp vendor lib so that templates and urls can resolve correctly
$linkPath = $projectRoot . '/vendor/simplesamlphp/simplesamlphp/modules/authoauth2';
if (file_exists($linkPath) === false) {
    print "Linking '$linkPath' to '$projectRoot'\n";
    symlink($projectRoot, $linkPath);
}
