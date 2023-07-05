<?php
/**
 * Rainmaker - Simple Podcasting (Labs).
 *
 * Extend Simple Podcasting plugin for Rainmaker Labs.
 *
 * Plugin Name: Rainmaker - Simple Podcasting (Labs).
 * Plugin URI:  https://rainmakerdigital.com
 * Description: This plugin extends the Simple Podcasting plugin for Rainmaker Labs.
 * Version:     1.0
 * Author:      Rainmaker DevOps Team
 * Author URI:  https://rainmakerdigital.com
 *
 * @package \Rainmaker\Simple_Podcasting\Labs
 * @author  Rainmaker Digital
 * @license GPL-2.0+
 * @link    https://www.rainmakerdigital.com/
 */

namespace Rainmaker\Simple_Podcasting\Labs;

define( 'RMSP_EXTEND_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'RMSP_EXTEND_PODCAST_REPORTS_SCRIPTS', plugin_dir_url( __FILE__ ) . 'assets/js/' );

/** @noinspection PhpIncludeInspection */ // phpcs:ignore
require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';

/** @noinspection PhpIncludeInspection */ // phpcs:ignore
require_once plugin_dir_path( __FILE__ ) . 'includes/hooks.php';

spl_autoload_register( __NAMESPACE__ . '\\autoload' );
