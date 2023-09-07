<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
use \WP_Query;
class Install {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		register_activation_hook(SOSPOPSPROJECT__FILE__, [$this, 'register_activation_hook']);
		register_deactivation_hook(SOSPOPSPROJECT__FILE__, [$this, 'register_deactivation_hook']);
	}
	public function register_activation_hook() {
		global $wpdb;$prefix = $wpdb->prefix . 'fwp_';
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		$charset_collate = $wpdb->get_charset_collate();
		$tables = [];
		foreach( $tables as $table ) {
			dbDelta( $table);
		}
		$options = ['fwp_google_auth_code', 'fwp_google_afterauth_redirect'];
		foreach( $options as $option ) {
			if( ! get_option( $option, false ) ) {add_option( $option, []);}
		}
	}
	public function register_deactivation_hook() {
		global $wpdb;$prefix = $wpdb->prefix . 'fwp_';
		$tables = []; // ['stripe_payments', 'stripe_subscriptions', 'googledrive'];
		foreach( $tables as $table ) {
			// $wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table};");
		}
	}
}