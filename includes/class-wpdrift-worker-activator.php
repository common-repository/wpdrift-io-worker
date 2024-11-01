<?php

/**
 * Fired during plugin activation
 *
 * @link       https://upnrunn.com
 * @since      1.0.0
 *
 * @package    WPdrift_IO
 * @subpackage WPdrift_IO/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WPdrift_IO
 * @subpackage WPdrift_IO/includes
 * @author     upnrunn <admin@upnrunn.com>
 */

class WPdrift_IO_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate( $network_wide ) {
		self::setup();
		self::oauth_db();
		self::oauth_db_upgrade();
		self::server_activation( $network_wide );
	}

	/**
	 * OAuth2 Server Activation
	 *
	 * @param  [type] $network_wide [description]
	 *
	 * @return [type]               [description]
	 */
	public function server_activation( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
			$mu_blogs = wp_get_sites();
			foreach ( $mu_blogs as $mu_blog ) {
				switch_to_blog( $mu_blog['blog_id'] );
				wpdrift_worker_server_register_rewrites();
				flush_rewrite_rules();
			}
			restore_current_blog();
		} else {
			wpdrift_worker_server_register_rewrites();
			flush_rewrite_rules();
		}
	}

	/**
	 * plugin setup. this is only ran on activation
	 *
	 * @return [type] [description]
	 */
	public function setup() {
		$options = get_option( 'wpdrift_worker_options' );
		if ( ! isset( $options['enabled'] ) ) {
			update_option( 'wpdrift_worker_options', _wpdw()->defualt_settings );
		}
	}

	/**
	 * Upgrade method
	 */
	public function oauth_db_upgrade() {

		// Fix
		// https://github.com/justingreerbbi/wp-oauth-server/issues/7
		// https://github.com/justingreerbbi/wp-oauth-server/issues/3
		// And other known issues with increasing the token length
		global $wpdb;
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}oauth_refresh_tokens MODIFY refresh_token VARCHAR(100);" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}oauth_refresh_tokens MODIFY client_id VARCHAR(100);" );

		$wpdb->query( "ALTER TABLE {$wpdb->prefix}oauth_clients MODIFY client_id VARCHAR(100);" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}oauth_clients MODIFY client_secret VARCHAR(100);" );

		$wpdb->query( "ALTER TABLE {$wpdb->prefix}oauth_public_keys MODIFY client_id VARCHAR(100);" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}oauth_jwt MODIFY client_id VARCHAR(100);" );
		$wpdb->query( "ALTER TABLE {$wpdb->prefix}oauth_authorization_codes MODIFY client_id VARCHAR(100);" );

		/**
		 * Update the clients and import then into the CPT format
		 *
		 * 1. Check if the clients table exists - Yes = Step 2
		 * 2. Query the clients table and return all the clients
		 *
		 */
		global $wpdb;
		$check_clients_table = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}oauth_clients' " );
		if ( $check_clients_table ) {
			$clients = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}oauth_clients " );

			$grant_types = array(
				'authorization_code',
				'implicit',
				'password',
				'client_credentials',
				'refresh_token',
			);

			foreach ( $clients as $client ) {
				$client_data = array(
					'post_title'     => $client->name,
					'post_status'    => 'publish',
					'post_author'    => get_current_user_id(),
					'post_type'      => 'oauth_client',
					'comment_status' => 'closed',
					'meta_input'     => array(
						'client_id'     => $client->client_id,
						'client_secret' => $client->client_secret,
						'grant_types'   => $grant_types,
						'redirect_uri'  => $client->redirect_uri,
						'user_id'       => $client->user_id,
					),
				);

				wp_insert_post( $client_data );
			}
		}

		// DELETE OLD CLIENTS TABLE
		$wpdb->query( "DROP TABLE {$wpdb->prefix}oauth_clients" );
	}

	/**
	 * plugin update check
	 *
	 * @return [type] [description]
	 */
	public function oauth_db() {

		global $wpdb;
		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		update_option( 'wpdrift_helper_version', WPDRIFT_WORKER_VERSION );
		$sql1 = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_clients (
			id 					  INT 			UNSIGNED NOT NULL AUTO_INCREMENT,
			client_id             VARCHAR(255)	NOT NULL UNIQUE,
			client_secret         VARCHAR(255)  NOT NULL,
			redirect_uri          VARCHAR(2000),
			grant_types           VARCHAR(80),
			scope                 VARCHAR(4000),
			user_id               VARCHAR(80),
			name                  VARCHAR(80),
			description           LONGTEXT,
			PRIMARY KEY (id)
			  );
		";

		$sql2 = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_access_tokens (
			id					 INT 			UNSIGNED NOT NULL AUTO_INCREMENT,
			access_token         VARCHAR(255) 	NOT NULL UNIQUE,
			client_id            VARCHAR(255)	NOT NULL,
			user_id              VARCHAR(80),
			expires              TIMESTAMP      NOT NULL,
			scope                VARCHAR(4000),
			PRIMARY KEY (id)
			  );
		";

		$sql3 = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_refresh_tokens (
			refresh_token       VARCHAR(255)    NOT NULL UNIQUE,
			client_id           VARCHAR(255)    NOT NULL,
			user_id             VARCHAR(80),
			expires             TIMESTAMP      	NOT NULL,
			scope               VARCHAR(4000),
			PRIMARY KEY (refresh_token)
			  );
		";

		$sql4 = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_authorization_codes (
			authorization_code  VARCHAR(255)    NOT NULL UNIQUE,
			client_id           VARCHAR(1000)   NOT NULL,
			user_id             VARCHAR(80),
			redirect_uri        VARCHAR(2000),
			expires             TIMESTAMP      	NOT NULL,
			scope               VARCHAR(4000),
			id_token            VARCHAR(3000),
			PRIMARY KEY (authorization_code)
			  );
		";

		$sql5 = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_scopes (
			id					INT 		 UNSIGNED NOT NULL AUTO_INCREMENT,
			scope               VARCHAR(80)  NOT NULL,
			is_default          BOOLEAN,
			PRIMARY KEY (id)
			  );
		";

		$sql6 = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_jwt (
			client_id           VARCHAR(255)  NOT NULL UNIQUE,
			subject             VARCHAR(80),
			public_key          VARCHAR(2000) NOT NULL,
			PRIMARY KEY (client_id)
			  );
		";

		$sql7 = "
			CREATE TABLE IF NOT EXISTS {$wpdb->prefix}oauth_public_keys (
			client_id            VARCHAR(255) NOT NULL UNIQUE,
			public_key           VARCHAR(2000),
			private_key          VARCHAR(2000),
			encryption_algorithm VARCHAR(100) DEFAULT 'RS256',
			PRIMARY KEY (client_id)
			  );
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql1 );
		dbDelta( $sql2 );
		dbDelta( $sql3 );
		dbDelta( $sql4 );
		dbDelta( $sql5 );
		dbDelta( $sql6 );
		dbDelta( $sql7 );

		/**
		 * Create certificates for signing
		 *
		 * @todo Add pure PHP library to handle openSSL functionality if the server does not support it.
		 */
		if ( function_exists( 'openssl_pkey_new' ) ) {

			if ( ! self::has_certificates() ) {
				$res = openssl_pkey_new( array(
					'private_key_bits' => 2048,
					'private_key_type' => OPENSSL_KEYTYPE_RSA,
				) );
				openssl_pkey_export( $res, $privKey );
				file_put_contents( WPDRIFT_WORKER_PATH . 'oauth/keys/private_key.pem', $privKey );

				$pubKey = openssl_pkey_get_details( $res );
				$pubKey = $pubKey['key'];
				file_put_contents( WPDRIFT_WORKER_PATH . 'oauth/keys/public_key.pem', $pubKey );

				// Update plugin version
				$plugin_data    = get_plugin_data( WPDRIFT_WORKER_FILE );
				$plugin_version = $plugin_data['Version'];
				update_option( 'wpdrift_helper_version', $plugin_version );
			}

		}

	}

	/**
	 * Retireve the server keys location
	 *
	 * @return array
	 */
	public function get_server_certs() {
		$keys = apply_filters( 'wpdrift_worket_server_keys', array(
			'public'  => WPDRIFT_WORKER_PATH . 'oauth/keys/public_key.pem',
			'private' => WPDRIFT_WORKER_PATH . 'oauth/keys/private_key.pem',
		) );

		return $keys;
	}

	/**
	 * Check to see if there is certificates that have been generated
	 *
	 * @return boolean [description]
	 */
	public function has_certificates() {
		$keys = self::get_server_certs();

		if ( is_array( $keys ) ) {
			foreach ( $keys as $key ) {
				if ( ! file_exists( $key ) ) {
					return false;
				}
			}

			return true;
		} else {

			return false;
		}
	}

}
