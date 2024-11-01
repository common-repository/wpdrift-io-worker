<?php
/**
 * WordPress OAuth Server Main Class
 * Responsible for being the main handler
 *
 * @author Justin Greer <justin@justin-greer.com>
 * @package WordPress OAuth Server
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class WPdrift_Worker {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WPdrift_IO_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/** Environment Type  */
	public $env = 'production';

	/** Default Settings */
	public $defualt_settings = [
		'enabled'                    => 1,
		'client_id_length'           => 30,
		'auth_code_enabled'          => 0,
		'client_creds_enabled'       => 0,
		'user_creds_enabled'         => 0,
		'refresh_tokens_enabled'     => 0,
		'jwt_bearer_enabled'         => 0,
		'implicit_enabled'           => 0,
		'require_exact_redirect_uri' => 0,
		'enforce_state'              => 0,
		'refresh_token_lifetime'     => 63072000, // 2 Year
		'access_token_lifetime'      => 31536000, // 1 Year
		'use_openid_connect'         => 0,
		'id_token_lifetime'          => 3600,
		'token_length'               => 40,
		'beta'                       => 0,
	];

	/** Server Instance */
	public static $_instance = null;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	function __construct() {
		if ( defined( 'WPDRIFT_WORKER_VERSION' ) ) {
			$this->version = WPDRIFT_WORKER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wpdrift-worker';

		if ( ! defined( 'WPDRIFT_WORKER_CHECKSUM' ) ) {
			define( 'WPDRIFT_WORKER_CHECKSUM', 'C5E89E4705DD6CAE770B289A43F2D459' );
		}

		/**
		 * [$this->load_dependencies description]
		 * @var [type]
		 */
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_oauth_hooks();
		$this->define_api_hooks();

		if ( function_exists( '__autoload' ) ) {
			spl_autoload_register( '__autoload' );
		}
		spl_autoload_register( array( $this, 'autoload' ) );

		add_filter( 'determine_current_user', array( $this, '_wpdrift_worker_authenicate_bypass' ), 9999 );

	}

	/**
	 * [load_dependencies description]
	 * @return [type] [description]
	 */
	private function load_dependencies() {

		/**
		 * Load dependecies managed by composer.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

		/**
		 * [require_once description]
		 * @var [type]
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/functions.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/rest-api/functions.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpdrift-worker-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpdrift-worker-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpdrift-worker-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wpdrift-worker-public.php';

		/**
		 * The class responsible for defining all actions that occur in the oauth-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'oauth/class-wpdrift-worker-oauth.php';

		/**
		 * [require_once description]
		 * @var [type]
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpdrift-worker-date-query.php';

		/**
		 * The class responsible for defining all actions that occur in the api-facing
		 * @var [type]
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpdrift-worker-api.php';

		$this->loader = new WPdrift_IO_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WPdrift_IO_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new WPdrift_IO_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WPdrift_Worker_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'incompatibility_with_wp_version' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'verifiy_authenticity_of_plugin_core' );
		$this->loader->add_action( 'init', $plugin_admin, 'register_post_types' );
		$this->loader->add_action( 'profile_update', $plugin_admin, 'profile_update_delete_tokens' );
		$this->loader->add_action( 'password_reset', $plugin_admin, 'password_reset_delete_tokens' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new WPdrift_Worker_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the oauth-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_oauth_hooks() {

		$plugin_oauth = new WPdrift_Worker_Oauth( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $plugin_oauth, 'server_register_query_vars' );
		$this->loader->add_action( 'wpdrift_worker_set_access_token', $plugin_oauth, 'only_allow_one_access_token' );
		$this->loader->add_filter( 'template_include', $plugin_oauth, 'server_template_redirect_intercept', 100 );
		$this->loader->add_filter( 'wpdrift_worker_endpoints', $plugin_oauth, 'server_default_endpoints', 1 );

	}

	/**
	 * Register all of the hooks related to the api-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_api_hooks() {

		$plugin_api = new WPdrift_Worker_Api( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'rest_index', $plugin_api, 'register_server_routes' );
		$this->loader->add_action( 'rest_api_init', $plugin_api, 'register_rest_routes' );
		$this->loader->add_action( 'rest_api_init', $plugin_api, 'user_meta_fields' );

		$this->loader->add_filter( 'rest_user_collection_params', $plugin_api, 'rest_user_collection_params' );
		$this->loader->add_filter( 'rest_user_query', $plugin_api, 'rest_user_query', 10, 2 );

	}

	/**
	 * Awesomeness for 3rd party support
	 *
	 * Filter; determine_current_user
	 * Other Filter: check_authentication
	 *
	 * This creates a hook in the determine_current_user filter that can check for a valid access_token
	 * and user services like WP JSON API and WP REST API.
	 *
	 * @param  [type] $user_id User ID to
	 *
	 * @author Mauro Constantinescu Modified slightly but still a contribution to the project.
	 *
	 * @return void
	 */
	public function _wpdrift_worker_authenicate_bypass( $user_id ) {
		if ( $user_id && $user_id > 0 ) {
			return (int) $user_id;
		}

		if ( wpdrift_worker_setting( 'enabled' ) == 0 ) {
			return (int) $user_id;
		}

		require_once( dirname( WPDRIFT_WORKER_FILE ) . '/oauth/OAuth2/Autoloader.php' );
		OAuth2\Autoloader::register();
		$server  = new OAuth2\Server( new OAuth2\Storage\Wordpressdb() );
		$request = OAuth2\Request::createFromGlobals();
		if ( $server->verifyResourceRequest( $request ) ) {
			$token = $server->getAccessTokenData( $request );
			if ( isset( $token['user_id'] ) && $token['user_id'] > 0 ) {
				return (int) $token['user_id'];
			}
		}

		return null;
	}

	/**
	 * populate the instance if the plugin for extendability
	 *
	 * @return object plugin instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * setup plugin class autoload
	 *
	 * @return void
	 */
	public function autoload( $class ) {
		$path  = null;
		$class = strtolower( $class );
		$file  = 'class-' . str_replace( '_', '-', $class ) . '.php';

		if ( strpos( $class, 'wpdrift_worker_' ) === 0 ) {
			$path = plugin_dir_path( dirname( __FILE__ ) ) . '/oauth/' . trailingslashit( substr( str_replace( '_', '-', $class ), 18 ) );
		}

		if ( $path && is_readable( $path . $file ) ) {
			include_once $path . $file;

			return;
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WPdrift_IO_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

function _wpdw() {
	return WPdrift_Worker::instance();
}
