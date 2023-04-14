<?php
/**
 * The login form template.
 *
 * @link              https://1128workroom.com
 * @since             1.0.0
 * @package           Coho custom Login
 */

?>

<style>
	#loginform {
		max-width: 600px;
	}
	#loginform input[type=email], #loginform input[type=password], #loginform input[type=text] {
		padding: 5px;
		width: 100%;
	}
	label {
		min-width:100px;
	}
</style>
<div class="login-form-container">
	<?php if ( $attributes['show_title'] ) : ?>
		<h2><?php esc_html_e( 'Sign In', 'coho-custom-login' ); ?></h2>
	<?php endif; ?>
	<div class="mt-2 mb-4">
	<!-- Show logged out message if user just logged out -->
	<?php if ( $attributes['logged_out'] ) : ?>
		<p class="login-info">
			<?php esc_html_e( 'You have signed out. Would you like to sign in again?', 'coho-custom-login' ); ?>
		</p>
	<?php endif; ?>
	<?php
	if ( isset( $attributes['registered'] ) ) {
		if ( $attributes['registered'] ) :
			?>
		<p class="login-info">
			<?php
				printf(
					/* translators: %s: Website name */
					esc_html__( 'You have successfully registered to %s. We do not email your password for security reasons. Instead, we have sent a link to the email address you entered to RESET your password. Return here to login with your new password.', 'coho-custom-login' ),
					esc_html( get_bloginfo( 'name' ) )
				);
			?>
		</p>
	<?php endif; ?>
	<?php } ?>
	<?php if ( $attributes['lost_password_sent'] ) : ?>
		<p class="login-info">
			<?php esc_html_e( 'Check your email for a link to reset your password.', 'coho-custom-login' ); ?>
		</p>
	<?php endif; ?>
	<?php if ( $attributes['password_updated'] ) : ?>
		<p class="login-info">
			<?php esc_html_e( 'Your password has been changed. You can sign in now.', 'coho-custom-login' ); ?>
		</p>
	<?php endif; ?>
	</div>
	<?php
	if ( ! is_user_logged_in() ) { // Display WordPress login form:.
		$args = array(
			'redirect'       => $attributes['redirect'],
			'form_id'        => 'loginform',
			'label_username' => __( 'Email', 'coho-custom-login' ),
			'label_password' => __( 'Password' ),
			'label_remember' => __( 'Remember Me for Next Time' ),
			'label_log_in'   => __( 'Sign in', 'coho-custom-login' ),
			'remember'       => true,
		);
		$login_url = esc_url( home_url() ) . '/member-login/';
	//	$nonce= wp_nonce_url( $login_url,'login_' . $user->ID) ;
		// Commented out - using custom form instead of wp_login_form( $args );.
		?>
		<div class="login-form-container">
			<form id="loginform" method="post" action="<?php echo esc_url( wp_login_url() ); ?>">
			<?php wp_nonce_field( 'coho_login', 'coho_login_wpnonce' ); ?>		
				<p class="login-username">
					<label for="user_login"><?php esc_html_e( 'Email', 'coho-custom-login' ); ?></label>
					<input type="text" name="log" id="user_login">
				</p>
				<p class="login-password">
					<label for="user_pass"><?php esc_html_e( 'Password', 'coho-custom-login' ); ?></label>
					<input type="password" name="pwd" id="user_pass">
				</p>
				<p class="login-submit">
					<input type="submit" value="<?php esc_html_e( 'Sign In', 'coho-custom-login' ); ?>">
				</p>
			</form>
		</div>
		<a class="forgot-password" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
		<?php esc_html_e( 'Forgot your password?', 'coho-custom-login' ); ?>
		</a>
		<?php
	} else { // If logged in:.
		echo 'You are already signed in.<br>';
		wp_loginout( home_url() ); // Display "Log Out" link.
		if ( current_user_can( 'edit_posts' ) ) {
			echo ' | ';
			wp_register( '', '' ); // Display "Site Admin" link.
		}
	}
	?>
</div>
