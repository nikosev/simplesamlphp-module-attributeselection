<?php

use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Module;
use SimpleSAML\Stats;
use SimpleSAML\XHTML\Template;

/**
 * This is the page the user lands on when choosing "no" in the attribute selection form.
 *
 * @package SimpleSAMLphp
 */
if (!array_key_exists('StateId', $_REQUEST)) {
    throw new BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'attributeselection:request');

$resumeFrom = Module::getModuleURL(
    'attributeselection/getattributeselection.php',
    ['StateId' => $id]
);

$logoutLink = Module::getModuleURL(
    'attributeselection/logout.php',
    ['StateId' => $id]
);

$aboutService = null;

$statsInfo = [];
if (isset($state['Destination']['entityid'])) {
    $statsInfo['spEntityID'] = $state['Destination']['entityid'];
}
Stats::log('attributeselection:reject', $statsInfo);

$globalConfig = Configuration::getInstance();

$t = new Template($globalConfig, 'attributeselection:noattributeselection.php');
$t->data['dstMetadata'] = $state['Destination'];
$t->data['resumeFrom'] = $resumeFrom;
$t->data['aboutService'] = $aboutService;
$t->data['logoutLink'] = $logoutLink;
$t->show();
