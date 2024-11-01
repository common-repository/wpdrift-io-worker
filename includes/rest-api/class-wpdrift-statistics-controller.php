<?php
/**
 * REST API: WPdrift_Statistics_Controller class
 *
 * @package WPdrift IO
 * @subpackage REST_API
 * @since 1.0.0
 */

use Carbon\Carbon;

/**
 * [WPdrift_Statistics_Controller description]
 */
class WPdrift_Statistics_Controller extends WP_REST_Controller {
	/**
	 * Here initialize our namespace and resource name.
	 */
	public function __construct() {
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'statistics';
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
		if ( ! current_user_can( 'list_users' ) ) {
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
		 * [$data description]
		 * @var [type]
		 */
		return rest_ensure_response( $this->statistics( $request ) );
	}

	/**
	 * [statistics description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function statistics( $request ) {
		/**
		 * [switch description]
		 * @var [type]
		 */
		switch ( $request['type'] ) {
			case 'users':
				return $this->get_users( $request );
				break;
			case 'posts':
				return $this->get_posts( $request );
				break;
			case 'pages':
				return $this->get_pages( $request );
				break;
			case 'comments':
				return $this->get_comments( $request );
				break;
			default:
				return [];
		}
	}

	/**
	 * [get_users description]
	 * @return [type] [description]
	 */
	public function get_users( $request ) {
		/**
		 * [$date description]
		 * @var [type]
		 */
		$date = $this->prepare_date( $request );

		/**
		 * [$data description]
		 * @var [type]
		 */
		$data          = $this->query_users( $date );
		$date_compared = $this->date_query_compared( $date );
		$data_compared = empty( $date_compared ) ? [] : $this->query_users( $date_compared );
		$progress      = $this->get_progress( $data, $data_compared );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'data'          => $data,
			'data_compared' => $data_compared,
			'progress'      => $progress,
			'counts'        => count_users(),
		];
	}

	/**
	 * [query_users description]
	 * @param  [type] $date_query [description]
	 * @return [type]            [description]
	 */
	public function query_users( $date ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date_query description]
		 * @var WP_Date_Query
		 */
		$date_query = new WP_Date_Query( $date, 'user_registered' );

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
		$query = "SELECT $query_fields $query_from $query_where $query_groupby $query_orderby";
		$col   = $wpdb->get_col( $query );

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
	 * @param  [type] $date_query [description]
	 * @return [type]            [description]
	 */
	public function get_comments( $request ) {
		/**
		 * [$date description]
		 * @var [type]
		 */
		$date = $this->prepare_date( $request );

		/**
		 * [$data description]
		 * @var [type]
		 */
		$data          = $this->query_comments( $date );
		$date_compared = $this->date_query_compared( $date );
		$data_compared = empty( $date_compared ) ? [] : $this->query_comments( $date_compared );
		$progress      = $this->get_progress( $data, $data_compared );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'data'          => $data,
			'data_compared' => $data_compared,
			'progress'      => $progress,
			'counts'        => wp_count_comments(),
		];
	}

	/**
	 * [query_comments description]
	 * @return [type] [description]
	 */
	public function query_comments( $date ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date_query description]
		 * @var WP_Date_Query
		 */
		$date_query = new WP_Date_Query( $date, 'comment_date' );

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
		$query = "SELECT $query_fields $query_from $query_where $query_groupby $query_orderby";
		$col   = $wpdb->get_col( $query );

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
	 * [get_posts description]
	 * @return [type] [description]
	 */
	public function get_posts( $request ) {
		/**
		 * [$type description]
		 * @var string
		 */
		$type = 'post';

		/**
		 * [return description]
		 * @var [type]
		 */
		return $this->data_posts( $type, $request );
	}

	/**
	 * [get_pages description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_pages( $request ) {
		/**
		 * [$type description]
		 * @var string
		 */
		$type = 'page';

		/**
		 * [return description]
		 * @var [type]
		 */
		return $this->data_posts( $type, $request );
	}

	/**
	 * [data_posts description]
	 * @return [type] [description]
	 */
	public function data_posts( $type, $request ) {
		/**
		 * [$date description]
		 * @var [type]
		 */
		$date_query = $this->prepare_date( $request );

		/**
		 * [$query_arguments description]
		 * @var array
		 */
		$query_arguments = array(
			'post_type'  => $type,
			'date_query' => $date_query,
		);

		/**
		 * [$data description]
		 * @var [type]
		 */
		$data                = $this->query_posts( $query_arguments );
		$date_query_compared = $this->date_query_compared( $date_query );

		/**
		 * [$query_arguments_compared description]
		 * @var array
		 */
		$query_arguments_compared = array(
			'post_type'  => $type,
			'date_query' => $date_query_compared,
		);
		$data_compared            = empty( $date_query_compared ) ? [] : $this->query_posts( $query_arguments_compared );
		$progress                 = $this->get_progress( $data, $data_compared );

		/**
		 * [return description]
		 * @var [type]
		 */
		return [
			'data'          => $data,
			'data_compared' => $data_compared,
			'progress'      => $progress,
			'counts'        => wp_count_posts( $type ),
		];
	}

	/**
	 * [query_posts description]
	 * @param  [type] $date_query [description]
	 * @return [type]            [description]
	 */
	public function query_posts( $query_arguments ) {
		/**
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date_query description]
		 * @var WP_Date_Query
		 */
		$date_query = new WP_Date_Query( $query_arguments['date_query'] );
		$post_type  = $query_arguments['post_type'];

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
	 * [date_query_compared description]
	 * @return [type] [description]
	 */
	public function date_query_compared( $date_query ) {
		/**
		 * [if description]
		 * @var [type]
		 */
		if ( ! isset( $date_query[0]['after'] ) ) {
			return array();
		}

		/**
		 * [$date_query_compared description]
		 * @var array
		 */
		$date_query_compared = array();

		/**
		 * [$after description]
		 * @var [type]
		 */
		$after    = Carbon::parse( $date_query[0]['after'] );
		$before   = isset( $date_query[0]['before'] ) ? Carbon::parse( $date_query[0]['before'] ) : Carbon::now();
		$diffdays = $after->diffInDays( $before, false );

		/**
		 * [$dt3 description]
		 * @var [type]
		 */
		$previous_after  = Carbon::parse( $date_query[0]['after'] );
		$previous_before = Carbon::parse( $date_query[0]['after'] );
		$previous_after->subDays( $diffdays );

		$date_query_compared[0]['after']  = $previous_after->toFormattedDateString();
		$date_query_compared[0]['before'] = $previous_before->toFormattedDateString();

		/**
		 * [return description]
		 * @var [type]
		 */
		return $date_query_compared;
	}

	/**
	 * [prepare_date description]
	 * @return [type] [description]
	 */
	public function prepare_date( $request ) {
		/**
		 * [$date description]
		 * @var array
		 */
		$date = array();

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( isset( $request['after'] ) ) {
			$date[0]['after'] = $request['after'];
		}

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( isset( $request['before'] ) ) {
			$date[0]['before'] = $request['before'];
		}

		/**
		 * [return description]
		 * @var [type]
		 */
		return $date;
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
