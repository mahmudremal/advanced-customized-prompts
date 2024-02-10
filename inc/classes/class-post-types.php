<?php
/**
 * Register Post Types for Order
 *
 * @package SOSPopsProject
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
		add_action('init', [$this,'create_service_order_cpt'], 10, 0);
		add_action('init', [$this,'create_custom_post_status'], 10, 0);
		
	}
	// Register Custom Post Type Order
	public function create_service_order_cpt() {
		global $SoS_Order;
		$icon = untrailingslashit(SOSPOPSPROJECT_BUILD_PATH.'/ico-ns/contract-document-svgrepo-com.svg');
		$icon = (file_exists($icon)&&!is_dir($icon))?esc_url(SOSPOPSPROJECT_BUILD_URI.'/icons/contract-document-svgrepo-com.svg'):'dashicons-superhero';

		$labels = [
			'name'                  => _x('Orders', 'Post Type General Name', 'domain'),
			'singular_name'         => _x('Order', 'Post Type Singular Name', 'domain'),
			'menu_name'             => _x('Orders', 'Admin Menu text', 'domain'),
			'name_admin_bar'        => _x('Order', 'Add New on Toolbar', 'domain'),
			'archives'              => __('Order Archives', 'domain'),
			'attributes'            => __('Order Attributes', 'domain'),
			'parent_item_colon'     => __('Parent Order:', 'domain'),
			'all_items'             => __('All Orders', 'domain'),
			'add_new_item'          => __('Add New Order', 'domain'),
			'add_new'               => __('Add New', 'domain'),
			'new_item'              => __('New Order', 'domain'),
			'edit_item'             => __('Edit Order', 'domain'),
			'update_item'           => __('Update Order', 'domain'),
			'view_item'             => __('View Order', 'domain'),
			'view_items'            => __('View Orders', 'domain'),
			'search_items'          => __('Search Order', 'domain'),
			'not_found'             => __('Not found', 'domain'),
			'not_found_in_trash'    => __('Not found in Trash', 'domain'),
			'featured_image'        => __('Featured Image', 'domain'),
			'set_featured_image'    => __('Set featured image', 'domain'),
			'remove_featured_image' => __('Remove featured image', 'domain'),
			'use_featured_image'    => __('Use as featured image', 'domain'),
			'insert_into_item'      => __('Insert into Order', 'domain'),
			'uploaded_to_this_item' => __('Uploaded to this Order', 'domain'),
			'items_list'            => __('Orders list', 'domain'),
			'items_list_navigation' => __('Orders list navigation', 'domain'),
			'filter_items_list'     => __('Filter Orders list', 'domain'),
		];
		$args   = [
			'label'               => __('Order', 'domain'),
			'description'         => __('The Orders', 'domain'),
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
			// 'query_var'				=> 'project',
			'taxonomies'			=> [],
			'public'				=> false,
			'show_ui'				=> true,
			'show_in_menu'			=> 'edit.php?post_type=service',
			// 'rewrite'				=> ['slug' => 'orders/completed'],
			'menu_position'			=> 5,
			'map_meta_cap'			=> true,
			'show_in_admin_bar'		=> true,
			'show_in_nav_menus'		=> true,
			'can_export'			=> true,
			'has_archive'			=> true,
			'hierarchical'			=> false,
			'exclude_from_search'	=> true,
			'show_in_rest'			=> true,
			'publicly_queryable'	=> false,
			'delete_with_user'		=> false,
			'capability_type'		=> 'post',
			'capabilities'			=> [
				'create_posts' => 'do_not_allow'
			],
			// 'rest_base' => 'services-api',
        	// 'rest_controller_class' => 'WP_REST_Posts_Controller',
		];
		register_post_type($SoS_Order->post_type, $args);
	}
	public function create_custom_post_status() {
		global $SoS_Order;
		register_post_status('unpaid', [
			'label'                     => _x('Unpaid', 'post status label', 'domain'),
			'public'                    => true,
			'label_count'               => _n_noop('Unpaid <span class="count">(%s)</span>', 'Unpaid <span class="count">(%s)</span>', 'domain'),
			'post_type'                 => [$SoS_Order->post_type],
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'show_in_metabox_dropdown'  => true,
			'show_in_inline_dropdown'   => true,
			'dashicon'                  => 'dashicons-clock',
		]);
	}
}
