<?php

/**
 * The oauth-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WPdrift_Worker
 * @subpackage WPdrift_Worker/oauth
 * @author     Support HQ <support@upnrunn.com>
 */

/**
 * [WPdrift_Worker_Oauth description]
 */
class WPdrift_Worker_Oauth {

	/**
	 * Adds/registers query vars
	 *
	 * @return void
	 */
	public function server_register_query_vars() {
		wpdrift_worker_server_register_rewrites();

		global $wp;
		$wp->add_query_var( 'oauth' );
	}

	/**
	 * [template_redirect_intercept description]
	 *
	 * @return [type] [description]
	 */
	public function server_template_redirect_intercept( $template ) {
		global $wp_query;

		if ( $wp_query->get( 'oauth' ) || $wp_query->get( 'well-known' ) ) {

			do_action( 'wpdrift_worker_before_api', array( $_REQUEST ) );
			require_once dirname( __FILE__ ) . '/OAuth2/Autoloader.php';
			OAuth2\Autoloader::register();

			$settings         = get_option( 'wpdrift_worker_options' );
			$default_settings = _wpdw()->defualt_settings;

			if ( 0 == wpdrift_worker_setting( 'enabled' ) ) {
				do_action( 'wpdrift_worker_before_unavailable_error' );
				$response = new OAuth2\Response();
				$response->setError( 503, 'error', __( 'temporarily unavailable', 'wpdrift-worker' ) );
				$response->send();
				exit;
			}

			$wpdrift_worker_strict_api_lockdown = apply_filters( 'wpdrift_worker_strict_api_lockdown', false );
			if ( $wpdrift_worker_strict_api_lockdown && ! wpdrift_worker_is_core_valid() && ! wpdrift_worker_is_dev() ) {
				$response = new OAuth2\Response();
				$response->setError( 403, 'security_risk', __( 'plugin core is not authenticate', 'wpdrift-worker' ) );
				$response->send();
				exit;
			}

			global $wp_query;
			$method     = $wp_query->get( 'oauth' );
			$well_known = $wp_query->get( 'well-known' );
			$storage    = new OAuth2\Storage\Wordpressdb();
			$config     = array(
				'use_crypto_tokens'                 => false,
				'store_encrypted_token_string'      => false,
				'use_openid_connect'                => wpdrift_worker_setting( 'use_openid_connect' ),
				'issuer'                            => home_url( null, 'https' ),
				'id_lifetime'                       => wpdrift_worker_setting( 'id_token_lifetime' ),
				'access_lifetime'                   => wpdrift_worker_setting( 'access_token_lifetime' ),
				'refresh_token_lifetime'            => wpdrift_worker_setting( 'refresh_token_lifetime' ),
				'www_realm'                         => 'Service',
				'token_param_name'                  => 'access_token',
				'token_bearer_header_name'          => 'Bearer',
				'enforce_state'                     => wpdrift_worker_setting( 'enforce_state' ),
				'require_exact_redirect_uri'        => wpdrift_worker_setting( 'require_exact_redirect_uri' ),
				'allow_implicit'                    => wpdrift_worker_setting( 'implicit_enabled' ),
				'allow_credentials_in_request_body' => apply_filters( 'wpdrift_worker_allow_credentials_in_request_body', true ),
				'allow_public_clients'              => apply_filters( 'wpdrift_worker_allow_public_clients', false ),
				'always_issue_new_refresh_token'    => apply_filters( 'wpdrift_worker_always_issue_new_refresh_token', true ),
				'unset_refresh_token_after_use'     => apply_filters( 'wpdrift_worker_unset_refresh_token_after_use', false ),
				'redirect_status_code'              => apply_filters( 'wpdrift_worker_redirect_status_code', 302 ),
			);

			$server = new OAuth2\Server( $storage, $config );

			/*
			|--------------------------------------------------------------------------
			| SUPPORTED GRANT TYPES
			|--------------------------------------------------------------------------
			|
			| Authorization Code will always be on. This may be a bug or a f@#$ up on
			| my end. None the less, these are controlled in the server settings page.
			|
			 */
			$support_grant_types = array();

			if ( '1' == wpdrift_worker_setting( 'auth_code_enabled' ) ) {
				$server->addGrantType( new OAuth2\GrantType\AuthorizationCode( $storage ) );
			}

			/*
			|--------------------------------------------------------------------------
			| DEFAULT SCOPES
			|--------------------------------------------------------------------------
			|
			| Supported scopes can be added to the plugin by modifying the wpdrift_worker_scopes.
			| Until further notice, the default scope is 'basic'. Plans are in place to
			| allow this scope to be adjusted.
			|
			| @todo Added dynamic default scope
			|
			 */
			$default_scope = 'basic';

			$supported_scopes = apply_filters( 'wpdrift_worker_scopes', array(
				'openid',
				'profile',
				'email',
			) );

			$scope_util = new OAuth2\Scope( array(
				'default_scope'    => $default_scope,
				'supported_scopes' => $supported_scopes,
			) );

			$server->setScopeUtil( $scope_util );

			/*
			|--------------------------------------------------------------------------
			| TOKEN CATCH
			|--------------------------------------------------------------------------
			|
			| The following code is ran when a request is made to the server using the
			| Authorization Code (implicit) Grant Type as well as request tokens
			|
			 */
			if ( 'token' == $method ) {
				do_action( 'wpdrift_worker_before_token_method', array( $_REQUEST ) );
				$server->handleTokenRequest( OAuth2\Request::createFromGlobals() )->send();
				exit;
			}

			/*
			|--------------------------------------------------------------------------
			| AUTHORIZATION CODE CATCH
			|--------------------------------------------------------------------------
			|
			| The following code is ran when a request is made to the server using the
			| Authorization Code (not implicit) Grant Type.
			|
			| 1. Check if the user is logged in (redirect if not)
			| 2. Validate the request (client_id, redirect_uri)
			| 3. Create the authorization request using the authentication user's user_id
			|
			*/
			if ( 'authorize' == $method ) {

				do_action( 'wpdrift_worker_before_authorize_method', array( $_REQUEST ) );
				$request  = OAuth2\Request::createFromGlobals();
				$response = new OAuth2\Response();

				if ( ! $server->validateAuthorizeRequest( $request, $response ) ) {
					$response->send();
					exit;
				}

				/**
				 * @todo Some how we need to manage the prompt to login here but only when using openID. This is important and may
				 * not be the best place to handle this request. Explore the options.
				 *
				 * $prompt   = $request->query( 'prompt', 'consent' );
				 * if( $prompt == 'login') do show the authorization form again even if they are logged in.
				 */
				if ( ! is_user_logged_in() ) {
					wp_redirect( wp_login_url( add_query_arg( $request->query, home_url( '/oauth/authorize' ) ) ) );
					exit;
				}

				// For backward compatibility. If grant request is enabled, it will be overridden during the grant request.
				$is_authorized = true;
				$prompt        = '';

				/**
				 * Check to see if prompt is enabled and if so, lets handle the request.
				 *
				 * The parameter for prompt is intended to be used for OpenID Connect but will will change it up. For example,
				 * Google uses ?prompt on their OAuth 2.0 flow. This should be a good addition but should be kept for off for
				 * backward compatibility.
				 *
				 * @link http://openid.net/specs/openid-connect-core-1_0.html#rfc.section.3.1.2.1
				 *
				 * @since 1.0.0
				 */
				if ( ! isset( $_REQUEST['ignore_prompt'] ) ) {
					if ( isset( $_REQUEST['prompt'] ) ) {
						$prompt = isset( $_REQUEST['prompt'] ) ? $_REQUEST['prompt'] : 'consent';

						if ( 'none' == $prompt ) {
							$is_authorized = false;
						} elseif ( 'login' == $prompt ) {
							wp_logout();
							wp_redirect( site_url( add_query_arg( array( 'ignore_prompt' => '' ) ) ) );
							exit;
						}
					}
				}

				/**
				 * Check and see if we should include a grant request or not to the user.
				 * For backward compatibility, this is disabled by default but can be enabled.
				 *
				 * @todo If the user clicks deny, then the application does not return to the app and simply loads the request again.
				 * we need to look into a to allow the app the report that it is not authorized while
				 *
				 * @example add_filter('wpdrift_worker_use_grant_request', '__return_true');
				 *
				 * @since 1.0.0
				 */
				if ( apply_filters( 'wpdrift_worker_use_grant_request', false ) ) {

					$current_user = get_current_user_id();

					$grant_status = get_user_meta( $current_user, 'wpdrift_worker_grant_' . $_REQUEST['client_id'], true );

					if ( '' == $grant_status || 'consent' == $prompt ) {

						// @todo Add documenation for this feature
						$request_template = dirname( __FILE__ ) . '/templates/grant-request.php';
						if ( file_exists( get_stylesheet_directory() . '/wp-oauth-server/templates/grant-request.php' ) ) {
							$request_template = get_stylesheet_directory() . '/wp-oauth-server/templates/grant-request.php';
						}

						include $request_template;
						exit;
					} elseif ( 'allow' == $grant_status ) {

						$is_authorized = true;

					} elseif ( 'deny' == $grant_status ) {

						$is_authorized = false;

					}
				}

				$user_id = get_current_user_id();
				do_action( 'wpdrift_worker_authorization_code_authorize', array( $user_id ) );

				$server->handleAuthorizeRequest( $request, $response, $is_authorized, $user_id );
				$response->send();
				exit;
			}

			/*
			|--------------------------------------------------------------------------
			| PUBLIC KEY
			|--------------------------------------------------------------------------
			|
			| Presents the generic public key for signing.
			|@since 1.0.0
			*/
			if ( 'keys' == $well_known ) {
				$keys       = apply_filters( 'wpdrift_worker_server_keys', array(
					'public'  => WPDRIFT_WORKER_PATH . 'oauth/keys/public_key.pem',
					'private' => WPDRIFT_WORKER_PATH . 'oauth/keys/private_key.pem',
				) );
				$public_key = openssl_pkey_get_public( file_get_contents( $keys['public'] ) );
				$public_key = openssl_pkey_get_details( $public_key );
				$response   = new OAuth2\Response( array(
					'keys' => array(
						array(
							'kty' => 'RSA',
							'alg' => 'RS256',
							'use' => 'sig',
							'n'   => rtrim( strtr( base64_encode( $public_key['rsa']['n'] ), '+/', '-_' ), '=' ),
							'e'   => base64_encode( $public_key['rsa']['e'] ),
						),
					),
				) );
				$response->send();
				exit;
			}

			/*
			|--------------------------------------------------------------------------
			| OpenID Discovery
			|--------------------------------------------------------------------------
			|
			*/
			if ( 'openid-configuration' == $well_known ) {
				$openid_discovery_values = array(
					'issuer'                   => home_url( null, 'https' ),
					'authorization_endpoint'   => home_url( '/oauth/authorize/' ),
					'token_endpoint'           => home_url( '/oauth/token/' ),
					'userinfo_endpoint'        => home_url( '/oauth/me/' ),
					'jwks_uri'                 => home_url( '/.well-known/keys' ),
					'response_types_supported' => array(
						'code',
						'id_token',
						'token id_token',
						'code id_token',
					),
					'subject_types_supported'  => array( 'public' ),
					'id_token_signing_alg_values_supported' => array( 'RS256' ),
					'token_endpoint_auth_methods_supported' => array( 'client_secret_basic' ),
				);

				$openid_discovery_configuration = apply_filters( 'wpdrift_worker_openid_discovery', $openid_discovery_values );

				$response = new OAuth2\Response( $openid_discovery_configuration );
				$response->send();
				exit;
			}

			/*
			|--------------------------------------------------------------------------
			| EXTENDABLE RESOURCE SERVER METHODS
			|--------------------------------------------------------------------------
			|
			| Below this line is part of the developer API. Do not edit directly.
			| Refer to the developer documentation for extending the WordPress OAuth
			| Server plugin core functionality.
			|
			| @todo Document and tighten up error messages. All error messages will soon be
			| controlled through apply_filters so start planning for a filter error list to
			| allow for developers to customize error messages.
			|
			*/
			$resource_server_methods = apply_filters( 'wpdrift_worker_endpoints', null );

			// Check to see if the method exists in the filter.
			if ( array_key_exists( $method, $resource_server_methods ) ) {

				// If the method is is set to public, lets just run the method without.
				if ( isset( $resource_server_methods[ $method ]['public'] ) && $resource_server_methods[ $method ]['public'] ) {
					call_user_func_array( $resource_server_methods[ $method ]['func'], $_REQUEST );
					exit;
				}

				/**
				 * Check if the user is logged in.
				 *
				 * @since 1.0.0
				 */
				$current_user = apply_filters( 'determine_current_user', null );
				if ( is_null( $current_user ) || empty( $current_user ) ) {
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

				$token = $server->getAccessTokenData( OAuth2\Request::createFromGlobals() );
				if ( is_null( $token ) ) {
					$server->getResponse()->send();
					exit;
				}

				do_action( 'wpdrift_worker_endpoint_user_authenticated', array( $token ) );
				call_user_func_array( $resource_server_methods[ $method ]['func'], array( $token ) );

				exit;
			}

			/*
			 * Server error response. End of line
			 *
			 * @since 1.0.0
			 */
			$response = new OAuth2\Response();
			$response->setError( 400, 'invalid_request', __( 'unknown request', 'wpdrift-worker' ) );
			$response->send();
			exit;
		}

		return $template;
	}

	/**
	 * Restrict users to only have a single access token
	 * @param  [type] $object [description]
	 * @return [type]         [description]
	 */
	public function only_allow_one_access_token( $object ) {
		if ( is_null( $object ) ) {
			return;
		}

		// Define the user ID
		$user_id = $object['user_id'];

		// Remove all other access tokens and refresh tokens from the system
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}oauth_access_tokens", array( 'user_id' => $user_id ) );
		$wpdb->delete( "{$wpdb->prefix}oauth_refresh_tokens", array( 'user_id' => $user_id ) );
	}

	/**
	 * Default Method Filter for the resource server API calls
	 *
	 * @since  1.0.0 Endpoints now can accept public methods that bypass the token authorization
	 */
	public function server_default_endpoints() {
		$endpoints = array(
			'me'            => array(
				'func'   => '_wpdrift_worker_method_me',
				'public' => false,
			),
			'destroy'       => array(
				'func'   => '_wpdrift_worker_method_destroy',
				'public' => false,
			),
			'introspection' => array(
				'func'   => '_wpdrift_worker__method_introspection',
				'public' => false,
			),
		);

		return $endpoints;
	}

}
