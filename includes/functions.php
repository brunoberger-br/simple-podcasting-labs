<?php
/**
 * Rainmaker - Simple Podcasting (Labs).
 *
 * @package \Rainmaker\Simple_Podcasting\Labs
 * @author  Rainmaker Digital
 * @license GPL-2.0+
 * @link    https://www.rainmakerdigital.com/
 */

namespace Rainmaker\Simple_Podcasting\Labs;

/**
 * Autoload classes from a list.
 *
 * @param string $class Name of class to be autoloaded.
 */
function autoload( $class ) {
	$classes = [
		'Podcast_Reports',
	];

	if ( in_array( $class, $classes ) ) {
		$filename = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';

		/** @noinspection PhpIncludeInspection */ // phpcs:ignore
		require_once RMSP_EXTEND_DIR_PATH . '/classes/' . $filename;
	}
}

/**
 * Remove default action for admin reports.
 */
function remove_default_podcast_reports_admin() {
	global $ss_podcasting;

	// Show Podcast Reports.
	remove_action( 'genesis_admin_init', [ $ss_podcasting, 'podcast_reports_admin' ], 99 );
}

/**
 * Load admin reports.
 */
function podcast_reports_admin() {
	require_once RMSP_EXTEND_DIR_PATH . '/classes/class-podcast-reports-admin.php';
}


/**
 * Add sortable arrows to Podcast Reports table
 */
function sortable_report_table() {
    $css  = '';

if ( isset( $_GET['page'] ) && $_GET['page'] == 'podcast-reports' ) {
    $css .= "th.analytics-table-heading { cursor: pointer; }\n";
    $css .= 'th.analytics-table-heading::after { content: " \25BE"; }'."\n";
    $css .= 'th.analytics-table-heading.down-arrow::after { content: " \25B4"; }'."\n";
	$css .= "tfoot td { color: #333 !important; font-size: 14px !important; font-style: normal !important;  font-weight: 600; font-family: inherit !important; }\n";
}
    echo "<style>$css</style>";
}

/**
 * Add pagination to Podcast Reports table
 */

function add_reports_pagination() {

// Get current page number
$current_page = max(1, get_query_var('paged'));

// Set number of reports per page
$reports_per_page = 10;

// Get total number of reports
$post_counts = wp_count_posts('podcast-reports');
$total_reports = isset($post_counts->publish) ? $post_counts->publish : 0;

// Calculate total number of pages
$total_pages = ceil($total_reports / $reports_per_page);

// Set query arguments for the reports query
$args = array(
    'post_type' => 'podcast-reports',
    'posts_per_page' => $reports_per_page,
    'paged' => $current_page,
    'order' => 'DESC',
    'orderby' => 'date',
);

// Get reports
$reports = new \WP_Query($args);

// Display reports
if ($reports->have_posts()) :
    while ($reports->have_posts()) : $reports->the_post();
        // Display report content
    endwhile;

    // Display pagination
    echo '<div class="pagination">';
    echo paginate_links(array(
        'base' => str_replace(9999999, '%#%', esc_url(get_pagenum_link(9999999))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $total_pages,
        'type' => 'list'
    ));
    echo '</div>';

endif;

// Reset post data
wp_reset_postdata();


}
