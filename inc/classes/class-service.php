<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
class Service {
	use Singleton;
	public $post_type = 'service';
	public $taxonomy = 'services';
	protected function __construct() {
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_filter('template_include', [$this, 'service_custom_template'], 10, 1);
		add_filter('template_include', [$this, 'service_archive_template'], 11, 1);
		add_filter('template_include', [$this, 'service_single_template'], 12, 1);

		add_action('services_add_form_fields', [$this, 'services_add_form_fields__image'], 10, 1);
		add_action('services_edit_form_fields', [$this, 'services_edit_form_fields__image'], 10, 2);
		add_action('services_add_form_fields', [$this, 'services_add_form_fields__faq'], 10, 1);
		add_action('services_edit_form_fields', [$this, 'services_edit_form_fields__faq'], 10, 2);
		add_action('created_services', [$this, 'save_services_field'], 10, 1);
		add_action('edited_services', [$this, 'save_services_field'], 10, 1);
		add_action('elementor/query/sos/single/service/recommended', [$this, 'single_service_recommended'], 10, 1);
	}
	public function service_custom_template($template) {
		if(is_singular('service')) {
			$template = SOSPOPSPROJECT_DIR_PATH . '/templates/service/single-service.php';
		}
		return $template;
	}
	public function service_archive_template($template) {
		$new_template = SOSPOPSPROJECT_DIR_PATH . '/templates/service/archive-template.php';
		if (is_tax('service')) {
			// The page is a taxonomy term page for the 'service' taxonomy.
			// wp_die('This is a taxonomy term label.');
		} elseif (is_archive('service')) {
			// The page is a taxonomy archive page for the 'service' taxonomy, but it is not a taxonomy term page.
			// wp_die('This is a taxonomy label.');
			return $new_template;
		} else {
			// print_r([is_single(), is_archive(), is_home()]);
			// The page is not a taxonomy archive page or a taxonomy term page.
			// wp_die('This is else factor.');
		}
	  
		return $template;
	}
	public function service_single_template($template) {
		return $template;
	}

	public function services_add_form_fields__image($taxonomy, $fieldOnly = false, $term = false) {
		global $SoS_Meta_Boxes;global $SoS_Menus;
		?>
		<?php if(!$fieldOnly) : ?>
		<div class="form-field">
			<label for="texonomy_featured_image">Featured Image</label>
			<?php endif; ?>
			<button type="button" id="texonomy_featured_image"><?php esc_html_e('Select Image', 'domain'); ?></button>
			<input name="texonomy_featured_image" type="hidden" value="<?php echo esc_attr(($fieldOnly)?$fieldOnly:''); ?>" />
			<p><?php esc_html_e('Upload/Change category featured image.', 'domain'); ?></p>
			<?php if(!$fieldOnly) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
	public function services_edit_form_fields__image($term, $taxonomy) {
		// wp_enqueue_media();
		$value = get_term_meta( $term->term_id, 'texonomy_featured_image', true );
		?>
		<tr class="form-field">
		  <th>
			<label for="texonomy_featured_image">Featured Image</label>
		  </th>
		  <td>
			<?php $this->services_add_form_fields__image($taxonomy, $value, $term); ?>
		  </td>
		</tr>
		<?php
	}
	public function services_add_form_fields__faq($taxonomy, $fieldOnly = false, $term = false) {
		global $SoS_Meta_Boxes;global $SoS_Menus;
		$options = ['' => __('Select Faq template', 'domain')];
		foreach(
			$SoS_Menus->get_query(['post_type' => 'elementor_library', 'type' => 'option', 'limit' => 100])
			as $_value => $_text) {
			$options[$_value] = $_text;
		}
		?>
		<?php if(!$fieldOnly) : ?>
		<div class="form-field">
			<label for="_faq_template">FAQS template</label>
			<?php endif; ?>
			<?php $SoS_Meta_Boxes->display_field([
				'field' => [
					'id' 					=> '_faq_template',
					'label'					=> __('Add another', 'domain'),
					'description'			=> false,
					'type'					=> 'select',
					'default'				=> ($term)?get_term_meta($term->term_id, '_faq_template', true):false,
					'options'				=> $options
				],
				'child' => false
			]); ?>
			<?php if(!$fieldOnly) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
	public function services_edit_form_fields__faq($term, $taxonomy) {
		// wp_enqueue_media();
		$value = get_term_meta( $term->term_id, '_faq_template', true );
		?>
		<tr class="form-field">
		  <th>
			<label for="_faq_template">FAQS template</label>
		  </th>
		  <td>
			<?php $this->services_add_form_fields__faq($taxonomy, $value, $term); ?>
		  </td>
		</tr>
		<?php
	}
	public function save_services_field($term_id) {
		if(isset($_POST['texonomy_featured_image'])) {update_term_meta($term_id, 'texonomy_featured_image', sanitize_text_field($_POST['texonomy_featured_image']));}
		if(isset($_POST['_faq_template'])) {update_term_meta($term_id, '_faq_template', sanitize_text_field($_POST['_faq_template']));}
		
	}
	public function single_service_recommended($query) {
		// $query->set( 'post_type', [ 'custom-post-type1', 'custom-post-type2' ] );
		// $meta_query = $query->get( 'meta_query' );
		// if(!$meta_query) {$meta_query = [];}
		// $meta_query[] = [
		// 	'key' => 'project_type',
		// 	'value' => [ 'design', 'development' ],
		// 	'compare' => 'in',
		// ];
		// $query->set( 'meta_query', $meta_query );
	}
}