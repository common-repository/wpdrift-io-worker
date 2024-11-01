<?php
/**
 * WordPress OAuth Main Functions File
 *
 * @version 3.2.0 (IMPORTANT)
 *
 * Modifying this file will cause the plugin to crash. This could also result in the the entire WordPress install
 * to become unstable. This file is considered sensitive and thus we have provided simple protection against file
 * manipulation.
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Registers rewrites for OAuth2 Server
 *
 * - authorize
 * - token
 * - .well-known
 * - wpoauthincludes
 *
 * @return void
 */
function wpdrift_worker_server_register_rewrites() {
	add_rewrite_rule( '^oauth/(.+)', 'index.php?oauth=$matches[1]', 'top' );
}

/**
 * Token Introspection
 * Since spec call for the response to return even with an invalid token, this method
 * will be set to public.
 * @since 1.0.0
 *
 * @param null $token
 */
function _wpdrift_worker_method_introspection( $token = null ) {
	$access_token = &$token['access_token'];

	$request = OAuth2\Request::createFromGlobals();

	if ( strtolower( @$request->server['REQUEST_METHOD'] ) != 'post' ) {
		$response = new OAuth2\Response();
		$response->setError(
			405,
			'invalid_request',
			'The request method must be POST when calling the introspection endpoint.',
			'https://tools.ietf.org/html/rfc7662#section-2.1'
		);
		$response->addHttpHeaders( array( 'Allow' => 'POST' ) );
		$response->send();
	}

	// Check if the token is valid
	$valid = wpdrift_worker_public_get_access_token( $access_token );
	if ( false == $valid ) {
		$response = new OAuth2\Response( array(
			'active' => false,
		) );
		$response->send();
	}

	if ( $valid['user_id'] != 0 || ! is_null( $valid['user_id'] ) ) {
		$user     = get_userdata( $valid['user_id'] );
		$username = $user->user_login;
	}
	$introspection = apply_filters( 'wpdrift_worker_introspection_response', array(
		'active'    => true,
		'scope'     => $valid['scope'],
		'client_id' => $valid['client_id']
	) );
	$response      = new OAuth2\Response( $introspection );
	$response->send();

	exit;
}

/**
 * DEFAULT DESTROY METHOD
 * This method has been added to help secure installs that want to manually destroy sessions (valid access tokens).
 * @since  1.0.0
 *
 * @param null $token
 */
function _wpdrift_worker_method_destroy( $token = null ) {
	$access_token = &$token['access_token'];

	global $wpdb;
	$stmt = $wpdb->delete( "{$wpdb->prefix}oauth_access_tokens", array( 'access_token' => $access_token ) );

	/** If there is a refresh token we need to remove it as well. */
	if ( ! empty( $_REQUEST['refresh_token'] ) ) {
		$stmt = $wpdb->delete( "{$wpdb->prefix}oauth_refresh_tokens", array( 'refresh_token' => $_REQUEST['refresh_token'] ) );
	}

	/** Prepare the return */
	$response = new OAuth2\Response( array(
		'status'      => true,
		'description' => __( 'Session destroyed successfully', 'wpdrift-worker' ),
	) );
	$response->send();
	exit;
}

/**
 * DEFAULT ME METHOD - DO NOT REMOVE DIRECTLY
 * This is the default resource call "/oauth/me". Do not edit or remove.
 *
 * @param null $token
 */
function _wpdrift_worker_method_me( $token = null ) {

	if ( ! isset( $token['user_id'] ) || 0 == $token['user_id'] ) {
		$response = new OAuth2\Response();
		$response->setError(
			400,
			'invalid_request',
			'Invalid token',
			'https://tools.ietf.org/html/draft-ietf-oauth-v2-31#section-7.2'
		);
		$response->send();
		exit;
	}

	$user    = get_user_by( 'id', $token['user_id'] );
	$me_data = (array) $user->data;

	unset( $me_data['user_pass'] );
	unset( $me_data['user_activation_key'] );
	unset( $me_data['user_url'] );

	/**
	 * @since  1.0.0
	 * OpenID Connect looks for the field "email".asd
	 * Sooooo. We shall provide it. (at least for Moodle)
	 */
	$me_data['email'] = $me_data['user_email'];

	/**
	 * user information returned by the default me method is filtered
	 * @since 1.0.0
	 * @filter wpdrift_worker_me_resource_return
	 */
	$me_data = apply_filters( 'wpdrift_worker_me_resource_return', $me_data );

	$response = new OAuth2\Response( $me_data );
	$response->send();
	exit;
}

/**
 * [wpdrift_worker_create_client description]
 *
 * @param  [type] $user [description]
 *
 * @return [type]       [description]
 *
 * @todo Add role and permissions check
 */
function wpdrift_worker_insert_client( $client_data = null ) {

	// @todo Look into changing capabilities to create_clients after proper mapping has been done
	if ( ! current_user_can( 'manage_options' ) || is_null( $client_data ) || has_a_client() ) {
		exit( 'Not Allowed' );

		return false;
	}

	do_action( 'wpdrift_worker_before_create_client', array( $client_data ) );

	// Generate the keys
	$client_id     = wpdrift_worker_gen_key();
	$client_secret = wpdrift_worker_gen_key();

	$grant_types = isset( $client_data['grant_types'] ) ? $client_data['grant_types'] : array();

	$client = array(
		'post_title'     => wp_strip_all_tags( $client_data['name'] ),
		'post_status'    => 'publish',
		'post_author'    => get_current_user_id(),
		'post_type'      => 'oauth_client',
		'comment_status' => 'closed',
		'meta_input'     => array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_types'   => $grant_types,
			'redirect_uri'  => $client_data['redirect_uri'],
			'user_id'       => $client_data['user_id'],
			'scope'         => $client_data['scope'],
		),
	);

	// Insert the post into the database
	$client_insert = wp_insert_post( $client );
	if ( is_wp_error( $client_insert ) ) {
		exit( $client_insert->get_error_message() );
	}

	/**
	 * [return description]
	 * @var [type]
	 */
	return $client_insert;
}

/**
 * Update a client
 *
 * @param null $client
 *
 * @return false|int|void
 */
function wpdrift_worker_update_client( $client = null ) {
	if ( is_null( $client ) ) {
		return;
	}

	$client_data = array(
		'ID'         => $client['edit_client'],
		'post_title' => $client['name'],
	);

	wp_update_post( $client_data, true );

	$grant_types = isset( $client['grant_types'] ) ? $client['grant_types'] : array();
	update_post_meta( $client['edit_client'], 'client_id', $client['client_id'] );
	update_post_meta( $client['edit_client'], 'client_secret', $client['client_secret'] );
	update_post_meta( $client['edit_client'], 'grant_types', $grant_types );
	update_post_meta( $client['edit_client'], 'redirect_uri', $client['redirect_uri'] );
	update_post_meta( $client['edit_client'], 'user_id', $client['user_id'] );
	update_post_meta( $client['edit_client'], 'scope', $client['scope'] );
}

/**
 * Get a client by client ID
 *
 * @param $client_id
 */
function wpdrift_worker_get_client_by_client_id( $client_id ) {
	$query   = new \WP_Query();
	$clients = $query->query(array(
		'post_type'   => 'oauth_client',
		'post_status' => 'any',
		'meta_query'  => array(
			array(
				'key'   => 'client_id',
				'value' => $client_id,
			),
		),
	));

	/**
	 * [if description]
	 * @var [type]
	 */
	if ( $clients ) {
		$client                = $clients[0];
		$client->client_secret = get_post_meta( $client->ID, 'client_secret', true );
		$client->redirect_uri  = get_post_meta( $client->ID, 'redirect_uri', true );
		$client->grant_types   = get_post_meta( $client->ID, 'grant_types', true );
		$client->user_id       = get_post_meta( $client->ID, 'user_id', true );
		$client->scope         = get_post_meta( $client->ID, 'scope', true );
		$client->meta          = get_post_meta( $client->ID );

		return (array) $client;
	}
}

/**
 * Retrieve a client from the database
 *
 * @param null $id
 *
 * @return array|null|object|void
 */
function wpdrift_worker_get_client( $id = null ) {
	if ( is_null( $id ) ) {
		return;
	}

	global $wpdb;

	$client = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}posts WHERE ID = %s", array( $id ) ) );
	if ( ! $client ) {
		return false;
	}

	$client->grant_types = maybe_unserialize( get_post_meta( $client->ID, 'grant_types', true ) );
	$client->user_id     = get_post_meta( $client->ID, 'user_id', true );

	return $client;
}

/**
 * Generates a 40 Character key is generated by default but should be adjustable in the admin
 * @return [type] [description]
 *
 * @todo Allow more characters to be added to the character list to provide complex keys
 */
function wpdrift_worker_gen_key( $length = 40 ) {
	// Gather the settings
	$user_defined_length = wpdrift_worker_setting( 'token_length' );

	/**
	 * Temp Fix for https://github.com/justingreerbbi/wp-oauth-server/issues/3
	 * @todo Remove this check on next standard release
	 */
	if ( $user_defined_length > 255 ) {
		$user_defined_length = 255;
	}

	// If user setting is larger than 0, then define it
	if ( $user_defined_length > 0 ) {
		$length = $user_defined_length;
	}

	$characters    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$random_string = '';

	for ( $i = 0; $i < $length; $i ++ ) {
		$random_string .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
	}

	return $random_string;
}

/**
 * Check if there is more than one client in the system
 * @return boolean [description]
 *
 * @todo Optimize query
 */
function has_a_client() {
	global $wpdb;
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_type = 'oauth_client'" );

	if ( intval( $count ) >= 1 ) {
		return true;
	}
}


/**
 * Return the private key for signing
 * @since 1.0.0
 * @return [type] [description]
 */
function get_private_server_key() {
	$keys = apply_filters('wpdrift_worker_server_keys', array(
		'public'  => WPDRIFT_WORKER_PATH . 'oauth/keys/public_key.pem',
		'private' => WPDRIFT_WORKER_PATH . 'oauth/keys/private_key.pem',
	));

	return file_get_contents( $keys['private'] );
}

/**
 * Returns the public key
 * @return [type] [description]
 * @since 1.0.0
 */
function get_public_server_key() {
	$keys = apply_filters('wpdrift_worker_server_keys', array(
		'public'  => WPDRIFT_WORKER_PATH . 'oauth/keys/public_key.pem',
		'private' => WPDRIFT_WORKER_PATH . 'oauth/keys/private_key.pem',
	));

	return file_get_contents( $keys['public'] );
}

/**
 * Returns the set ALGO that is to be used for the server to encode
 *
 * @todo Possibly set this to be adjusted somewhere. The id_token calls for it to be set by each
 * client as a pref but we need to keep this simple.
 *
 * @since 1.0.0
 * @return String Type of algorithm used for encoding and decoding.
 */
function wpdrift_worker_get_algorithm() {
	return 'RS256';
}

/**
 * Retrieves WP OAuth Server settings
 *
 * @param  [type] $key [description]
 *
 * @return [type]      [description]
 */
function wpdrift_worker_setting( $key = null ) {
	$default_settings = _wpdw()->defualt_settings;
	$settings         = get_option( 'wpdrift_worker_options' );
	$settings         = array_merge($default_settings, array_filter( $settings, function ( $value ) {
		return '' !== $value;
	}));

	// No key is provided, let return the entire options table
	if ( is_null( $key ) ) {
		return $settings;
	}

	if ( ! isset( $settings[ $key ] ) ) {
		return;
	}

	return $settings[ $key ];
}

/**
 * Returns if the core is valid
 * @return [type] [description]
 */
function wpdrift_worker_is_core_valid() {
	if ( WPDRIFT_WORKER_CHECKSUM != strtoupper( md5_file( __FILE__ ) ) ) {
		return false;
	}

	return true;
}

/**
 * Determine is environment is development
 * @return [type] [description]
 *
 * @todo Need to make this more extendable by using __return_false
 */
function wpdrift_worker_is_dev() {
	return _wpdw()->env == 'development' ? true : false;
}

/**
 * Public Functions for WP OAuth Server
 *
 * @author Justin Greer  <jusin@justin-greer.com>
 * @package WP OAuth Server
 */

/**
 *
 * @deprecated in favor of wpdrift_worker_public_get_access_token
 */
function wpdrift_worker_get_access_token( $access_token, $return_type = ARRAY_A ) {

	$data = wpdrift_worker_public_get_access_token( $access_token, $return_type );

	return $data;
}

/**
 * Retrieve information about an access token
 *
 * @param $access_token
 * @param string $return_type
 *
 * @return array|bool|null|object|void
 */
function wpdrift_worker_public_get_access_token( $access_token, $return_type = ARRAY_A ) {
	if ( is_null( $access_token ) ) {
		return false;
	}

	global $wpdb;

	$access_token = $wpdb->get_row( $wpdb->prepare(
		"
		SELECT *
		FROM {$wpdb->prefix}oauth_access_tokens
		WHERE access_token = %s
		LIMIT 1
		",
		array( $access_token )
	), $return_type );

	if ( $access_token ) {
		$expires = strtotime( $access_token['expires'] );
		if ( current_time( 'timestamp' ) > $expires ) {
			return false;
		}
	}

	return $access_token;
}

/**
 * Insert a new OAuth 2 client
 *
 * @param null $client_data
 *
 * @return bool|int|WP_Error
 */
function wpdrift_worker_public_insert_client( $client_data = null ) {

	do_action( 'wpdrift_worker_before_create_client', array( $client_data ) );

	$client_id     = wpdrift_worker_gen_key();
	$client_secret = wpdrift_worker_gen_key();

	$grant_types = isset( $client_data['grant_types'] ) ? $client_data['grant_types'] : array();
	$user_id     = isset( $client_data['user_id'] ) ? intval( $client_data['user_id'] ) : 0;

	$client = array(
		'post_title'     => wp_strip_all_tags( $client_data['name'] ),
		'post_status'    => 'publish',
		'post_author'    => 1,
		'post_type'      => 'oauth_client',
		'comment_status' => 'closed',
		'meta_input'     => array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'grant_types'   => $grant_types,
			'redirect_uri'  => sanitize_text_field( $client_data['redirect_uri'] ),
			'user_id'       => $user_id,
			'scope'         => sanitize_text_field( $client_data['scope'] ),
		),
	);

	// Insert the post into the database
	$client_insert = wp_insert_post( $client );
	if ( is_wp_error( $client_insert ) ) {
		return $client_insert->get_error_message();
	}

	return $client_insert;
}
