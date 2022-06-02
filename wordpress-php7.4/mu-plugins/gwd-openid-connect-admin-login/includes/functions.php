<?php
/**
 * Global OIDCG functions.
 *
 * @package   GWDAdminLogin
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * Return a single use authentication URL.
 *
 * @return string
 */
function gwdadmlgn_get_authentication_url() {
	return \GWDAdminLogin::instance()->client_wrapper->get_authentication_url();
}

/**
 * Refresh a user claim and update the user metadata.
 *
 * @param WP_User $user             The user object.
 * @param array   $token_response   The token response.
 *
 * @return WP_Error|array
 */
function gwdadmlgn_refresh_user_claim( $user, $token_response ) {
	return \GWDAdminLogin::instance()->client_wrapper->refresh_user_claim( $user, $token_response );
}
