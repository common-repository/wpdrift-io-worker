<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://wpdrift.io
 * @since      1.0.0
 *
 * @package    WPdrift_IO
 * @subpackage WPdrift_IO/public
 */

/**
 * [use description]
 * @var [type]
 */
use Models\Hit;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WPdrift_IO
 * @subpackage WPdrift_IO/public
 * @author     Your Name <email@example.com>
 */
class WPdrift_Worker_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WPdrift_IO_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WPdrift_IO_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpdrift-worker-public.js', array( 'jquery' ), $this->version, true );

		/**
		 * [$localize_script_data description]
		 * @var [type]
		 */
		$localize_script_data = [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		];

		wp_localize_script( $this->plugin_name, 'wpdrift_worker', $localize_script_data );
	}

}
