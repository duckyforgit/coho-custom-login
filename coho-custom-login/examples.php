<form name="pms_login" id="pms_login" action="" method="post">
                
                <p class="login-username">
                    <label for="user_login">Username or Email Address</label>
                    <input type="text" name="log" id="user_login" class="input" value="" size="20">
               </p>

                <p class="login-password">
                    <label for="user_pass">Password</label>
                    <input type="password" name="pwd" id="user_pass" class="input" value="" size="20">
                </p>

                
                <p class="login-remember">
                        <input name="rememberme" type="checkbox" id="rememberme" value="forever">

                        <label for="rememberme">
                            Remember Me                        </label>
                    </p>
                
                <p class="login-submit">
                    <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Log In">
                    <input type="hidden" name="redirect_to" value="https://cottagehome.info/my-account">
                    <input type="hidden" name="pms_login_nonce" value="368dbc728b">
                </p>

                <p class="login-extra">
                    <input type="hidden" name="pms_login" value="1">
					<input type="hidden" name="pms_redirect" value="https://cottagehome.info/login/">
					<a class="register" href="https://cottagehome.info/get-involved/membership/">Become a member</a><span class="separator">|</span><a class="lostpassword" href="https://cottagehome.info/recover-password">Lost your password?</a>                </p>
            </form>
<?php
			get_userdatabylogin('id', 1);
			$user = get_user_by('login','loginname');
			if ( $user ) {
				echo $user->ID;
			}
			get_user_by( 'login', $user_login );
			get_user_by('login', $new_member_id );
			 
$new_member_id = $_POST['new_member_id'];
$u = null;
    
if (intval($new_member_id)) $u = get_user_by('id', $new_member_id);
else if (strpos($new_member_id, '@') != false) $u = get_user_by('email', $new_member_id);
else $u = get_user_by('login', $new_member_id);
/**
 * Custom register email
 */
add_filter( 'wp_new_user_notification_email', 'custom_wp_new_user_notification_email', 10, 3 );
function custom_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {

	$user_login = stripslashes( $user->user_login );
	$user_email = stripslashes( $user->user_email );
	$login_url	= wp_login_url();
	$message  = __( 'Hi there,' ) . "/r/n/r/n";
	$message .= sprintf( __( "Welcome to %s! Here's how to log in:" ), get_option('blogname') ) . "/r/n/r/n";
	$message .= wp_login_url() . "/r/n";
	$message .= sprintf( __('Username: %s'), $user_login ) . "/r/n";
	$message .= sprintf( __('Email: %s'), $user_email ) . "/r/n";
	$message .= __( 'Password: The one you entered in the registration form. (For security reason, we save encripted password)' ) . "/r/n/r/n";
	$message .= sprintf( __('If you have any problems, please contact us at %s.'), get_option('admin_email') ) . "/r/n/r/n";
	$message .= __( 'bye!' );

	$wp_new_user_notification_email['subject'] = sprintf( '[%s] Your credentials.', $blogname );
	$wp_new_user_notification_email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
	$wp_new_user_notification_email['message'] = $message;

	return $wp_new_user_notification_email;
}
 // do not send email if user has already logged in once.
 if ( current_user_can( 'administrator' ) || get_user_meta( $user->ID, 'wpdocs_welcome_email_sent', true ) ) {
	return;
}

// send welcome email if logging in first time.
$message = str_replace(
	array( '%%firstname%%', '%%name%%', '%%sitename%%' ),
	array(
		$user->first_name,
		$user->data->user_login,
		get_bloginfo( 'name' ),
	),
	get_option( 'welcome_message' )
);

// send email to user
if ( wp_mail( $user->data->user_email, 'Welcome subject', $message ) ) {
	// update or add user meta if email sent successfully
	update_user_meta( $user->ID, 'wpdocs_welcome_email_sent', 1 );
}

	/**
	 * Modify new user notification email.
	 *
	 * @param  [type] $wp_new_user_notification_email
	 * @param  [type] $user
	 * @param  [type] $blogname
	 * @return void
	 */
	public function custom_wp_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {

		$user_login = stripslashes( $user->user_login );
		$user_email = stripslashes( $user->user_email );
		$login_url	= wp_login_url();
		$message  = __( 'Hi there,' ) . "/r/n/r/n";
		$message .= sprintf( __( "Welcome to %s! Here's how to log in:" ), get_option('blogname') ) . "/r/n/r/n";
		$message .= wp_login_url() . "/r/n";
		$message .= sprintf( __('Username: %s'), $user_login ) . "/r/n";
		$message .= sprintf( __('Email: %s'), $user_email ) . "/r/n";
		$message .= __( 'Password: The one you entered in the registration form. (For security reason, we save encripted password)' ) . "/r/n/r/n";
		$message .= sprintf( __('If you have any problems, please contact us at %s.'), get_option('admin_email') ) . "/r/n/r/n";
		$message .= __( 'bye!' );

		$wp_new_user_notification_email['subject'] = sprintf( '[%s] Your credentials.', $blogname );
		$wp_new_user_notification_email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
		$wp_new_user_notification_email['message'] = $message;

		return $wp_new_user_notification_email;
	}
