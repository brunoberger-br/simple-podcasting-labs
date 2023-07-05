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

add_action( 'genesis_pre_framework', __NAMESPACE__ . '\\remove_default_podcast_reports_admin' );

// Show custom Podcast Reports.
add_action( 'genesis_admin_init', __NAMESPACE__ . '\\podcast_reports_admin', 99 );

// Add sortable arrows to Podcast Reports table
add_action( 'genesis_admin_init', __NAMESPACE__ . '\\sortable_report_table' );

// Add pagination feature to Podcast Reports table
add_action( 'genesis_admin_init', __NAMESPACE__ . '\\add_reports_pagination' );
