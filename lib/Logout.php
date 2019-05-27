<?php

namespace SimpleSAML\Module\attributeselection;

/**
 * Class defining the logout completed handler for the attribute selection page.
 *
 * @package SimpleSAMLphp
 */
class Logout {

	public static function postLogout(SimpleSAML_IdP $idp, array $state) {
		$url = SimpleSAML_Module::getModuleURL('attributeselection/logout_completed.php');
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
	}

}
