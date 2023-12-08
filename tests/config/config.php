<?php

$config = [];
// require a vanilla SSP config
require "ssp2_0-config.php";
$config['module.enable']['authoauth2'] = true;
$config['baseurlpath'] = '/';
$config['logging.handler'] = 'stderr';
$config['logging.level'] = SimpleSAML\Logger::DEBUG;
