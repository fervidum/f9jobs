<?php
/**
 * Installation related functions and actions.
 *
 * @version  1.0.0
 * @package  F9jobs/Classes
 * @category Admin
 * @author   Fervidum
 */

defined( 'ABSPATH' ) || exit;

/**
 * F9jobs_Install Class.
 */
class F9jobs_Install {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Check F9jobs version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'f9jobs_version' ) !== f9jobs()->version ) {
			self::install();
			do_action( 'f9jobs_updated' );
		}
	}

	/**
	 * Install F9jobs.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'f9jobs_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'f9jobs_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		f9jobs()->define( 'F9JOBS_INSTALLING', true );

		self::create_roles();
		self::setup_environment();
		self::update_f9jobs_version();

		delete_transient( 'f9jobs_installing' );

		do_action( 'f9jobs_flush_rewrite_rules' );
		do_action( 'f9jobs_installed' );
	}

	/**
	 * Setup F9jobs environment - post types.
	 */
	private static function setup_environment() {
		F9jobs_Post_types::register_post_types();
	}

	/**
	 * Update F9jobs version to current.
	 */
	private static function update_f9jobs_version() {
		delete_option( 'f9jobs_version' );
		add_option( 'f9jobs_version', f9jobs()->version );
	}

	/**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		// Customer role.
		add_role(
			'candidate',
			__( 'Candidato', 'f9jobs' ),
			array(
				'read' => true,
			)
		);

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'editor', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Get capabilities for F9jobs - these are assigned to admin/shop manager during installation or reset.
	 *
	 * @return array
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_f9jobs',
		);

		$capabilities['application'] = array(
			// Post type.
			//'edit_application', @codingStandardsIgnoreLine
			'read_application',
			'delete_application',
			//'edit_applications', @codingStandardsIgnoreLine
			//'edit_others_applications', @codingStandardsIgnoreLine
			'publish_applications',
			'read_private_applications',
			'delete_applications',
			'delete_private_applications',
			'delete_published_applications',
			'delete_others_applications',
			//'edit_private_applications', @codingStandardsIgnoreLine
			//'edit_published_applications' @codingStandardsIgnoreLine,
		);

		return $capabilities;
	}

	/**
	 * Remove F9jobs roles.
	 */
	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'editor', $cap );
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}
	}
}

F9jobs_Install::init();
