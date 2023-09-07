<?php
/**
 * Blocks
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
class I18n {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_action('init', [$this, 'load_plugin_textdomain'], 1, 0);

		add_action('wp_ajax_nopriv_sospopsproject/ajax/i18n/js', [$this, 'js_translates'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/i18n/js', [$this, 'js_translates'], 10, 0);
	}
	public function js_translates() {
		$translates = [
			// backend JS
			'continue' => __('Continue', 'sospopsprompts'),
			'back' => __('Back', 'sospopsprompts'),
			'selectatype' => __('Select a type', 'sospopsprompts'),
			'proceed' => __('Proceed', 'sospopsprompts'),
			'popup_subheading_text' => __('PopUp Sub-heading text', 'sospopsprompts'),
			'popup_subheading' => __('PopUp Sub Heading', 'sospopsprompts'),
			'select_image' => __('Select image', 'sospopsprompts'),
			'select_image_desc' => __('Select an image for popup header. It should be less weight, vertical and optimized.', 'sospopsprompts'),
			'popup_heading_text' => __('PopUp Heading text', 'sospopsprompts'),
			'popup_heading' => __('PopUp Heading', 'sospopsprompts'),
			'required' => __('Required', 'sospopsprompts'),
			'placeholder_text' => __('Placeholder text', 'sospopsprompts'),
			'placeholder_ordefault' => __('Additional cost', 'sospopsprompts'),
			'input_label' => __('Input label', 'sospopsprompts'),
			'add_new_group' => __('Add new group', 'sospopsprompts'),
			'add_new_option' => __('Add new option', 'sospopsprompts'),
			'teddy_name' => __('Teddy name', 'sospopsprompts'),
			'teddy_birth' => __('Teddy birth', 'sospopsprompts'),
			'teddy_sender' => __('Sender\'s Name', 'sospopsprompts'),
			'teddy_reciever' => __('Reciever\'s Name', 'sospopsprompts'),
			'remove' => __('Remove', 'sospopsprompts'),
			'select_thumbnail' => __('Select thumbnail', 'sospopsprompts'),
			'field_type' => __('Field type', 'sospopsprompts'),
			'row_title' => __('Row title', 'sospopsprompts'),
			'layer_order' => __('Layer Order', 'sospopsprompts'),

			// frontend JS
			'somethingwentwrong' => __('Something went wrong!', 'sospopsprompts'),
			'back' => __('Back', 'sospopsprompts'),
			'checkout' => __('Checkout', 'sospopsprompts'),
			'continue' => __('Continue', 'sospopsprompts'),
			'record' => __('Record', 'sospopsprompts'),
			'stop' => __('Stop', 'sospopsprompts'),
			'remove' => __('Remove', 'sospopsprompts'),
			'play' => __('play', 'sospopsprompts'),
			'download' => __('Download', 'sospopsprompts'),
			'pause' => __('Pause', 'sospopsprompts'),
		];
		wp_send_json_success([
			'hooks'			=> ['ajaxi18nloaded'],
			'translates'	=> $translates
		]);
	}
	public function load_plugin_textdomain() {
		/**
		 * loco translator Lecto AI: api: V13Y91F-DR14RP6-KP4EAF9-S44K7SX
		 */
		load_plugin_textdomain( 'sospopsprompts', false, dirname( plugin_basename( SOSPOPSPROJECT__FILE__ ) ) . '/languages' );
		
		// add_action ( 'wp', function() {load_theme_textdomain( 'theme-name-here' );}, 1, 0 );
	}

}
