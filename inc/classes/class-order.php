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
	private $post_type;
	private $lastCustomData;
	protected $confirmMailTrack = false;
	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->post_type = 'product_services';
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
		add_action('add_meta_boxes',[$this, 'add_custom_meta_box']);
	}
	public function add_custom_meta_box() {
		$screens = ['product_services'];
		foreach($screens as $screen) {
			add_meta_box(
				'sos_order_details',           				// Unique ID
				__('Order Details', 'sospopsprompts'),  	// Box title
				[$this, 'order_details_meta_box_html'],		// Content callback, must be of type callable
				$screen,									// Post type
				'normal',                   				// Context
				'high'										// Priority
			);
			add_meta_box(
				'sos_payment_details',           			// Unique ID
				__('Payment Details', 'sospopsprompts'),  	// Box title
				[$this, 'payment_details_meta_box_html'],	// Content callback, must be of type callable
				$screen,									// Post type
				'normal',                   				// Context
				'high'										// Priority
			);
		}
	}
	public function order_details_meta_box_html($post) {
		$order_id = $post->ID;
		?>
		<div class="fwp-outfit__container">
			<!-- <div class="fwp-outfit__header">
				<span class="fwp-outfit__title"><?php esc_html_e('Order details', 'sospopsprompts'); ?></span>
			</div> -->
			<div class="fwp-outfit__body">
				<?php
				$args = (object) get_post_meta($order_id, 'order_infos', true);
				if (!empty($args)) {
					if (isset($args->charges)) {
						?>
						<span class="fwp-outfit__product"><?php echo esc_html(__('Charges', 'doamin')); ?></span>
						<ul class="fwp-outfit__list">
							<li class="fwp-outfit__items">
								<span class="fwp-outfit__title"><?php esc_html_e('Product :', 'domain'); ?> :</span>
								<span class="fwp-outfit__price">
									<a class="fwp-outfit__link" href="<?php echo esc_url(get_the_permalink($args->product_id)); ?>" target="_blank"><?php echo esc_html(get_the_title($args->product_id)); ?></a>
								</span>
							</li>
						<?php
							foreach($args->charges as $_i => $_row) {
								$_row = (object) $_row;
								if (!isset($_row->item)) {continue;}
								$_row->item = substr(
									$_row->item,
									0,
									(strlen($_row->item) - (strlen(end(explode('-', $_row->item))) + 2))
								);
								
								?>
								<li class="fwp-outfit__items">
									<span class="fwp-outfit__title"><?php echo esc_html($_row->item); ?> :</span>
									<span class="fwp-outfit__price"><?php echo esc_html($_row->price); ?></span>
								</li>
								<?php
							}
						?>
						</ul>
						<?php
					}
					if (isset($args->dataset)) {
						?>
						<span class="fwp-outfit__product"><?php echo esc_html(__('Dataset', 'doamin')); ?></span>
						<ul class="fwp-outfit__list">
						<?php
							foreach($args->dataset as $_i => $_row) {
								$_row = (object) $_row;
								if (!isset($_row->value)) {continue;}
								if ($_row->value == __('Select Your Service', 'domain')) {
									$_row->value = 'N/A';
								}
								?>
								<li class="fwp-outfit__items">
									<span class="fwp-outfit__title"><?php echo esc_html($_row->title); ?> :</span>
									<span class="fwp-outfit__price"><?php echo esc_html($_row->value); ?></span>
								</li>
								<?php
							}
						?>
						</ul>
						<?php
					}
					//  else {}
				} ?>
			</div>
			<div class="fwp-outfit__footer"></div>
		</div>
		<?php
	}
	public function payment_details_meta_box_html($post) {
		$order_id = $post->ID;

		$payment_status = get_post_meta($order_id, 'payment_status', true);
		$payment_result = get_post_meta($order_id, 'payment_result', true);
		// print_r($payment_result);
		?>
		<div class="fwp-outfit__container">
			<!-- <div class="fwp-outfit__header">
				<span class="fwp-outfit__title"><?php // echo esc_html(get_post_meta($order_id, 'payment_status', true)); ?></span>
			</div> -->
			<div class="fwp-outfit__body">
				<ul class="fwp-outfit__list">
					<li class="fwp-outfit__items">
						<span class="fwp-outfit__title"><?php echo esc_html(__('Subtotal', 'domain')); ?> :</span>
						<span class="fwp-outfit__price" style="text-transform: capitalize;"><?php echo esc_html($payment_result['amount_subtotal'] / 100); ?></span>
					</li>
					<li class="fwp-outfit__items">
						<span class="fwp-outfit__title"><?php echo esc_html(__('Total', 'domain')); ?> :</span>
						<span class="fwp-outfit__price" style="text-transform: capitalize;"><?php echo esc_html($payment_result['amount_total'] / 100); ?></span>
					</li>
					<li class="fwp-outfit__items">
						<span class="fwp-outfit__title"><?php echo esc_html(__('Payment ID#', 'domain')); ?> :</span>
						<?php
						$link_before = '';$link_after = '';
						if (! in_array(strtolower($payment_status), ['complete'])) {
							$link_before = '<a href="' . esc_url(get_post_meta($order_id, 'payment_url', true)) . '" class="fwp-outfit__link" target="_blank">;';
							$link_after = '</a>';
						}
						?>
						<span class="fwp-outfit__price"><?php echo $link_before . esc_html(get_post_meta($order_id, 'payment_id', true)) . $link_after; ?></span>
					</li>
					<li class="fwp-outfit__items">
						<span class="fwp-outfit__title"><?php echo esc_html(__('Payment Status', 'domain')); ?> :</span>
						<span class="fwp-outfit__price" style="text-transform: capitalize;"><?php echo esc_html($payment_status); ?></span>
					</li>
				</ul>
			</div>
			<div class="fwp-outfit__footer"></div>
		</div>
		<?php
	}


	/**
	 * Create an Order
	 */
	public function createOrder($args) {
		$args = (object) wp_parse_args($args, ['request_type' => 'get_quotation']);
		$order_id = wp_insert_post([
			'post_title'			=> wp_date('M d, Y H:i:s'),
			'post_status'			=> ($args->request_type == 'get_quotation')?'publish':'draft', // pending
			'post_type'				=> $this->post_type,
			'comment_status'		=> 'closed'
		], true);
		if ($order_id && !is_wp_error($order_id)) {
			$is_updated = add_post_meta($order_id, 'order_infos', $args);
			return $order_id;
		} else {
			return false;
		}
	}
	public function handle_payment_success($result) {
		
		/**
		 * Update Order status from Draft to Publish.
		 * Adding some meta data.
		 */
		if (isset($result['client_reference_id']) && $result['client_reference_id'] && !empty($result['client_reference_id'])) {
			$meta_updated = update_post_meta($result['client_reference_id'], 'payment_status', $result['status']);
			$meta_updated = update_post_meta($result['client_reference_id'], 'payment_result', $result);
			$is_updated = wp_update_post([
			'ID'           => $result['client_reference_id'],
			'post_status' => 'publish'
			], true);
			if ($meta_updated && $is_updated && !is_wp_error($is_updated)) {
				try {
					$args = get_post_meta($result['client_reference_id'], 'order_infos', true);
					if ($args && !empty($args)) {
						$email_sent = apply_filters('sos_send_email', '', $args);
						if ($email_sent !== false && !empty($email_sent)) {
							/**
							 * Successfully Updated order status.
							 */
							return true;
						}
					}
					
				} catch (\ErrorException $th) {
					//throw $th;
				}
			}
		}
		return false;
	}
	public function handle_payment_canceled($result) {
		
		/**
		 * Update Order status from Draft to Publish.
		 * Adding some meta data.
		 */
		if (isset($result['client_reference_id']) && $result['client_reference_id'] && !empty($result['client_reference_id'])) {
			$meta_updated = update_post_meta($result['client_reference_id'], 'payment_status', $result['status']);
			$meta_updated = update_post_meta($result['client_reference_id'], 'payment_result', $result);
			$is_updated = wp_update_post([
				'ID'           	=> $result['client_reference_id'],
				'post_status'	=> 'trash'
			], true);
			if ($meta_updated && $is_updated && !is_wp_error($is_updated)) {}
		}
		return false;
	}
}
