<?php

declare(strict_types=1);

$config = [];
// require a vanilla SSP config
require "ssp2_3-config.php";
$config['module.enable']['authoauth2'] = true;
$config['baseurlpath'] = '/';
$config['logging.handler'] = 'stderr';
$config['logging.level'] = SimpleSAML\Logger::DEBUG;
