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
		add_action('post_thumbnail_id', [$this, 'post_thumbnail_id'], 10, 2);
		add_action('elementor/query/sos/single/service/recommended', [$this, 'single_service_recommended'], 10, 1);
	}
	public function service_custom_template($template) {
		if (is_singular('service')) {
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

	public function services_add_form_fields__image($taxonomy, $value = false, $term = false) {
		global $SoS_Meta_Boxes;global $SoS_Menus;
		$imgUrl = ($value && !empty($value))?wp_get_attachment_thumb_url($value):false;
		$imgUrl = ($imgUrl && !is_wp_error($imgUrl) && !empty($imgUrl))?$imgUrl:'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAYAAAAeP4ixAAAAAXNSR0IArs4c6QAAAsRJREFUaEPt2ulq6kAYBuAvLrjjLqKCf7z/K/ESRPwTRdw33E15x5NT26qZNZrSgeKSTPI+881I0bHa7bZTKBQoFotRENt+v6fpdEpWt9t18KTValE2mw2UZbFYUKfTIRTCsm3bSaVS7I0gYVwEMm82myukVqvR7YF3r8z3rP1+/xOCORUEzL2MPyDvjnk00Hch74p5NlseQt4N4zXln0LeBeOFQE5PyKsxPAhuyKswvAghiN8YEYQwxC+MKEIKYhojg5CGmMLIIpQgujEqCGWILowqQgtEFaMDoQ0ii9GF0AoRxehEaIfwYnQjjEC8MCYQxiCPMKYQRiHfMXht8ssNrn/jEUK2uVVAf5Pf0PxBeCp0uyYCO7XuLezALfZngU1htK8RnqA85/BM3dtztEJEAoqcy4PSBpEJJtPnEUoLRCWQSl+tU0tHEB3XUKqIjgDuqKpeSxqieuN7c13lmlIQlRt6fQLJXlsYInsjL8DtcZl7CEFkbiACUMFwQ/xEyHwAcEFegRDFeEJeiRDB+PrTm+M4NBqN6Hg8kmVZbHNCKBSi2WxGl8uF/eifTCa/LKvdbseOo892u6VKpcL6rlYrikajVC6XKRwOP/7FykQlDocDTSYTKpVKLATaYDCgdDr9P1w+n2fHENYNj34AIFOv1yOcg30BAGIgisXifYgJBEJjdwIgCIlRzOVyLAyCIBCO4X1UDi0SibBKoRJoqNb5fKb5fE7VapW9h30oQPq6YQA3xehmMhk2xRAYwQBBAwSjjUc0hAUCIFRpOByyR1wHzxuNBhuUHxBTlXAn/XK5pNPpxNYCwgKBP3etYGOPWyUgMQXX6zUlEglWDUxD7GDCYOC1bdusb71e/6yIH5tqsHDH4zGbPm4lMLoIi5FFYLzGIxqqEY/Hrxtm/k1HLG5UE/0xKBj8ZrN5PefXbHP6LRvPPgBpDA6rn5txOgAAAABJRU5ErkJggg==';


		// print_r([$taxonomy, $value, $term]);
		
		?>
		<?php if (!$value) : ?>
		<div class="form-field">
			<label for="texonomy_featured_image">Featured Image</label>
			<?php endif; ?>
			<button type="button" id="texonomy_featured_image"><?php esc_html_e('Select Image', 'domain'); ?></button>
			<input name="texonomy_featured_image" type="hidden" value="<?php echo esc_attr(($value)?$value:''); ?>" />
			<img src="<?php echo esc_attr($imgUrl); ?>" alt="Select Image" data-handle="texonomy_featured_image">
			<p><?php esc_html_e('Upload/Change category featured image.', 'domain'); ?></p>
			<?php if (!$value) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
	public function services_edit_form_fields__image($term, $taxonomy) {
		// wp_enqueue_media();
		$value = get_term_meta($term->term_id, 'texonomy_featured_image', true);
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
		<?php if (!$fieldOnly) : ?>
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
			<?php if (!$fieldOnly) : ?>
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
		if (isset($_POST['texonomy_featured_image'])) {update_term_meta($term_id, 'texonomy_featured_image', sanitize_text_field($_POST['texonomy_featured_image']));}
		if (isset($_POST['_faq_template'])) {update_term_meta($term_id, '_faq_template', sanitize_text_field($_POST['_faq_template']));}
		
		// print_r(['texonomy_featured_image', $_POST['texonomy_featured_image']]);wp_die();
		
	}
	public function single_service_recommended($query) {
		// $query->set( 'post_type', [ 'custom-post-type1', 'custom-post-type2' ] );
		// $meta_query = $query->get( 'meta_query' );
		// if (!$meta_query) {$meta_query = [];}
		// $meta_query[] = [
		// 	'key' => 'project_type',
		// 	'value' => [ 'design', 'development' ],
		// 	'compare' => 'in',
		// ];
		// $query->set( 'meta_query', $meta_query );
	}
	/**
	 * replacing service thumbnail
	 */
	public function post_thumbnail_id($thumbnail_id, $post) {
		// if (is_singular()) {return $thumbnail_id;} // Pause if it is singular
		if (!$thumbnail_id || empty($thumbnail_id)) {
			$placeholder = apply_filters('sos/project/system/getoption', 'standard-placeholder', 38190);
			if ($placeholder && !empty($placeholder)) {
				$thumbnail_id = (int) $placeholder;
			}
		}
		return $thumbnail_id;
	}
}