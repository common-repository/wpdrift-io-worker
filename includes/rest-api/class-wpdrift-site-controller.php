<?php
/**
 * REST API: WPdrift_Site_Controller class
 *
 * @package WPdrift IO
 * @subpackage REST_API
 * @since 1.0.0
 */

/**
 * [WPdrift_Site_Controller description]
 */
class WPdrift_Site_Controller extends WP_REST_Controller {

	/**
	 * Here initialize our namespace and resource name.
	 */
	public function __construct() {
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'site';
	}

	/**
	 * Register our routes.
	 * @return [type] [description]
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		) );

		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route($this->namespace, '/' . $this->rest_base . '/plugin-status', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_plugin_status' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		));

		/**
		 * check edd plugin status
		 * @var [type]
		 */
		register_rest_route($this->namespace, '/' . $this->rest_base . '/edd-plugin-status', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_edd_plugin_status' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		));
	}

	/**
	 * Grabs site details.
	 * @return [type] [description]
	 */
	public function get_items( $request ) {
		/**
		 * [$data description]
		 * @var array
		 */
		$data = array(
			'name'              => get_bloginfo( 'name' ),
			'description'       => get_bloginfo( 'description' ),
			'version'           => get_bloginfo( 'version' ),
			'url'               => get_bloginfo( 'url' ),
			'admin_email'       => get_bloginfo( 'admin_email' ),
			'language'          => get_bloginfo( 'language' ),
			'rss2_url'          => get_bloginfo( 'rss2_url' ),
			'comments_rss2_url' => get_bloginfo( 'comments_rss2_url' ),
			'admin_url'         => admin_url(),
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
		);

		/**
		 * [return description]
		 * @var [type]
		 */
		return rest_ensure_response( $data );
	}

	/**
	 * [public description]
	 * @var [type]
	 */
	public function get_plugin_status( $request ) {
		/**
		 * Detect plugin.
		 * @var [type]
		 */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// check for plugin using plugin name
		if ( ! is_plugin_active( 'wpdrift-io-worker/wpdrift-worker.php' ) ) {
			return rest_ensure_response( array() );
		}

		/**
		 * [return description]
		 * @var [type]
		 */
		return [ 'version' => WPDRIFT_WORKER_VERSION ];
	}

	/**
	 * get the edd plugin status
	 * @var [type]
	 */
	public function get_edd_plugin_status( $request ) {
		/**
		 * Detect plugin.
		 * @var [type]
		 */
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// check for plugin using plugin name
		if ( ! is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
			return rest_ensure_response( array() );
		}

		/**
		 * [return description]
		 * @var [type]
		 */
		return [ 'version' => EDD_VERSION ];
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
