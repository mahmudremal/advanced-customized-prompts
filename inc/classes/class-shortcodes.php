<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */

namespace SOSPOPSPROJECT\inc;

use SOSPOPSPROJECT\inc\Traits\Singleton;
class Shortcodes {
	use Singleton;

	public $payment_stripe_info = false;
	
	protected function __construct() {
		$this->setup_hooks();
	}
	public function setup_hooks() {
		add_shortcode('custom-popup-btn', [$this, 'custom_popup_btn']);
		add_shortcode('sos_book_now', [$this, 'custom_popup_btn']);
		add_shortcode('sos_zip_popup', [$this, 'sos_zip_popup']);
		add_shortcode('my_zip_code', [$this, 'my_zip_code']);
		add_shortcode('services_archives', [$this, 'services_archives']);
		add_shortcode('service_categories_sidebar', [$this, 'service_categories_sidebar']);
		add_shortcode('sos_hero_search_suggestions', [$this, 'sos_hero_search_suggestions']);
		add_shortcode('sos_single_service_faqs', [$this, 'sos_single_service_faqs']);
		add_shortcode('sos_single_service_price', [$this, 'sos_single_service_price']);
		add_shortcode('sos_single_service_review_form', [$this, 'sos_single_service_review_form']);
		add_shortcode('sos_single_service_reviews', [$this, 'sos_single_service_reviews']);
		add_shortcode('sos_single_service_in_area', [$this, 'sos_single_service_in_area']);

		add_shortcode('sos_stripe_info_print', [$this, 'sos_stripe_info_print']);

		// add_filter('posts_pre_query', [$this, 'posts_pre_query'], 10, 2);
	}
	public function custom_popup_btn($args) {
		$args = (object) wp_parse_args($args, [
			'title'		=> __('Book Now Service', 'sospopsprompts'),
		]);
		$_post_id = get_the_ID();
		ob_start();
		?>
		<button class="btn button custom_pops_btn" type="button" data-config="<?php echo esc_attr(json_encode(['id' => $_post_id])); ?>"><?php echo esc_html($args->title); ?></button>
		<?php
		$output = ob_get_clean();
		// defined('DOING_AJAX') && DOING_AJAX && 
		return $output;
	}
	public function sos_zip_popup($args) {
		$args = (object) wp_parse_args($args, [
			'input'		=> true,
			'title'		=> __('Zip Code', 'sospopsprompts'),
			'user_id'	=> get_current_user_id(),
			'zip_code'	=> ''
		]);
		$_post_id = get_the_ID();
		$args->zip_code = get_user_meta($args->user_id, '_zip_code', true);
		ob_start();
		?>
		<button class="btn button custom_zip_btn sos_zip_preview" type="button" data-config="<?php echo esc_attr(json_encode(['id' => $_post_id])); ?>"><?php echo esc_html(($args->input && $args->zip_code && !empty($args->zip_code))?$args->zip_code:$args->title); ?></button>
		<?php
		if ($args->input) {
			?>
			<input type="hidden" name="zip_code" value="<?php echo esc_attr($args->zip_code); ?>">
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
		global $SoS_Service;ob_start();
		$term_ids[] = [35];
		$service_term = get_queried_object();
		$service_args = ['post_type' => $SoS_Service->post_type];
		if ($service_term) {
			$service_args['tax_query'] = [
				[
				  'taxonomy'	=> $SoS_Service->taxonomy,
				  'field'		=> 'term_id',
				  'terms'		=> [$service_term->term_id],
				  'operator'	=> 'IN',
				],
			];
		}
		$__services = get_posts($service_args);
		if ($__services && ! is_wp_error($__services)) :
			?><div class="services-loops"><?php
			foreach($__services as $_service) :
				$_service_id = $_service->ID;
				// setup_postdata($_service_id);
				// print_r([$_service]);
				// include SOSPOPSPROJECT_DIR_PATH . '/templates/service/loop-service.php';
				if ($_service_id && is_int($_service_id) && $_service_id >= 1) :
					?>
					<div class="serviceloop <?php echo esc_attr('serviceloop-' . $_service_id); ?>">
						<div class="serviceloop__wrap">
							<a href="<?php echo esc_url(get_the_permalink($_service_id)); ?>" class="serviceloop__link">
								<?php echo get_the_post_thumbnail($_service_id, 'post-thumbnail', ['class' => 'serviceloop__image']); ?>
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
		global $SoS_Service;global $wp_query;

		$currentTerm = get_queried_object();
		$args = (object) wp_parse_args((array) $args, [
			'hide_empty' 	=> true,
			'parent'		=> false,
			'limit'		 	=> 24
		]);
		$terms = get_terms([
			'taxonomy'		=> $SoS_Service->taxonomy,
			'post_type'		=> $SoS_Service->post_type,
			'hide_empty'	=> $args->hide_empty,
			'number'		=> $args->limit,
			// 'parent'		=> $args->parent,
		]);
		ob_start();
		try {
			if (!empty($terms) && !is_wp_error($terms)) {
				?>
				<div class="service_catlist">
					<!-- <div class="service_catlist__header">
						<span><?php echo esc_html($currentTerm->name); ?></span>
					</div> -->
					<ul class="service_catlist__list">
						<?php foreach($terms as $term) : ?>
							<li class="service_catlist__list__item <?php echo esc_attr(($term->term_id == $currentTerm->term_id)?'service_catlist__list__item__active':''); ?>">
								<a href="<?php echo esc_url(get_term_link($term)); ?>" class="service_catlist__list__link" data-category="<?php echo esc_attr($term->term_id); ?>" data-count="<?php echo esc_attr($term->count); ?>"><?php echo esc_html(
									strlen($term->name) >= 30?substr($term->name, 0, 27) . '...':$term->name
								); ?></a>
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
		global $SoS_Service;
		$args = (object) wp_parse_args($args, [
			'zip'		 => true,
			'grouped'	 => true,
			'closable'	 => true,
			'hide_empty' => true,
			'parent'	=> false,
			'limit'		 => 6
		]);
		ob_start();
		?>
		<div class="sos_hero">
			<form class="sos_hero__wrap" action="<?php echo esc_url(site_url('/service')); ?>">
				<div class="sos_hero__row">
					<div class="sos_hero__searchable">
						<select name="" id="" class="sos_hero__searchable__select">
							<option value=""><?php esc_html_e('What’s on your to-do list?', 'sospopsprompts'); ?></option>
						</select>
					</div>
					<?php if ($args->zip) : ?>
						<div class="sos_hero__zip">
							<?php echo do_shortcode('[sos_zip_popup input=true]'); ?>
						</div>
					<?php endif; ?>
					<div class="sos_hero__submit">
						<button class="sos_hero__submit__btn" type="submit"><?php esc_html_e('Search', 'sospopsprompts'); ?></button>
					</div>
				</div>
				<div class="sos_hero__suggestion">
					<ul class="sos_hero__suggestion__list">
						<?php
						$terms = get_terms([
							'taxonomy'   		=> $SoS_Service->taxonomy,
							'hide_empty' 		=> $args->hide_empty,
							'number'			=> $args->limit,
							// 'parent'			=> $args->parent,
						]);
						foreach($terms as $term) :
							?>
							<li class="sos_hero__suggestion__list__item">
								<a href="<?php echo esc_url(get_term_link($term)); ?>" class="sos_hero__suggestion__list__link"><?php echo esc_html(
									strlen($term->name) >= 30?substr($term->name, 0, 27) . '...':$term->name
								); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
	public function sos_single_service_faqs($args) {
		global $post;global $SoS_Faqs;
		$args = (object) wp_parse_args($args, ['post_id' => get_the_ID()]);
		$template_id = $SoS_Faqs->get_template_id($args->post_id);
		return do_shortcode('[elementor-template id="'. $template_id .'"]');
		// ob_start();$html = ob_get_clean();return $html;
	}
	public function sos_single_service_price($args) {
		$args = (object) wp_parse_args($args, [
			'post_id'		=> get_the_ID(),
			'format'		=> '%currency%%prices%%price_after%',
			'prices'		=> '',
			'result'		=> '',
		]);
		$args->pricing_type = get_post_meta($args->post_id, 'pricing_type', true);
		$args->price_after = get_post_meta($args->post_id, 'price_after', true);
		$args->text_after = get_post_meta($args->post_id, 'text_after', true);
		$args->currency = get_post_meta($args->post_id, 'currency', true);
		$args->prices = get_post_meta($args->post_id, 'prices', true);

		$replace4 = array_keys((array) $args);
		foreach($replace4 as $i => $item) {$replace4[$i] = '%'.$item.'%';}
		foreach($args as $key => $value) {$args->{$key} = ($value === false)?'':$value;}
		$replace2 = array_values((array) $args);
		
		$args->result = str_replace($replace4, $replace2, $args->format);
		return $args->result;
	}
	public function sos_single_service_in_area($args) {
		global $post;global $SoS_Faqs;
		$args = (object) wp_parse_args($args, ['post_id' => get_the_ID()]);

		$zip_code = get_query_var('zip_code');
		if (
			($zip_code && !empty($zip_code))
				|| 
			(is_user_logged_in() && $zip_code = get_user_meta(get_current_user_id(), '_zip_code', true))
		) {
			if (!has_term($zip_code, 'area', $post)) {
				return do_shortcode('[elementor-template id="1876"]');
			}
		}
		return '';
	}
	public function sos_single_service_review_form($args) {
		// $args = (object) wp_parse_args($args, []);
		if (!is_user_logged_in()) {return '';}
		// Some conditions here to filterout.
		return do_shortcode('[site_reviews_form assigned_posts="post_id" assigned_users="user_id" hide="email,title"]');
	}
	public function sos_single_service_reviews($args) {
		// $args = (object) wp_parse_args($args, []);
		return do_shortcode('[site_reviews assigned_posts="post_id" display="6" pagination="ajax" hide="title" fallback="[elementor-template id=1466]"]');
	}
	public function posts_pre_query($posts, $args) {
		if ($args->query['post_type'] == 'service') {
			// print_r($args);
		}
		return $posts;
	}


	public function sos_stripe_info_print($args) {
		$args = (object) wp_parse_args($args, [
			'field' => 'url'
		]);
		if ($this->payment_stripe_info && isset($this->payment_stripe_info['id'])) {
			if ($args->field && !empty($args->field) && isset($this->payment_stripe_info[$args->field])) {
				return $this->payment_stripe_info[$args->field];
			}
		}
		return '';
	}
	
}
