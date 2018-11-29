<?php
/**
 * Attribute Selection Processing filter
 *
 * Filter for requesting the user to select values for specific attributes before attributes are
 * released to the SP.
 *
 * @package SimpleSAMLphp
 */
class sspmod_attributeselection_Auth_Process_AttributeSelection extends SimpleSAML_Auth_ProcessingFilter {
    /**
     * Initialize attribute selection filter
     *
     * Validates and parses the configuration
     *
     * @param array $config   Configuration information
     * @param mixed $reserved For future use
     */
    public function __construct($config, $reserved) {
        assert('is_array($config)');
        parent::__construct($config, $reserved);
        if (array_key_exists('selectattributes', $config)) {
            if (!is_array($config['selectattributes'])) {
                throw new SimpleSAML_Error_Exception(
                    'AttributeSelection: selectattributes must be an array. ' .
                    var_export($config['selectattributes'], true) . ' given.'
                );
            }
            $this->_selectattributes = $config['selectattributes'];
        }
    }
    /**
     * Helper function to check whether attribute selection is disabled.
     *
     * @param mixed $option  The attributeselection.disable option. Either an array or a boolean.
     * @param string $entityIdD  The entityID of the SP/IdP.
     * @return boolean  TRUE if disabled, FALSE if not.
     */
    private static function checkDisable($option, $entityId) {
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
    public function process(&$state) {
        assert('is_array($state)');
        assert('array_key_exists("Destination", $state)');
        assert('array_key_exists("entityid", $state["Destination"])');
        assert('array_key_exists("metadata-set", $state["Destination"])');		
        assert('array_key_exists("entityid", $state["Source"])');
        assert('array_key_exists("metadata-set", $state["Source"])');
        $spEntityId = $state['Destination']['entityid'];
        $idpEntityId = $state['Source']['entityid'];
        $userAttributes = $state['Attributes'];
        $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        /**
         * If the attribute selection module is active on a bridge $state['saml:sp:IdP']
         * will contain an entry id for the remote IdP. If not, then the
         * attribute selection module is active on a local IdP and nothing needs to be
         * done.
         */
        if (isset($state['saml:sp:IdP'])) {
            $idpEntityId = $state['saml:sp:IdP'];
            $idpmeta         = $metadata->getMetaData($idpEntityId, 'saml20-idp-remote');
            $state['Source'] = $idpmeta;
        }
        $statsData = array('spEntityID' => $spEntityId);
        // Do not use attribute selection if disabled
        if (isset($state['Source']['attributeselection.disable']) && self::checkDisable($state['Source']['attributeselection.disable'], $spEntityId)) {
            SimpleSAML_Logger::debug('AttributeSelection: AttributeSelection disabled for entity ' . $spEntityId . ' with IdP ' . $idpEntityId);
            SimpleSAML_Stats::log('attributeselection:disabled', $statsData);
            return;
        }
        if (isset($state['Destination']['attributeselection.disable']) && self::checkDisable($state['Destination']['attributeselection.disable'], $idpEntityId)) {
            SimpleSAML_Logger::debug('AttributeSelection: AttributeSelection disabled for entity ' . $spEntityId . ' with IdP ' . $idpEntityId);
            SimpleSAML_Stats::log('attributeselection:disabled', $statsData);
            return;
        }
        $state['attributeselection:selectattributes'] = $this->_selectattributes;
        // User interaction nessesary. Throw exception on isPassive request	
        if (isset($state['isPassive']) && $state['isPassive'] === true) {
            SimpleSAML_Stats::log('attributeselection:nopassive', $statsData);
            throw new SimpleSAML_Error_NoPassive(
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
            SimpleSAML_Logger::debug('AttributeSelection: User doesn\'t have the required attributes for attribute selection');
            SimpleSAML_Stats::log('attributeSelection:empty', $statsData);
            return;
        }
        // Save state and redirect
        $id  = SimpleSAML_Auth_State::saveState($state, 'attributeselection:request');
        $url = SimpleSAML_Module::getModuleURL('attributeselection/getattributeselection.php');
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
    }
    /**
     * Generate a unique identifier of the user
     * 
     * @param string $userid The user id
     * @param string $source The source id
     *
     * @return string SHA1 of the user id, source id and salt 
     */
    public static function getHashedUserID($userid, $source) {
        return hash('sha1', $userid . '|' . SimpleSAML\Utils\Config::getSecretSalt() . '|' . $source);
    }
    /**
     * Generate a unique targeted identifier
     *
     * @param string $userid      The user id
     * @param string $source      The source id
     * @param string $destination The destination id
     *
     * @return string SHA1 of the user id, source id, destination id and salt 
     */
    public static function getTargetedID($userid, $source, $destination) {
        return hash('sha1', $userid . '|' . SimpleSAML\Utils\Config::getSecretSalt() . '|' . $source . '|' . $destination);
    }
    /**
     * Generate unique identitier for attributes
     *
     * Create a hash value for the attributes that changes when attributes are
     * added or removed. If the attribute values are included in the hash, the 
     * hash will change if the values change.
     *
     * @param string $attributes    The attributes
     * @param bool   $includeValues Whether or not to include the attribute
     *                              value in the generation of the hash.
     *
     * @return string SHA1 of the user id, source id, destination id and salt 
     */
    public static function getAttributeHash($attributes, $includeValues = false) {
        $hashBase = null;	
        if ($includeValues) {
            ksort($attributes);
            $hashBase = serialize($attributes);
        } else {
            $names = array_keys($attributes);
            sort($names);
            $hashBase = implode('|', $names);
        }
        return hash('sha1', $hashBase);
    }
}
