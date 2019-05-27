<?php
/**
 * This is the handler for logout started from the attribute selection page.
 *
 * @package SimpleSAMLphp
 */

if (!array_key_exists('StateId', $_GET)) {
    throw new SimpleSAML\Error\BadRequest('Missing required StateId query parameter.');
}
$state = SimpleSAML\Auth\State::loadState($_GET['StateId'], 'attributeselection:request');

$state['Responder'] = ['sspmod_attributeselection_Logout', 'postLogout'];

$idp = SimpleSAML\IdP::getByState($state);
$idp->handleLogoutRequest($state, NULL);
assert('FALSE');
