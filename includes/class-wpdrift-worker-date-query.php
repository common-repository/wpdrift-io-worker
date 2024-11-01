<?php
/**
 * [WPdrift_Worker_Date_Query description]
 */
class WPdrift_Worker_Date_Query extends WP_Date_Query {

	/**
	 * [validate_column description]
	 * @param  [type] $column [description]
	 * @return [type]         [description]
	 */
	public function validate_column( $column ) {
		global $wpdb;

		$valid_columns = array(
			'post_date',
			'post_date_gmt',
			'post_modified',
			'post_modified_gmt',
			'comment_date',
			'comment_date_gmt',
			'user_registered',
			'registered',
			'last_updated',
			'created_at',
			'updated_at',
		);

		// Attempt to detect a table prefix.
		if ( false === strpos( $column, '.' ) ) {

			/**
			 * [if description]
			 * @var [type]
			 */
			if ( ! in_array( $column, apply_filters( 'date_query_valid_columns', $valid_columns ) ) ) {
				$column = 'post_date';
			}

			/**
			 * [$known_columns description]
			 * @var array
			 */
			$known_columns = array(
				$wpdb->posts                     => array(
					'post_date',
					'post_date_gmt',
					'post_modified',
					'post_modified_gmt',
				),
				$wpdb->comments                  => array(
					'comment_date',
					'comment_date_gmt',
				),
				$wpdb->users                     => array(
					'user_registered',
				),
				$wpdb->blogs                     => array(
					'registered',
					'last_updated',
				),
			);

			// If it's a known column name, add the appropriate table prefix.
			foreach ( $known_columns as $table_name => $table_columns ) {
				if ( in_array( $column, $table_columns ) ) {
					$column = $table_name . '.' . $column;
					break;
				}
			}
		}

		// Remove unsafe characters.
		return preg_replace( '/[^a-zA-Z0-9_$\.]/', '', $column );
	}
}
