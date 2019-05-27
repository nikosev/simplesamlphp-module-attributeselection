<?php

namespace SimpleSAML\Module\attributeselection\Auth\Process;

use SimpleSAML\Auth\State;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module;
use SimpleSAML\Stats;
use SimpleSAML\Utils\HTTP;

/**
 * Attribute Selection Processing filter
 *
 * Filter for requesting the user to select values for specific attributes before attributes are
 * released to the SP.
 *
 * @package SimpleSAMLphp
 */
class AttributeSelection extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * Initialize attribute selection filter
     *
     * Validates and parses the configuration
     *
     * @param array $config   Configuration information
     * @param mixed $reserved For future use
     */
    public function __construct($config, $reserved)
    {
        assert('is_array($config)');
        parent::__construct($config, $reserved);
        if (array_key_exists('selectattributes', $config)) {
            if (!is_array($config['selectattributes'])) {
                throw new Error\Exception(
                    'AttributeSelection: selectattributes must be an array. ' .
                        var_export($config['selectattributes'], true) . ' given.'
                );
            }
            $this->selectAttributes = $config['selectattributes'];
        }

        if (array_key_exists('intro', $config)) {
            $this->intro = $config['intro'];
        }
    }
    /**
     * Helper function to check whether attribute selection is disabled.
     *
     * @param mixed $option  The attributeselection.disable option. Either an array or a boolean.
     * @param string $entityIdD  The entityID of the SP/IdP.
     * @return boolean  TRUE if disabled, FALSE if not.
     */
    private static function checkDisable($option, $entityId)
    {
        if (is_array($option)) {
            return in_array($entityId, $option, TRUE);
        } else {
            return (boolean)$option;
        }
    }
    /**
     * Process a authentication response
     *
     * This function saves the state, and redirects the user to the page where
     * the user can authorize the release of the attributes.
     *
     * @param array &$state The state of the response.
     *
     * @return void
     */
    public function process(&$state)
    {
        assert('is_array($state)');
        assert('array_key_exists("Destination", $state)');
        assert('array_key_exists("entityid", $state["Destination"])');
        assert('array_key_exists("metadata-set", $state["Destination"])');
        assert('array_key_exists("entityid", $state["Source"])');
        assert('array_key_exists("metadata-set", $state["Source"])');
        $spEntityId = $state['Destination']['entityid'];
        $idpEntityId = $state['Source']['entityid'];
        $userAttributes = $state['Attributes'];
        $metadata = MetaDataStorageHandler::getMetadataHandler();
        /**
         * If the attribute selection module is active on a bridge $state['saml:sp:IdP']
         * will contain an entry id for the remote IdP. If not, then the
         * attribute selection module is active on a local IdP and nothing needs to be
         * done.
         */
        if (isset($state['saml:sp:IdP'])) {
            $idpEntityId = $state['saml:sp:IdP'];
            $idpMeta         = $metadata->getMetaData($idpEntityId, 'saml20-idp-remote');
            $state['Source'] = $idpMeta;
        }
        $statsData = ['spEntityID' => $spEntityId];
        // Do not use attribute selection if disabled
        if (isset($state['Source']['attributeselection.disable']) && self::checkDisable($state['Source']['attributeselection.disable'], $spEntityId)) {
            Logger::debug('AttributeSelection: AttributeSelection disabled for entity ' . $spEntityId . ' with IdP ' . $idpEntityId);
            Stats::log('attributeselection:disabled', $statsData);
            return;
        }
        if (isset($state['Destination']['attributeselection.disable']) && self::checkDisable($state['Destination']['attributeselection.disable'], $idpEntityId)) {
            Logger::debug('AttributeSelection: AttributeSelection disabled for entity ' . $spEntityId . ' with IdP ' . $idpEntityId);
            Stats::log('attributeselection:disabled', $statsData);
            return;
        }
        $state['attributeselection:intro'] = $this->intro;
        $state['attributeselection:selectattributes'] = $this->selectAttributes;
        // User interaction nessesary. Throw exception on isPassive request	
        if (isset($state['isPassive']) && $state['isPassive'] === true) {
            Stats::log('attributeselection:nopassive', $statsData);
            throw new Error\NoPassive(
                'Unable to give attribute selection on passive request.'
            );
        }
        // Skip attribute selection when user's attributes 
        $hasValues = false;
        foreach ($state['attributeselection:selectattributes'] as $key => $value) {
            if (!empty($userAttributes[$key])) {
                $hasValues = true;
                break;
            }
        }
        if (!$hasValues) {
            Logger::debug('AttributeSelection: User doesn\'t have the required attributes for attribute selection');
            Stats::log('attributeSelection:empty', $statsData);
            return;
        }
        foreach ($state['attributeselection:selectattributes'] as $key => $value) {
            if (!empty($value['regex'])) {
                foreach ($userAttributes[$key] as $valKey => $valValue) {
                    if (!preg_match($value['regex'], $valValue)) {
                        unset($userAttributes[$key][$valKey]);
                    }
                }
            }
        }
        $state['Attributes'] = $userAttributes;
        // Save state and redirect
        $id  = State::saveState($state, 'attributeselection:request');
        $url = Module::getModuleURL('attributeselection/getattributeselection.php');
        HTTP::redirectTrustedURL($url, ['StateId' => $id]);
    }
}
