<?php
/**
 * Attribute selection script
 *
 * This script displays a page to the user, which requests that the user
 * authorizes the release of attributes.
 *
 * @package SimpleSAMLphp
 */
/**
 * Explicit instruct attribute selection page to send no-cache header to browsers to make 
 * sure the users attribute information are not store on client disk.
 * 
 * In an vanilla apache-php installation is the php variables set to:
 *
 * session.cache_limiter = nocache
 *
 * so this is just to make sure.
 */
session_cache_limiter('nocache');
$globalConfig = SimpleSAML\Configuration::getInstance();
SimpleSAML\Logger::info('AttributeSelection - attributeselection: Accessing attribute selection interface');
if (!array_key_exists('StateId', $_REQUEST)) {
    throw new SimpleSAML\Error\BadRequest(
        'Missing required StateId query parameter.'
    );
}
$id = $_REQUEST['StateId'];
$state = SimpleSAML\Auth\State::loadState($id, 'attributeselection:request');
if (array_key_exists('attributeSelection', $_REQUEST)) {
    $userData = json_decode($_REQUEST['attributeSelection'], true);
    foreach ($userData as $name => $value) {
        if (!empty($value)) {
            $currentAttributes = $state['Attributes'][$name];
            SimpleSAML\Logger::debug('AttributeSelection - attributeselection: currentAttributes=' . var_export($currentAttributes, true));
            SimpleSAML\Logger::debug('AttributeSelection - attributeselection: attributeSelection=' . var_export($valuesReseted, true));
            $state['Attributes'][$name] = array_values(array_intersect($currentAttributes, $value));
            SimpleSAML\Logger::debug('AttributeSelection - attributeselection: array_intersect=' . var_export(array_intersect($currentAttributes, $value), true));
        } else {
            unset($state['Attributes'][$name]);
        }
    }
}
if (array_key_exists('core:SP', $state)) {
    $spentityid = $state['core:SP'];
} else if (array_key_exists('saml:sp:State', $state)) {
    $spentityid = $state['saml:sp:State']['core:SP'];
} else {
    $spentityid = 'UNKNOWN';
}
// The user has pressed the yes-button
if (array_key_exists('yes', $_REQUEST)) {
    if (isset($state['Destination']['entityid'])) {
        $statsInfo['spEntityID'] = $state['Destination']['entityid'];
    }
    SimpleSAML\Stats::log('attributeSelection:accept', $statsInfo);
    SimpleSAML\Auth\ProcessingChain::resumeProcessing($state);
}
// Prepare attributes for presentation
$attributes = $state['Attributes'];
$selectattributes = $state['attributeselection:selectattributes'];
$selectattributes = array_keys($selectattributes);
// Remove attributes that are not required to select value(s)
foreach ($attributes as $attrkey => $attrval) {
    if (!in_array($attrkey, $selectattributes)) {
        unset($attributes[$attrkey]);
        continue;
    }
}
$para = array(
    'attributes' => &$attributes
);
// Reorder attributes according to attributepresentation hooks
SimpleSAML\Module::callHooks('attributepresentation', $para);
// Make, populate and layout attribute selection form
$t = new SimpleSAML\XHTML\Template($globalConfig, 'attributeselection:attributeselectionform.php');
$t->data['srcMetadata'] = $state['Source'];
$t->data['dstMetadata'] = $state['Destination'];
$t->data['yesTarget'] = SimpleSAML\Module::getModuleURL('attributeselection/getattributeselection.php');
$t->data['yesData'] = array('StateId' => $id);
$t->data['noTarget'] = SimpleSAML\Module::getModuleURL('attributeselection/noattributeselection.php');
$t->data['noData'] = array('StateId' => $id);
$t->data['attributes'] = $attributes;
// Fetch privacypolicy
if (array_key_exists('privacypolicy', $state['Destination'])) {
    $privacypolicy = $state['Destination']['privacypolicy'];
} elseif (array_key_exists('privacypolicy', $state['Source'])) {
    $privacypolicy = $state['Source']['privacypolicy'];
} else {
    $privacypolicy = false;
}
if ($privacypolicy !== false) {
    $privacypolicy = str_replace(
        '%SPENTITYID%',
        urlencode($spentityid),
        $privacypolicy
    );
}
$t->data['sppp'] = $privacypolicy;
if (array_key_exists('attributeselection:selectattributes', $state)) {
    $t->data['selectattributes'] = $state['attributeselection:selectattributes'];
} else {
    $t->data['selectattributes'] = array();
}

if (array_key_exists('attributeselection:intro', $state)) {
    $t->data['intro'] = $state['attributeselection:intro'];
} else {
    $t->data['intro'] = array();
}
$t->show();
