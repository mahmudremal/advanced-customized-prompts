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
			if (is_post_type_archive('service') || $query->is_tax('services') || $query->is_tax('area')) {
				if (!$query->is_main_query()) {return;}
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
					$zip_term = $SoS_Zip->get_user_zip_term();
					if (!$zip_term || empty($zip_term)) {return;}
					$prevTexonomies = (array) $query->get('tax_query');
					if (isset($prevTexonomies[0]) && empty($prevTexonomies[0])) {
						unset($prevTexonomies[0]);
					}
					$zip_codes = [$zip_term->term_id];

					if ($query->is_tax('services')) {
						$query->set('tax_query', [
							...$prevTexonomies,
							[
								'taxonomy'			=> 'area',
								'field'				=> 'term_id', // slug
								'terms'				=> (array) $zip_codes,
								'operator'			=> 'IN',
								'include_children'	=> true
							]
						]);
					}
				}
				
			}
		}
	}
	public function query_vars($query_vars) {
		$query_vars[] = 'zip_code';
    	return $query_vars;
	}
}