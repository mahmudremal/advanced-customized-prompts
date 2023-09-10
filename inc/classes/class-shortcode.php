<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */

namespace SOSPOPSPROJECT\inc;

use SOSPOPSPROJECT\inc\Traits\Singleton;
class Shortcode {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	public function setup_hooks() {
		add_shortcode('custom-popup-btn', [$this, 'custom_popup_btn']);
		add_shortcode('my_zip_code', [$this, 'my_zip_code']);
	}
	public function custom_popup_btn($args) {
		ob_start();
		?>
		<button class="btn button custom_pops_btn" data-config="<?php echo esc_attr(json_encode(['id' => 2696])); ?>">Open Popup</button>
		<button class="btn button custom_zip_btn" data-config="<?php echo esc_attr(json_encode(['id' => 2696])); ?>">Zip Popup</button>
		<?php
		$output = ob_get_clean();
		// defined('DOING_AJAX') && DOING_AJAX && 
		return $output;
	}
	public function my_zip_code($args) {
		ob_start(); ?>
		<?php echo esc_html(get_user_meta(get_current_user_id(), '_zip_code', true)); ?>
		<?php $output = ob_get_clean();return $output;
	}
	
}
