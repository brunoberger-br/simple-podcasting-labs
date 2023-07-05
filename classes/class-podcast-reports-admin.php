<?php

class Podcast_Reports_Admin extends Genesis_Admin {

	public $options;

	function __construct() {

		$this->options = get_option( 'Rainmaker_Action_Log' );

		// Specify a unique page ID.
		$page_id = 'podcast-reports';

		$menu_ops = array(
			'submenu' => array(
				'parent_slug' => 'rm_reports',
				'page_title'  => __( 'Podcast Reports', 'rainmaker-simple-podcasting' ),
				'menu_title'  => __( 'Podcast Reports', 'rainmaker-simple-podcasting' ),
				'capability'  => 'edit_posts',
			),
		);

		// Set up page options. These are optional, so only uncomment if you want to change the defaults.
		$page_ops = array();

		$settings_field = '';

		$default_settings = array();

		// Create the Admin Page.
		$this->create( $page_id, $menu_ops, $page_ops, $settings_field, $default_settings );

		// actions
		add_action( 'init', array( $this, 'podcast_report_init' ) );

	}

	/**
	 * Init: Register AJAX
	 *
	 * @since 0.1.0
	 */
	public function podcast_report_init() {
		// require_once( plugin_dir_path(__FILE__) . 'reports-class.php' );
		add_action( 'wp_ajax_podcast_report_run', array( $this, 'report_run' ) );
		add_action( 'wp_ajax_podcast_series_episodes', array( $this, 'series_episodes' ) );

		add_action( 'wp_dashboard_setup', array( $this, 'add_meta_box' ) );
		add_action( 'wp_ajax_podcast-statistics', array( $this, 'handle_ajax_statistics' ) );

		// tablesorter
		wp_enqueue_script( 'series_table_sort', RMSP_EXTEND_PODCAST_REPORTS_SCRIPTS . 'jquery.tablesorter.min.js', 'jquery', null, true );

	}

	/**
	 * Run Report
	 *
	 * @since 0.1.0
	 */
	public function report_run() {
		$range      = $_POST['report_range'];
		$start_date = $_POST['report_start'];
		$end_date   = $_POST['report_end'];

		if ( $range != 'custom' ) {
			// get dates
			$dates = Rainmaker_Dates::get_date_range( $range );
		} else {
			// get custom dates
			$dates['start']['year']  = date( 'Y', strtotime( $start_date ) );
			$dates['start']['month'] = date( 'n', strtotime( $start_date ) );
			$dates['start']['day']   = date( 'j', strtotime( $start_date ) );

			$dates['end']['year']  = date( 'Y', strtotime( $end_date ) );
			$dates['end']['month'] = date( 'n', strtotime( $end_date ) );
			$dates['end']['day']   = date( 'j', strtotime( $end_date ) );
		}

		/*
		$date_query = array(
			array (
				'after'	=> array (
						'year'	=> $dates['start']['year'],
						'month'	=> $dates['start']['month'],
						'day'	=> $dates['start']['day'],
						'hour' 	=> 0,
						'minute' => 0,
						'second' => 0
					),
				'before'	=> array (
					'year'	=> $dates['end']['year'],
					'month'	=> $dates['end']['month'],
					'day'	=> $dates['end']['day'],
					'hour' 	=> 23,
					'minute' => 59,
					'second' => 59
				),
				'inclusive'	=> true
			)
		);
		*/

		maybe_Log_event( 'podcast_report: ' . $_POST['report_type'], 'Podcast_Reports_Admin' );

		$this->run_podcast_report( $dates, $_POST['report_type'], $_POST['detail_id'] );
	}

	public function run_podcast_report( $date_query, $report, $id ) {

		$report_method = $report . '_report';
		// $this->$report_method( $date_range, $id );
		$podcast_reports = new Podcast_Reports();
		$report          = $podcast_reports->$report_method( $date_query, $id );
		echo $report;

		die();
	}

	/**
	 * Get Episodes by Series
	 */
	public function series_episodes() {

		$series_episodes = $this->get_podcast_episodes( $_POST['series'] );

		$episode_list = '';
		foreach ( $series_episodes as $k => $e ) {
			$episode_list .= printf( '<option value="%d">%s</option>', $k, $e );
		}
		echo $episode_list;
		die();
	}

	/**
	 * Return array of Podcast episodes, optionally by Series
	 */
	function get_podcast_episodes( $series = '' ) {

		$episodes = array();

		$query_args = array(
			'post_type'      => 'podcast',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'order'          => 'DESC',
			'orderby'        => 'date',
		);

		// authors can only see their own episodes
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$query_args['author'] = get_current_user_id();
		}

		if ( $series ) {
			$query_args['series'] = $series;
		}

		$post_episodes = get_posts( $query_args );
		$product_query = new WP_Query( $query_args );

		if ( $post_episodes ) {
			foreach ( $post_episodes as $episode ) {
				$episodes[ $episode->ID ] = $episode->post_title;
			}
		}

		return $episodes;
	}

	/**
	 * Initialize the admin
	 *
	 * @since 0.1.0
	 */
	function admin() {
		if ( function_exists( '\Rainmaker\Core\API\Module\factory' ) ) {
			$plugin     = \Rainmaker\Core\API\Module\factory()->get_plugin( 'rainmaker-ui' );
			$assets_url = untrailingslashit( $plugin->{'dir_url'} ) . '/dist/assets';
		} else {
			$assets_url = content_url( 'mu-plugins/synthesis/library/rainmaker-ui/dist/assets' );
		}
		?>
		<div class="wrap _conversion-reports">
			<h2><?php _e( 'Podcast Reports', 'rainmaker-simple-podcasting' ); ?></h2>
			<?php $this->_parameters(); ?>
		<p>
			<a class="button-primary" id="report-button"><?php _e( 'View Report', 'rainmaker-simple-podcasting' ); ?></a>
		</p>
		</form>
		<div id="report_loading" style="display: none;" class="synthesis-loading"><img src="<?php echo $assets_url . '/images/preloader.gif'; ?>" title="Loading..." alt="Loading..."></div>
		<div id="podcast-report-display"></div>
		<?php
		// temp date query args
		$date_query = array(
			array(
				'after'  => array(
					'year'  => date( 'Y' ),
					'month' => date( 'm' ) - 1,
					'day'   => 1,
				),
				'before' => array(
					'year'  => date( 'Y' ),
					'month' => date( 'm' ) + 1,
					'day'   => 1,
				),
			),
		);
		?>


		</div>
		<?php
	}

	/**
	 * Display the parameters form
	 *
	 * @since 0.1.0
	 */
	private function _parameters() {
		$report           = '';
		$default_selected = '';
		$default_start    = '';
		$default_end      = '';
		$auto_run         = 'false';
		$series           = $this->get_podcast_series();

		$default_args = apply_filters(
			'premise_member_report_default_args',
			array(
				'start'   => date( 'n/j/Y', time() ),
				'end'     => date( 'n/j/Y', time() ),
				'type'    => '',
				'product' => 0,
			)
		);
		$args         = wp_parse_args( $_GET, $default_args );

		if ( $args['type'] != '' ) {
			// $default_selected = '';
			$default_date     = current_time( 'timestamp' );
			$default_selected = ' selected="selected"';

			$args = $_GET;
			$args = wp_parse_args( $args, $default_args );

			$default_start = $args['start'];
			$default_end   = $args['end'];
			$report        = $args['type'];
			$podcast_id    = $args['podcast'];
			$auto_run      = 'true';
		}

		if ( function_exists( '\Rainmaker\Core\API\Module\factory' ) ) {
			$plugin     = \Rainmaker\Core\API\Module\factory()->get_plugin( 'rainmaker-ui' );
			$assets_url = untrailingslashit( $plugin->{'dir_url'} ) . '/dist/assets';
		} else {
			$assets_url = content_url( 'mu-plugins/synthesis/library/rainmaker-ui/dist/assets' );
		}
		?>

	<script>
		var autorun = <?php echo esc_html( $auto_run ); ?>;
		var report = '<?php echo esc_html( $report ); ?>';
	</script>
		<form name="report_run" id="report_run" method="post">
			<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e( 'Select Report', 'rainmaker-simple-podcasting' ); ?></th>
					<td>
						<select id="report_type" name="report_type">
							<?php if ( $series ) { ?>
							<option value="series"><?php _e( 'Series Summary', 'rainmaker-simple-podcasting' ); ?></option>
							<?php } else { ?>
							<option value="summary"><?php _e( 'Podcast Summary', 'rainmaker-simple-podcasting' ); ?></option>
							<?php } ?>
							<option value="episode"><?php _e( 'Episode Detail', 'rainmaker-simple-podcasting' ); ?></option>
							<option value="downloads"><?php _e( 'Download Summary', 'rainmaker-simple-podcasting' ); ?></option>
							<?php if ( $series ) { ?>
							<option value="monthly_downloads"><?php _e( 'Monthly Download Summary', 'rainmaker-simple-podcasting' ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr valign="top" id="date_selection">
					<th scope="row"><?php _e( 'Date Range', 'rainmaker-simple-podcasting' ); ?>:</th>
					<td>
					<select id="report-date-range" name="report_range">
						<option value="today"><?php _e( 'Today', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="yesterday"><?php _e( 'Yesterday', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="this_week"><?php _e( 'This Week', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="last_week"><?php _e( 'Last Week', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="this_month"><?php _e( 'This Month', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="last_month"><?php _e( 'Last Month', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="this_quarter"><?php _e( 'This Quarter', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="last_quarter"><?php _e( 'Last Quarter', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="this_year"><?php _e( 'This Year', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="last_year"><?php _e( 'Last Year', 'rainmaker-simple-podcasting' ); ?></option>
						<option value="custom" <?php echo $default_selected; ?>><?php _e( 'Custom', 'rainmaker-simple-podcasting' ); ?></option>
					</select>
					</td>
				</tr>
				<tr valign="top" class="custom-date-range">
					<th scope="row"><?php _e( 'Start Date', 'rainmaker-simple-podcasting' ); ?></th>
					<td>
						<input type="text" id="report_start" name="report_start" class="ga_datepicker" value="<?php echo $default_start; ?>" required >
					</td>
				</tr>
				<tr valign="top" class="custom-date-range">
					<th scope="row"><?php _e( 'End Date', 'rainmaker-simple-podcasting' ); ?></th>
					<td>
						<input type="text" id="report_end" name="report_end" class="ga_datepicker" value="<?php echo $default_end; ?>" required >
					</td>
				</tr>

				<?php if ( $series ) { ?>

				<tr valign="top" id="series_selection" <?php echo ( $series ) ? '' : 'style="display: none;"'; ?>>
					<th scope="row"><?php _e( 'Select Series', 'rainmaker-simple-podcasting' ); ?>:</th>
					<td>
						<select id="report_series" name="report_series">
							<?php
							printf( '<option value="-all-">All Series</option>' );
							foreach ( $series as $k => $s ) {
								printf( '<option value="%s">%s</option>', $k, $s );
							}
							?>
						</select>

					</td>
				</tr>
					<?php
				}
				?>

				<tr valign="top" id="episode_selection" style="display: none;">
					<th scope="row"><?php _e( 'Select Episode', 'rainmaker-simple-podcasting' ); ?>:</th>
					<td>
					<div id="episodes_loading" style="display: none;"><img src="<?php echo $assets_url . '/images/preloader.gif'; ?>" title="Loading..." alt="Loading..."></div>

						<?php
						// use first series on initial load
						$episodes = false;

						if ( is_array( $series ) && count( $series ) ) {
							$episodes = $this->get_podcast_episodes( array_keys( $series )[0] );
						}

						if ( $episodes ) {
							?>
						<select id="report_episodes" name="report_episodes">
							<!--  <option value="-1"><?php _e( '--All Products--', 'rainmaker-simple-podcasting' ); ?></option> -->
							<?php
							if ( ! $series ) {
								foreach ( $episodes as $k => $e ) {
									printf( '<option value="%s">%s</option>', $k, $e );
								}
							}
							?>
						</select>
							<?php
						}
						?>
					</td>
				</tr>

				<tr valign="top" style="display: none;" id="report_detail_id">
					<th scope="row"><span id="detail_id_label"></span></th>
					<td>
						<input type="text" id="detail_id" name="detail_id" class="" value="" required >
					</td>
				</tr>
			</tbody>
			</table>

		<?php
	}

	/**
	 * Return array of Podcast Series with at least one episode
	 */
	function get_podcast_series() {

		$series = array();
		$terms  = array();

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$podcast_reports    = new Podcast_Reports();
			$author_podcast_ids = $podcast_reports->author_podcasts( get_current_user_id() );

			foreach ( $author_podcast_ids as $podcast_id ) {
				$podcast_series_list = wp_get_post_terms( $podcast_id, 'series' );

				foreach ( $podcast_series_list as $s ) {
					if ( ! in_array( $s, $series ) ) {
						$terms[] = $s;
					}
				}
			}
		} else {
			$terms = get_terms( 'series' );
		}

		if ( count( $terms ) > 0 ) {
			foreach ( $terms as $term ) {
				if ( $term->count > 0 ) {
					$series[ $term->slug ] = $term->name;
				}
			}
		}

		return $series;
	}

	/**
	 * Initialize the settings page, by enqueuing scripts
	 *
	 * @since 0.1.0
	 */
	public function settings_init() {
		add_action( 'load-' . $this->pagehook, array( $this, 'scripts' ) );
	}

	/**
	 * Load scripts
	 *
	 * @since 0.1.0
	 */
	public function scripts() {
		$plugin = Rainmaker\Core\API\Module\get_plugin_instance( 'rainmaker-simple-podcasting' );

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'premise-date-picker', $plugin->dir_url . 'assets/css/date-picker.css', RAINMAKER_BUILD_VERSION );

		wp_enqueue_script( 'podcast-reports', RMSP_EXTEND_PODCAST_REPORTS_SCRIPTS . 'podcast-reports.js', array( 'jquery' ), true );

		// localize script text
		// wp_localize_script( 'rainmaker-simple-podcasting_scipt', 'rainmaker-simple-podcasting_vars', array(
		// 'report'        => __( 'Report', 'rainmaker-simple-podcasting' ),
		// )
		// );

		wp_register_script(
			'rm-googleCharts-js',
			'//www.google.com/jsapi',
			array()
		);
		wp_enqueue_script( 'rm-googleCharts-js' );
	}

	function add_meta_box() {

		$podcast_count = wp_count_posts( 'podcast' );
		if ( $podcast_count->publish > 0 ) {
			$screen = get_current_screen();
			add_meta_box( 'podcast_downloads_summary_dashboard', __( 'Podcast Downloads Summary', 'rainmaker-simple-podcasting' ), array( $this, 'podcast_downloads_summary_metabox' ), $screen, 'side', 'high' );
		}
	}

	function podcast_downloads_summary_metabox() {

		$author_podcast_ids = array();
		$show_reports_link  = false;
		$all_time_dl        = -1;
		$add_prototype      = false;

		$sql = 'select count(*) from wp_rm_actions where action_type = \'podcast-download\'';

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$add_prototype = true;

			$podcast_reports    = new Podcast_Reports();
			$author_podcast_ids = $podcast_reports->author_podcasts( get_current_user_id() );

			if ( count( $author_podcast_ids ) > 0 ) {
				$sql              .= sprintf( ' AND post_id IN (%s) ', implode( ',', $author_podcast_ids ) );
				$show_reports_link = true;
			} else {
				$all_time_dl       = 0;
				$show_reports_link = true;
			}
		}

		// Show all podcasts for editors, need to include prototype and show link
		if ( ( ! $add_prototype && ! current_user_can( 'manage_options' ) ) || current_user_can( 'manage_options' ) ) {
			$add_prototype     = true;
			$show_reports_link = true;
		}

		if ( $all_time_dl < 0 ) {
			$logger      = new RM_Action_Log();
			$all_time_dl = $logger->getvar_action_log( $sql );
		}
		?>
<div class="synthesis-widget podcast-statistics">
		<?php wp_nonce_field( 'podcast-statistics', 'podcast-statistics-nonce', false ); ?>
	<ul class="filter">
		<li class="this_week"><button>This Week</button></li>
		<li class="this_month"><button>This Month</button></li>
		<li class="this_year"><button>This Year</button></li>
	</ul>

	<div class="synthesis-loading" style="display:none">
		<img src="<?php echo dirname( dirname( plugin_dir_url( __FILE__ ) ) ); ?>/rainmaker-ui/dist/assets/images/preloader.gif" title="Loading..." alt="Loading..." />
	</div>

	<ul class="chart-list-horizontal chart-week"></ul>
	<ul class="chart-list-vertical chart-month">
		<li class="scale">
			<span class="prototype"></span>
		</li>
	</ul>

	<ul class="chart-list-vertical chart-year">
		<li class="scale">
			<span class="prototype"></span>
		</li>
	</ul>
	<p class="label">All Time: <?php echo $all_time_dl; ?> </p>

		<?php
		if ( $show_reports_link ) {
			$reports_url = admin_url( 'admin.php?page=podcast-reports' );
			?>
	<p class="author_podcast_reports_link"><a href="<?php echo $reports_url; ?>">View Podcast Reports</a></p>
	<?php } ?>
</div>

		<?php if ( $add_prototype ) { ?>
<div class="synthesis-widget synthesis-summary">
<ul class="bar-chart-prototype">
		<li class="prototype">
			<span class="number tooltip"></span>
			<span class="index">
				<span class="orders"></span>
				<span class="refunds"></span>
			</span>
			<span class="label"></span>
		</li>
	</ul>

	<div class="synthesis-loading" style="display:none">
		<img src="<?php echo SPUI_IMAGES_URL; ?>preloader.gif" title="Loading..." alt="Loading..." />
	</div>

	<div class="summary-charts">
		<ul class="chart-list-horizontal chart-products"></ul>
		<ul class="chart-list-horizontal chart-accesslevels"></ul>
		<ul class="chart-list-horizontal chart-coupons"></ul>
	</div>
</div>
		<?php } ?>

<script type="text/javascript">
//<!--
function podcast_insert_summary_chart(selector, value, direction){

	// default to horizontal chart
	direction = direction || 'width';

	if (!value.name)
		return;

	$child = jQuery('.synthesis-summary .bar-chart-prototype li.prototype').clone().removeClass('prototype');

	$child.children('.label').html(value.name);
	$child.find('.index .orders').attr('style', direction + ':' + value.orderwidth + '%');

	if (value.date)
		$child.children('.tooltip').removeClass('number').html(value.date + '<br />' + value.orders + ' orders');
	else
		$child.children('.number').removeClass('tooltip').html(value.orders);

	if (!value.refunds)
		$child.find('.index .refunds').remove();
	else
		$child.find('.index .refunds').html(value.refunds).attr('style', direction + ':' + value.refundwidth + '%');

	if (value.current)
		$child.addClass('current');

	jQuery(selector).append($child);

}

jQuery(document).ready(function($){

	$('.podcast-statistics > ul:not(.filter)').hide();
	$('.podcast-statistics .filter li').click(function(){

		$('.podcast-statistics > ul:not(.filter)').hide();

		$('.podcast-statistics .filter li').removeClass('active');

		$type = $(this).attr('class');
		$(this).addClass('active');

		if ('this_week' == $type) {

			if (! $('.podcast-statistics .chart-week li').length) {

				$('.podcast-statistics .synthesis-loading').show();

				$.post( ajaxurl,
					{
						'action': 'podcast-statistics',
						'nonce': $('#podcast-statistics-nonce').val(),
						'type': $type
					},
					function(data,status){

						$.each(data, function(index, value){
							podcast_insert_summary_chart('.podcast-statistics .chart-week', value);
						});
						$('.podcast-statistics .synthesis-loading').hide();
					},
					'json'
				);
			}
			$('.podcast-statistics .chart-week').show();

			return;
		}

		selector = null;
		if ('this_month' == $type) {
			selector = '.podcast-statistics .chart-month';
		} else if ('this_year' == $type) {
			selector = '.podcast-statistics .chart-year';
		}

		if (! selector)
			return;

		if (! $(selector + ' li:not(.scale)').length) {
			//@todo: show loading icon
			$.post( ajaxurl,
				{
					'action': 'podcast-statistics',
					'nonce': $('#podcast-statistics-nonce').val(),
					'type': $type
				},
				function(data,status){
					if (!$(selector + ' .scale span:not(.prototype)').length) {
						scale_increment = data.scale / 10;
						for (c = data.scale; c >= 0; c -= scale_increment) {
							$scale = $(selector + ' .scale .prototype').clone().removeClass('prototype');
							$scale.html(c);
							if (c == data.scale)
								$scale.addClass('max');

							$(selector + ' .scale').append($scale);
						}
					}
					$.each(data, function(index, value){
						podcast_insert_summary_chart(selector, value, 'height');
					});
					//@todo: hide loading icon
				},
				'json'
			);
		}
		$(selector).show();

	});

	// lazy load this week's data
	$('.podcast-statistics .filter li.this_week').click();
});

//-->
</script>
		<?php
	}

	function handle_ajax_statistics() {

		$author_podcast_ids = array();
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$podcast_reports    = new Podcast_Reports();
			$author_podcast_ids = $podcast_reports->author_podcasts( get_current_user_id() );

			// author has no podcasts, stop here.
			if ( count( $author_podcast_ids ) == 0 ) {
				return;
			}
		}

		// validate post back.
		if ( ! isset( $_POST['type'] ) || ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'podcast-statistics' ) ) {
			exit( '0' );
		}

		// set up summary query
		$date_query = Rainmaker_Dates::get_date_range( $_POST['type'] );

		// add 23:59:59 to end date to include records for the selected end day
		$start               = mktime( 0, 0, 0, $date_query['start']['month'], $date_query['start']['day'], $date_query['start']['year'] );
		$end                 = mktime( 23, 59, 59, $date_query['end']['month'], $date_query['end']['day'], $date_query['end']['year'] );
		$date_range['start'] = date( 'Y-m-d', $start );
		$date_range['end']   = date( 'Y-m-d H:i:s', $end );

		$group_by = ( 'this_year' == $_POST['type'] ) ? 'MONTH(action_date) ' : 'DATE(action_date) ';
		$sql      = sprintf( 'SELECT COUNT(*) podcast_downloads, DATE(action.action_date) action_date FROM wp_rm_actions AS action INNER JOIN wp_posts as post ON post.ID = action.post_id AND post.post_status = \'publish\'  WHERE action.action_type = \'podcast-download\' AND action.action_date BETWEEN \'%s\' AND \'%s\' ', $date_range['start'], $date_range['end'] );

		if ( count( $author_podcast_ids ) > 0 ) {
			$sql .= sprintf( ' AND post_id IN (%s) ', implode( ',', $author_podcast_ids ) );
		}

		$sql .= sprintf( ' GROUP BY %s', $group_by );

		$logger    = new RM_Action_Log();
		$downloads = $logger->query_action_log( $sql );

		$data_points = array();
		$sort_type   = SORT_NUMERIC;

		foreach ( $downloads as $dl ) {
			$action_time = strtotime( $dl->action_date );

			$data_point = array(
				'orders'     => $dl->podcast_downloads,
				'orderwidth' => 0,
			);

			// mark the current data point
			if ( empty( $data_points ) ) {
				$data_point['current'] = 1;
			}

			switch ( $_POST['type'] ) {
				case 'this_week':
					$data_point['name'] = date( 'l', $action_time );
					$sort_type          = SORT_REGULAR;
					break;
				case 'this_month':
					$data_point['name'] = date( 'j', $action_time );
					$data_point['date'] = date( 'd/m/Y', $action_time );
					$sort_type          = SORT_REGULAR;
					break;
				case 'this_year':
					$data_point['name'] = date( 'M', $action_time );
					$data_point['date'] = date( 'm/Y', $action_time );
					$sort_type          = SORT_REGULAR;
					break;
			}

			$data_points[ 'ts' . $action_time ] = $data_point;

		}

		ksort( $data_points, $sort_type );
		$data_points = $this->calculate_bar_graph_range( $data_points, true );

		// response data structure
		$response = array( 'scale' => isset( $data_points['scale'] ) ? $data_points['scale'] : 10 );
		unset( $data_points['scale'] );

		// sort ascending by date
		ksort( $data_points, $sort_type );
		$response = array_merge( $response, $data_points );
		echo json_encode( $response );
		die;
	}

	function calculate_bar_graph_range( $data_points, $include_scale = false ) {

		// get the max value for the graphs
		$max = 0;
		foreach ( $data_points as $element ) {

			if ( isset( $element['orders'] ) ) {
				$max = max( $max, $element['orders'] );
			}
		}

		$max = (int) ceil( $max / 10 ) * 10;
		if ( ! $max ) {
			return $data_points;
		}

		// now calculate width/height percentage
		foreach ( $data_points as $id => $element ) {
			$data_points[ $id ]['orderwidth'] = (int) ( $element['orders'] / $max * 100 );
		}

		if ( $include_scale ) {
			$data_points['scale'] = $max;
		}

		return $data_points;
	}

}

new Podcast_Reports_Admin();
