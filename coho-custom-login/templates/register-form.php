<?php
/**
 * The login form template.
 *
 * @link              https://1128workroom.com
 * @since             1.0.0
 * @package           Coho custom Login
 */

?>
<div id="register-form" class="widecolumn">
	<?php if ( $attributes['show_title'] ) : ?>
		<h3><?php esc_html_e( 'Register', 'coho-custom-login' ); ?></h3>
	<?php endif; ?>

	<?php if ( isset( $attributes['errors'] ) ) : ?>
		<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
			<?php foreach ( $attributes['errors'] as $coho_error ) : ?>
				<p>
					<?php echo esc_html( $coho_error ); ?>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endif; ?>
	<style>
		#signupform {
			max-width: 600px;
		}
		#signupform input[type=email], #signupform input[type=password], #signupform input[type=text] {
			padding: 5px;
			width: 100%;
		}
		label {
			min-width:100px;
		}
		#signupform input[type=submit] {
			background-color: #5C78A1;
			border-color: #5C78A1;
			border-radius: 3px;
			color: #fff;
			padding: 0.375em 0.625em;
		}
	</style>
	<?php echo do_shortcode( '[formidable id=2]' ); ?>
</div>
