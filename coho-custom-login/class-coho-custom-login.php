<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://1128workroom.com
 * @since             1.0.0
 * @package           Coho custom Login
 *
 * @wordpress-plugin
 * Plugin Name:       Coho custom Login
 * Plugin URI:        https://1128workroom.com
 * Description:       Membership plugin for board and general members to create a unique membership and content restriction process for the community.
 * Version:           1.0.0
 * Author:            Maureen Mladucky
 * Author URI:        https://1128workroom.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:        coho-custom-login
 * Domain Path:       /languages
 * https://code.tutsplus.com/tutorials/build-a-custom-wordpress-user-flow-part-1-replace-the-login-page--cms-23627.
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    coho_custom_login
 * @author     Maureen Mladucky
 */
class Coho_Custom_Login {
	/**
	 * Form ID
	 *
	 * @access public
	 * @var int
	 */
	public $form_id;
	/**
	 * Initializes the plugin.
	 *
	 * To keep the initialization fast, only add filter and action
	 * hooks in the constructor.
	 */
	public function __construct() {
		add_filter( 'wp_new_user_notification_email', 'custom_wp_new_user_notification_email', 10, 3 );
		add_filter( 'frm_validate_entry', array( $this, 'check_nonce_on_submit' ), 10, 2 );
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
		add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
		add_shortcode( 'custom-register-form', array( $this, 'render_register_form' ) );
		// localhost/coho//wp-login.php?action=register.
		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) ); // 30.
		add_action( 'frm_after_create_entry', array( $this, 'copy_into_my_table' ), 20, 2 );
		add_action( 'frm_after_create_entry', array( $this, 'add_entry_id_to_user' ), 30, 2 );
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) );
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 );
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) );
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
		add_shortcode( 'custom-account-info', array( $this, 'render_account_page' ) );
		add_filter( 'wp_nav_menu_items', 'add_login_logout_to_primary_menu', 10, 2 );
	}
	/**
	 * Plugin activation hook.
	 *
	 * Creates all WordPress pages needed by the plugin.
	 */
	public static function plugin_activated() {
		// Information needed for creating the plugin's pages.
		$page_definitions = array(
			'member-login'          => array(
				'title'   => __( 'Sign In', 'coho-custom-login' ),
				'content' => '[custom-login-form]',
			),
			'member-account'        => array(
				'title'   => __( 'My Account', 'coho-custom-login' ),
				'content' => '[custom-account-info]',
			),
			'member-register'       => array(
				'title'   => __( 'Register', 'coho-custom-login' ),
				'content' => '[formidable id=2]',
			),
			'member-password-lost'  => array(
				'title'   => __( 'Forgot Your Password?', 'coho-custom-login' ),
				'content' => '[custom-password-lost-form]',
			),
			'member-password-reset' => array(
				'title'   => __( 'Pick a New Password', 'coho-custom-login' ),
				'content' => '[custom-password-reset-form]',
			),
		);
		foreach ( $page_definitions as $slug => $page ) {
			// Check that the page doesn't exist already.
			$coho_query = new WP_Query( 'pagename=' . $slug );
			if ( ! $coho_query->have_posts() ) {
				// Add the page using the data from the array above.
				wp_insert_post(
					array(
						'post_content'   => $page['content'],
						'post_name'      => $slug,
						'post_title'     => $page['title'],
						'post_status'    => 'publish',
						'post_type'      => 'page',
						'ping_status'    => 'closed',
						'comment_status' => 'closed',
					)
				);
			}
		}
	}
	/**
	 * Deactivate Plugin.
	 *
	 * @return void
	 */
	public static function plugin_deactivated() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		remove_filter( 'wp_new_user_notification_email', 'custom_wp_new_user_notification_email', 10, 3 );
		remove_shortcode( 'custom-login-form', 'render_login_form' );
		remove_action( 'login_form_login', 'redirect_to_custom_login' );
		remove_filter( 'authenticate', 'maybe_redirect_at_authenticate' );
		remove_action( 'wp_logout', 'redirect_after_logout' );
		remove_filter( 'login_redirect', 'redirect_after_login' );
		remove_shortcode( 'custom-register-form', 'render_register_form' );
		// localhost/coho//wp-login.php?action=register .
		remove_action( 'login_form_register', 'redirect_to_custom_register' ); // 30.
		remove_action( 'login_form_register', 'do_register_user' ); // 1.
		remove_action( 'login_form_lostpassword', 'redirect_to_custom_lostpassword' );
		remove_shortcode( 'custom-password-lost-form', 'render_password_lost_form' );
		remove_action( 'login_form_lostpassword', 'do_password_lost' );
		remove_filter( 'retrieve_password_message', 'replace_retrieve_password_message' );
		remove_action( 'login_form_rp', 'redirect_to_custom_password_reset' );
		remove_action( 'login_form_resetpass', 'redirect_to_custom_password_reset' );
		remove_shortcode( 'custom-password-reset-form', 'render_password_reset_form' );
		remove_action( 'login_form_rp', 'do_password_reset' );
		remove_action( 'login_form_resetpass', 'do_password_reset' );
		remove_shortcode( 'custom-account-page', 'render_account_page' );
		remove_filter( 'wp_nav_menu_items', 'add_login_logout_to_primary_menu' );
	}
	/**
	 * A shortcode for rendering the login form.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $content The text content for shortcode. Not used.
	 *
	 * @return string The shortcode output.
	 */
	public function render_login_form( $attributes, $content = null ) {
		// Parse shortcode attributes .
		$default_attributes = array( 'show_title' => false );
		$attributes         = shortcode_atts( $default_attributes, $attributes );
		$show_title         = $attributes['show_title'];
		// Check if the user just registered.
		if ( isset( $_REQUEST['coho_register_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['coho_register_wpnonce'] ) ), 'coho_register' ) ) {
			// did just register.
			$attributes['registered'] = '';
			$attributes['registered'] = isset( $_REQUEST['registered'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['registered'] ) ) : '';
		}
		$attributes['registered'] = isset( $_REQUEST['registered'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['registered'] ) ) : '';

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'coho-custom-login' );
		}
		// Pass the redirect parameter to the WordPress login functionality: by default,.
		// don't specify a redirect, but if a valid redirect URL has been passed as.
		// request parameter, use it.
		$attributes['redirect'] = '';

		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$redirecturl            = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) );
			$attributes['redirect'] = wp_validate_redirect( $redirecturl, $attributes['redirect'] );
		}
		// }
		// Check if user just updated password.
		$attributes['password_updated'] = isset( $_REQUEST['password'] ) && 'changed' === $_REQUEST['password'];

		// Error messages.
		$errors = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) );
			foreach ( $error_codes as $code ) {
				$msg       = $this->get_error_message( $code );
				$errors [] = $msg;
			}
		}
		$attributes['errors'] = $errors;

		// Check if user just logged out.
		$attributes['logged_out'] = '';
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && true === $_REQUEST['logged_out'];

		// Check if the user just requested a new password .
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && 'confirm' === $_REQUEST['checkemail'];

		// Render the login form using an external template.
		return $this->get_template_html( 'login-form', $attributes );
	}
	/**
	 * Renders the contents of the given template to a string and returns it.
	 *
	 * @param string $template_name The name of the template to render (without .php).
	 * @param array  $attributes The PHP variables for the template.
	 *
	 * @return string The contents of the template.
	 */
	private function get_template_html( $template_name, $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}
		ob_start();
		do_action( 'coho_custom_login_before_' . $template_name );
		include plugin_dir_path( __FILE__ ) . 'templates/' . $template_name . '.php';
		do_action( 'coho_custom_login_after_' . $template_name );
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	public function redirect_to_custom_login() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			// If get, then page is just loaded; post is form is submitted.
			if ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
				if ( ! isset( $_POST['frm_submit_entry_2'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['frm_submit_entry_2'] ) ), 'frm_submit_entry_nonce' ) ) {
					return __( 'You are cheating.', 'coho-custom-login' );
				}
				$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : null;
				if ( is_user_logged_in() ) {
					$this->redirect_logged_in_user( $redirect_to );
					exit;
				}
				// The rest are redirected to the login page .
				$login_url = home_url( 'member-login' );
				if ( ! empty( $redirect_to ) ) {
					$login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
				}
				wp_safe_redirect( $login_url );
				exit;
			}
		}
	}
	/**
	 * Redirects the user to the correct page depending on whether he / she is an admin or not.
	 *
	 * @param string $redirect_to An optional redirect_to URL for admin users.
	 */
	private function redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( user_can( $user, 'manage_options' ) ) {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
				exit;
			} else {
				wp_safe_redirect( admin_url() );
				exit;
			}
		} else {
			wp_safe_redirect( home_url( 'member-account' ) );
			exit;
		}
	}
	/**
	 * Redirect the user after authentication if there were any errors.
	 *
	 * @param Wp_User|Wp_Error $user The signed in user, or the errors that have occurred during login.
	 * @param string           $username The user name used to log in.
	 * @param string           $password The password used to log in.
	 *
	 * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
	 */
	public function maybe_redirect_at_authenticate( $user, $username, $password ) {
		// Check if the earlier authenticate filter (most likely,.
		// the default WordPress authentication) functions have found errors .
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				if ( is_wp_error( $user ) ) {
					$error_codes = join( ',', $user->get_error_codes() );
					$login_url   = home_url( 'member-login' );
					$login_url   = add_query_arg( 'login', $error_codes, $login_url );
					wp_safe_redirect( $login_url );
					exit;
				}
			}
		}
		return $user;
	}
	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code The error code to look up.
	 *
	 * @return string An error message.
	 */
	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			case 'empty_username':
				return __( 'You do have an email address, right?', 'coho-custom-login' );
			case 'empty_password':
				return __( 'You need to enter a password to login.', 'coho-custom-login' );
			case 'invalid_email':
				return __(
					"We don't have any users with that email address. Maybe you used a different one when signing up?",
					'coho-custom-login'
				);
			case 'incorrect_password':
				/* translators: %s: Link for lost password */
				return printf(
					'The password you entered was not quite right. <a href="%s">%s</a>?',
					esc_url( wp_lostpassword_url() ),
					esc_html__( 'Did you forget your password', 'coho-custom-login' )
				);
			// Lost password.
			case 'empty_username':
				return __( 'You need to enter your email address to continue.', 'coho-custom-login' );
			case 'invalid_email':
			case 'invalidcombo':
				return __( 'There are no users registered with this email address.', 'coho-custom-login' );
			// Registration errors.
			case 'email':
				return __( 'The email address you entered is not valid.', 'coho-custom-login' );
			case 'email_exists':
				return __( 'An account already exists with this email address.', 'coho-custom-login' );
			case 'closed':
				return __( 'Registering new users is currently not allowed.', 'coho-custom-login' );
			// Reset password.
			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'coho-custom-login' );
			case 'password_reset_mismatch':
				return __( "The two passwords you entered don't match.", 'coho-custom-login' );
			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'coho-custom-login' );
			case 'retrieve_password_email_failure':
				return __( 'Sorry, the password email failed. Please contact chnacommunications@gmail.com for help.', 'coho-custom-login' );
			default:
				break;
		}
		return __( 'An unknown error occurred. Please try again later.', 'coho-custom-login' );
	}
	/**
	 * Redirect to custom login page after the user has been logged out.
	 */
	public function redirect_after_logout() {
		$redirect_url = home_url( 'member-login?logged_out=true' );
		wp_safe_redirect( $redirect_url );
		exit;
	}
	/**
	 * Returns the URL to which the user should be redirected after the (successful) login.
	 *
	 * @param string           $redirect_to The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string Redirect URL.
	 */
	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
		$redirect_url = home_url();
		if ( ! isset( $user->ID ) ) {
			return $redirect_url;
		}
		if ( user_can( $user, 'manage_options' ) ) {
			// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
			if ( '' === $requested_redirect_to ) {
				$redirect_url = admin_url();
			} else {
				$redirect_url = $requested_redirect_to;
			}
		} else {
			// Non-admin users always go to their account page after login.
			$redirect_url = home_url( 'member-account' );
		}

		return wp_validate_redirect( $redirect_url, home_url() );
	}
	/**
	 * A shortcode for rendering the new user registration form.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $content The text content for shortcode. Not used.
	 *
	 * @return string The shortcode output.
	 */
	public function render_register_form( $attributes, $content = null ) {
		// Parse shortcode attributes.
		$default_attributes = array( 'show_title' => false );
		$attributes         = shortcode_atts( $default_attributes, $attributes );
		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'coho-custom-login' );
		} elseif ( ! get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'coho-custom-login' );
		} else {
			// Retrieve possible errors from request parameters.
			if ( isset( $_REQUEST['coho_register_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['coho_register_wpnonce'] ) ), 'coho_register' ) ) {
				// Already filled out register form.
				if ( isset( $_REQUEST['register-errors'] ) ) {
					$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['register-errors'] ) ) );
					foreach ( $error_codes as $code ) {
						$msg       = $this->get_error_message( $code );
						$errors [] = $msg;
					}
					$attributes['errors'] = $errors;
				} else {
					$attributes['errors'] = '';
				}
				return $this->get_template_html( 'login-form', $attributes );
			}
			// maybe load register errors into an array and send to form.
			if ( isset( $_REQUEST['register-errors'] ) ) {
				$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['register-errors'] ) ) );
				foreach ( $error_codes as $code ) {
					$msg       = $this->get_error_message( $code );
					$errors [] = $msg;
				}
				$attributes['errors'] = $errors;
			}
			return $this->get_template_html( 'register-form', $attributes );
		}
	}
	/**
	 * A shortcode for rendering the account form.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $content The text content for shortcode. Not used.
	 *
	 * @return string The shortcode output. account-info.
	 */
	public function render_account_page( $attributes, $content = null ) {
		// Parse shortcode attributes.
		$default_attributes = array( 'show_title' => false );
		$attributes         = shortcode_atts( $default_attributes, $attributes );
		if ( ! is_user_logged_in() ) {
			return __( 'You are not signed in.', 'coho-custom-login' );
		} else {
			return $this->get_template_html( 'member-account', $attributes );
		}
	}
	/**
	 * Redirects the user to the custom registration page instead of wp-login.php?action=register.
	 */
	public function redirect_to_custom_register() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
				if ( is_user_logged_in() ) {
					$this->redirect_logged_in_user();
				} else {
					// Change to this after pages are created wp_safe_redirect( home_url( 'get-involved/membership' ) );.
					wp_safe_redirect( home_url( 'member-register' ) );
					exit;
				}
				exit;
			}
		}
	}
	/**
	 * Validates and then completes the new user signup process if all went well. Notes: user_status = 0 => false or normal status user_status = 1 => User marked as spammer user_status = 2 => User pending (user account not activated yet).
	 *
	 * @param string $email The new user's email address.
	 * @param string $first_name The new user's first name.
	 * @param string $last_name The new user's last name.
	 *
	 * @return int|WP_Error The id of the user that was created, or error if failed.
	 */
	private function register_user( $email, $first_name, $last_name ) {
		$errors = new WP_Error();
		// Email address is used as both username and email. It is also the only.
		// parameter we need to validate.
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ) );
			return $errors;
		}
		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
			return $errors;
		}
		// Generate the password so that the subscriber will have to check email..
		$password = wp_generate_password( 12, false );
		$role     = 'general_member';
		// ID, user_nicename, user_url, display_name, description, user_registered, jabber, aim, yim, locale.
		$user_data = array(
			'user_login' => $email,
			'user_email' => $email,
			'user_pass'  => $password,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'nickname'   => $first_name,
			'role'       => $role,
		);
		$user_id   = wp_insert_user( $user_data );
		$user_id   = wp_update_user(
			array(
				'ID'   => $user_id,
				'role' => 'general_member',
			),
		);
		// Sends generated password to new user and notifies the admin of the new user. Replace with sending email to request password reset.
		wp_new_user_notification( $user_id );
		// Set this if you want user to be automatically logged in wp_set_current_user( $user_id ); // auto logs in user.
		// Set this if you want user to be automatically logged in wp_set_auth_cookie( $user_id );  // auto logs in user.
		return $user_id;
	}
	/**
	 * Copy member data into wp_members and wp_member_parent tables.
	 *
	 * @param  [type] $entry_id Entry id of form - not used here.
	 * @param  [type] $form_id The form id.
	 * @return [type] $result_check
	 */
	public function copy_into_my_table( $entry_id, $form_id ) {
		$redirect_url = '';
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				$redirect_url = home_url( 'member-register' );
				if ( ! get_option( 'users_can_register' ) ) {
					// Registration closed, display error.
					$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
					wp_safe_redirect( $redirect_url );
					exit;
				}
				// <input type="hidden" id="frm_submit_entry_2" name="frm_submit_entry_2" value="bb8cc89074">.
				if ( ! isset( $_POST['frm_submit_entry_2'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['frm_submit_entry_2'] ) ), 'frm_submit_entry_nonce' ) ) {
					return __( 'You are cheating.', 'coho-custom-login' );
				}
				// Change 2 to the form id of the form to copy.
				// if ( 2 === $form_id ) {.
				// Items to due. 1. loop through multiple members to add to wp_members.
				// 2. Add member parent id to all wp_members in same family.
				// 3. Add wp_member display in admin.
				// Add data to member_parent table.
				global $wpdb;
				$table_name          = $wpdb->prefix . 'members';
				$table_name          = 'wp_member_parent';
				$timestamp           = time();
				$start_date          = gmdate( 'Y-m-d', $timestamp );
				$expiration_date     = gmdate( 'Y-m-d', strtotime( '+365 days' ) ); // add 365 days to date.
				$member_status       = isset( $_POST['item_meta'][55] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][55] ) ) : '';
				$payment_type_paypal = isset( $_POST['item_meta'][46] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][46] ) ) : '';
				$payment_type_venmo  = isset( $_POST['item_meta'][48] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][48] ) ) : '';
				$payment_type_cash   = isset( $_POST['item_meta'][50] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][50] ) ) : '';
				$billing_amount      = isset( $_POST['item_meta'][45] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][45] ) ) : '';
				// %d (integer) %f (float) %s (string) %i (identifier, e.g. table/field names).
				$result_check = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery.
					$table_name,
					array(
						'plan_id'              => 1,
						'start_date'           => $start_date,
						'expiration_date'      => $expiration_date,
						'status'               => $member_status,
						'payment_type_paypal'  => $payment_type_paypal,
						'payment_type_venmo'   => $payment_type_venmo,
						'payment_type_cash'    => $payment_type_cash,
						'billing_amount'       => $billing_amount,
						'billing_duration'     => 365,
						'billing_next_payment' => $expiration_date,
						'billing_last_payment' => $start_date,
					),
					array(
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%f',
						'%d',
						'%s',
						'%s',
					),
				);
				if ( isset( $_POST['item_meta'][78] ) ) {
					// Add all members to user table.
					$email = isset( $_POST['item_meta'][15] ) ? sanitize_email( wp_unslash( $_POST['item_meta'][15] ) ) : '';
					$fname = isset( $_POST['item_meta'][78] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][78] ) ) : '';
					$lname = isset( $_POST['item_meta'][79] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][79] ) ) : '';
					if ( isset( $_POST['item_meta'][73] ) && ( 'Yes' === $_POST['item_meta'][73] ) ) {
						$role = 'Board Member';
					} else {
						$role = 'General Member';
					}
					$password = wp_generate_password( 12, false );
					// Add user to user table. $result is user id.
					$result = $this->register_user_now( $email, $fname, $lname, $role, $password );
					if ( $result ) {
						$redirect_url = $this->check_member_errors( $result, $redirect_url, $result_check, $email );
					}
				}

				if ( isset( $_POST['item_meta'][81] ) ) {
					$email2 = isset( $_POST['item_meta'][17] ) ? sanitize_email( wp_unslash( $_POST['item_meta'][17] ) ) : '';
					$fname2 = isset( $_POST['item_meta'][81] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][81] ) ) : '';
					$lname2 = isset( $_POST['item_meta'][82] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][82] ) ) : '';
					if ( isset( $_POST['item_meta'][72] ) && ( 'Yes' === $_POST['item_meta'][72] ) ) {
						$role2 = 'Board Member';
					} else {
						$role2 = 'General Member';
					}
					$password2 = wp_generate_password( 12, false );
					$result2   = $this->register_user_now( $email2, $fname2, $lname2, $role2, $password2 );
					if ( $result2 ) {
						$redirect_url = $this->check_member_errors( $result2, $redirect_url, $result_check, $email2 );
					}
				}
				if ( isset( $_POST['item_meta'][83] ) ) {
					$email3 = isset( $_POST['item_meta'][19] ) ? sanitize_email( wp_unslash( $_POST['item_meta'][19] ) ) : '';
					$fname3 = isset( $_POST['item_meta'][83] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][83] ) ) : '';
					$lname3 = isset( $_POST['item_meta'][84] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][84] ) ) : '';
					if ( isset( $_POST['item_meta'][74] ) && ( 'Yes' === $_POST['item_meta'][74] ) ) {
						$role3 = 'Board Member';
					} else {
						$role3 = 'General Member';
					}
					$password3 = wp_generate_password( 12, false );
					$result3   = $this->register_user_now( $email3, $fname3, $lname3, $role3, $password3 );
					if ( $result3 ) {
						$redirect_url = $this->check_member_errors( $result3, $redirect_url, $result_check, $email3 );
					}
				}

				if ( isset( $_POST['item_meta'][85] ) ) {
					$email4 = isset( $_POST['item_meta'][21] ) ? sanitize_email( wp_unslash( $_POST['item_meta'][21] ) ) : '';
					$fname4 = isset( $_POST['item_meta'][85] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][85] ) ) : '';
					$lname4 = isset( $_POST['item_meta'][86] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][86] ) ) : '';
					if ( isset( $_POST['item_meta'][75] ) && ( 'Yes' === $_POST['item_meta'][75] ) ) {
						$role4 = 'Board Member';
					} else {
						$role4 = 'General Member';
					}
					$password4 = wp_generate_password( 12, false );
					$result4 = $this->register_user_now( $email4, $fname4, $lname4, $role4, $password4 );
					if ( $result4 ) {
						$redirect_url = $this->check_member_errors( $result4, $redirect_url, $result_check, $email4 );
					}
				}
				if ( isset( $_POST['item_meta'][87] ) ) {
					$email5 = isset( $_POST['item_meta'][23] ) ? sanitize_email( wp_unslash( $_POST['item_meta'][23] ) ) : '';
					$fname5 = isset( $_POST['item_meta'][87] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][87] ) ) : '';
					$lname5 = isset( $_POST['item_meta'][88] ) ? sanitize_text_field( wp_unslash( $_POST['item_meta'][88] ) ) : '';
					if ( isset( $_POST['item_meta'][76] ) && ( 'Yes' === $_POST['item_meta'][76] ) ) {
						$role5 = 'Board Member';
					} else {
						$role5 = 'General Member';
					}
					$password5 = wp_generate_password( 12, false );
					$result5 = $this->register_user_now( $email5, $fname5, $lname5, $role5, $password5 );
					if ( $result5 ) {
						$redirect_url = $this->check_member_errors( $result5, $$redirect_url, $result_check, $email5 );
					}
				}
			}
			wp_safe_redirect( $redirect_url );
			// http://localhost/coho/wp-login.php?checkemail=registered. using wp-login.php?action=register.
							// Registration complete. Please check your email, then visit the login page.
			exit;
		}
	}
	/**
	 * Check on member errors and update.
	 *
	 * @param mixed  $result         The result from the last update.
	 * @param link   $redirect_url   THe redirect link.
	 * @param [type] $result_check   THe result of the table add.
	 * @param email  $email          The last email updated.
	 * @return link  $redirect_url
	 */
	public function check_member_errors( $result, $redirect_url, $result_check, $email ) {
		if ( is_wp_error( $result ) ) {
			// Parse errors into a string and append as parameter to redirect.
			$errors       = join( ',', $result->get_error_codes() );
			$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
		} else {
			// Success, redirect to registration success page.
			// registration-success/?registered=cal@gmail.com.
			// update user meta with show_admin_bar_front, nickname.
			if ( $result ) {
				update_user_meta( $result, 'frm_the_entry_id', $result );
			}
			// updated user meta and then ??
			$attributes['registered'] = 'registered';
			$redirect_url             = home_url( 'member-login' );
			$arr_params               = array(
				'registered' => $email,
				'_wp_nonce'  => wp_create_nonce( 'registered', $redirect_url ),
			);
			$redirect_url             = add_query_arg( $arr_params, $redirect_url );
			$form_id                  = 2;
			// returns false or number of rows inserted which should be one.
			if ( 1 === $result_check ) {
				$arr_params2  = array(
					'insertdb'  => $result_check,
					'_wp_nonce' => wp_create_nonce( 'inserted', $redirect_url ),
				);
				$redirect_url = add_query_arg( $arr_params2, $redirect_url );
			} else {
				$arr_params3  = array(
					'insert error' => $result_check,
					'_wp_nonce'    => wp_create_nonce( 'inserted', $redirect_url ),
				);
				$redirect_url = add_query_arg( $arr_params3, $redirect_url );
			}
		}
		return $redirect_url;
	}
	/**
	 * CHeck nonce on submit. Filtering frm_validate_entry.
	 *
	 * @param  [type] $errors The nonce form errors.
	 * @param  [type] $values The form input values.
	 * @return [type] $errors
	 */
	public function check_nonce_on_submit( $errors, $values ) {
		// <input type="hidden" name="form_id" value="2">.
		// <input type="hidden" id="frm_submit_entry_2" name="frm_submit_entry_2" value="d67b802787">
		// <input type="hidden" name="_wp_http_referer" value="/coho/member-register/">
		$target_form_id = 2; // Replace 2 with the ID of your form.
		if ( $target_form_id === $values['form_id'] && ! wp_verify_nonce( $values[ 'frm_submit_entry_' . $values['form_id'] ], 'frm_submit_entry_nonce' ) ) {
			$errors['nonce'] = 'CSRF attack blocked';
		}
		return $errors;
	}
	/**
	 * Register User.
	 *
	 * @param  [type] $email Email.
	 * @param  [type] $fname  fName.
	 * @param  [type] $lname  lName.
	 * @param  [type] $role  The role.
	 * @param  [type] $password Set up password.
	 * @return int|WP_Error The id of the user that was created, or error if failed.
	 */
	public function register_user_now( $email, $fname, $lname, $role, $password ) {
		$errors = new WP_Error();
		// Email address is used as both username and email. It is also the only.
		// parameter we need to validate.
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ) );
			return $errors;
		}
		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
			return $errors;
		}
		$user_data = array(
			'user_login' => $email,
			'user_email' => $email,
			'user_pass'  => $password,
			'first_name' => $fname,
			'last_name'  => $lname,
			'nickname'   => $fname,
		);
		$user_id   = wp_insert_user( $user_data );
		$user_id   = wp_update_user(
			array(
				'ID'   => $user_id,
				'role' => $role,
			),
		);
		// Sends generated password to new user and notifies the admin of the new user. Replace with sending email to request password reset.
		wp_new_user_notification( $user_id );
		// Set this if you want user to be automatically logged in wp_set_current_user( $user_id ); // auto logs in user.
		// Set this if you want user to be automatically logged in wp_set_auth_cookie( $user_id );  // auto logs in user.
		return $user_id;
	}
	/**
	 * Redirects the user to the custom "Forgot your password?" page instead of.
	 * wp-login.php?action=lostpassword.
	 */
	public function redirect_to_custom_lostpassword() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
				if ( is_user_logged_in() ) {
					$this->redirect_logged_in_user();
					exit;
				}
				wp_safe_redirect( home_url( 'member-password-lost' ) );
				exit;
			}
		}
	}
	/**
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $content The text content for shortcode. Not used.
	 *
	 * @return string The shortcode output.
	 */
	public function render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode attributes.
		$default_attributes = array( 'show_title' => false );
		$attributes         = shortcode_atts( $default_attributes, $attributes );

		// Retrieve possible errors from request parameters.
		$attributes['errors'] = array();
		if ( isset( $_REQUEST['coho_lostpassword_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['coho_lostpassword_wpnonce'] ) ), 'coho_lostpassword' ) ) {
			// Password Lost form has been submitted and not tampered with.
			if ( is_user_logged_in() ) {
				return __( 'You are already signed in.', 'coho-custom-login' );
			} else {
				return __( 'You need to submit the password reset form.', 'coho-custom-login' );
			}
		}
		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'coho-custom-login' );
		} else {
			return $this->get_template_html( 'password-lost-form', $attributes );
		}
	}
	/**
	 * Initiates password reset.
	 */
	public function do_password_lost() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				$errors = retrieve_password();
				if ( is_wp_error( $errors ) ) {
					// Errors found.
					$redirect_url = home_url( 'member-password-lost' );
					$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
				} else {
					// Email sent.
					$redirect_url = home_url( 'member-login' );
					$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}
	/**
	 * Returns the message body for the password reset mail.
	 * Called through the retrieve_password_message filter.
	 *
	 * @param string  $message Default mail message.
	 * @param string  $key The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data WP_User object.
	 *
	 * @return string The mail message to send.
	 */
	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		// Create new message.
		$msg = __( 'Hello Cottage Home Neighborhood Member!', 'coho-custom-login' ) . "\r\n\r\n";
		/* translators: %s, $user_login */
		$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'coho-custom-login' ), $user_login ) . "\r\n\r\n";
		$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'coho-custom-login' ) . "\r\n\r\n";
		$msg .= __( 'To reset your password, visit the following address:', 'coho-custom-login' ) . "\r\n\r\n";
		$msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
		$msg .= __( 'Thanks!', 'coho-custom-login' ) . "\r\n";
		return $msg;
	}
	/**
	 * Redirects to the custom password reset page, or the login page.
	 * if there are errors.
	 */
	public function redirect_to_custom_password_reset() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
				// Verify key / login combo.
				if ( ! isset( $_REQUEST['coho_lostpassword_wpnonce'] )
					|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['coho_lostpassword_wpnonce'] ) ), 'coho_register' ) ) {
						print 'Sorry, your nonce did not verify.';
						exit;
				}
				if ( isset( $_REQUEST['key'] ) && isset( $_REQUEST['login'] ) ) {
					$user = check_password_reset_key( sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ), sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) );
				}
				if ( ! $user || is_wp_error( $user ) ) {
					if ( $user && $user->get_error_code() === 'expired_key' ) {
						wp_safe_redirect( home_url( 'member-login?login=expiredkey' ) );
					} else {
						wp_safe_redirect( home_url( 'member-login?login=invalidkey' ) );
					}
					exit;
				}
				$redirect_url = home_url( 'member-password-reset' );
				if ( isset( $_REQUEST['login'] ) ) {
					$redirect_url = add_query_arg( 'login', esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) ), $redirect_url );
				}
				if ( isset( $_REQUEST['key'] ) ) {
					$redirect_url = add_query_arg( 'key', esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) ), $redirect_url );
				}
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}
	/**
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $content The text content for shortcode. Not used.
	 *
	 * @return string The shortcode output.
	 */
	public function render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode attributes.
		$default_attributes = array( 'show_title' => false );
		$attributes         = shortcode_atts( $default_attributes, $attributes );
		if ( ! isset( $_REQUEST['coho_reset_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['coho_reset_wpnonce'] ) ), 'coho_reset' ) ) {
				return __( 'You are cheating.', 'coho-custom-login' );
		}
		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'coho-custom-login' );
		} elseif ( empty( sanitize_text_field( wp_unslash( $_REQUEST['login'] ) ) ) && empty( sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) ) ) {
			$attributes['login'] = sanitize_text_field( wp_unslash( $_REQUEST['login'] ) );
			$attributes['key']   = sanitize_text_field( wp_unslash( $_REQUEST['key'] ) );
			// Error messages.
			$errors = array();
			if ( ! empty( sanitize_text_field( wp_unslash( $_REQUEST['error'] ) ) ) ) {
				$error_codes = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['error'] ) ) );
				foreach ( $error_codes as $code ) {
					$errors [] = $this->get_error_message( $code );
				}
			}
			$attributes['errors'] = $errors;
			return $this->get_template_html( 'password_reset_form', $attributes );
		} else {
			return __( 'Invalid password reset link.', 'coho-custom-login' );
		}
	}
	/**
	 * Resets the user's password if the password reset form was submitted.
	 */
	public function do_password_reset() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				// Check nonce.
				if ( ! isset( $_POST['coho_reset_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['coho_reset_wpnonce'] ) ), 'coho_reset' ) ) {
					return __( 'You are cheating.', 'coho-custom-login' );
				}
				if ( empty( sanitize_text_field( wp_unslash( $_REQUEST['rp_key'] ) ) ) ) {
					$rp_key = sanitize_text_field( wp_unslash( $_REQUEST['rp_key'] ) );
				}
				if ( empty( sanitize_text_field( wp_unslash( $_REQUEST['rp_login'] ) ) ) ) {
					$rp_login = sanitize_text_field( wp_unslash( $_REQUEST['rp_login'] ) );
				}
				$user = check_password_reset_key( $rp_key, $rp_login );
				if ( ! $user || is_wp_error( $user ) ) {
					if ( $user && $user->get_error_code() === 'expired_key' ) {
						wp_safe_redirect( esc_url( home_url( 'member-login?login=expiredkey' ) ) );
					} else {
						wp_safe_redirect( esc_url( home_url( 'member-login?login=invalidkey' ) ) );
					}
					exit;
				}
				if ( isset( $_POST['pass1'] ) && isset( $_POST['pass2'] ) ) {
					if ( $_POST['pass1'] !== $_POST['pass2'] ) {
						// Passwords don't match.
						$redirect_url = home_url( 'member-password-reset' );
						$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
						$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
						$redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );
						wp_safe_redirect( $redirect_url );
						exit;
					}
					if ( empty( $_POST['pass1'] ) ) {
						// Password is empty.
						$redirect_url = home_url( 'member-password-reset' );
						$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
						$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
						$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );
						wp_safe_redirect( $redirect_url );
						exit;
					}
					// Parameter checks OK, reset password.
					reset_password( $user, sanitize_text_field( wp_unslash( $_POST['pass1'] ) ) );
					wp_safe_redirect( home_url( 'member-login?password=changed' ) );
					exit;
				} else {
					echo 'Invalid request.';
				}
				exit;
			}
		}
	}
	/**
	 * Add Login Logout menu item.
	 *
	 * @param  [type] $items Menu Items.
	 * @param  [type] $args Menu Arguments.
	 * @return [type] $items
	 */
	public function add_login_logout_to_primary_menu( $items, $args ) {
		if ( 'primary' === $args->theme_location || 'dashboard-links' === $args->theme_location ) {
			if ( is_user_logged_in() ) {
				$items .= '<li itemscope="itemscope" itemtype="https://www.schema.org/SiteNavigationElement" class="menu-item menu-item-logout nav-item"><a href="';
				$items .= wp_logout_url();
				$items .= '" class="btn btn--dark-orange float-left btn--with-photo">
					<span class="site-header__avatar">';
				$items .= get_avatar( get_current_user_id(), 60 );
				$items .= '</span><span class="btn__text">Log Out</span>
				</a></li>';
			} else {
				$items .= '<li itemscope="itemscope" itemtype="https://www.schema.org/SiteNavigationElement" class="menu-item menu-item-login nav-item d-flex align-items-center"><a href="';
				$items .= wp_login_url();
				$items .= '" class="btn btn--login float-left push-right">Login</a></li>';
			}
		}
		if ( 'member-links' === $args->theme_location ) {
			if ( is_user_logged_in() ) {
				// Get all the user roles for this user as an array.
				$user_roles = wp_get_current_user()->roles;
				// Check if the specified role is present in the array.
				if ( in_array( 'boardmember', $user_roles, true ) ) {
					$items .= '<li itemscope="itemscope" itemtype="https://www.schema.org/SiteNavigationElement" class="menu-item menu-item-dashboard nav-item"><a title="Board Member Dashboard" href="';
					$items .= esc_url( home_url( '/board-member-dashboard/' ) );
					$items .= '" class="nav-link">Board Member Dashboard</a></li>';
				} elseif ( in_array( 'generalmember', $user_roles, true ) ) {
					$items .= '<li itemscope="itemscope" itemtype="https://www.schema.org/SiteNavigationElement" class="menu-item menu-item-dashboard nav-item"><a title="General Member Dashboard" href="';
					$items .= esc_url( home_url( '/general-member-dashboard/' ) );
					$items .= '" class="nav-link">General Member Dashboard</a></li>';
				}
				$items .= '<li itemscope="itemscope" itemtype="https://www.schema.org/SiteNavigationElement" class="menu-item menu-item-logout nav-item"><a href="';
				$items .= wp_logout_url();
				$items .= '" class="btn btn--dark-orange float-left btn--with-photo">
					<span class="site-header__avatar">';
				$items .= get_avatar( get_current_user_id(), 60 );
				$items .= '</span><span class="btn__text">Log Out</span>
				</a></li>';
			} else {
				$items .= '<li itemscope="itemscope" itemtype="https://www.schema.org/SiteNavigationElement" class="menu-item menu-item-login nav-item d-flex align-items-center"><a href="';
				$items .= wp_login_url();
				$items .= '" class="btn btn--login float-left push-right">Login</a></li>';
			}
		}
		return $items;
	}
	/**
	 * Create tables wp_member_parent, wp_members, wp_member_logs, wp_payments.
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = 'wp_member_parent';
		$sql             = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		plan_id bigint(20) NOT NULL,
		start_date datetime DEFAULT '0000-00-00 00:00:00' NULL,
		expiration_date datetime DEFAULT '0000-00-00 00:00:00' NULL,
		status varchar(32) NOT NULL,
		payment_type_paypal varchar(32) NOT NULL,
		payment_type_venmo varchar(32) NOT NULL,
		payment_type_cash varchar(32) NOT NULL,
		billing_amount float NOT NULL,
		billing_duration int(10) NOT NULL,
		billing_duration_unit int(10) NOT NULL,
		billing_cycles int(10) NOT NULL,
		billing_next_payment datetime DEFAULT '0000-00-00 00:00:00' NULL,
		billing_last_payment datetime DEFAULT '0000-00-00 00:00:00' NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		$wpdb->prefix = 'wp';
		$welcome_name = 'Mr. WordPress';
		$welcome_text = 'Congratulations, you just completed the installation!';

		$table_name = $wpdb->prefix . 'liveshoutbox';

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery.
			$table_name,
			array(
				'time' => current_time( 'mysql' ),
				'name' => $welcome_name,
				'text' => $welcome_text,
			),
		);
	}
}
// Initialize the plugin.
$coho_custom_login_pages = new coho_custom_login();
// Create the custom pages at plugin activation.
register_activation_hook( __FILE__, array( 'coho_custom_login', 'plugin_activated' ) );
register_deactivation_hook( __FILE__, array( 'coho_custom_login', 'plugin_deactivated' ) );
