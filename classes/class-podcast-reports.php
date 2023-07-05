<?php

// phpcs:ignorefile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

class Podcast_Reports {

	var $options;

	public function monthly_downloads_report() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$author_podcast_ids = array();
		$month1             = '';
		$month2             = '';
		$month3             = '';
		$month4             = '';

		$series_downloads = get_transient( 'rm_podcast_monthly_downloads' );
		if ( ! $series_downloads ) {
			$logger = new RM_Action_Log();

			if ( ! current_user_can( 'edit_others_posts' ) ) {
				$author_podcast_ids = $this->author_podcasts( get_current_user_id() );
			}

			$this_month        = Rainmaker_Dates::get_dates_as_time( Rainmaker_Dates::get_dates_this_month() );
			$last_month        = Rainmaker_Dates::get_dates_as_time( Rainmaker_Dates::get_dates_last_month() );
			$two_prior_start   = strtotime( 'first day of -2 month' );
			$two_prior_end     = strtotime( 'last day of -2 month' );
			$three_prior_start = strtotime( 'first day of -3 month' );
			$three_prior_end   = strtotime( 'last day of -3 month' );

			$two_month = Rainmaker_Dates::get_dates_as_time( array(
				                                                 'start' => array(
					                                                 'day'   => date( 'j', $two_prior_start ),
					                                                 'month' => date( 'n', $two_prior_start ),
					                                                 'year'  => date( 'Y', $two_prior_start ),
				                                                 ),
				                                                 'end'   => array(
					                                                 'day'   => date( 'j', $two_prior_end ),
					                                                 'month' => date( 'n', $two_prior_end ),
					                                                 'year'  => date( 'Y', $two_prior_end ),
				                                                 ),
			                                                 )
			);

			$three_month = Rainmaker_Dates::get_dates_as_time( array(
				                                                   'start' => array(
					                                                   'day'   => date( 'j', $three_prior_start ),
					                                                   'month' => date( 'n', $three_prior_start ),
					                                                   'year'  => date( 'Y', $three_prior_start ),
				                                                   ),
				                                                   'end'   => array(
					                                                   'day'   => date( 'j', $three_prior_end ),
					                                                   'month' => date( 'n', $three_prior_end ),
					                                                   'year'  => date( 'Y', $three_prior_end ),
				                                                   ),
			                                                   )
			);

			$sql = sprintf( 'SELECT action_type, action_date, post_id post_id, CONCAT(YEAR(action_date), \'-\', MONTH(action_date), \'-01\') action_date, count(*) downloads
							FROM wp_rm_actions
							WHERE action_type = \'podcast-download\' AND action_date BETWEEN \'%s\' AND \'%s\' %s
							GROUP BY post_id, MONTH(action_date)',
			                date( 'Y-d-m', $three_prior_start ),
			                date( 'Y-m-d 23:59:59' ),
			                count( $author_podcast_ids ) > 0 ? sprintf( ' AND post_id IN (%s) ', implode( ',', $author_podcast_ids ) ) : ''
			);

			$downloads = $logger->query_action_log( $sql );

			$new_downloads    = array( 'this_month' => 0, 'last_month' => 0, 'two_month' => 0, 'three_month' => 0 );
			$all_downloads    = array( 'this_month' => 0, 'last_month' => 0, 'two_month' => 0, 'three_month' => 0 );
			$series_downloads = array();
			$post_dates       = array();
			$post_series      = array();

			foreach ( $downloads as $dl ) {
				$action_date = strtotime( $dl->action_date );

				// series downloads
				if ( ! array_key_exists( $dl->post_id, $post_series ) ) {
					$post_series[ $dl->post_id ] = wp_get_post_terms( $dl->post_id, 'series' );
				}

				if ( ( $action_date >= $this_month['start'] && $action_date < $this_month['end'] ) ) {
					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						$series_downloads['shows'][ $show_terms->name ]['this_month'] = $dl->downloads;
					}
				}

				if ( ( $action_date >= $last_month['start'] && $action_date < $last_month['end'] ) ) {
					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						$series_downloads['shows'][ $show_terms->name ]['last_month'] = $dl->downloads;
					}
				}

				if ( ( $action_date >= $two_month['start'] && $action_date < $two_month['end'] ) ) {
					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						$series_downloads['shows'][ $show_terms->name ]['two_month'] = $dl->downloads;
					}
				}

				if ( ( $action_date >= $three_month['start'] && $action_date < $three_month['end'] ) ) {
					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						$series_downloads['shows'][ $show_terms->name ]['three_month'] = $dl->downloads;
					}
				}
			}

			$series_downloads['month1'] = date( 'M', $this_month['end'] );
			$series_downloads['month2'] = date( 'M', $last_month['end'] );
			$series_downloads['month3'] = date( 'M', $two_month['end'] );
			$series_downloads['month4'] = date( 'M', $three_month['end'] );

			set_transient( 'rm_podcast_monthly_downloads', $series_downloads, 10 * MINUTE_IN_SECONDS );
		}

		$summary_report = sprintf( '<h2 class="nav-tab-wrapper"><span class="nav-tab nav-tab-active ">%s</span></h2>',
		                           __( 'Monthly Download Summary', 'rainmaker-simple-podcasting' ) );

		if ( $series_downloads['shows'] open the class-podcast-reports.php file

) {
			maybe_Log_event( 'monthly_downloads_report: has data', 'Podcast_Reports' );

			$summary_report .= '<!-- labs_podcast_reports -->';
			$summary_report .= sprintf( '<h3>%s</h3>', __( 'New Monthly Downloads', 'rainmaker-simple-podcasting' ) );
			$summary_report .= '<div class="analytics-table-content">';
			$summary_report .= '<table class="data-table" id="series-download-table"><thead class="table_generator_action_bar">';
			$summary_report .= sprintf( '<tr><th class="analytics-table-heading" onclick="sortTable(0)" data-order="0">%s</th><th class="analytics-table-heading" onclick="sortTable(1)" data-order="1">%s</th><th class="analytics-table-heading" onclick="sortTable(2)" data-order="2">%s</th><th class="analytics-table-heading" onclick="sortTable(3)" data-order="3">%s</th><th class="analytics-table-heading" onclick="sortTable(4)" data-order="4">%s</th></tr></thead>',
			                            __( 'Series', 'rainmaker-simple-podcasting' ),
			                            $series_downloads['month1'],
			                            $series_downloads['month2'],
			                            $series_downloads['month3'],
			                            $series_downloads['month4']
			);

			$summary_report .= '<tbody class="loading_tbody">';
			$summary_report .= '<tbody class="loading_tbody">';

			ksort( $series_downloads['shows'] );

			foreach ( $series_downloads['shows'] as $series => $downloads ) {
				$summary_report .= sprintf( '<tr><td>%s</a></td><td>%d</td><td>%d</td><td>%d</td><td>%d</td></tr>',
				                            $series,
				                            $downloads['this_month'],
				                            $downloads['last_month'],
				                            $downloads['two_month'],
				                            $downloads['three_month']
				);
			}

			$summary_report .= '</tbody></table>';

			$summary_report .= '<script>jQuery(function(){
								  jQuery("#series-download-table").tablesorter({ sortInitialOrder: "desc" });
								});</script>';
		} else {
			$summary_report .= '<!-- labs_podcast_reports -->';
			$summary_report .= 'No downloads.';
		}

		echo $summary_report;
	}

	function author_podcasts( $author_id, $series = '' ) {
		$ids  = array();
		$args = array(
			'author'      => $author_id,
			'post_status' => 'publish',
			'post_type'   => 'podcast',
		);

		// filter by series
		if ( $series ) {
			$args['series'] = $series;
		}
		$author_podcasts = get_posts( $args );

		foreach ( $author_podcasts as $auth_pod ) {
			array_push( $ids, $auth_pod->ID );
		}

		return $ids;
	}
public function downloads_report($page_number = 1, $items_per_page = 10) {
	public function downloads_report() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$author_podcast_ids = array();

		$download_data = get_transient( 'rm_podcast_download_summary_data' );
		if ( ! $download_data ) {
			$date_range = Rainmaker_Dates::get_dates_as_string( Rainmaker_Dates::get_dates_this_year() );
			$logger     = new RM_Action_Log();

			if ( ! current_user_can( 'edit_others_posts' ) ) {
				$author_podcast_ids = $this->author_podcasts( get_current_user_id() );
			}

			$sql = sprintf( 'SELECT post_id post_id, DATE(action_date) action_date, count(*) downloads
							FROM wp_rm_actions
							WHERE action_type = \'podcast-download\' AND action_date BETWEEN \'%s\' AND \'%s\' %s
							GROUP BY post_id, DATE(action_date)',
			                $date_range['start'],
			                $date_range['end'],
			                count( $author_podcast_ids ) > 0 ? sprintf( ' AND post_id IN (%s) ', implode( ',', $author_podcast_ids ) ) : ''
			);

			$downloads = $logger->query_action_log( $sql );

			$day_range   = Rainmaker_Dates::get_dates_as_time( Rainmaker_Dates::get_dates_today() );
			$week_range  = Rainmaker_Dates::get_dates_as_time( Rainmaker_Dates::get_dates_this_week() );
			$month_range = Rainmaker_Dates::get_dates_as_time( Rainmaker_Dates::get_dates_this_month() );

			$new_downloads    = array( 'day' => 0, 'week' => 0, 'month' => 0, 'year' => 0 );
			$all_downloads    = array( 'day' => 0, 'week' => 0, 'month' => 0, 'year' => 0 );
			$series_downloads = array();
			$post_dates       = array();
			$post_series      = array();


			foreach ( $downloads as $dl ) {
				date_default_timezone_set( 'America/Chicago' );

				$action_date = strtotime( $dl->action_date );

				// new downloads
				if ( ! array_key_exists( $dl->post_id, $post_dates ) ) {
					$post_dates[ $dl->post_id ] = get_the_date( 'U', $dl->post_id );
				}

				if ( $action_date > $day_range['start'] && $post_dates[ $dl->post_id ] > $day_range['start'] ) {
					$new_downloads['day'] ++;
				}

				if ( $action_date > $week_range['start'] && $post_dates[ $dl->post_id ] > $week_range['start'] ) {
					$new_downloads['week'] ++;
				}

				if ( $action_date > $month_range['start'] && $post_dates[ $dl->post_id ] > $month_range['start'] ) {
					$new_downloads['month'] ++;
				}

				if ( $action_date > ( $year_range['start'] ?? 0 ) && $post_dates[ $dl->post_id ] > ( $year_range['start'] ?? 0 ) ) {
					$new_downloads['year'] ++;
				}

				// sreies downloads
				if ( ! array_key_exists( $dl->post_id, $post_series ) ) {
					$post_series[ $dl->post_id ] = wp_get_post_terms( $dl->post_id, 'series' );
				}

				// all downloads
				if ( $action_date > $day_range['start'] ) {
					$all_downloads['day'] += $dl->downloads;

					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						if ( ! isset( $series_downloads[ $show_terms->name ]['day'] ) ) {
							$series_downloads[ $show_terms->name ]['day'] = 0;
						}
						$series_downloads[ $show_terms->name ]['day'] += $dl->downloads;
					}
				}

				if ( $action_date > $week_range['start'] ) {
					$all_downloads['week'] += $dl->downloads;

					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						if ( ! isset( $series_downloads[ $show_terms->name ]['week'] ) ) {
							$series_downloads[ $show_terms->name ]['week'] = 0;
						}
						$series_downloads[ $show_terms->name ]['week'] += $dl->downloads;
					}
				}

				if ( $action_date > $month_range['start'] ) {
					$all_downloads['month'] += $dl->downloads;

					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						if ( ! isset( $series_downloads[ $show_terms->name ]['month'] ) ) {
							$series_downloads[ $show_terms->name ]['month'] = 0;
						}
						$series_downloads[ $show_terms->name ]['month'] += $dl->downloads;
					}
				}

				if ( $action_date > ( $year_range['start'] ?? 0 ) ) {
					$all_downloads['year'] += $dl->downloads;

					foreach ( $post_series[ $dl->post_id ] as $show_terms ) {
						if ( ! isset( $series_downloads[ $show_terms->name ]['year'] ) ) {
							$series_downloads[ $show_terms->name ]['year'] = 0;
						}
						$series_downloads[ $show_terms->name ]['year'] += $dl->downloads;
					}
				}
			}
			$download_data = array( 'all' => $all_downloads, 'new' => $new_downloads, 'series' => $series_downloads );

			set_transient( 'rm_podcast_download_summary_data', $download_data, 10 * MINUTE_IN_SECONDS );
		}

		$summary_report = sprintf( '<h2 class="nav-tab-wrapper"><span class="nav-tab nav-tab-active ">%s</span></h2>', __( 'Download Summary', 'rainmaker-simple-podcasting' ) );

		$summary_report .= sprintf( '<h3>%s</h3>', __( 'New Episode Downloads', 'rainmaker-simple-podcasting' ) );
		$summary_report .= '<table class="affwp_table">';

		$table_header = sprintf( '<thead><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr></thead>',
		                         __( 'Today', 'rainmaker-simple-podcasting' ),
		                         __( 'This Week', 'rainmaker-simple-podcasting' ),
		                         __( 'This Month', 'rainmaker-simple-podcasting' ),
		                         __( 'This Year', 'rainmaker-simple-podcasting' )
		);

		$summary_report .= $table_header;
		$summary_report .= sprintf( '<tbody><tr><td>%d</td><td>%d</td><td>%d</td><td>%d</td></tr></tbody>',
		                            $download_data['new']['day'],
		                            $download_data['new']['week'],
		                            $download_data['new']['month'],
		                            $download_data['new']['year']
		);
		$summary_report .= '</table>';

		$summary_report .= sprintf( '<h3>%s</h3>', __( 'All Episode Downloads', 'rainmaker-simple-podcasting' ) );
		$summary_report .= '<table class="affwp_table">';
		$summary_report .= $table_header;
		$summary_report .= sprintf( '<tbody><tr><td>%d</td><td>%d</td><td>%d</td><td>%d</td></tr></tbody>',
		                            $download_data['all']['day'],
		                            $download_data['all']['week'],
		                            $download_data['all']['month'],
		                            $download_data['all']['year']
		);
		$summary_report .= '</table>';

		if ( $download_data['series'] ) {
			$summary_report .= sprintf( '<h3>%s</h3>', __( 'Series Downloads', 'rainmaker-simple-podcasting' ) );
			$summary_report .= '<div class="analytics-table-content">';
			$summary_report .= '<table class="data-table" id="series-download-table"><thead class="table_generator_action_bar">';
			$summary_report .= sprintf( '<tr><th class="analytics-table-heading" onclick="sortTable(0)" data-order="0">%s</th><th class="analytics-table-heading" onclick="sortTable(1)" data-order="1">%s</th><th class="analytics-table-heading" onclick="sortTable(2)" data-order="2">%s</th><th class="analytics-table-heading" onclick="sortTable(3)" data-order="3">%s</th><th class="analytics-table-heading" onclick="sortTable(4)" data-order="4">%s</th></tr></thead>',
			                            __( 'Series', 'rainmaker-simple-podcasting' ),
			                            __( 'Today', 'rainmaker-simple-podcasting' ),
			                            __( 'This Week', 'rainmaker-simple-podcasting' ),
			                            __( 'This Month', 'rainmaker-simple-podcasting' ),
			                            __( 'This Year', 'rainmaker-simple-podcasting' )
			);

			$summary_report .= '<tbody class="loading_tbody">';

			ksort( $download_data['series'] );

			foreach ( $download_data['series'] as $series => $downloads ) {
				$summary_report .= sprintf( '<tr><td>%s</a></td><td>%d</td><td>%d</td><td>%d</td><td>%d</td></tr>',
				                            $series,
					( $downloads['day'] ?? 0 ),
					( $downloads['week'] ?? 0 ),
					( $downloads['month'] ?? 0 ),
					( $downloads['year'] ?? 0 )
				);
			}

			$summary_report .= '</tbody></table>';

			$summary_report .= '<script>jQuery(function(){
								  jQuery("#series-download-table").tablesorter({ sortInitialOrder: "desc" });
								});</script>';
		}

		echo $summary_report;
	}

	public function series_report( $date_range, $series ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$author_podcast_ids = array();
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$podcast_ids = $this->author_podcasts( get_current_user_id(), $series );
		} else {
			$query_ = array(
				'post_type'   => 'podcast',
				'numberposts' => - 1,
				$query_['tax_query'] = array(
					array(
						'taxonomy' => 'series',
						'operator' => 'EXISTS'
					),
				),
			);
			if ( '-all-' !== $series ) {
				$query_['tax_query'] = array(
					array(
						'taxonomy' => 'series',
						'field'    => 'slug',
						'terms'    => $series,
					),
				);
			}
			$podcasts = get_posts( $query_ );

			$podcast_ids = wp_list_pluck( $podcasts, 'ID' );
		}

		$data = $this->get_summary_data( $podcast_ids, $this->get_report_date_range( $date_range ) );

		return $this->get_summary_report( $data, __( 'Series Summary', 'rainmaker-simple-podcasting' ) );
	}

	public function get_summary_data( $post_ids, $date_range ) {
		global $wpdb;

		$logger = new RM_Action_Log();

		$ids     = count( $post_ids ) > 0 ? sprintf( ' AND a.post_id IN (%s) ', implode( ',', $post_ids ) ) : '';

		$raw_sql = "SELECT
    					a.post_id post_id,
    					a.post_title post_title,
    					COUNT(ap.id) plays,
    					COUNT(ac.id) complete,
    					AVG(ast.detail) avg_seconds,
    					COUNT(adl.id) downloads,
    					SUM(upl.count) unique_plays
					FROM
						(
							SELECT
							    *
							FROM wp_rm_actions a
							WHERE
							    a.action_type IN ('podcast-play', 'podcast-stopped', 'podcast-complete', 'podcast-download')
									AND a.action_date BETWEEN %s AND %s $ids
						) a
						LEFT JOIN
			            	wp_rm_actions ap ON a.ID = ap.ID AND ap.action_type = 'podcast-play'
						LEFT JOIN
						    wp_rm_actions ac ON a.ID = ac.ID AND ac.action_type = 'podcast-complete'
						LEFT JOIN
						    wp_rm_actions ast ON a.ID = ast.ID AND ast.action_type = 'podcast-stopped'
						LEFT JOIN
						    wp_rm_actions adl ON a.ID = adl.ID AND adl.action_type = 'podcast-download'
						LEFT JOIN
						    (
								SELECT
								    a.id,
								    play_counts.ct count
								FROM wp_rm_actions a
								LEFT JOIN
									(
										SELECT
											distinct_plays.post_id,
							                COUNT(1)
										ct FROM
											(
												SELECT
													DISTINCT post_id,
								    				detail
												FROM
													wp_rm_actions a
												WHERE a.action_type = 'podcast-play' AND a.action_date BETWEEN %s AND %s $ids
											) AS distinct_plays
										GROUP BY distinct_plays.post_id
									) AS play_counts ON play_counts.post_id = a.post_id
								WHERE a.action_type = 'podcast-play'
								AND a.action_date BETWEEN %s AND %s $ids
								GROUP BY a.post_id, a.id, play_counts.ct
							) AS upl ON a.id = upl.id
					GROUP BY a.post_id, a.post_title
					ORDER BY downloads DESC, plays DESC, unique_plays DESC";



		$sql = $wpdb->prepare( $raw_sql,
		                       $date_range['start'],
		                       $date_range['end'],
		                       $date_range['start'],
		                       $date_range['end'],
		                       $date_range['start'],
		                       $date_range['end']
		);

		$results = $logger->query_action_log( $sql );
		// Filter out unwanted results.
		if ( count( $post_ids ) > 0 ) {
			foreach ( $results as $key => $result ) {
				if ( ! in_array( (int) $result->post_id, $post_ids ) ) {
					unset( $results[ $key ] );
				}
			}
		}

		return $results;
	}

	function get_report_date_range( $date_query ) {
		// add 23:59:59 to end date to include records for the selected end day
		$start = mktime( 0, 0, 0, $date_query['start']['month'], $date_query['start']['day'], $date_query['start']['year'] );
		$end   = mktime( 23, 59, 59, $date_query['end']['month'], $date_query['end']['day'], $date_query['end']['year'] );

		$date_range['start'] = date( 'Y-m-d', $start );
		$date_range['end']   = date( 'Y-m-d H:i:s', $end );

		return $date_range;
	}

	/**
	 * Out put the Podcast Summary report
	 *
	 * @param array #date_range Start and End date for the query
	 *
	 * @since  1.4.2
	 */
	public function get_summary_report( $plays, $title ) {
		$summary_report = sprintf( '<h2 class="nav-tab-wrapper"><span class="nav-tab nav-tab-active ">%s</span></h2>', $title );
		$summary_report .= '<div id="podcast-report-display"></div>';
		$summary_report .= '<div class="analytics-table-content">';
		$summary_report .= '<table id="custom-table-report" class="data-table"><thead class="table_generator_action_bar">';
		$summary_report .= '<tr><th class="analytics-table-heading" onclick="sortTable(0)" data-order="0">Podcast Episode</th><th class="analytics-table-heading" onclick="sortTable(1)" data-order="1">Downloads</th><th class="analytics-table-heading" onclick="sortTable(2)" data-order="2">Total Plays</th><th class="analytics-table-heading" onclick="sortTable(3)" data-order="3">Unique Plays</th><th class="analytics-table-heading" onclick="sortTable(4)" data-order="4">Completed</th><th class="analytics-table-heading" onclick="sortTable(5)" data-order="5">Average Duration</th></tr></thead>';
		$summary_report .= '<tbody class="loading_tbody">';

		$total_downloads    = 0;
		$total_plays        = 0;
		$total_unique_plays = 0;
		$total_completed    = 0;
		$total_avg_seconds  = 0;
		$avg_count          = 0;

		foreach ( $plays as $p ) {
			// don't show podcast id 0 on report
			if ( $p->post_id ) {
				$complete_percent = ( $p->complete > 0 && $p->plays > 0 ) ? ( $p->complete / $p->plays ) * 100 : 0;
				$summary_report   .= sprintf( '<tr><td><a href="javascript:void(0)" class="podcast_detail" data-podcast="%d">%s</a></td><td>%d</td><td>%d</td><td>%d</td><td>%d (%s%%)</td><td>%s</td>',
				                              $p->post_id,
				                              $p->post_title,
				                              $p->downloads,
				                              $p->plays,
				                              $p->unique_plays,
				                              $p->complete,
				                              round( $complete_percent, 1 ),
				                              gmdate( "H:i:s", $p->avg_seconds )
				);

				$total_downloads    += $p->downloads;
				$total_plays        += $p->plays;
				$total_unique_plays += $p->unique_plays;
				$total_completed    += $p->complete;
				if ( $p->avg_seconds > 0 ) {
					$total_avg_seconds += $p->avg_seconds;
					$avg_count ++;
				}
			}
		}

		$summary_report .= '</tbody>';

		// totals
		$summary_report .= sprintf( '<tfoot><tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td><td>%s</td></tfoot>',
		                            __( 'Total', 'rainmaker-simple-podcasting' ),
		                            $total_downloads,
		                            $total_plays,
		                            $total_unique_plays,
		                            $total_completed,
		                            ( $avg_count ) ? gmdate( "H:i:s", $total_avg_seconds / $avg_count ) : ''
		);

		$summary_report .= '</table>';
		
		return $summary_report;
	}

	public function summary_report( $date_range ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_others_posts' ) && ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$author_podcast_ids = array();
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$author_podcast_ids = $this->author_podcasts( get_current_user_id() );
		}

		$summary_data = $this->get_summary_data( $author_podcast_ids, $this->get_report_date_range( $date_range ) );

		return $this->get_summary_report( $summary_data, __( 'Podcast Summary', 'rainmaker-simple-podcasting' ) );
	}

	public function episode_report( $date_range, $podcast_id ) {
		$date_range = $this->get_report_date_range( $date_range );

		$actions = array( 'podcast-play', 'podcast-stopped', 'podcast-complete', 'podcast-download' );
		$logger  = new RM_Action_Log();
		$episode = $logger->get_action_log( $actions, $date_range['start'], $date_range['end'], $podcast_id );

		$detail_rows      = array();
		$plays            = 0;
		$completes        = 0;
		$downloads        = 0;
		$durations        = array();
		$duration_counts  = array();
		$stops            = 0;
		$duration_seconds = 0;

		foreach ( $episode as $e ) {
			$action_date = date( 'Y-m-d', strtotime( $e->action_date ) );
			switch ( $e->action_type ) {
				case 'podcast-play':
					$plays ++;
					if ( ! isset( $detail_rows[ $action_date ]['plays'] ) ) {
						$detail_rows[ $action_date ]['plays'] = 0;
					}
					$detail_rows[ $action_date ]['plays'] += 1;
					break;
				case 'podcast-complete':
					$completes ++;
					//$detail_rows[$action_date]['completes'] += 1;
					break;
				case 'podcast-download':
					$downloads ++;
					if ( ! isset( $detail_rows[ $action_date ]['downloads'] ) ) {
						$detail_rows[ $action_date ]['downloads'] = 0;
					}
					$detail_rows[ $action_date ]['downloads'] += 1;
					break;
				case 'podcast-stopped':
					$stops ++;
					//$detail_rows[$action_date]['stops'] += 1;
					//$duration_counts[$e->detail] += 1;
					if ( is_numeric( $e->detail ) ) {
						if ( ! isset( $duration_counts[ floor( $e->detail / 60 ) ] ) ) {
							$duration_counts[ floor( $e->detail / 60 ) ] = 0;
						}
						$duration_counts[ floor( $e->detail / 60 ) ] += 1;
						array_push( $durations, $e->detail );
					}

					break;
			}
		}

		$podcast_duration = get_post_meta( $podcast_id, 'duration', true );
		if ( $podcast_duration ) {
			if ( count( explode( ':', $podcast_duration ) ) === 2 ) {
				$podcast_duration = '00:' . $podcast_duration;
			}

			// user can put anything for duration, make sure its hh:mm:ss
			if ( preg_match( '/\\d+:\\d+:\\d+/', $podcast_duration ) ) {
				$duration_seconds = strtotime( "1970-01-01 $podcast_duration UTC" );
			} else {
				// no duration, use the max listen length.
				$duration_seconds = max( array_keys( $duration_counts ) ) * 60;
			}
		}

		$episode_report = sprintf( '<h2 class="nav-tab-wrapper"><span class="nav-tab nav-tab-active ">%s: %s</span></h2>',
		                           __( 'Episode Detail', 'rainmaker-simple-podcasting' ),
		                           get_the_title( $podcast_id ) );
		$episode_report .= '<table id="affwp_total_earnings" class="affwp_table">';
		$episode_report .= sprintf( '<thead><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr></thead>',
		                            __( 'Total Plays', 'rainmaker-simple-podcasting' ),
		                            __( 'Completed', 'rainmaker-simple-podcasting' ),
		                            __( 'Stopped (Avg. Duration)', 'rainmaker-simple-podcasting' ),
		                            __( 'Downloads', 'rainmaker-simple-podcasting' )
		);
		$episode_report .= sprintf( '<tbody><tr><td>%d</td><td>%d</td><td>%d (%s)</td><td>%d</td></tr></tbody>',
		                            $plays,
		                            $completes,
		                            $stops,
		                            count( $durations ) > 0 ? gmdate( "H:i:s", array_sum( $durations ) / count( $durations ) ) : 'N/A',
		                            $downloads
		);
		$episode_report .= '</table>';

		// plays chart
		$play_rows     = array();
		$download_rows = array();
		foreach ( $detail_rows as $d => $detail ) {
			if ( isset( $detail['plays'] ) && $detail['plays'] > 0 ) {
				array_push( $play_rows, array( sprintf( "'%s'", $d ), $detail['plays'] ) );
			}

			if ( isset( $detail['downloads'] ) && $detail['downloads'] > 0 ) {
				array_push( $download_rows, array( sprintf( "'%s'", $d ), $detail['downloads'] ) );
			}
		}

		$play_options = array(
			'title'     => "'Episode Plays'",
			'hAxis'     => "{title: 'Date'}",
			'vAxis'     => "{title: 'Plays'}",
			'isStacked' => true,
			'colors'    => "['#0274be', '#02be94']",
		);

		$play_chart_data = array(
			'headers'    => array( 'Date', 'Plays' ),
			'rows'       => $play_rows,
			'chart_type' => 'ColumnChart',
			'chart_div'  => 'podcast_play_report_display',
			'options'    => $play_options,
		);

		if ( ! function_exists( 'google_chart' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'google-chart.php';
		}

		// do chart
		$episode_report .= '<div id="podcast_play_report_display" style="width: 800px; height: 400px;"></div>';
		$episode_report .= google_chart( $play_chart_data );

		$download_options = array(
			'title'  => "'Episode Downloads'",
			'hAxis'  => "{title: 'Date'}",
			'vAxis'  => "{title: 'Downloads'}",
			'colors' => "['#0274be', '#02be94']",
		);

		$download_chart_data = array(
			'headers'    => array( 'Date', 'Downloads' ),
			'rows'       => $download_rows,
			'chart_type' => 'ColumnChart',
			'chart_div'  => 'podcast_download_report_display',
			'options'    => $download_options,
		);

		// do chart
		$episode_report .= '<div id="podcast_download_report_display" style="width: 800px; height: 400px;"></div>';
		$episode_report .= google_chart( $download_chart_data );

		// stops chart
		$stop_rows = array();
		$cur_plays = $plays;
		array_push( $stop_rows, array( 0, $plays ) );
		ksort( $duration_counts );
		foreach ( $duration_counts as $d => $count ) {
			$cur_plays = $cur_plays - $count;
			array_push( $stop_rows, array( $d, $cur_plays ) );
		}
		array_push( $stop_rows, array( intval( $duration_seconds / 60 ), $completes ) );

		$stop_options = array(
			'title'  => "'Episode Duration'",
			'hAxis'  => "{title: 'Minutes'}",
			'vAxis'  => "{title: 'Listeners', minValue: '0'}",
			'colors' => "['#0274be', '#02be94']",
			//'curveType' => "'function'",
			'legend' => "'none'",
		);

		$stop_chart_data = array(
			'headers'    => array( 'Duration', 'Listeners' ),
			'rows'       => $stop_rows,
			'chart_type' => 'LineChart',
			'chart_div'  => 'podcast_stop_report_display',
			'options'    => $stop_options,
		);

		// do chart
		$episode_report .= '<div id="podcast_stop_report_display" style="width: 800px; height: 400px;"></div>';
		$episode_report .= google_chart( $stop_chart_data );

		return $episode_report;
	}

}
