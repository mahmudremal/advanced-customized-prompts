<?php
/**
 * Register Menus
 *
 * @package ESignBindingAddons
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
class Menus {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		/**
		 * Actions.
		 */
		// add_action('init', [$this, 'register_menus']);
		add_filter('teddybear/project/settings/general', [$this, 'general'], 10, 1);
		add_filter('teddybear/project/settings/fields', [$this, 'menus'], 10, 1);
		add_action('in_admin_header', [$this, 'in_admin_header'], 100, 0);
	}
	public function register_menus() {
		register_nav_menus([
			'aquila-header-menu' => esc_html__('Header Menu', 'sospopsprompts'),
			'aquila-footer-menu' => esc_html__('Footer Menu', 'sospopsprompts'),
		]);
	}
	/**
	 * Get the menu id by menu location.
	 *
	 * @param string $location
	 *
	 * @return integer
	 */
	public function get_menu_id($location) {
		// Get all locations
		$locations = get_nav_menu_locations();
		// Get object id by location.
		$menu_id = ! empty($locations[$location]) ? $locations[$location] : '';
		return ! empty($menu_id) ? $menu_id : '';
	}
	/**
	 * Get all child menus that has given parent menu id.
	 *
	 * @param array   $menu_array Menu array.
	 * @param integer $parent_id Parent menu id.
	 *
	 * @return array Child menu array.
	 */
	public function get_child_menu_items($menu_array, $parent_id) {
		$child_menus = [];
		if(! empty($menu_array) && is_array($menu_array)) {
			foreach ($menu_array as $menu) {
				if(intval($menu->menu_item_parent) === $parent_id) {
					array_push($child_menus, $menu);
				}
			}
		}
		return $child_menus;
	}
	public function in_admin_header() {
		if(! isset($_GET['page']) || $_GET['page'] != 'crm_dashboard') {return;}
		
		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
		// add_action('admin_notices', function () {echo 'My notice';});
	}
	/**
	 * Supply necessry tags that could be replace on frontend.
	 * 
	 * @return string
	 * @return array
	 */
	public function commontags($html = false) {
		$arg = [];$tags = [
			'username', 'sitename', 
		];
		if($html === false) {return $tags;}
		foreach($tags as $tag) {
			$arg[] = sprintf("%s{$tag}%s", '<code>{', '}</code>');
		}
		return implode(', ', $arg);
	}
	public function contractTags($tags) {
		$arg = [];
		foreach($tags as $tag) {
			$arg[] = sprintf("%s{$tag}%s", '<code>{', '}</code>');
		}
		return implode(', ', $arg);
	}
	/**
	 * WordPress Option page.
	 * 
	 * @return array
	 */
	public function general($args) {
		return $args;
	}
	public function menus($args) {
		$args['standard'] 		= [
			'title'							=> __('General', 'sospopsprompts'),
			'description'					=> __('General settings for teddy-bear customization popup.', 'sospopsprompts'),
			'fields'						=> [
				[
					'id' 					=> 'standard-enable',
					'label'					=> __('Enable', 'sospopsprompts'),
					'description'			=> __('Mark to enable teddy-bear customization popup.', 'sospopsprompts'),
					'type'					=> 'checkbox',
					'default'				=> false
				],
				[
					'id' 					=> 'standard-global',
					'label'					=> __('Global product', 'sospopsprompts'),
					'description'			=> __('Select a global product that will be replaced if requsted product doesn\'t have any customization popup set.', 'sospopsprompts'),
					'type'					=> 'select',
					'default'				=> '',
					'options'				=> $this->get_query(['post_type' => 'product', 'type' => 'option', 'limit' => 500])
				],
				[
					'id' 					=> 'standard-forceglobal',
					'label'					=> __('Force global', 'sospopsprompts'),
					'description'			=> __('Forcefully globalize this product for all products whether there are customization exists or not.', 'sospopsprompts'),
					'type'					=> 'checkbox',
					'default'				=> false
				],
			]
		];
		$args['default'] 		= [
			'title'							=> __('Teddy Meta', 'sospopsprompts'),
			'description'					=> __('Teddy bear\'s default data that will be replaced if meta on specific product not exists or empty exists. Existing data won\'t be replaced.', 'sospopsprompts'),
			'fields'						=> [
				[
					'id' 						=> 'default-eye',
					'label'					=> __('Eye color', 'sospopsprompts'),
					'description'			=> __('Teddy\'s default eye color that will be replaced if meta not exists on birth certificates.', 'sospopsprompts'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'default-brow',
					'label'					=> __('Fur color', 'sospopsprompts'),
					'description'			=> __('Teddy\'s default brow color that will be replaced if meta not exists on birth certificates.', 'sospopsprompts'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'default-weight',
					'label'					=> __('Teddy\'s weight', 'sospopsprompts'),
					'description'			=> __('Teddy\'s default weight that will be replaced if meta not exists on birth certificates.', 'sospopsprompts'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'default-height',
					'label'					=> __('Teddy\'s height', 'sospopsprompts'),
					'description'			=> __('Teddy\'s default height that will be replaced if meta not exists on birth certificates.', 'sospopsprompts'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'default-accessoriesUrl',
					'label'					=> __('Accessories url', 'sospopsprompts'),
					'description'			=> __('Accessories url that will be applied after user added an item on cart through customization process. It will redirect user to this url when user choose to purches accessories.', 'sospopsprompts'),
					'type'					=> 'text',
					'default'				=> ''
				],
			]
		];
		/**
			$args['names'] 		= [
				'title'							=> __('Teddy name', 'sospopsprompts'),
				'description'					=> __('List of teddy names that will include in a lottery when user choose to suggest a teddy name.', 'sospopsprompts'),
				'fields'						=> [
					...$this->optionaize_teddy_names(),
					[
						'id' 					=> 'do_repeater',
						'label'					=> '',
						'description'			=> false,
						'type'					=> 'button',
						'default'				=> __('Add another', 'sospopsprompts')
					],
				]
			];
		*/
		return $args;
	}
	public function get_query($args) {
		// $args = ['post_type' => 'product', 'type' => 'option', 'limit' => 500];
		$args = (object) $args;
		$options = [];
		$query = get_posts([
			'numberposts'		=> $args->limit,
			'post_type'			=> $args->post_type,
			'order'				=> 'DESC',
			'orderby'			=> 'date',
			'post_status'		=> 'publish',
		]);
		foreach($query as $_post) {
			$options[$_post->ID] = get_the_title($_post->ID);
		}
		return $options;
	}
	public function optionaize_teddy_names() {
		$args = [];$filteredData = [];
		$filteredKeys = array_keys(SOSPOPSPROJECT_OPTIONS);
		foreach($filteredKeys as $key) {
			if(strpos($key, 'teddy-name-') !== false) {
				$filteredData[] = SOSPOPSPROJECT_OPTIONS[$key];
			}
		}
		
		foreach($filteredData as $i => $name) {
			$args[] = [
				'id' 					=> 'teddy-name-' . $i,
				'label'					=> sprintf('%s%s', __('#', 'sospopsprompts'), number_format_i18n($i, 0)),
				'description'			=> false,
				'type'					=> 'text',
				'default'				=> $name
			];
		}
		return $args;
	}
}

/**
 * {{client_name}}, {{client_address}}, {{todays_date}}, {{retainer_amount}}
 */
