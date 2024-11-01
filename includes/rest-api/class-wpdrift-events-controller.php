<?php
/**
 * WD_RecentEvents_Endpoint class
 */

defined('ABSPATH') || exit;

/**
 * RecentEvents endpoints.
 *
 * @since 1.0.0
 */
class WPdrift_Events_Controller extends WP_REST_Controller
{
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'events';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes()
	{
		register_rest_route($this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			)
		));
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items($request)
	{
		$parameters = $request->get_params();
		$items = array();
		$items['recent_events'] = $this->retrieve_recent_events($parameters);
		$data = array();
		foreach ($items as $key => $item) {
			$itemdata = $this->prepare_item_for_response($item, $request);
			$data[$key] = $this->prepare_response_for_collection($itemdata);
		}

		return rest_ensure_response($data);
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response($item, $request)
	{
		return $item;
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check($request)
	{
		/**
		 * [if description]
		 * @var [type]
		 */
		if ( ! current_user_can( 'list_users' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the resource.' ), array( 'status' => $this->authorization_status_code() ) );
		}

		return true;
	}


	/**
	* Retrieve Recent Sigup Users
	*
	* @since 1.0.0
	*/
	public function retrieve_recent_events($parameters)
	{
		$type = (isset($parameters['type']) && trim($parameters['type']) != "") ? trim($parameters['type']) : "all";
		global $wpdb;
		$last_ten_uids = $wpdb->get_results("SELECT ID FROM $wpdb->users ORDER BY ID DESC LIMIT 25");
		$users_ids = array();
		foreach ($last_ten_uids as $last_ten_uid) {
			$users_ids[] = $last_ten_uid->ID;
		}
		$args = array(
		  'include' => $users_ids
		);

		$users = get_users($args);
		if ($type == "comment") {
			$users = array();
		}
		// Get recent 25 comments
		$args_cmts = array(
			'orderby' => array('comment_date'),
			'order' => 'DESC',
			'number' => 25
		);

		$comments = get_comments($args_cmts);
		if ($type == "user") {
			$comments = array();
		}

		$recent_events_raw = array_merge($comments, $users);
		$i = 0;
		$recent_events = array();
		foreach ($recent_events_raw as $recent_event) {
			if ($recent_event != null) {
				if ($recent_event->ID != "") {
					$key_for_sort = strtotime($recent_event->data->user_registered);
					// User Sign Up
					$recent_events[$key_for_sort]['event_type'] = 'signup';
					$recent_events[$key_for_sort]['event_id'] = $recent_event->ID;
					$recent_events[$key_for_sort]['user_display_name'] = $this->get_display_name_by_id($recent_event->ID);
					$recent_events[$key_for_sort]['event_date'] = $this->get_event_date($recent_event->data->user_registered);
					$recent_events[$key_for_sort]['user_avatar'] = $this->get_user_avatar_by_email($recent_event->data->user_email);
					$recent_events[$key_for_sort]['user_id'] = $recent_event->ID;
				}
				if ($recent_event->comment_ID != "") {
					// Comment
					$key_for_sort = strtotime($recent_event->comment_date);
					$recent_events[$key_for_sort]['event_type'] = 'comment';
					$recent_events[$key_for_sort]['event_id'] = $recent_event->comment_ID;
					$recent_events[$key_for_sort]['user_display_name'] = $this->get_display_name_by_id($recent_event->user_id);
					$recent_events[$key_for_sort]['event_date'] = $this->get_event_date($recent_event->comment_date);
					$recent_events[$key_for_sort]['user_avatar'] = $this->get_user_avatar_by_email($recent_event->comment_author_email);
					$recent_events[$key_for_sort]['user_id'] = $recent_event->user_id;
				}
				$i++;
			}
		}
		// Sort array by ksort
		krsort($recent_events, 1);
		$new_events = array();
		$k = 0;
		foreach ($recent_events as $recent_event) {
			$new_events[$k]['event_type'] = $recent_event['event_type'];
			$new_events[$k]['event_id'] = $recent_event['event_id'];
			$new_events[$k]['user_display_name'] = $recent_event['user_display_name'];
			$new_events[$k]['event_date'] = $recent_event['event_date'];
			$new_events[$k]['user_avatar'] = $recent_event['user_avatar'];
			$new_events[$k]['user_id'] = $recent_event['user_id'];
			$k++;
		}

		return $new_events;
	}

	// get user display name by id
	public function get_display_name_by_id($id)
	{
		$first_name = get_user_meta($id, 'first_name', true);
		$last_name = get_user_meta($id, 'last_name', true);
		$user_name = "";
		if ($first_name == "" && $last_name == "") {
			// get display name of user
			$user_name = get_the_author_meta('display_name', $id);
		} else {
			$user_name = $first_name . " " . $last_name;
		}
		return $user_name;
	}
	// get user avatar by applied $email_address
	public function get_user_avatar_by_email($email_address)
	{
		if (wpdrift_worker_has_gravatar($email_address)) {
			$user_avatar = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email_address))) . '?s=48&d=404';
			;
		} else {
			$user_avatar = "";
		}
		return $user_avatar;
	}
	// get event date by supplied date and format it and return
	public function get_event_date($supplied_date)
	{
		return human_time_diff(strtotime($supplied_date), current_time('timestamp')) . " " . __('ago');
	}

	/**
	 * Sets up the proper HTTP status code for authorization.
	 * @return [type] [description]
	 */
	public function authorization_status_code() {

		$status = 401;

		if ( is_user_logged_in() ) {
			$status = 403;
		}

		return $status;
	}
}
