<?php
/**
 * This is the handler for logout completed from the attribute selection page.
 *
 * @package SimpleSAMLphp
 */

$globalConfig = SimpleSAML\Configuration::getInstance();
$t = new SimpleSAML\XHTML\Template($globalConfig, 'attributeselection:logout_completed.php');
$t->show();
