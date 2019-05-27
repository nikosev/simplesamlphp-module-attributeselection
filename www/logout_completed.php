<?php

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

/**
 * This is the handler for logout completed from the attribute selection page.
 *
 * @package SimpleSAMLphp
 */

$globalConfig = Configuration::getInstance();
$t = new Template($globalConfig, 'attributeselection:logout_completed.php');
$t->show();
