<?php

namespace SimpleSAML\Module\attributeselection;

use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

/**
 * Class defining the logout completed handler for the attribute selection page.
 *
 * @package SimpleSAMLphp
 */
class Logout
{

	public static function postLogout(SimpleSAML_IdP $idp, array $state)
	{
		$url = Module::getModuleURL('attributeselection/logout_completed.php');
		HTTP::redirectTrustedURL($url);
	}
}
