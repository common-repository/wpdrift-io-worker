<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://upnrunn.com
 * @since      1.0.0
 *
 * @package    WPdrift_IO
 * @subpackage WPdrift_IO/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WPdrift_IO
 * @subpackage WPdrift_IO/includes
 * @author     upnrunn <admin@upnrunn.com>
 */

class WPdrift_IO_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate( $network_wide ) {
		self::drop_db_tables();
		self::server_deactivation( $network_wide );
	}

	/**
	 * [drop_db_tables description]
	 * @return [type] [description]
	 */
	public static function drop_db_tables() {
		global $wpdb;
	}

	/**
	 * OAuth Server Deactivation
	 *
	 * @param  [type] $network_wide [description]
	 *
	 * @return [type]               [description]
	 */
	public static function server_deactivation( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
			$mu_blogs = wp_get_sites();
			foreach ( $mu_blogs as $mu_blog ) {
				switch_to_blog( $mu_blog['blog_id'] );
				flush_rewrite_rules();
			}
			restore_current_blog();
		} else {
			flush_rewrite_rules();
		}
	}

}
