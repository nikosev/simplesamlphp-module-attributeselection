<?php
/**
 * This is the page the user lands on when choosing "no" in the attribute selection form.
 *
 * @package SimpleSAMLphp
 */
if (!array_key_exists('StateId', $_REQUEST)) {
    throw new SimpleSAML\Error\BadRequest(
        'Missing required StateId query parameter.'
    );
}

$id = $_REQUEST['StateId'];
$state = SimpleSAML\Auth\State::loadState($id, 'attributeselection:request');

$resumeFrom = SimpleSAML\Module::getModuleURL(
    'attributeselection/getattributeselection.php',
    ['StateId' => $id]
);

$logoutLink = SimpleSAML\Module::getModuleURL(
    'attributeselection/logout.php',
    ['StateId' => $id]
);

$aboutService = null;

$statsInfo = [];
if (isset($state['Destination']['entityid'])) {
    $statsInfo['spEntityID'] = $state['Destination']['entityid'];
}
SimpleSAML\Stats::log('attributeselection:reject', $statsInfo);

$globalConfig = SimpleSAML\Configuration::getInstance();

$t = new SimpleSAML\XHTML\Template($globalConfig, 'attributeselection:noattributeselection.php');
$t->data['dstMetadata'] = $state['Destination'];
$t->data['resumeFrom'] = $resumeFrom;
$t->data['aboutService'] = $aboutService;
$t->data['logoutLink'] = $logoutLink;
$t->show();
