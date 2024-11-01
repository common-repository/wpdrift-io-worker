<?php

/**
 * The api-specific functionality of the plugin.
 *
 * @link       http://wpdrift.io/
 * @since      1.0.0
 *
 * @package    WPdrift_Worker
 * @subpackage WPdrift_Worker/includes
 * @author     Support HQ <support@upnrunn.com>
 */

class WPdrift_Worker_Api {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Adds OAuth2 to the WP-JSON index
	 *
	 * @param $response_object
	 *
	 * @return mixed
	 */
	public function register_server_routes( $response_object ) {

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( empty( $response_object->data['authentication'] ) ) {
			$response_object->data['authentication'] = [];
		}

		/**
		 * [$response_object->data description]
		 * @var [type]
		 */
		$response_object->data['authentication']['oauth2'] = [
			'authorize' => site_url( 'oauth/authorize' ),
			'token'     => site_url( 'oauth/token' ),
			'me'        => site_url( 'oauth/me' ),
			'version'   => '2.0',
			'software'  => 'WPdrift IO - Worker',
		];

		/**
		 * [return description]
		 * @var [type]
		 */
		return $response_object;
	}

	/**
	 * Function to register our new routes from the controller.
	 * @return [type] [description]
	 */
	public function register_rest_routes() {
		/**
		 * [require_once description]
		 * @var [type]
		 */
		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/class-wpdrift-site-controller.php' );
		$site_controller = new WPdrift_Site_Controller();
		$site_controller->register_routes();

		/**
		 * [require_once description]
		 * @var [type]
		 */
		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/class-wpdrift-dashboard-controller.php' );
		$dashboard_controller = new WPdrift_Dashboard_Controller();
		$dashboard_controller->register_routes();

		/**
		 * [require_once description]
		 * @var [type]
		 */
		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/class-wpdrift-clients-controller.php' );
		$clients_controller = new WPdrift_Clients_Controller();
		$clients_controller->register_routes();

		/**
		 * [require_once description]
		 * @var [type]
		 */
		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/class-wpdrift-users-controller.php' );
		$users_controller = new WPdrift_Users_Controller();
		$users_controller->register_routes();

		/**
		 * Register new recent events end points
		 * @var [type]
		 */
		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/class-wpdrift-events-controller.php' );
		$events_controller = new WPdrift_Events_Controller();
		$events_controller->register_routes();

		/**
		 * Register new recent events end points
		 * @var [type]
		 */
		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/class-wpdrift-statistics-controller.php' );
		$statistics_controller = new WPdrift_Statistics_Controller();
		$statistics_controller->register_routes();

		/**
		 * Detect EDD plugin. Then add edd all api end points
		 */
		if ( in_array( 'easy-digital-downloads/easy-digital-downloads.php', (array) get_option( 'active_plugins', array() ) ) ) {
			require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/edd/edd-end-points.php' );
		}

		/**
		 * New rest end points for users listing
		 * @var [type]
		 */
		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/includes/rest-api/class-wpdrift-rest-users-controller.php' );
		$wpdrift_user_list = new WPdrift_Users_List_Controller();
		$wpdrift_user_list->registerRoutes();

	}

	/**
	 * [rest_user_collection_params description]
	 * @param  [type] $query_params [description]
	 * @return [type]               [description]
	 */
	public function rest_user_collection_params( $query_params ) {
		$query_params['after'] = array(
			'description' => __( 'Limit response to users registered after a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['before'] = array(
			'description' => __( 'Limit response to users registered before a given ISO8601 compliant date.' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		return $query_params;
	}

	/**
	 * [rest_user_query description]
	 * @param  [type] $prepared_args [description]
	 * @param  [type] $request       [description]
	 * @return [type]                [description]
	 */
	public function rest_user_query( $prepared_args, $request ) {
		$prepared_args['date_query'] = array();

		if ( isset( $request['before'] ) ) {
			$prepared_args['date_query'][0]['before'] = $request['before'];
		}

		if ( isset( $request['after'] ) ) {
			$prepared_args['date_query'][0]['after'] = $request['after'];
		}

		return $prepared_args;
	}

	/**
	 * [user_meta_fields description]
	 * @return [type] [description]
	 */
	public function user_meta_fields() {
		register_rest_field(
			'user',
			'ip_data',
			array(
				'get_callback' => 'wpdrift_worker_get_user_ip_location_data',
				'schema'       => null,
			)
		);

		register_rest_field(
			'user',
			'last_login',
			array(
				'get_callback' => 'wpdrift_worker_get_user_last_login',
				'schema'       => null,
			)
		);

		register_rest_field(
			'user',
			'has_avatar',
			array(
				'get_callback' => 'wpdrift_worker_get_check_user_avatar',
				'schema'       => null,
			)
		);

		// user registered date formatted
		register_rest_field(
			'user',
			'joined_date',
			array(
				'get_callback' => 'wpdrift_worker_get_user_joined_date',
				'schema'       => null,
			)
		);
		// user total comments, posts, pages count
		register_rest_field(
			'user',
			'posted_content_count',
			array(
				'get_callback' => 'wpdrift_worker_get_user_posted_content_count',
				'schema'       => null,
			)
		);
	}

}
