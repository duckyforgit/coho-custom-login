<?php
/**
 * The memberaccount template.
 *
 * @link              https://1128workroom.com
 * @since             1.0.0
 * @package           Coho custom Login
 */

?>

<style>
	#account {
		max-width: 600px;
	}
	#account input[type=email], #account input[type=password], #account input[type=text] {
		padding: 5px;
		width: 100%;
	}
	label {
		min-width:100px;
	}
</style>
<div class="account-form-container">
	<?php if ( $attributes['show_title'] ) : ?>
		<h2><?php esc_html_e( 'Account', 'coho-custom-login' ); ?></h2>
	<?php endif; ?>
	<h2>Welcome to your Account page</h2> 
	<?php
	  global $wpdb;
	  $wpdb->prefix ='wp_';
	  $user_id = 1;
	  $result       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}members WHERE id = %d", $user_id ) );
	// do_action( 'show_user_profile', $profileuser );
	// do_action( 'edit_user_profile', $profileuser );
	?>
</div>
<!--
wp_capabilities a:1:{s:13:"administrator";s:1:"1";}
wp_user_level 10 -->
