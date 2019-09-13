<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @class     F9jobs_Post_Types
 * @version   1.0.0
 * @package   F9jobs/Classes/Products
 * @category  Class
 * @author    Fervidum
 */

defined( 'ABSPATH' ) || exit;

/**
 * F9jobs_Post_Types Class.
 */
class F9jobs_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'application' ) ) {
			return;
		}

		do_action( 'f9jobs_register_post_type' );

		$supports = array( 'editor' );

		register_post_type( 'application',
			apply_filters( 'f9jobs_register_post_type_application',
				array(
					'labels'              => array(
						'name'                  => __( 'Candidaturas', 'f9jobs' ),
						'singular_name'         => __( 'Candidatura', 'f9jobs' ),
						'all_items'             => __( 'Todas as candidaturas', 'f9jobs' ),
						'menu_name'             => _x( 'Candidaturas', 'Admin menu name', 'f9jobs' ),
						'add_new'               => __( 'Adicionar nova', 'f9jobs' ),
						'add_new_item'          => __( 'Adicionar nova candidatura', 'f9jobs' ),
						'edit'                  => __( 'Editar', 'f9jobs' ),
						'edit_item'             => __( 'Editar candidatura', 'f9jobs' ),
						'new_item'              => __( 'Nova candidatura', 'f9jobs' ),
						'view_item'             => __( 'Ver candidatura', 'f9jobs' ),
						'view_items'            => __( 'Ver candidaturas', 'f9jobs' ),
						'search_items'          => __( 'Pesquisar candidaturas', 'f9jobs' ),
						'not_found'             => __( 'Nenhuma candidatura foi encontrada', 'f9jobs' ),
						'not_found_in_trash'    => __( 'Nenhuma candidatura encontrada na lixeira', 'f9jobs' ),
						'parent'                => __( 'Candidatura principal', 'f9jobs' ),
					),
					'description'         => __( 'Aqui e onde você pode adicionar novas candidaturas.', 'f9jobs' ),
					'public'              => true,
					'show_ui'             => true,
					'menu_position'       => 5,
					'menu_icon'           => 'dashicons-nametag',
					//'capability_type'     => 'application',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'rewrite'             => false,
					'query_var'           => true,
					'supports'            => $supports,
					'has_archive'         => false,
					'show_in_nav_menus'   => true,
					'show_in_rest'        => true,
				)
			)
		);

		do_action( 'f9jobs_after_register_post_type' );
	}
}

F9jobs_Post_Types::init();
