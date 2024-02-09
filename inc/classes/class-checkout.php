<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */

namespace SOSPOPSPROJECT\inc;

use SOSPOPSPROJECT\inc\Traits\Singleton;
class Checkout {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	public function setup_hooks() {
		add_filter('init', [$this, 'woocommerce_checkout_screen_init'], 10, 0);
		add_filter('woocommerce_checkout_fields', [$this, 'woocommerce_checkout_fields'], 10, 1);
		// add_filter('woocommerce_checkout_posted_data', [$this, 'woocommerce_checkout_posted_data'], 10, 1);
		// add_filter('woocommerce_form_field_args', [$this, 'woocommerce_form_field_args'], 10, 3);
		add_filter('woocommerce_checkout_get_value', [$this, 'woocommerce_checkout_get_value'], 10, 2);
	}
	public function woocommerce_checkout_screen_init() {}
	public function woocommerce_checkout_fields($fields) {
		// $user_data = get_userdata(get_current_user_id());
		// print_r([$user_data, $fields]);// wp_die();
		return $fields;
	}
	/**
	 * Get data when proceed checkout submitted to payment
	 */
	public function woocommerce_checkout_posted_data($fields) {
		// print_r([$fields]);wp_die();
		return $fields;
	}
	public function woocommerce_form_field_args($args, $key, $value) {
		// print_r([$args, $key, $value]);wp_die();
		return $args;
	}
	public function woocommerce_checkout_get_value($value, $input) {
		$fields = $this->get_fields();
		if (array_key_exists($input, $fields)) {
			// print_r([$value, $input]);
			return $fields[$input];
		}
		return $value;
	}
	public function get_fields() {
		return (array) $_GET;
	}
	
}
