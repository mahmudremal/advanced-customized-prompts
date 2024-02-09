<?php
/**
 * Blocks
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
class Zip {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		// 
		add_action('wp_ajax_nopriv_sospopsproject/ajax/update/zipcode', [$this, 'update_zipcode'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/update/zipcode', [$this, 'update_zipcode'], 10, 0);
		// 
		add_filter('futurewordpress/project/sospopupaddon/javascript/siteconfig', [ $this, 'jsConfig' ], 10, 1);
	}
	public function jsConfig($args) {
		if (isset($_REQUEST['zip_code']) && empty($_REQUEST['zip_code']) && !$this->has_user_zip()) {
			$args['showPrompts'] = true;
		}
		$_zip_code = get_query_var('zip_code', false);
		if ($_zip_code && !empty(trim($_zip_code))) {
			$args['zipCode'] = $_zip_code;
		} else {
			$args['zipCode'] = $this->get_user_zip();
		}

		return $args;
	}
	public function has_user_zip($user_id = false) {
		if ((!$user_id || empty($user_id)) && is_user_logged_in()) {
			$user_id = get_current_user_id();
		}
		$zip_code = $this->get_user_zip($user_id);
		return ($zip_code && !empty($zip_code));
	}
	public function get_user_zip($user_id = false) {
		if ((!$user_id || empty($user_id)) && is_user_logged_in()) {
			$user_id = get_current_user_id();
		}
		if ($user_id && !empty($user_id)) {
			return get_user_meta($user_id, '_zip_code', true);
		}
		/**
		 * Get zipcode from session
		 */
		if (!is_user_logged_in() && (!$user_id || empty($user_id))) {
			if (!session_id()) {session_start();}
			if (isset($_SESSION['_zipcode'])) {
				return $_SESSION['_zipcode'];
			}
		}
		return false;
		
	}
	public function update_zipcode() {
		$args = ['message' => __('Something went wrong. Failed to update zip code', 'domain'), 'hooks' => ['zipcodeupdatefailed']];
		if (isset($_POST['_zipcode']) && !empty($_POST['_zipcode'])) {
			if (is_user_logged_in()) {
				update_user_meta(get_current_user_id(), '_zip_code', $_POST['_zipcode']);
			} else {
				/**
				 * Store zip code on session
				 */
				if (!session_id()) {
					session_start();
				}
				$_SESSION['_zipcode'] = $_POST['_zipcode'];
			}
			$args = ['message' => __('Zip code updated!', 'domain'), 'hooks' => ['zipcodeupdated'], 'zipcode' => $_POST['_zipcode']];
			$args['message'] = false;
			wp_send_json_success($args);
		}
		wp_send_json_error($args);
	}

}
