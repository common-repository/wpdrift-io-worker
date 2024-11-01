<?php
/**
 * WD_Dashboard_Endpoint class
 */

defined( 'ABSPATH' ) || exit;
use Carbon\Carbon;

/**
 * Dashboard endpoints.
 *
 * @since 1.0.0
 */
class WPdrift_Dashboard_Controller extends WP_REST_Controller {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'dashboard';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route($this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		));
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date_args description]
		 * @var array
		 */
		$date_args = array();

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( isset( $request['after'] ) ) {
			$date_args[0]['after'] = $request['after'];
		}

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( isset( $request['before'] ) ) {
			$date_args[0]['before'] = $request['before'];
		}

		$items                   = array();
		$items['count_users']    = count_users();
		$items['count_posts']    = wp_count_posts();
		$items['count_pages']    = wp_count_posts( 'page' );
		$items['count_comments'] = wp_count_comments();
		$items['users']          = $this->get_users( $date_args );
		$items['posts']          = $this->get_posts( $date_args );
		$items['pages']          = $this->get_posts( $date_args, 'page' );
		$items['comments']       = $this->get_comments( $date_args );

		/**
		 * [$data description]
		 * @var array
		 */
		$data = array();
		foreach ( $items as $key => $item ) {
			$itemdata     = $this->prepare_item_for_response( $item, $request );
			$data[ $key ] = $this->prepare_response_for_collection( $itemdata );
		}

		/**
		 * [return description]
		 * @var [type]
		 */
		return rest_ensure_response( $data );
	}

	/**
	 * [get_users description]
	 * @return [type] [description]
	 */
	public function get_users( $date_args ) {
		/**
		 * [$data description]
		 * @var [type]
		 */
		$data               = $this->query_users( $date_args );
		$date_args_compared = $this->date_args_compared( $date_args );
		$data_compared      = empty( $date_args_compared ) ? [] : $this->query_users( $date_args_compared );
		$progress           = $this->get_progress( $data, $data_compared );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'data'          => $data,
			'data_compared' => $data_compared,
			'progress'      => $progress,
		];
	}

	/**
	 * [query_users description]
	 * @param  [type] $date_args [description]
	 * @return [type]            [description]
	 */
	public function query_users( $date_args ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date_query description]
		 * @var WP_Date_Query
		 */
		$date_query = new WP_Date_Query( $date_args, 'user_registered' );

		/**
		 * [$query_fields description]
		 * @var string
		 */
		$query_fields  = "COUNT(*), EXTRACT(DAY FROM user_registered) day, EXTRACT(MONTH FROM user_registered) month, EXTRACT(YEAR FROM user_registered) year";
		$query_from    = "FROM $wpdb->users";
		$query_where   = "WHERE 1=1";
		$query_where  .= $date_query->get_sql();
		$query_groupby = "GROUP BY day, month, year";
		$query_orderby = "ORDER BY user_registered ASC";

		/**
		 * [$request description]
		 * @var string
		 */
		$request = "SELECT $query_fields $query_from $query_where $query_groupby $query_orderby";
		$col     = $wpdb->get_col( $request );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'counts' => array_sum( $col ),
			'data'   => $col,
		];
	}

	/**
	 * [get_posts description]
	 * @return [type] [description]
	 */
	public function get_posts( $date_args, $post_type = 'post' ) {
		/**
		 * [$data description]
		 * @var [type]
		 */
		$data               = $this->query_posts( $date_args, $post_type );
		$date_args_compared = $this->date_args_compared( $date_args );
		$data_compared      = empty( $date_args_compared ) ? [] : $this->query_posts( $date_args_compared, $post_type );
		$progress           = $this->get_progress( $data, $data_compared );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'data'          => $data,
			'data_compared' => $data_compared,
			'progress'      => $progress,
		];
	}

	/**
	 * [query_posts description]
	 * @param  [type] $date_args [description]
	 * @return [type]            [description]
	 */
	public function query_posts( $date_args, $post_type = 'post' ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date_query description]
		 * @var WP_Date_Query
		 */
		$date_query = new WP_Date_Query( $date_args );

		/**
		 * [$query_fields description]
		 * @var string
		 */
		$query_fields  = "COUNT(*), EXTRACT(DAY FROM post_date) day, EXTRACT(MONTH FROM post_date) month, EXTRACT(YEAR FROM post_date) year";
		$query_from    = "FROM $wpdb->posts";
		$query_where   = "WHERE post_type = '$post_type'";
		$query_where  .= $date_query->get_sql();
		$query_groupby = "GROUP BY day, month, year";
		$query_orderby = "ORDER BY post_date ASC";

		/**
		 * [$request description]
		 * @var string
		 */
		$request = "SELECT $query_fields $query_from $query_where $query_groupby $query_orderby";
		$col     = $wpdb->get_col( $request );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'counts' => array_sum( $col ),
			'data'   => $col,
		];
	}

	/**
	 * [get_comments description]
	 * @param  [type] $date_args [description]
	 * @return [type]            [description]
	 */
	public function get_comments( $date_args ) {
		/**
		 * [$data description]
		 * @var [type]
		 */
		$data               = $this->query_comments( $date_args, $post_type );
		$date_args_compared = $this->date_args_compared( $date_args );
		$data_compared      = empty( $date_args_compared ) ? [] : $this->query_comments( $date_args_compared, $post_type );
		$progress           = $this->get_progress( $data, $data_compared );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'data'          => $data,
			'data_compared' => $data_compared,
			'progress'      => $progress,
		];
	}

	/**
	 * [query_comments description]
	 * @return [type] [description]
	 */
	public function query_comments( $date_args ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date_query description]
		 * @var WP_Date_Query
		 */
		$date_query = new WP_Date_Query( $date_args, 'comment_date' );

		/**
		 * [$query_fields description]
		 * @var string
		 */
		$query_fields  = "COUNT(*), EXTRACT(DAY FROM comment_date) day, EXTRACT(MONTH FROM comment_date) month, EXTRACT(YEAR FROM comment_date) year";
		$query_from    = "FROM $wpdb->comments";
		$query_where   = "WHERE 1=1";
		$query_where  .= $date_query->get_sql();
		$query_groupby = "GROUP BY day, month, year";
		$query_orderby = "ORDER BY comment_date ASC";

		/**
		 * [$request description]
		 * @var string
		 */
		$request = "SELECT $query_fields $query_from $query_where $query_groupby $query_orderby";
		$col     = $wpdb->get_col( $request );

		/**
		 * [return description]
		 * @var array
		 */
		return [
			'counts' => array_sum( $col ),
			'data'   => $col,
		];
	}

	/**
	 * [date_args_compared description]
	 * @return [type] [description]
	 */
	public function date_args_compared( $date_args ) {
		/**
		 * [if description]
		 * @var [type]
		 */
		if ( ! isset( $date_args[0]['after'] ) ) {
			return array();
		}

		/**
		 * [$date_args_compared description]
		 * @var array
		 */
		$date_args_compared = array();

		/**
		 * [$after description]
		 * @var [type]
		 */
		$after    = Carbon::parse( $date_args[0]['after'] );
		$before   = isset( $date_args[0]['before'] ) ? Carbon::parse( $date_args[0]['before'] ) : Carbon::now();
		$diffdays = $after->diffInDays( $dt2, false );

		/**
		 * [$dt3 description]
		 * @var [type]
		 */
		$previous_after  = Carbon::parse( $date_args[0]['after'] );
		$previous_before = Carbon::parse( $date_args[0]['after'] );
		$previous_after->subDays( $diffdays );

		$date_args_compared[0]['after']  = $previous_after->toFormattedDateString();
		$date_args_compared[0]['before'] = $previous_before->toFormattedDateString();

		/**
		 * [return description]
		 * @var [type]
		 */
		return $date_args_compared;
	}

	/**
	 * [get_progress description]
	 * @param  [type] $data          [description]
	 * @param  [type] $data_compared [description]
	 * @return [type]                [description]
	 */
	public function get_progress( $data, $data_compared ) {
		/**
		 * [$progress description]
		 * @var array
		 */
		$progress = array();

		/**
		 * [$data_list description]
		 * @var array
		 */
		$progress['data']['current']    = $data['counts'];
		$progress['data']['previously'] = isset( $data_compared['counts'] ) ? $data_compared['counts'] : 0;

		/**
		 * [$percentage description]
		 * @var [type]
		 */
		$percentage = $progress['data']['previously'] ? ( 100 / $progress['data']['previously'] ) : 100;

		/**
		 * [$sum description]
		 * @var array
		 */
		$progress['data']['diff'] = ( $progress['data']['current'] - $progress['data']['previously'] );
		$progress['percentage']   = number_format( ( $percentage * $progress['data']['diff'] ), 2, '.', '' );

		/**
		 * [return description]
		 * @var [type]
		 */
		return $progress;
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		/**
		 * [if description]
		 * @var [type]
		 */
		if ( ! current_user_can( 'list_users' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the resource.' ), array( 'status' => $this->authorization_status_code() ) );
		}

		return true;
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		return $item;
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
