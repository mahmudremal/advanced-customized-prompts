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
		global $SOS_Service;$SOS_Service = $this;
		// load class.
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_filter('template_include', [$this, 'service_custom_template'], 10, 1);
		add_filter('template_include', [$this, 'service_archive_template'], 11, 1);
		add_filter('template_include', [$this, 'service_single_template'], 12, 1);

		add_action('services_add_form_fields', [$this, 'services_add_form_fields'], 10, 1);
		add_action('services_edit_form_fields', [$this, 'services_edit_form_fields'], 10, 2);
		add_action('created_services', [$this, 'save_services_field'], 10, 1);
		add_action('edited_services', [$this, 'save_services_field'], 10, 1);
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

	public function services_add_form_fields($taxonomy, $fieldOnly = false) {
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
	public function services_edit_form_fields($term, $taxonomy) {
		// wp_enqueue_media();
		$value = get_term_meta( $term->term_id, 'texonomy_featured_image', true );
		?>
		<tr class="form-field">
		  <th>
			<label for="texonomy_featured_image">Featured Image</label>
		  </th>
		  <td>
			<?php $this->services_add_form_fields($taxonomy, $value); ?>
		  </td>
		</tr>
		<?php
	}
	public function save_services_field($term_id) {
		update_term_meta(
			$term_id,
			'texonomy_featured_image',
			sanitize_text_field($_POST['texonomy_featured_image'])
		);
	}
}