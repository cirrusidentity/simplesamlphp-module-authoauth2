<?php

$config = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'authoauth2:errors/consent.php');
$t->show();
