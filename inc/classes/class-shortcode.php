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
		add_shortcode('sos_zip_popup', [$this, 'sos_zip_popup']);
		add_shortcode('my_zip_code', [$this, 'my_zip_code']);
		add_shortcode('services_archives', [$this, 'services_archives']);
		add_shortcode('service_categories_sidebar', [$this, 'service_categories_sidebar']);
		add_shortcode('sos_hero_search_suggestions', [$this, 'sos_hero_search_suggestions']);
		add_shortcode('sos_single_service_faqs', [$this, 'sos_single_service_faqs']);
	}
	public function custom_popup_btn($args) {
		$_post_id = get_the_ID();
		ob_start();
		?>
		<button class="btn button custom_pops_btn" data-config="<?php echo esc_attr(json_encode(['id' => $_post_id])); ?>">Open Popup</button>
		<?php
		$output = ob_get_clean();
		// defined('DOING_AJAX') && DOING_AJAX && 
		return $output;
	}
	public function sos_zip_popup($args) {
		$args = (object) wp_parse_args($args, [
			'input'		=> true,
			'title'		=> __('Zip Popup', 'sospopsprompts'),
			'user_id'	=> get_current_user_id()
		]);
		$_post_id = get_the_ID();
		ob_start();
		?>
		<button class="btn button custom_zip_btn" data-config="<?php echo esc_attr(json_encode(['id' => $_post_id])); ?>"><?php echo esc_html($args->title); ?></button>
		<?php
		if($args->input) {
			?>
			<input type="hidden" name="zip_code" value="<?php echo esc_attr(get_user_meta($args->user_id, '_zip_code', true)); ?>">
			<?php
		}
		$output = ob_get_clean();
		// defined('DOING_AJAX') && DOING_AJAX && 
		return $output;
	}
	public function my_zip_code($args) {
		$args = (object) wp_parse_args($args, [
			'user_id'		=> get_current_user_id()
		]);
		return esc_html(get_user_meta($args->user_id, '_zip_code', true));
	}
	public function services_archives($args) {
		global $SOS_Service;ob_start();
		$term_ids[] = [35];
		$service_term = get_queried_object();
		$service_args = ['post_type' => $SOS_Service->post_type];
		if($service_term) {
			$service_args['tax_query'] = [
				[
				  'taxonomy'	=> $SOS_Service->taxonomy,
				  'field'		=> 'term_id',
				  'terms'		=> [$service_term->term_id],
				  'operator'	=> 'IN',
				],
			];
		}
		$__services = get_posts($service_args);
		if($__services && ! is_wp_error($__services)) :
			?><div class="services-loops"><?php
			foreach($__services as $_service) :
				$_service_id = $_service->ID;
				// setup_postdata($_service_id);
				// print_r([$_service]);
				// include SOSPOPSPROJECT_DIR_PATH . '/templates/service/loop-service.php';
				if($_service_id && is_int($_service_id) && $_service_id >= 1) :
					?>
					<div class="serviceloop <?php echo esc_attr('serviceloop-' . $_service_id); ?>">
						<div class="serviceloop__wrap">
							<a href="<?php echo esc_url(get_the_permalink($_service_id)); ?>" class="serviceloop__link">
								<?php echo get_the_post_thumbnail($_service_id, 'post-thumbnail', ['class' => 'serviceloop_image']); ?>
								<div class="serviceloop__title"><?php echo esc_html(get_the_title($_service_id)); ?></div>
							</a>
						</div>
					</div>
					<?php
				endif;
			endforeach;
			?></div><?php
		else :
			echo do_shortcode('[elementor-template id="633"]', false);
		endif;
		wp_reset_postdata();
		return ob_get_clean();
	}
	public function service_categories_sidebar($args) {
		global $SOS_Service;
		$args = (object) wp_parse_args((array) $args, [
			'hide_empty'		=> false,
		]);
		$the_texonomy_slug = 'services';
		$the_texonomy = get_taxonomy($the_texonomy_slug);
		$terms = get_terms([
			'taxonomy'		=> $SOS_Service->taxonomy,
			'post_type'		=> $SOS_Service->post_type,
			'hide_empty'	=> $args->hide_empty
		]);
		ob_start();

		$service_term = get_queried_object();
		try {
			if(!empty($terms) && !is_wp_error($terms)) {
				?>
				<div class="service_catlist">
					<div class="service_catlist__header">
						<span><?php echo esc_html($the_texonomy->label); ?></span>
					</div>
					<ul class="service_catlist__list">
						<?php foreach($terms as $term) : ?>
							<li class="service_catlist__list__item <?php echo esc_attr(($term->term_id == $service_term->term_id)?'service_catlist__list__item__active':''); ?>">
								<a href="<?php echo esc_url(get_term_link($term)); ?>" class="service_catlist__list__link" data-category="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
			}
		} catch(\WP_Error $error) {}

		return ob_get_clean();
	}
	public function sos_hero_search_suggestions($args) {
		global $SOS_Service;
		$args = wp_parse_args($args, [
			'zip'		=> true,
			'grouped'	=> true,
			'closable'	=> true
		]);
		ob_start();
		?>
		<div class="sos_hero">
			<div class="sos_hero__wrap">
				<div class="sos_hero__row">
					<div class="sos_hero__searchable">
						<select name="" id="" class="sos_hero__searchable__select"></select>
					</div>
					<div class="sos_hero__zip">
						<?php echo do_shortcode('[sos_zip_popup input=true]'); ?>
					</div>
					<div class="sos_hero__submit">
						<button class="sos_hero__submit_btn" type="submit"><?php esc_html_e('Submit', 'sospopsprompts'); ?></button>
					</div>
				</div>
				<div class="sos_hero__suggestion">
					<ul class="sos_hero__suggestion__list">
						<?php
						$terms = get_terms([
							'taxonomy'   => $SOS_Service->taxonomy,
							'hide_empty' => false
						]);
						foreach($terms as $term) :
							?>
							<li class="sos_hero__suggestion__list__item">
								<a href="<?php echo esc_url(get_term_link($term)); ?>" class="sos_hero__suggestion__list__link"><?php echo esc_html($term->name); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	public function sos_single_service_faqs($args) {
		ob_start();
		?>
		Faq template goes here.
		<?php
		return ob_get_clean();
	}
	
}
