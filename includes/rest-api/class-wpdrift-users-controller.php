<?php
/**
 * REST API: WPdrift_Users_Controller class
 *
 * @package WPdrift IO
 * @subpackage REST_API
 * @since 1.0.0
 */

use Carbon\Carbon;

/**
 * [WPdrift_Users_Controller description]
 */
class WPdrift_Users_Controller extends WP_REST_Controller {

	/**
	 * Here initialize our namespace and resource name.
	 */
	public function __construct() {
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'users';
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
		 * [global description]
		 * @var [type]
		 */
		global $wpdb;

		/**
		 * [$date description]
		 * @var [type]
		 */
		$date = $this->prepare_date( $request );

		/**
		 * [$mode description]
		 * @var [type]
		 */
		$mode = isset( $request['mode'] ) ? $request['mode'] : 'day';

		/**
		 * [$date_query description]
		 * @var WP_Date_Query
		 */
		$date_query = new WP_Date_Query( $date, 'user_registered' );

		/**
		 * [$query_fields description]
		 * @var string
		 */
		$query_fields  = "COUNT(*), user_registered, MINUTE(user_registered) minute, HOUR(user_registered) hour, DAY(user_registered) day, WEEK(user_registered) week, MONTH(user_registered) month, YEAR(user_registered) year";
		$query_from    = "FROM $wpdb->users";
		$query_where   = "WHERE 1=1";
		$query_where  .= $date_query->get_sql();
		$query_groupby = $this->query_groupby( $mode );
		$query_orderby = "ORDER BY user_registered ASC";

		/**
		 * [$request description]
		 * @var string
		 */
		$query               = "SELECT $query_fields $query_from $query_where $query_groupby $query_orderby";
		$col                 = $wpdb->get_col( $query );
		$col_user_registered = $wpdb->get_col( $query, 1 );

		/**
		 * [$data description]
		 * @var array
		 */
		$data = array();

		/**
		 * [$response description]
		 * @var [type]
		 */
		$response = [
			'total'   => array_sum( $col ),
			'data'    => $col,
			'labels'  => $this->prepare_labels( $col_user_registered, $mode ),
			'filters' => $this->get_filters( $date, $mode ),
		];

		/**
		 * [$data description]
		 * @var [type]
		 */
		$data['results'] = $this->prepare_response_for_collection( $response );

		/**
		 * Return all of our comment response data.
		 * @var [type]
		 */
		return rest_ensure_response( $data );
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
	 * [prepare_labels description]
	 * @param  [type] $labels [description]
	 * @return [type]         [description]
	 */
	public function prepare_labels( $dates, $mode ) {
		/**
		 * [$labels description]
		 * @var array
		 */
		$labels = array();

		foreach ( $dates as $date ) {
			// $labels[] = $date;
			$dt       = Carbon::createFromFormat( 'Y-m-d H:i:s', $date );
			$labels[] = $this->formated_date( $dt, $mode );
		}

		/**
		 * [return description]
		 * @var [type]
		 */
		return $labels;
	}

	/**
	 * [query_groupby description]
	 * @return [type] [description]
	 */
	public function query_groupby( $mode ) {
		/**
		 * [switch description]
		 * @var [type]
		 */
		switch ( $mode ) {
			case 'minute':
				return "GROUP BY minute, hour, day, month, year";
				break;
			case 'hour':
				return "GROUP BY hour, day, month, year";
				break;
			case 'week':
				return "GROUP BY week, month, year";
				break;
			case 'month':
				return "GROUP BY month, year";
				break;
			case 'year':
				return "GROUP BY year";
				break;
			default:
				return "GROUP BY day, month, year";
		}
	}

	/**
	 * [formated_date description]
	 * @param  [type] $dt   [description]
	 * @param  [type] $mode [description]
	 * @return [type]       [description]
	 */
	public function formated_date( $dt, $mode ) {
		switch ( $mode ) {
			case 'minute':
				return $dt->format( 'h:i A' );
				break;
			case 'hour':
				return $dt->format( 'h A, d' );
				break;
			case 'week':
				return $dt->format( 'd M y' );
				break;
			case 'month':
				return $dt->format( 'M Y' );
				break;
			case 'year':
				return $dt->format( 'Y' );
				break;
			default:
				return $dt->toFormattedDateString();
		}
	}

	/**
	 * [get_filters description]
	 * @param  [type] $dates [description]
	 * @param  [type] $mode  [description]
	 * @return [type]        [description]
	 */
	public function get_filters( $date, $mode ) {
		/**
		 * [$diffdays description]
		 * @var integer
		 */
		$diff_minutes = 0;

		/**
		 * [if description]
		 * @var [type]
		 */
		if ( isset( $date[0]['after'] ) ) {
			$after        = Carbon::parse( $date[0]['after'] );
			$before       = isset( $date[0]['before'] ) ? Carbon::parse( $date[0]['before'] ) : Carbon::now();
			$diff_minutes = $after->diffInMinutes( $before, false );
		}

		/**
		 * [$filters description]
		 * @var array
		 */
		$filters = [
			'minute' => [
				'active' => ( $diff_minutes > 1 ) ? true : false,
				'label'  => __( 'Minute', 'wpdrift-worker' ),
			],
			'hour'   => [
				'active' => ( $diff_minutes > 60 ) ? true : false,
				'label'  => __( 'Hour', 'wpdrift-worker' ),
			],
			'day'    => [
				'active' => ( $diff_minutes > ( 60 * 24 ) ) ? true : false,
				'label'  => __( 'Day', 'wpdrift-worker' ),
			],
			'week'   => [
				'active' => ( $diff_minutes > ( 60 * 24 * 7 ) ) ? true : false,
				'label'  => __( 'Week', 'wpdrift-worker' ),
			],
			'month'  => [
				'active' => ( $diff_minutes > ( 60 * 24 * 30 ) ) ? true : false,
				'label'  => __( 'Month', 'wpdrift-worker' ),
			],
			'year'   => [
				'active' => ( $diff_minutes > ( 60 * 24 * 365 ) ) ? true : false,
				'label'  => __( 'Year', 'wpdrift-worker' ),
			],
		];

		/**
		 * [return description]
		 * @var [type]
		 */
		return $filters;
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
