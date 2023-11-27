<?php
/**
 * Theme Sidebars.
 *
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;
/**
 * Class Shortcode.
 */
class Order {
	use Singleton;
	private $lastCustomData;
	protected $confirmMailTrack = false;
	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->lastCustomData = false;
		$this->setup_hooks();
	}
	/**
	 * To register action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		/**
		 * Actions
		 */
		// add_shortcode( 'checkout_video', [ $this, 'checkout_video' ] );
		add_action('add_meta_boxes',[$this, 'add_custom_meta_box']);
	}
	public function add_custom_meta_box() {
		$screens = ['shop_order'];
		foreach($screens as $screen) {
			add_meta_box(
				'sos_meta_data',           				// Unique ID
				__( 'Appearances', 'sospopsprompts' ),  // Box title
				[ $this, 'custom_meta_box_html' ],  		// Content callback, must be of type callable
				$screen,                   							// Post type
				'side'                   								// context
			);
		}
	}
	public function custom_meta_box_html($post) {
		$order_id = $post->ID;
		$order = wc_get_order($order_id);
		$target_dir = SOSPOPSPROJECT_UPLOAD_DIR;
		?>
		<div class="fwp-outfit__container">
			<div class="fwp-outfit__header">
				<span class="fwp-outfit__title"><?php esc_html_e('Customized order', 'sospopsprompts'); ?></span>
			</div>
			<div class="fwp-outfit__body">
				<?php
				$teddyNameRequired = [];
				foreach($order->get_items() as $order_item_id => $order_item) {
					$item_name = $order_item->get_name();
					$item_meta_data = $order_item->get_meta_data();
					$name_required = $this->is_name_required($order, $order_item);
					if($name_required) {
						$teddyNameRequired[] = [
							'prod_name'			=> $item_name,
							'order_id'			=> $order_id,
							'item_id'			=> $order_item_id,
						];
					}
					if(!empty($item_meta_data)) {
						?>
						<span class="fwp-outfit__product"><?php echo esc_html(sprintf('Item: %s', $item_name)); ?></span>
						<ul class="fwp-outfit__list">
						<?php
						foreach($item_meta_data as $meta) {
							if(is_array($meta->value)) {continue;}
							$thumbnailImage = false;
							
							// Getting Icons.
							$custom_data = (array) $this->get_order_item_meta($order_item->get_id(), 'custom_teddey_bear_data');
							if($custom_data && isset($custom_data['field'])) {
								foreach((array) $custom_data['field'] as $i => $iRow) {
									if(is_array($iRow)) {
										foreach($iRow as $j => $jRow) {
											$jRow = (object) $jRow;
											if(
												isset($jRow->value) && isset($jRow->price) && isset($jRow->image) && 
												!empty($jRow->image) && 
												$jRow->value == $meta->key // && $jRow->price == $meta->value
											) {
												$thumbnailImage = $jRow->image;
												$attachment_id = attachment_url_to_postid($thumbnailImage);
												if($attachment_id) {
													$thumbnailImage = wp_get_attachment_thumb_url($attachment_id);
												} else {
													// print_r('Not found');
												}
											}
										}
									}
								}
							}
							
							$target_file = (file_exists($target_dir.$meta->value) && !is_dir($target_dir.$meta->value))?$target_dir.$meta->value:false;
							?>
							<li class="fwp-outfit__items <?php echo esc_attr(($target_file)?'fwp-outfit__audio':''); ?>">
								<?php if(!$target_file): ?>
									<?php if($thumbnailImage): ?><img src="<?php echo esc_url($thumbnailImage); ?>" alt="<?php echo esc_attr($meta->value); ?>" class="fwp-outfit__image" data-product="<?php echo esc_attr($item_name); ?>" data-item="<?php echo esc_attr($meta->key); ?>" data-price="<?php echo esc_attr($meta->value); ?>"><?php endif; ?>
									<span class="fwp-outfit__title"><?php echo esc_html($meta->key); ?></span>
									<span class="fwp-outfit__price"><?php echo wp_kses_post($meta->value); ?></span>
								<?php else: ?>
									<div class="fwp-outfit__player" data-audio="<?php echo esc_url(site_url(str_replace([ABSPATH], [''], $target_file))); ?>" title="<?php echo esc_attr($meta->value); ?>"></div>
								<?php endif; ?>
							</li>
							<?php
						}
						?>
						<?php if($custom_data): ?>
							<li class="fwp-outfit__items <?php echo esc_attr(($target_file)?'fwp-outfit__certificate':''); ?>">
								<a href="<?php echo esc_url(home_url('?certificate_preview='. $order_id .'-'.$order_item_id)); ?>" class="btn button link" target="_blank"><?php esc_html_e('Certificate', 'sospopsprompts'); ?></a>
							</li>
						<?php endif; ?>
						</ul>
						<?php
					} else {
						echo '<p>No custom meta data found for this item.</p>';
					}
				}
				if(count($teddyNameRequired) >= 1) {
					?>
					<script>window.teddyNameRequired = <?php echo json_encode($teddyNameRequired); ?>;</script>
					<?php
				}
				?>
			</div>
			<div class="fwp-outfit__footer"></div>
		</div>
		<?php
	}


	/**
	 * Work on wooommerce order email.
	 */
	public function woocommerce_email_order_meta($order, $sent_to_admin, $plain_text) {}
}
