<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
use \WP_Query;
class Query {
	use Singleton;
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_action('pre_get_posts', [$this, 'pre_get_posts'], 10, 1);
		add_filter('query_vars', [$this, 'query_vars'], 10, 1);
	}
	public function pre_get_posts($query) {
		global $SoS_Zip;
		if (!apply_filters('sos/project/system/isactive', 'standard-zipfilter')) {return;}
		if (!is_admin()) {
			if (is_post_type_archive('service') || is_tax('services') || is_tax('area')) {
				if (! $query->is_main_query() && ! $query->is_tax()) {return;}
				// $query->set('posts_per_page', 2);
				/**
				 * Sortingout using a meta tag.
				 */
				/*
					$query->set('meta_query', [
						...$query->get('meta_query'),
						[
							'key'     => 'example_meta',
							'value'   => '',
							'compare' => 'EXISTS',
						]
					]);
				*/
				if ($SoS_Zip->has_user_zip()) {
					$zip_code = $SoS_Zip->get_user_zip();
					if (!$zip_code || empty($zip_code)) {return;}
					$prevTexonomies = (array) $query->get('tax_query');
					$query->set('tax_query', [
						...$prevTexonomies,
						[
							'taxonomy'	=> 'area',
							'field'		=> 'slug',
							'terms'		=> $zip_code
						]
					]);
				}
				
			}
		}
	}
	public function query_vars($query_vars) {
		$query_vars[] = 'zip_code';
    	return $query_vars;
	}
}