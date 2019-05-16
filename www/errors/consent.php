<?php

$config = SimpleSAML\Configuration::getInstance();
$t = new SimpleSAML\XHTML\Template($config, 'authoauth2:errors/consent.php');
$t->show();
