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
		add_filter('sos/project/settings/general', [$this, 'general'], 10, 1);
		add_filter('sos/project/settings/fields', [$this, 'menus'], 10, 1);
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
		$args['standard']	= [
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
					'label'					=> __('Global Service', 'sospopsprompts'),
					'description'			=> __('Select a global Service that will be replaced if requsted service doesn\'t have any customized popup set.', 'sospopsprompts'),
					'type'					=> 'select',
					'default'				=> '',
					'options'				=> $this->get_query(['post_type' => 'service', 'type' => 'option', 'limit' => 500])
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
		$args['email'] 		= [
			'title'							=> __('Email', 'sospopsprompts'),
			'description'					=> __('Email temlates, address & all necessery informations goes here.', 'sospopsprompts'),
			'fields'						=> [
				[
					'id' 					=> 'email-enable',
					'label'					=> __('Enable', 'sospopsprompts'),
					'description'			=> __('Mark to enable email while pops submits.', 'sospopsprompts'),
					'type'					=> 'checkbox',
					'default'				=> false
				],
				[
					'id' 						=> 'email-reciever',
					'label'					=> __('Email Reciever\'s', 'sospopsprompts'),
					'description'			=> __('Give here reciever full address. Comma seperated, you could enter multiple email without space and using comma.', 'sospopsprompts'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'email-subject',
					'label'					=> __('Email Subject', 'sospopsprompts'),
					'description'			=> __('Email subject content. Try to turncate it around 30 characters', 'sospopsprompts'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'email-template',
					'label'					=> __('Email template', 'sospopsprompts'),
					'description'			=> __('Here you could change email template by replacing this template contents.', 'sospopsprompts'),
					'type'					=> 'textarea',
					'default'				=> "Dear [Name],\nWe are delighted to invite you to join us for [Event/Service/Product], a [brief description of event/service/product].\n[Event/Service/Product] offers [brief summary of benefits or features]. As a valued member of our community, we would like to extend a special invitation for you to be part of this exciting opportunity.\nTo register, simply click on the link below:\n[Registration link]\nShould you have any questions or require additional information, please do not hesitate to contact us at [contact information].\nWe look forward to seeing you at [Event/Service/Product].\nBest regards,\n[Your Name/Company Name]",
					'attr'					=> ['data-a-tinymce' => true]
				],
			]
		];
		$args['stripe']		= [
			'title'							=> __('Stripe', 'domain'),
			'description'				=> __('Stripe payment system configuration process should be do carefully. Here some field is importent to work with no inturrupt. Such as API key or secret key, if it\'s expired on your stripe id, it won\'t work here. New user could face problem fo that reason.', 'domain'),
			'fields'						=> [
				[
					'id' 						=> 'stripe-cancelsubscription',
					'label'					=> __('Cancellation', 'domain'),
					'description'		=> __('Enable it to make a possibility to user to cancel subscription from client dashboard.', 'domain'),
					'type'					=> 'checkbox',
					'default'				=> false
				],
				[
					'id' 						=> 'stripe-publishablekey',
					'label'					=> __('Publishable Key', 'domain'),
					'description'		=> __('The key which is secure, could import into JS, and is safe evenif any thirdparty got those code. Note that, secret key is not a publishable key.', 'domain'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'stripe-secretkey',
					'label'					=> __('Secret Key', 'domain'),
					'description'		=> __('The secret key that never share with any kind of frontend functionalities and is ofr backend purpose. Is required.', 'domain'),
					'type'					=> 'text',
					'default'				=> ''
				],
				[
					'id' 						=> 'stripe-currency',
					'label'					=> __('Currency', 'domain'),
					'description'		=> __('Default currency which will use to create payment link.', 'domain'),
					'type'					=> 'text',
					'default'				=> 'usd'
				],
				[
					'id' 						=> 'stripe-productname',
					'label'					=> __('Product name text', 'domain'),
					'description'		=> __('A text to show on product name place on checkout sanbox.', 'domain'),
					'type'					=> 'text',
					'default'				=> __('Subscription',   'domain')
				],
				[
					'id' 						=> 'stripe-productdesc',
					'label'					=> __('Product Description', 'domain'),
					'description'		=> __('Some text to show on product description field.', 'domain'),
					'type'					=> 'text',
					'default'				=> __('Payment for',   'domain') . ' ' . get_option('blogname', 'We Make Content')
				],
				[
					'id' 						=> 'stripe-productimg',
					'label'					=> __('Product Image', 'domain'),
					'description'		=> __('A valid image url for product. If image url are wrong or image doesn\'t detect by stripe, process will fail.', 'domain'),
					'type'					=> 'url',
					'default'				=> esc_url(SOSPOPSPROJECT_BUILD_URI . '/icons/Online payment_Flatline.svg')
				],
				[
					'id' 						=> 'stripe-paymentmethod',
					'label'					=> __('Payment Method', 'domain'),
					'description'		=> __('Select which payment method you will love to get payment.', 'domain'),
					'type'					=> 'select',
					'default'				=> 'card',
					'options'				=> apply_filters('sos/project/payment/stripe/payment_methods', [])
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
