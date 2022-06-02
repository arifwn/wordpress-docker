<?php
/**
 * GWD OpenID Connect Administators Login
 *
 * This plugin provides the ability to authenticate users with Identity
 * Providers using the OpenID Connect OAuth2 API with Authorization Code Flow.
 *
 * @package   GWDAdminLogin
 * @category  General
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 * @link      https://github.com/daggerhart
 *
 * @wordpress-plugin
 * Plugin Name:       GWD OpenID Connect Administators Login
 * Plugin URI:        https://groverwebdesign.com
 * Description:       Grover Web Design administrative login via OpenID Connect.
 * Version:           1.0.0
 * Requires at least: 4.9
 * Requires PHP:      7.4
 * Author:            Grover Web Design
 * Author URI:        
 * Text Domain:       gwd-openid-connect-admin-login
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/daggerhart/gwd-admin-login
 */

/*
Notes
  Spec Doc - http://openid.net/specs/openid-connect-basic-1_0-32.html

  Filters
  - gwd-admin-login-alter-request       - 3 args: request array, plugin settings, specific request op
  - gwd-admin-login-settings-fields     - modify the fields provided on the settings page
  - gwd-admin-login-login-button-text   - modify the login button text
  - gwd-admin-login-cookie-redirect-url - modify the redirect url stored as a cookie
  - gwd-admin-login-user-login-test     - (bool) should the user be logged in based on their claim
  - gwd-admin-login-user-creation-test  - (bool) should the user be created based on their claim
  - gwd-admin-login-auth-url            - modify the authentication url
  - gwd-admin-login-alter-user-claim    - modify the user_claim before a new user is created
  - gwd-admin-login-alter-user-data     - modify user data before a new user is created
  - gwd-admin-login-modify-token-response-before-validation - modify the token response before validation
  - gwd-admin-login-modify-id-token-claim-before-validation - modify the token claim before validation

  Actions
  - gwd-admin-login-user-create                     - 2 args: fires when a new user is created by this plugin
  - gwd-admin-login-user-update                     - 1 arg: user ID, fires when user is updated by this plugin
  - gwd-admin-login-update-user-using-current-claim - 2 args: fires every time an existing user logs in and the claims are updated.
  - gwd-admin-login-redirect-user-back              - 2 args: $redirect_url, $user. Allows interruption of redirect during login.
  - gwd-admin-login-user-logged-in                  - 1 arg: $user, fires when user is logged in.
  - gwd-admin-login-cron-daily                      - daily cron action
  - gwd-admin-login-state-not-found                 - the given state does not exist in the database, regardless of its expiration.
  - gwd-admin-login-state-expired                   - the given state exists, but expired before this login attempt.

  Callable actions

  User Meta
  - gwd-admin-login-subject-identity    - the identity of the user provided by the idp
  - gwd-admin-login-last-id-token-claim - the user's most recent id_token claim, decoded
  - gwd-admin-login-last-user-claim     - the user's most recent user_claim
  - gwd-admin-login-last-token-response - the user's most recent token response

  Options
  - gwd_admin_login_settings     - plugin settings
  - gwd-admin-login-valid-states - locally stored generated states
*/


/**
 * GWDAdminLogin class.
 *
 * Defines plugin initialization functionality.
 *
 * @package GWDAdminLogin
 * @category  General
 */
class GWDAdminLogin {

	/**
	 * Singleton instance of self
	 *
	 * @var GWDAdminLogin
	 */
	protected static $_instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin settings.
	 *
	 * @var GWDAdminLogin_Option_Settings
	 */
	private $settings;

	/**
	 * Plugin logs.
	 *
	 * @var GWDAdminLogin_Option_Logger
	 */
	private $logger;

	/**
	 * Openid Connect Generic client
	 *
	 * @var GWDAdminLogin_Client
	 */
	private $client;

	/**
	 * Client wrapper.
	 *
	 * @var GWDAdminLogin_Client_Wrapper
	 */
	public $client_wrapper;

	/**
	 * Setup the plugin
	 *
	 * @param GWDAdminLogin_Option_Settings $settings The settings object.
	 * @param GWDAdminLogin_Option_Logger   $logger   The loggin object.
	 *
	 * @return void
	 */
	public function __construct( GWDAdminLogin_Option_Settings $settings, GWDAdminLogin_Option_Logger $logger ) {
		$this->settings = $settings;
		$this->logger = $logger;
		self::$_instance = $this;
	}

	/**
	 * WordPress Hook 'init'.
	 *
	 * @return void
	 */
	public function init() {

		$redirect_uri = admin_url( 'admin-ajax.php?action=gwd-admin-oid-connect-authorize' );

		if ( $this->settings->alternate_redirect_uri ) {
			$redirect_uri = site_url( '/gwd-admin-oid-connect-authorize' );
		}

		$state_time_limit = 180;
		if ( $this->settings->state_time_limit ) {
			$state_time_limit = intval( $this->settings->state_time_limit );
		}

		$this->client = new GWDAdminLogin_Client(
			$this->settings->client_id,
			$this->settings->client_secret,
			$this->settings->scope,
			$this->settings->endpoint_login,
			$this->settings->endpoint_userinfo,
			$this->settings->endpoint_token,
			$redirect_uri,
			$this->settings->acr_values,
			$state_time_limit,
			$this->logger
		);

		$this->client_wrapper = GWDAdminLogin_Client_Wrapper::register( $this->client, $this->settings, $this->logger );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		GWDAdminLogin_Login_Form::register( $this->settings, $this->client_wrapper );

		// Add a shortcode to get the auth URL.
		add_shortcode( 'openid_connect_generic_auth_url', array( $this->client_wrapper, 'get_authentication_url' ) );

		// Add actions to our scheduled cron jobs.
		add_action( 'gwd-admin-login-cron-daily', array( $this, 'cron_states_garbage_collection' ) );

		$this->upgrade();

		if ( is_admin() ) {
			GWDAdminLogin_Settings_Page::register( $this->settings, $this->logger );
		}
	}

	/**
	 * Check if privacy enforcement is enabled, and redirect users that aren't
	 * logged in.
	 *
	 * @return void
	 */
	public function enforce_privacy_redirect() {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			// The client endpoint relies on the wp admind ajax endpoint.
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! isset( $_GET['action'] ) || 'gwd-admin-oid-connect-authorize' != $_GET['action'] ) {
				auth_redirect();
			}
		}
	}

	/**
	 * Enforce privacy settings for rss feeds.
	 *
	 * @param string $content The content.
	 *
	 * @return mixed
	 */
	public function enforce_privacy_feeds( $content ) {
		if ( $this->settings->enforce_privacy && ! is_user_logged_in() ) {
			$content = __( 'Private site', 'gwd-admin-login' );
		}
		return $content;
	}

	/**
	 * Handle plugin upgrades
	 *
	 * @return void
	 */
	public function upgrade() {
		$last_version = get_option( 'gwd-admin-login-plugin-version', 0 );
		$settings = $this->settings;

		if ( version_compare( self::VERSION, $last_version, '>' ) ) {
			// An upgrade is required.
			self::setup_cron_jobs();

			// @todo move this to another file for upgrade scripts
			if ( isset( $settings->ep_login ) ) {
				$settings->endpoint_login = $settings->ep_login;
				$settings->endpoint_token = $settings->ep_token;
				$settings->endpoint_userinfo = $settings->ep_userinfo;

				unset( $settings->ep_login, $settings->ep_token, $settings->ep_userinfo );
				$settings->save();
			}

			// Update the stored version number.
			update_option( 'gwd-admin-login-plugin-version', self::VERSION );
		}
	}

	/**
	 * Expire state transients by attempting to access them and allowing the
	 * transient's own mechanisms to delete any that have expired.
	 *
	 * @return void
	 */
	public function cron_states_garbage_collection() {
		global $wpdb;
		$states = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_gwd-admin-login-state--%'" );

		if ( ! empty( $states ) ) {
			foreach ( $states as $state ) {
				$transient = str_replace( '_transient_', '', $state );
				get_transient( $transient );
			}
		}
	}

	/**
	 * Ensure cron jobs are added to the schedule.
	 *
	 * @return void
	 */
	public static function setup_cron_jobs() {
		if ( ! wp_next_scheduled( 'gwd-admin-login-cron-daily' ) ) {
			wp_schedule_event( time(), 'daily', 'gwd-admin-login-cron-daily' );
		}
	}

	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activation() {
		self::setup_cron_jobs();
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook( 'gwd-admin-login-cron-daily' );
	}

	/**
	 * Simple autoloader.
	 *
	 * @param string $class The class name.
	 *
	 * @return void
	 */
	public static function autoload( $class ) {
		$prefix = 'GWDAdminLogin_';

		if ( stripos( $class, $prefix ) !== 0 ) {
			return;
		}

		$filename = $class . '.php';

		// Internal files are all lowercase and use dashes in filenames.
		if ( false === strpos( $filename, '\\' ) ) {
			$filename = strtolower( str_replace( '_', '-', $filename ) );
		} else {
			$filename  = str_replace( '\\', DIRECTORY_SEPARATOR, $filename );
		}

		$filepath = dirname( __FILE__ ) . '/includes/' . $filename;

		if ( file_exists( $filepath ) ) {
			require_once $filepath;
		}
	}

	/**
	 * Instantiate the plugin and hook into WordPress.
	 *
	 * @return void
	 */
	public static function bootstrap() {
		/**
		 * This is a documented valid call for spl_autoload_register.
		 *
		 * @link https://www.php.net/manual/en/function.spl-autoload-register.php#71155
		 */
		spl_autoload_register( array( 'GWDAdminLogin', 'autoload' ) );

		// set default GWD OpenID Connect settings

		$base_realm = getenv( 'OPENID_CONNECT_REALM', true );
		$endpoint_login = '';
		$endpoint_userinfo = '';
		$endpoint_token = '';
		$endpoint_end_session = '';

		if ( $base_realm ) {
			$endpoint_login = $base_realm.'/protocol/openid-connect/auth';
			$endpoint_userinfo = $base_realm.'/protocol/openid-connect/userinfo';
			$endpoint_token = $base_realm.'/protocol/openid-connect/token';
			$endpoint_end_session = $base_realm.'/protocol/openid-connect/logout';
		}

		$settings = new GWDAdminLogin_Option_Settings(
			'gwd_admin_login_settings',
			// Default settings values.
			array(
				// OAuth client settings.
				'login_type'           => defined( 'OIDC_LOGIN_TYPE' ) ? OIDC_LOGIN_TYPE : 'button',
				'client_id'            => getenv( 'OPENID_CONNECT_CLIENT_ID', true ) ? getenv( 'OPENID_CONNECT_CLIENT_ID', true ) : '',
				'client_secret'        => getenv( 'OPENID_CONNECT_CLIENT_SECRET', true ) ? getenv( 'OPENID_CONNECT_CLIENT_SECRET', true ) : '',
				'scope'                => defined( 'OIDC_CLIENT_SCOPE' ) ? OIDC_CLIENT_SCOPE : 'openid email profile roles',
				'endpoint_login'       => $endpoint_login,
				'endpoint_userinfo'    => $endpoint_userinfo,
				'endpoint_token'       => $endpoint_token,
				'endpoint_end_session' => $endpoint_end_session,
				'acr_values'           => defined( 'OIDC_ACR_VALUES' ) ? OIDC_ACR_VALUES : '',

				// Non-standard settings.
				'no_sslverify'    => 0,
				'http_request_timeout' => 5,
				'identity_key'    => 'preferred_username',
				'nickname_key'    => 'preferred_username',
				'email_format'       => '{email}',
				'displayname_format' => '',
				'identify_with_username' => false,

				// Plugin settings.
				'enforce_privacy' => defined( 'OIDC_ENFORCE_PRIVACY' ) ? intval( OIDC_ENFORCE_PRIVACY ) : 0,
				'alternate_redirect_uri' => 0,
				'token_refresh_enable' => 1,
				'link_existing_users' => defined( 'OIDC_LINK_EXISTING_USERS' ) ? intval( OIDC_LINK_EXISTING_USERS ) : 1,
				'create_if_does_not_exist' => defined( 'OIDC_CREATE_IF_DOES_NOT_EXIST' ) ? intval( OIDC_CREATE_IF_DOES_NOT_EXIST ) : 1,
				'redirect_user_back' => defined( 'OIDC_REDIRECT_USER_BACK' ) ? intval( OIDC_REDIRECT_USER_BACK ) : 0,
				'redirect_on_logout' => defined( 'OIDC_REDIRECT_ON_LOGOUT' ) ? intval( OIDC_REDIRECT_ON_LOGOUT ) : 1,
				'enable_logging'  => 0,
				'log_limit'       => 1000,
			)
		);

		$logger = new GWDAdminLogin_Option_Logger( 'gwd-admin-login-logs', 'error', $settings->enable_logging, $settings->log_limit );

		$plugin = new self( $settings, $logger );

		add_action( 'init', array( $plugin, 'init' ) );

		// Privacy hooks.
		add_action( 'template_redirect', array( $plugin, 'enforce_privacy_redirect' ), 0 );
		add_filter( 'the_content_feed', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'the_excerpt_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
		add_filter( 'comment_text_rss', array( $plugin, 'enforce_privacy_feeds' ), 999 );
	}

	/**
	 * Create (if needed) and return a singleton of self.
	 *
	 * @return GWDAdminLogin
	 */
	public static function instance() {
		if ( null === self::$_instance ) {
			self::bootstrap();
		}
		return self::$_instance;
	}
}

GWDAdminLogin::instance();

register_activation_hook( __FILE__, array( 'GWDAdminLogin', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'GWDAdminLogin', 'deactivation' ) );

// Provide publicly accessible plugin helper functions.
require_once( 'includes/functions.php' );
