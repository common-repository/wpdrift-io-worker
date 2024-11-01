<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://wpdrift.io/
 * @since      1.0.0
 *
 * @package    WPdrift_Worker
 * @subpackage WPdrift_Worker/admin
 * @author     Support HQ <support@upnrunn.com>
 */

class WPdrift_Worker_Admin {

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
	 * Check incompatibility with WordPress version.
	 * @var [type]
	 */
	public function incompatibility_with_wp_version() {
		global $wp_version;

		/**
		 * Exit early.
		 * @var [type]
		 */
		if ( $wp_version >= 4.3 ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p>
				<?php _e( 'WPdrift IO - Worker requires that WordPress 4.4 or greater be used. Update to the latest WordPress version.', 'wpdrift-worker' ); ?>
				<a href="<?php echo admin_url( 'update-core.php' ); ?>">
					<?php _e( 'Update Now', 'wpdrift-worker' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * [verifiy_authenticity_of_plugin_core description]
	 * @return [type] [description]
	 */
	public function verifiy_authenticity_of_plugin_core() {

		/**
		 * Exit early.
		 * @var [type]
		 */
		if ( wpdrift_worker_is_dev() ) {
			return;
		}

		/**
		 * Looks good, exit.
		 * @var [type]
		 */
		if ( WPDRIFT_WORKER_CHECKSUM == strtoupper( md5_file( __FILE__ ) ) ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p><strong>You are at risk!</strong> WPdrift IO - Worker is not genuine. Please contact kishore@upnrunn.com immediately.</p>
		</div>
		<?php
	}

	/**
	 * [register_post_types description]
	 * @return [type] [description]
	 */
	public function register_post_types() {
		$labels = array(
			'name'               => _x( 'Client', 'post type general name', 'wpdrift-worker' ),
			'singular_name'      => _x( 'Client', 'post type singular name', 'wpdrift-worker' ),
			'menu_name'          => _x( 'Clients', 'admin menu', 'wpdrift-worker' ),
			'name_admin_bar'     => _x( 'Client', 'add new on admin bar', 'wpdrift-worker' ),
			'add_new'            => _x( 'Add New', 'Client', 'wpdrift-worker' ),
			'add_new_item'       => __( 'Add New BoClientok', 'wpdrift-worker' ),
			'new_item'           => __( 'New Client', 'wpdrift-worker' ),
			'edit_item'          => __( 'Edit Client', 'wpdrift-worker' ),
			'view_item'          => __( 'View Client', 'wpdrift-worker' ),
			'all_items'          => __( 'All Clients', 'wpdrift-worker' ),
			'search_items'       => __( 'Search Clients', 'wpdrift-worker' ),
			'parent_item_colon'  => __( 'Parent Clients:', 'wpdrift-worker' ),
			'not_found'          => __( 'No clients found.', 'wpdrift-worker' ),
			'not_found_in_trash' => __( 'No clients found in Trash.', 'wpdrift-worker' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => __( 'Description.', 'wpdrift-worker' ),
			'public'              => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'oauth_client' ),
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
		);

		register_post_type( 'oauth_client', $args );
	}

	/**
	 * [profile_update_delete_tokens description]
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	function profile_update_delete_tokens( $user_id ) {
		if ( ! isset( $_POST['pass1'] ) || '' == $_POST['pass1'] ) {
			return;
		}

		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}oauth_access_tokens", array( 'user_id' => $user_id ) );
		$wpdb->delete( "{$wpdb->prefix}oauth_refresh_tokens", array( 'user_id' => $user_id ) );
	}

	/**
	 * Invalidate any token and refresh tokens during password reset
	 *
	 * @param  object $user WP_User Object
	 *
	 * @return Void
	 *
	 * @since 1.0.0
	 */
	public function password_reset_delete_tokens( $user ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$wpdb->delete description]
		 * @var [type]
		 */
		$wpdb->delete( "{$wpdb->prefix}oauth_access_tokens", array( 'user_id' => $user->ID ) );
		$wpdb->delete( "{$wpdb->prefix}oauth_refresh_tokens", array( 'user_id' => $user->ID ) );
	}

}
