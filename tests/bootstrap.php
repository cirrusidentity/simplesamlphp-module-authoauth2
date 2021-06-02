<?php

$projectRoot = dirname(__DIR__);
require_once($projectRoot . '/vendor/autoload.php');

new \SimpleSAML\Error\ConfigurationError('Load to prevent some exception class resolution issues with aspectMock');

$aopCacheDir = sys_get_temp_dir() . '/aop-cache/' . (new DateTime())->getTimestamp();
if (!file_exists($aopCacheDir)) {
    mkdir($aopCacheDir, 0777, true);
    echo "Using aop cache $aopCacheDir";
}

$sspInstall = $projectRoot . '/vendor/simplesamlphp/simplesamlphp/';

// Enable AspectMock. This allows us to stub/double out static methods.
$kernel = \AspectMock\Kernel::getInstance();
$kernel->init([
    'debug' => true,
    'cacheDir'     => $aopCacheDir, // Cache directory
    // Any class that we want to stub/mock needs to be in included paths
    'includePaths' => [
        $projectRoot . '/lib',
       // Need to explicitly list any classes we want to mock.
        // Mocking all classes results in weird syntax errors in config files or missing dependencies
       $sspInstall . '/lib/'
    ]
]);


// Symlink module into ssp vendor lib so that templates and urls can resolve correctly
$linkPath = $projectRoot . '/vendor/simplesamlphp/simplesamlphp/modules/authoauth2';
if (file_exists($linkPath) === false) {
    print "Linking '$linkPath' to '$projectRoot'\n";
    symlink($projectRoot, $linkPath);
}
