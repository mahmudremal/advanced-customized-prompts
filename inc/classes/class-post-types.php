<?php
/**
 * Register Post Types
 *
 * @package domainAddons
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
class Post_Types {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		/**
		 * Actions.
		 */
		add_action('init', [$this,'create_service_product_cpt'], 10, 0);
	}
	// Register Custom Post Type Product service
	public function create_service_product_cpt() {
		$icon = untrailingslashit(SOSPOPSPROJECT_BUILD_PATH.'/icons/contract-document-svgrepo-com.svg');
		$icon = (file_exists($icon)&&!is_dir($icon))?esc_url(SOSPOPSPROJECT_DIR_URI.'/icons/contract-document-svgrepo-com.svg'):'dashicons-superhero';

		$labels = [
			'name'                  => _x('Product services', 'Post Type General Name', 'domain'),
			'singular_name'         => _x('Product service', 'Post Type Singular Name', 'domain'),
			'menu_name'             => _x('Product services', 'Admin Menu text', 'domain'),
			'name_admin_bar'        => _x('Product service', 'Add New on Toolbar', 'domain'),
			'archives'              => __('Product service Archives', 'domain'),
			'attributes'            => __('Product service Attributes', 'domain'),
			'parent_item_colon'     => __('Parent Product service:', 'domain'),
			'all_items'             => __('All Product services', 'domain'),
			'add_new_item'          => __('Add New Product service', 'domain'),
			'add_new'               => __('Add New', 'domain'),
			'new_item'              => __('New Product service', 'domain'),
			'edit_item'             => __('Edit Product service', 'domain'),
			'update_item'           => __('Update Product service', 'domain'),
			'view_item'             => __('View Product service', 'domain'),
			'view_items'            => __('View Product services', 'domain'),
			'search_items'          => __('Search Product service', 'domain'),
			'not_found'             => __('Not found', 'domain'),
			'not_found_in_trash'    => __('Not found in Trash', 'domain'),
			'featured_image'        => __('Featured Image', 'domain'),
			'set_featured_image'    => __('Set featured image', 'domain'),
			'remove_featured_image' => __('Remove featured image', 'domain'),
			'use_featured_image'    => __('Use as featured image', 'domain'),
			'insert_into_item'      => __('Insert into Product service', 'domain'),
			'uploaded_to_this_item' => __('Uploaded to this Product service', 'domain'),
			'items_list'            => __('Product services list', 'domain'),
			'items_list_navigation' => __('Product services list navigation', 'domain'),
			'filter_items_list'     => __('Filter Product services list', 'domain'),
		];
		$args   = [
			'label'               => __('Product service', 'domain'),
			'description'         => __('The Product services', 'domain'),
			'labels'              => $labels,
			'menu_icon'           => $icon,
			'supports'            => [
				'title',
				// 'editor',
				// 'excerpt',
				// 'thumbnail',
				// 'revisions',
				'author',
				// 'comments',
				// 'trackbacks',
				// 'page-attributes',
				// 'custom-fields',
			],
			'taxonomies'          => [],
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'show_in_rest'        => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
		];
		register_post_type('product_services', $args);
	}
}
