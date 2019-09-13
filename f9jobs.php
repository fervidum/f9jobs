<?php
/**
 * Plugin Name: F9jobs
 * Plugin URI: https://fervidum.github.io/f9jobs/
 * Description: Brazilian cities for various purposes in WordPress.
 * Version: 1.0.0
 * Author: Fervidum
 * Author URI: https://fervidum.github.io/
 *
 * Text Domain: f9jobs
 * Domain Path: /i18n/languages/
 *
 * @package F9jobs
 */

defined( 'ABSPATH' ) || exit;

// Define F9JOBS_PLUGIN_FILE.
if ( ! defined( 'F9JOBS_PLUGIN_FILE' ) ) {
	define( 'F9JOBS_PLUGIN_FILE', __FILE__ );
}

// Include the main F9jobs class.
if ( ! class_exists( 'F9jobs' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-f9jobs.php';
}

/**
 * Main instance of F9jobs.
 *
 * Returns the main instance of f9jobs to prevent the need to use globals.
 *
 * @return F9jobs
 */
function f9jobs() {
	return F9jobs::instance();
}

f9jobs();
