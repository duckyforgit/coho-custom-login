<?php
/**
 * The password list form template.
 *
 * @link              https://1128workroom.com
 * @since             1.0.0
 * @package           Coho custom Login
 */

?>
<style>
	#password-lost-form {
		max-width: 600px;
	}
	label {
		min-width:100px;
	}
	#password-lost-form input[type=email],
	#password-lost-form input[type=password],
	#password-lost-form input[type=text] {
		padding: 5px;
		width: 100%;
	}
	#password-lost-form input[type=submit] {
		background-color: #5C78A1;
		border-color: #5C78A1;
		border-radius: 3px;
		color: #fff;
		padding: 0.375em 0.625em;
	}
</style>
<div id="password-lost-form" class="widecolumn">
	<?php if ( $attributes['show_title'] ) : ?>
		<h3><?php esc_html_e( 'Forgot Your Password?', 'coho-custom-login' ); ?></h3>
	<?php endif; ?>
	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<?php foreach ( $attributes['errors'] as $coho_error ) : ?>
			<p>
				<?php echo esc_html( $coho_error ); ?>
			</p>
		<?php endforeach; ?>
	<?php endif; ?>
	<p>
		<?php
			esc_html_e(
				"Enter your email address and we'll send you a link you can use to pick a new password.",
				'coho_custom_login'
			);
			?>
	</p>
	<form id="lostpasswordform" action="<?php echo esc_url( wp_lostpassword_url() ); ?>" method="post">
		<?php wp_nonce_field( 'coho_lostpassword', 'coho_lostpassword_wpnonce' ); ?>
		<p class="form-row">
			<label for="user_login"><?php esc_html_e( 'Email', 'coho-custom-login' ); ?>
			<input type="text" name="user_login" id="user_login">
		</p>
		<p class="lostpassword-submit">
			<input type="submit" name="submit" class="lostpassword-button"
				value="<?php esc_html_e( 'Reset Password', 'coho-custom-login' ); ?>"/>
		</p>
	</form>
</div>
