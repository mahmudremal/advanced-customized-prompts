<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */

namespace SOSPOPSPROJECT\inc;

use SOSPOPSPROJECT\inc\Traits\Singleton;
class Faqs {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	public function setup_hooks() {
		// add_action('elementor/widget/accordion/skins_init', [$this, 'filter_accordion_items'], 10, 1);
	}
	public function filter_accordion_items($widget) {
		if ($widget->get_name() !== 'accordion') {return;}
		// $widget->set_settings('accordion_items', []);
		$settings = $widget->get_frontend_settings();
		// $accordion_items = $widget->get_name('accordion_items');
		print_r([ $settings ]);
	}
	public function get_template_id($post_id) {
		$terms = wp_get_post_terms($post_id, 'services', ['fields' => 'ids']);
		$faq_template = false;
		foreach($terms as $term_id) {
			$meta = get_term_meta($term_id, '_faq_template', true);
			if ($meta && !is_wp_error($meta) && !empty(trim($meta)) && (int) $meta > 0) {
				$faq_template = $meta;
				break;
			}
		}
		// Faq template not exists on term. So looking for term parents.
		if (!$faq_template) {
			foreach($terms as $term_id) {
				$parent = wp_get_term_taxonomy_parent_id($term_id, 'services');
				if ($parent && !is_wp_error($parent)) {
					$meta = get_term_meta($parent, '_faq_template', true);
					if ($meta && !is_wp_error($meta) && !empty(trim($meta)) && (int) $meta > 0) {
						$faq_template = $meta;
						break;
					}
				}
			}
		}
		return (int) $faq_template;
	}

	
}
