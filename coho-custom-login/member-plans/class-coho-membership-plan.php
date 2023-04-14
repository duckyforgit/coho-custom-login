<?php
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
class Coho_Custom_Membership_Plan {
	/**
	 * Initializes the plugin.
	 *
	 * To keep the initialization fast, only add filter and action
	 * hooks in the constructor.
	 */
	public function __construct() {
		
		add_action( 'delete_user', 'pms_member_delete_user_subscription_cancel' );
	}
	/**
	 * Plugin activation hook.
	 *
	 * Creates all WordPress pages needed by the plugin.
	 */
	public static function plugin_activated() {
		// Information needed for creating the plugin's pages.
		$page_definitions = array(
			'member-plan'          => array(
				'title'   => __( 'Member Plan', 'coho-custom-login' ),
				'content' => '[custom-member-plan]',
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
	 * Check if the currently logged in user or a specific user is an active member (or an active member of a certain subscription)
	 *
	 * @param  int   $user_id  The id of the user we want to check. Defaults to the currently logged in user.
	 * @param  array $subscription_plans Array of subscription plans to check against. If more then one is specified, the function will return true if the user is an active member in any one of them (OR).
	 * @return boolean
	 */
	public function pms_is_member( $user_id = '', $subscription_plans = array() ) {
		if ( '' == $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( 0 === $user_id ) {
			return false;
		}

		$member_subscriptions = $this->pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

		foreach ( $member_subscriptions as $member_subscription ) {

			if ( ! empty( $subscription_plans ) ) {
				if ( ! in_array( $member_subscription->subscription_plan_id, $subscription_plans ) ) {
					continue;
				}
			}
			$time_expire = ( ! empty( $member_subscription->expiration_date ) && time() > strtotime( $member_subscription->expiration_date ) ? true : false );

			if ( ( 'active' === $member_subscription->status || 'canceled' === $member_subscription->status ) && ! $time_expire ) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Wrapper for pms_is_member().
	 * Usage: pms_is_member_of_plan( 123 ) or pms_is_member_of_plan( array( 123, 124 ) )
	 * The user id can also be specified as the second parameter.
	 *
	 * @param  int $subscription_plans A single subscription plan id or an array of them.
	 * @param  int $user_id The current user id.
	 * @return boolean
	 */
	public function pms_is_member_of_plan( $subscription_plans, $user_id = '' ) {
		if ( ! is_array( $subscription_plans ) && is_numeric( $subscription_plans ) ) {
			return $this->pms_is_member( $user_id, array( $subscription_plans ) );
		}
		if ( is_array( $subscription_plans ) ) {
			return $this->pms_is_member( $user_id, $subscription_plans );
		}

		return false;
	}
	/**
	 * Wrapper function to return a member object.
	 *
	 * @param int $user_id  - The id of the user we wish to return.
	 *
	 * @return PMS_Member
	 */
	public function pms_get_member( $user_id ) {
		include plugin_dir_path( __FILE__ ) . 'class-pms-member.php';

		return new PMS_Member( $user_id );

	}
	/**
	 * Returns an array with member subscriptions based on the given arguments
	 *
	 * @param array $args The array of arguments to get the subscription information.
	 *
	 * @return array
	 */
	public function pms_get_member_subscriptions( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'order'                       => 'DESC',
			'orderby'                     => 'id',
			'number'                      => 1000,
			'offset'                      => '',
			'status'                      => '',
			'user_id'                     => '',
			'subscription_plan_id'        => '',
			'start_date'                  => '',
			'start_date_after'            => '',
			'start_date_before'           => '',
			'expiration_date'             => '',
			'expiration_date_after'       => '',
			'expiration_date_before'      => '',
			'billing_next_payment'        => '',
			'billing_next_payment_after'  => '',
			'billing_next_payment_before' => '',
			'include_abandoned'           => false,
		);

		/**
		 * Filter the query args
		 *
		 * @param array $query_args - the args for which the query will be made.
		 * @param array $args       - the args passed as parameter.
		 * @param array $defaults   - the default args for the query.
		 */
		$args = apply_filters( 'pms_get_member_subscriptions_args', wp_parse_args( $args, $defaults ), $args, $defaults );
		// Start query string.
		$query_string = 'SELECT * ';
		$query_from   = 'FROM {$wpdb->prefix}pms_member_subscriptions ';
		$query_where  = 'WHERE 1=%d ';
		// Filter by user id.
		if ( ! empty( $args['user_id'] ) ) {
			$user_id      = absint( $args['user_id'] );
			$query_where .= ' AND user_id LIKE "{$user_id}"';
		}
		// Filter by status.
		if ( ! empty( $args['status'] ) ) {

			$status       = sanitize_text_field( $args['status'] );
			$query_where .= " AND status LIKE '{$status}'";

		}

		// Exclude Abandoned subscriptions unless requested.
		if ( isset( $args['include_abandoned'] ) && false === $args['include_abandoned'] ) {
			$query_where .= " AND status NOT LIKE 'abandoned'";
		}

		// Filter by start date.
		if ( ! empty( $args['start_date'] ) ) {

			$query_where .= " AND start_date LIKE '%%{$args['start_date']}%%'";

		}

		// Filter by start date after.
		if ( ! empty( $args['start_date_after'] ) ) {

			$query_where .= " AND start_date > '{$args['start_date_after']}'";

		}

		// Filter by start date before.
		if ( ! empty( $args['start_date_before'] ) ) {

			$query_where .= " AND start_date < '{$args['start_date_before']}'";

		}

		// Filter by expiration date.
		if ( ! empty( $args['expiration_date'] ) ) {

			$query_where .= " AND expiration_date LIKE '%%{$args['expiration_date']}%%'";

		}

		// Filter by expiration date after.
		if ( ! empty( $args['expiration_date_after'] ) ) {

			$query_where .= " AND expiration_date > '{$args['expiration_date_after']}'";

		}

		// Filter by expiration date before.
		if ( ! empty( $args['expiration_date_before'] ) ) {

			$query_where .= " AND expiration_date < '{$args['expiration_date_before']}'";

		}

		// Filter by billing next payment date.
		if ( ! empty( $args['billing_next_payment'] ) ) {

			$query_where .= " AND billing_next_payment LIKE '%%{$args['billing_next_payment']}%%'";

		}

		// Filter by billing next date payment after.
		if ( ! empty( $args['billing_next_payment_after'] ) ) {

			$query_where .= " AND billing_next_payment > '{$args['billing_next_payment_after']}'";

		}

		// Filter by billing next payment date before.
		if ( ! empty( $args['billing_next_payment_before'] ) ) {

			$query_where .= " AND billing_next_payment < '{$args['billing_next_payment_before']}'";

		}

		// Filter by subscription plan id.
		if ( ! empty( $args['subscription_plan_id'] ) ) {

			$query_where .= " AND subscription_plan_id = '{$args['subscription_plan_id']}'";

		}

		// Query order by.
		$query_order_by = '';

		if ( ! empty( $args['orderby'] ) ) {

			// On the edit_member page, make sure abandoned subs are last.
			if ( isset( $_GET['page'], $_GET['subpage'] ) && 'pms-members-page' === $_GET['page'] && 'edit_member' === $_GET['subpage'] ) {
				$query_order_by = ' ORDER BY status = "abandoned", status ';
			} else {
				$query_order_by = ' ORDER BY ' . trim( $args['orderby'] ) . ' ';
			}
		}

		// Query order.
		$query_order = $args['order'] . ' ';

		// Query limit.
		$query_limit = '';

		if ( ! empty( $args['number'] ) ) {

			$query_limit = 'LIMIT ' . (int) trim( $args['number'] ) . ' ';

		}

		// Query offset.
		$query_offset = '';

		if ( ! empty( $args['offset'] ) ) {

			$query_offset = 'OFFSET ' . (int) trim( $args['offset'] ) . ' ';

		}

		$query_string .= $query_from . $query_where . $query_order_by . $query_order . $query_limit . $query_offset;
		$data_array = $wpdb->get_results( $wpdb->prepare( $query_string, 1 ), ARRAY_A );
		$subscriptions = array();

		foreach ( $data_array as $key => $data ) {
			$subscriptions[$key] = new PMS_Member_Subscription( $data );
		}
		/**
		 * Filter member subscriptions just before returning them.
		 *
		 * @param array $subscriptions - the array of returned member subscriptions from the db.
		 * @param array $args          - the arguments used to query the member subscriptions from the db.
		 */
		$subscriptions = apply_filters( 'pms_get_member_subscriptions', $subscriptions, $args );

		return $subscriptions;

	}


	/**
	 * Returns a member subscription object from the database by the given id.
	 * or null if no subscription is found.
	 *
	 * @param int $member_subscription_id THe subscription id.
	 *
	 * @return mixed
	 *
	 */
	public function pms_get_member_subscription( $member_subscription_id = 0 ) {

		global $wpdb;

		$result = $wpdb->get_row( 'SELECT * FROM {$wpdb->prefix}pms_member_subscriptions WHERE id = {$member_subscription_id}', ARRAY_A );

		if ( ! is_null( $result ) ) {
			$result = new PMS_Member_Subscription( $result );
		}
		return $result;

	}

	/**
	 * Function that returns all available member subscription statuses.
	 *
	 * @return array
	 */
	public function pms_get_member_subscription_statuses() {

		$statuses = array(
			'active'    => __( 'Active', 'paid-member-subscriptions' ),
			'canceled'  => __( 'Canceled', 'paid-member-subscriptions' ),
			'expired'   => __( 'Expired', 'paid-member-subscriptions' ),
			'pending'   => __( 'Pending', 'paid-member-subscriptions' ),
			'abandoned' => __( 'Abandoned', 'paid-member-subscriptions' ),
		);

		/**
		 * Filter to add/remove member subscription statuses.
		 *
		 * @param array $statuses.
		 */
		$statuses = apply_filters( 'pms_member_subscription_statuses', $statuses );

		return $statuses;

	}


	/**
	 * Returns the metadata for a given member subscription.
	 *
	 * @param int    $member_subscription_id Member subscription id.
	 * @param string $meta_key               The meta key in member that stores subscription id.
	 * @param bool   $single                 If single subscription.
	 * @return mixed - single metadata value | array of values.
	 */
	public function pms_get_member_subscription_meta( $member_subscription_id = 0, $meta_key = '', $single = false ) {

		return get_metadata( 'member_subscription', $member_subscription_id, $meta_key, $single );

	}


	/**
	 * Adds the metadata for a member subscription
	 *
	 * @param int    $member_subscription_id Subscription id.
	 * @param string $meta_key               The meta key in member that stores subscription id.
	 * @param string $meta_value             THe matching meta value.
	 * @param bool   $unique                 Is this key value unique.
	 * @return mixed - int | false
	 */
	public function pms_add_member_subscription_meta( $member_subscription_id = 0, $meta_key = '', $meta_value = '', $unique = false ) {
		return add_metadata( 'member_subscription', $member_subscription_id, $meta_key, $meta_value, $unique );
	}
	/**
	 * Updates the metadata for a member subscription
	 *
	 * @param int    $member_subscription_id Subscription id.
	 * @param string $meta_key               The meta key in member that stores subscription id.
	 * @param string $meta_value             THe matching meta value.
	 * @param string $prev_value             Previous meta value.
	 *
	 * @return mixed int /false
	 */
	public function pms_update_member_subscription_meta( $member_subscription_id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {
		return update_metadata( 'member_subscription', $member_subscription_id, $meta_key, $meta_value, $prev_value );
	}
	/**
	 * Deletes the metadata for a member subscription.
	 *
	 * @param int    $member_subscription_id  Subscription id.
	 * @param string $meta_key      Meta key.
	 * @param string $meta_value    Meta value.
	 * @param string $delete_all - If true, delete matching metadata entries for all member subscriptions, ignoring
	 *                             the specified member_subscription_id. Otherwise, only delete matching metadata
	 *                             entries for the specified member_subscription_id.
	 *
	 */
	public function pms_delete_member_subscription_meta( $member_subscription_id = 0, $meta_key = '', $meta_value = '', $delete_all = false ) {

		return delete_metadata( 'member_subscription', $member_subscription_id, $meta_key, $meta_value, $delete_all );

	}
	/**
	 * Adds log data to a given subscription.
	 *
	 * @param int    $member_subscription_id
	 * @param string $type
	 * @param array  $data
	 */
	public function pms_add_member_subscription_log( $member_subscription_id, $type, $data = array() ){

		if ( empty( $type ) ) {
			return false;
		}
		include plugin_dir_path( __FILE__ ) . 'class-pms-member-subscription.php';
		$subscription_logs = pms_get_member_subscription_meta( $member_subscription_id, 'logs', true );

		if ( empty( $subscription_logs ) ) {
			$subscription_logs = array();
		}
		$subscription_logs[] = array(
			'date' => date( 'Y-m-d H:i:s' ),
			'type' => $type,
			'data' => ! empty( $data ) ? $data : '',
		);

		$update_result = pms_update_member_subscription_meta( $member_subscription_id, 'logs', $subscription_logs );

		if ( false !== $update_result ) {
			$update_result = true;
		}
		// Save the abandon date as a subscription meta.
		if ( 'subscription_abandoned' == $type ) {
			include plugin_dir_path( __FILE__ ) . 'functions-member-subscriptions.php';
			pms_add_member_subscription_meta( $member_subscription_id, 'abandon_date', gmdate( 'Y-m-d H:i:s' ) );
		}
		return $update_result;

	}

	/**
	 * Retrieves the extra information like payment method type, last 4, expiration date when they are available.
	 * 
	 * @param int    $member_subscription_id Subscription id.
	 */
	public function pms_get_member_subscription_payment_method_details( $member_subscription_id ) {

		if ( empty( $member_subscription_id ) )
			return array();

		$data    = array();
		$targets = array( 'pms_payment_method_number', 'pms_payment_method_type', 'pms_payment_method_expiration_month', 'pms_payment_method_expiration_year' );

		foreach( $targets as $target ){
			$value = pms_get_member_subscription_meta( $member_subscription_id, $target, true );

			if ( !empty( $value ) )
				$data[ $target ] = $value;
		}

		return $data;

	}

	/**
	 * Cancels all member subscriptions for a user when the user is deleted
	 *
	 * @param int $user_id
	 *
	 */
	public function pms_member_delete_user_subscription_cancel( $user_id = 0 ) {

		if ( empty( $user_id ) )
			return;

		$member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => (int)$user_id ) );

		if ( empty( $member_subscriptions ) )
			return;

		foreach( $member_subscriptions as $member_subscription ) {

			if ( $member_subscription->status == 'active' ) {

				$member_subscription->update( array( 'status' => 'canceled' ) );
				do_action( 'pms_api_cancel_paypal_subscription', $member_subscription->payment_profile_id, $member_subscription->subscription_plan_id );
				apply_filters( 'pms_confirm_cancel_subscription', true, $user_id, $member_subscription->subscription_plan_id );

				pms_add_member_subscription_log( $member_subscription->id, 'subscription_canceled_user_deletion', array( 'who' => get_current_user_id() ) );

			}

		}

	}



	/**
	 * Function triggered by the cron job that checks for any expired subscriptions.
	 *
	 * Note 1: This function has been refactored due to slow performance. It would take all members and then
	 *         for each one of the subscription it would check to see if it was expired and if so, set the status
	 *         to expired.
	 * Note 2: The function now gets all active subscriptions without using the PMS_Member class and checks to see
	 *         if they have passed their expiration time and if so, sets the status to expire. Due to the fact that
	 *         the PMS_Member class is not used, the "pms_member_update_subscription" had to be added here also to
	 *         deal with further actions set on the hook
	 *
	 * @return void	 *
	 */
	public function pms_member_check_expired_subscriptions() {

		global $wpdb;

		/**
		 * This filter can be used to modify the delay when subscriptions are expired
		 * The value is a MySQL Interval
		 * 
		 * @since 2.6.9
		 */
		$delay = apply_filters( 'pms_check_expired_subscriptions_delay', 'INTERVAL 12 HOUR' );

		$subscriptions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pms_member_subscriptions WHERE ( status = 'active' OR status = 'canceled' ) AND expiration_date > '0000-00-00 00:00:00' AND expiration_date < DATE_SUB( NOW(), {$delay} )", ARRAY_A );

		if ( empty( $subscriptions ) ) {
			return;
		}
		foreach ( $subscriptions as $subscription ) {

			/**
			 * @since 2.8.5 Added status to where clause to only affect the desired subscription instead of reactivating 
			 *              abandoned subscriptions with the same plan
			 */
			$update_result = $wpdb->update( $wpdb->prefix . 'pms_member_subscriptions', array( 'status' => 'expired' ), array( 'user_id' => $subscription['user_id'], 'subscription_plan_id' => $subscription['subscription_plan_id'], 'status' => $subscription['status'] ) );

			pms_add_member_subscription_log( $subscription['id'], 'subscription_expired' );

			// Can return 0 if no data was changed.
			if ( $update_result !== false ) {
				$update_result = true;
			}
			if ( $update_result ) {

				/**
				 * Fires right after the Member Subscription db entry was updated.
				 *
				 * This action is the same as the one in the "update" method in PMS_Member_Subscription class.
				 *
				 * @param int   $id            - the id of the subscription that has been updated.
				 * @param array $data          - the array of values to be updated for the subscription.
				 * @param array $old_data      - the array of values representing the subscription before the update.
				 */
				do_action( 'pms_member_subscription_update', $subscription['id'], array( 'status' => 'expired' ), $subscription );
			}
		}
	}
}
// Initialize the plugin.
$coho_custom_login_pages = new coho_custom_login();
// Create the custom pages at plugin activation.
register_activation_hook( __FILE__, array( 'coho_custom_login', 'plugin_activated' ) );
