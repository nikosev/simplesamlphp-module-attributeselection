<?php

use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Auth\State;
use SimpleSAML\Stats;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;

/**
 * Attribute selection script
 *
 * This script displays a page to the user, which requests that the user
 * authorizes the release of attributes.
 *
 * @package \SimpleSAMLphp
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
$globalConfig = Configuration::getInstance();
Logger::info('AttributeSelection - attributeselection: Accessing attribute selection interface');
if (!array_key_exists('StateId', $_REQUEST)) {
    throw new BadRequest(
        'Missing required StateId query parameter.'
    );
}
$id = $_REQUEST['StateId'];
$state = State::loadState($id, 'attributeselection:request');
if (array_key_exists('attributeSelection', $_REQUEST)) {
    $userData = json_decode($_REQUEST['attributeSelection'], true);
    foreach ($userData as $name => $value) {
        if (!empty($value)) {
            $currentAttributes = $state['Attributes'][$name];
            Logger::debug('AttributeSelection - attributeselection: currentAttributes=' . var_export($currentAttributes, true));
            Logger::debug('AttributeSelection - attributeselection: attributeSelection=' . var_export($value, true));
            $state['Attributes'][$name] = array_values(array_intersect($currentAttributes, $value));
            Logger::debug('AttributeSelection - attributeselection: array_intersect=' . var_export(array_intersect($currentAttributes, $value), true));
        } else {
            unset($state['Attributes'][$name]);
        }
    }
}
if (array_key_exists('core:SP', $state)) {
    $spEntityId = $state['core:SP'];
} elseif (array_key_exists('saml:sp:State', $state)) {
    $spEntityId = $state['saml:sp:State']['core:SP'];
} else {
    $spEntityId = 'UNKNOWN';
}
// The user has pressed the yes-button
if (array_key_exists('yes', $_REQUEST)) {
    if (isset($state['Destination']['entityid'])) {
        $statsInfo['spEntityID'] = $state['Destination']['entityid'];
    }
    Stats::log('attributeSelection:accept', $statsInfo);
    ProcessingChain::resumeProcessing($state);
}
// Prepare attributes for presentation
$attributes = $state['Attributes'];
$selectAttributes = $state['attributeselection:selectattributes'];
$selectAttributes = array_keys($selectAttributes);
// Remove attributes that are not required to select value(s)
foreach ($attributes as $attrKey => $attrVal) {
    if (!in_array($attrKey, $selectAttributes)) {
        unset($attributes[$attrKey]);
        continue;
    }
}
$para = [
    'attributes' => &$attributes
];
// Reorder attributes according to attributepresentation hooks
Module::callHooks('attributepresentation', $para);
// Make, populate and layout attribute selection form
$t = new Template($globalConfig, 'attributeselection:attributeselectionform.php');
$t->data['srcMetadata'] = $state['Source'];
$t->data['dstMetadata'] = $state['Destination'];
$t->data['yesTarget'] = Module::getModuleURL('attributeselection/getattributeselection.php');
$t->data['yesData'] = ['StateId' => $id];
$t->data['noTarget'] = Module::getModuleURL('attributeselection/noattributeselection.php');
$t->data['noData'] = ['StateId' => $id];
$t->data['attributes'] = $attributes;
// Fetch privacypolicy
if (array_key_exists('privacypolicy', $state['Destination'])) {
    $privacyPolicy = $state['Destination']['privacypolicy'];
} elseif (array_key_exists('privacypolicy', $state['Source'])) {
    $privacyPolicy = $state['Source']['privacypolicy'];
} else {
    $privacyPolicy = false;
}
if ($privacyPolicy !== false) {
    $privacyPolicy = str_replace(
        '%SPENTITYID%',
        urlencode($spEntityId),
        $privacyPolicy
    );
}
$t->data['sppp'] = $privacyPolicy;
if (array_key_exists('attributeselection:selectattributes', $state)) {
    $t->data['selectattributes'] = $state['attributeselection:selectattributes'];
} else {
    $t->data['selectattributes'] = [];
}

if (array_key_exists('attributeselection:intro', $state)) {
    $t->data['intro'] = $state['attributeselection:intro'];
} else {
    $t->data['intro'] = [];
}
$t->show();
