<?php
/**
 * REST API: WPdrift_Clients_Controller class
 *
 * @package WPdrift Worker
 * @subpackage WPdrift Worker/rest-api
 * @since 1.0.0
 */

/**
 * [WPdrift_Clients_Controller description]
 */
class WPdrift_Clients_Controller extends WP_REST_Controller {

	/**
	 * Here initialize our namespace and resource name.
	 */
	public function __construct() {
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'clients';
	}

	/**
	 * Register our routes.
	 * @return [type] [description]
	 */
	public function register_routes() {
		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		) );

		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route($this->namespace, '/' . $this->rest_base . '/token', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_token' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		));
	}

	/**
	 * Check permissions for the posts.
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_items_permissions_check( $request ) {
		/**
		 * [if description]
		 * @var [type]
		 */
		if ( ! in_array( $_SERVER['REMOTE_ADDR'], [ '159.89.224.161', '167.99.167.87' ] ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the resource.' ), array( 'status' => $this->authorization_status_code() ) );
		}

		return true;
	}

	/**
	 * Grabs the most recent users and outputs them as a rest response.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_items( $request ) {
		/**
		 * [$args description]
		 * @var array
		 */
		$args = array(
			'post_type' => 'oauth_client',
		);

		/**
		 * Get clients.
		 * @var [type]
		 */
		$clients = get_posts( $args );

		/**
		 * [$data description]
		 * @var array
		 */
		$data = array();

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( empty( $clients ) ) {
			return rest_ensure_response( $data );
		}

		/**
		 * [foreach description]
		 * @var [type]
		 */
		foreach ( $clients as $client ) {
			$response = $this->prepare_item_for_response( $client, $request );
			$data[]   = $this->prepare_response_for_collection( $response );
		}

		/**
		 * Return all of our comment response data.
		 * @var [type]
		 */
		return rest_ensure_response( $data );
	}

	/**
	 * [create_item description]
	 * @return [type] [description]
	 */
	public function create_item( $request ) {
		/**
		 * [$params description]
		 * @var [type]
		 */
		$params = $request->get_params();

		/**
		 * [$client_data description]
		 * @var [type]
		 */
		$client_data = $this->prepare_item_for_database( $request );

		/**
		 * [$client description]
		 * @var [type]
		 */
		$client = wp_insert_post( $client_data );
		if ( $client ) {
			$client_data['store_id'] = $params['sid'];
			return $client_data;
		}

		/**
		 * [return description]
		 * @var [type]
		 */
		return new WP_Error( 'error_creating_client', __( 'Error when creating client.', 'text-domain' ) );
	}

	/**
	 * [get_token description]
	 * @return [type] [description]
	 */
	public function get_token( $request ) {
		/**
		 * [$requested_token description]
		 * @var [type]
		 */
		$requested_token = $request['token'];
		$token           = wpdrift_worker_public_get_access_token( $requested_token );

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( ! $token ) {
			return rest_ensure_response( array() );
		}

		// Return all of our token response data.
		return $token;
	}

	/**
	 * Matches the post data to the schema we want.
	 *
	 * @param WP_Post $post The comment object whose response is being prepared.
	 */
	public function prepare_item_for_response( $client, $request ) {
		/**
		 * [$client_data description]
		 * @var array
		 */
		$client_data = array();

		/**
		 * [$client_data description]
		 * @var [type]
		 */
		$client_data['id']            = (int) $client->ID;
		$client_data['client_id']     = get_post_meta( $client->ID, 'client_id', true );
		$client_data['client_secret'] = get_post_meta( $client->ID, 'client_secret', true );
		$client_data['redirect_uri']  = get_post_meta( $client->ID, 'redirect_uri', true );

		/**
		 * [return description]
		 * @var [type]
		 */
		return rest_ensure_response( $client_data );
	}

	/**
	 * [prepare_item_for_database description]
	 * @return [type] [description]
	 */
	public function prepare_item_for_database( $request ) {
		/**
		 * [$params description]
		 * @var [type]
		 */
		$params = $request->get_params();

		/**
		 * [$client_data description]
		 * @var array
		 */
		$client_data = array(
			'post_title'     => $params['store_name'],
			'post_status'    => 'publish',
			'post_author'    => '1',
			'post_type'      => 'oauth_client',
			'comment_status' => 'closed',
			'meta_input'     => array(
				'client_id'     => wpdrift_worker_gen_key(),
				'client_secret' => wpdrift_worker_gen_key(),
				'grant_types'   => array(
					'authorization_code',
					'implicit',
					'password',
					'client_credentials',
					'refresh_token',
				),
				'redirect_uri'  => $params['return_url'],
				'user_id'       => '-1',
			),
		);

		/**
		 * [return description]
		 * @var [type]
		 */
		return $client_data;
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
