<?php
/**
 * Login form and login button handlong class.
 *
 * @package   GWDAdminLogin
 * @category  Login
 * @author    Jonathan Daggerhart <jonathan@daggerhart.com>
 * @copyright 2015-2020 daggerhart
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */

/**
 * GWDAdminLogin_Login_Form class.
 *
 * Login form and login button handlong.
 *
 * @package GWDAdminLogin
 * @category  Login
 */
class GWDAdminLogin_Login_Form {

	/**
	 * Plugin settings object.
	 *
	 * @var GWDAdminLogin_Option_Settings
	 */
	private $settings;

	/**
	 * Plugin client wrapper instance.
	 *
	 * @var GWDAdminLogin_Client_Wrapper
	 */
	private $client_wrapper;

	/**
	 * The class constructor.
	 *
	 * @param GWDAdminLogin_Option_Settings $settings       A plugin settings object instance.
	 * @param GWDAdminLogin_Client_Wrapper  $client_wrapper A plugin client wrapper object instance.
	 */
	public function __construct( $settings, $client_wrapper ) {
		$this->settings = $settings;
		$this->client_wrapper = $client_wrapper;
	}

	/**
	 * Create an instance of the GWDAdminLogin_Login_Form class.
	 *
	 * @param GWDAdminLogin_Option_Settings $settings       A plugin settings object instance.
	 * @param GWDAdminLogin_Client_Wrapper  $client_wrapper A plugin client wrapper object instance.
	 *
	 * @return void
	 */
	public static function register( $settings, $client_wrapper ) {
		$login_form = new self( $settings, $client_wrapper );

		// Alter the login form as dictated by settings.
		add_filter( 'login_message', array( $login_form, 'handle_login_page' ), 99 );
		add_filter( 'login_site_html_link', array( $login_form, 'make_gwd_login_button' ), 99 );

		// Add a shortcode for the login button.
		add_shortcode( 'gwd_admin_login_button', array( $login_form, 'make_login_button' ) );
		

		$login_form->handle_redirect_login_type_auto();
	}

	/**
	 * Auto Login redirect.
	 *
	 * @return void
	 */
	public function handle_redirect_login_type_auto() {

		if ( 'wp-login.php' == $GLOBALS['pagenow']
			&& ( 'auto' == $this->settings->login_type || ! empty( $_GET['force_redirect'] ) )
			// Don't send users to the IDP on logout or post password protected authentication.
			&& ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], array( 'logout', 'postpass' ) ) )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP Login Form doesn't have a nonce.
			&& ! isset( $_POST['wp-submit'] ) ) {
			if ( ! isset( $_GET['login-error'] ) ) {
				wp_redirect( $this->client_wrapper->get_authentication_url() );
				exit;
			} else {
				add_action( 'login_footer', array( $this, 'remove_login_form' ), 99 );
			}
		}

	}

	/**
	 * Implements filter login_message.
	 *
	 * @param string $message The text message to display on the login page.
	 *
	 * @return string
	 */
	public function handle_login_page( $message ) {

		if ( isset( $_GET['login-error'] ) ) {
			$error_message = ! empty( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : 'Unknown error.';
			$message .= $this->make_error_output( sanitize_text_field( wp_unslash( $_GET['login-error'] ) ), $error_message );
		}

		// Login button is appended to existing messages in case of error.
		// $message .= $this->make_login_button();

		return $message;
	}

	/**
	 * Display an error message to the user.
	 *
	 * @param string $error_code    The error code.
	 * @param string $error_message The error message test.
	 *
	 * @return string
	 */
	public function make_error_output( $error_code, $error_message ) {

		ob_start();
		?>
		<div id="login_error"><?php // translators: %1$s is the error code from the IDP. ?>
			<strong><?php printf( esc_html__( 'ERROR (%1$s)', 'gwd-admin-login' ), esc_html( $error_code ) ); ?>: </strong>
			<?php print esc_html( $error_message ); ?>
		</div>
		<?php
		return wp_kses_post( ob_get_clean() );
	}

	/**
	 * Create a login button (link).
	 *
	 * @param array $atts Array of optional attributes to override login buton
	 * functionality when used by shortcode.
	 *
	 * @return string
	 */
	public function make_login_button( $atts = array() ) {

		$atts = shortcode_atts(
			array(
				'button_text' => __( 'GWD Login', 'gwd-admin-login' ),
			),
			$atts,
			'gwd_admin_login_button'
		);

		$text = apply_filters( 'gwd-admin-login-login-button-text', $atts['button_text'] );
		$text = esc_html( $text );

		$href = $this->client_wrapper->get_authentication_url( $atts );
		$href = esc_url_raw( $href );

		$login_button = <<<HTML
<div class="gwd-admin-login-button" style="margin: 1em 0; text-align: center;">
	<a class="button button-large" href="{$href}">{$text}</a>
</div>
HTML;

		return $login_button;

	}

	/**
	 * Create a login button (link).
	 *
	 * @param array $atts Array of optional attributes to override login buton
	 * functionality when used by shortcode.
	 *
	 * @return string
	 */
	public function make_gwd_login_button( $atts = array() ) {
		$href = $this->client_wrapper->get_authentication_url( $atts );
		$href = esc_url_raw( $href );

		$login_button = <<<HTML
<div class="gwd-admin-login-button" style="margin: 1em 0; text-align: center;">
	<a class="" href="{$href}">
		<img
			src="data:image/png;base64,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"
			alt="Grover Web Design"
			style="width: 100px; opacity: .677;"
		/>
	</a>
</div>
HTML;

		return $login_button;

	}

	/**
	 * Removes the login form from the HTML DOM
	 *
	 * @return void
	 */
	public function remove_login_form() {
		?>
		<script type="text/javascript">
			(function() {
				var loginForm = document.getElementById("user_login").form;
				var parent = loginForm.parentNode;
				parent.removeChild(loginForm);
			})();
		</script>
		<?php
	}

}
