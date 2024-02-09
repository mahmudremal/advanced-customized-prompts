<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
class Mobile {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_filter('template_redirect', [$this, 'template_redirect'], 10, 0);
		// add_filter('pre_get_posts', [$this, 'pre_get_posts'], 10, 1);
	}
	/**
	 * This function will redrect user to a different page if requested device is mobile.
	 */
	public function template_redirect() {
		/**
		 * Mobile screen home page ID is 1507
		 */

		// if (wp_is_mobile()) {wp_die('Working');}
		
		$mobile_home_page = 1507;
		if (is_front_page() && wp_is_mobile() && !is_page($mobile_home_page)) {
			wp_redirect(get_permalink($mobile_home_page));exit;
		}
		$mobile_service_page = 1764;$desktop_service_page = 424;
		if (is_page($desktop_service_page) && wp_is_mobile() && !is_page($mobile_service_page)) {
			wp_redirect(get_permalink($mobile_service_page));exit;
		}
	}
	public function pre_get_posts($query) {
		/**
		 * Mobile screen home page ID is 1507
		 */
		if ($query->is_main_query() && $query->is_front_page()) {
			$mobile_home_page = 1507;
			if (is_front_page() && wp_is_mobile() && !is_page($mobile_home_page)) {
				print_r($query);
				$query->set('post_id', $mobile_home_page);
				// $query->set('page_id', $mobile_home_page);
			}
		}
	}
}