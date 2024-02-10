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
	public $post_type;
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
		add_action('add_meta_boxes',[$this, 'add_custom_meta_box'], 10, 2);
	}
	public function add_custom_meta_box($post_type, $post) {
		$screens = [$this->post_type];
		foreach($screens as $screen) {
			$order_type = get_post_meta($post->ID, 'order_type', true);
			add_meta_box(
				'sos_order_details',           				// Unique ID

				($order_type == 'get_quotation')?
				__('Quotation Details', 'sospopsprompts')
				:
				__('Order Details', 'sospopsprompts'),  	// Box title

				[$this, 'order_details_meta_box_html'],		// Content callback, must be of type callable
				$screen,									// Post type
				'normal',                   				// Context
				'high'										// Priority
			);
			if ($order_type == 'get_quotation') {continue;}
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
		if(!$payment_result || empty($payment_result)) {
			include SOSPOPSPROJECT_DIR_PATH . '/templates/metabox/pending-payment.php';
		} else {
			$payment = $payment_result;
			?>
			<div class="fwp-outfit__container">
				<!-- <div class="fwp-outfit__header">
					<span class="fwp-outfit__title"><?php // echo esc_html(get_post_meta($order_id, 'payment_status', true)); ?></span>
				</div> -->
				<div class="fwp-outfit__body">
					<ul class="fwp-outfit__list">
						<?php
						$args = [
							[
								'label'			=> __('Subtotal', 'domain'),
								'value'			=> ($payment['amount_subtotal'] / 100)
							],
							[
								'label'			=> __('Total', 'domain'),
								'value'			=> ($payment['amount_total'] / 100)
							],
							[
								'label'			=> __('Payment Status', 'domain'),
								'value'			=> $payment_status
							],
						];
						if (isset($payment['client_reference_id']) && !empty($payment['client_reference_id'])) {
							$args[] = [
								'label'		=> __('Client Reference ID', 'domain'),
								'value'		=> $payment['client_reference_id']
							];
						}
						if (isset($payment['created']) && !empty($payment['created'])) {
							$args[] = [
								'label'		=> __('Payment Made', 'domain'),
								'value'		=> wp_date('d M, Y H:i', $payment['created'])
							];
						}
						if (isset($payment['currency']) && !empty($payment['currency'])) {
							$args[] = [
								'label'		=> __('Payment Currency', 'domain'),
								'value'		=> strtoupper($payment['currency'])
							];
						}
						if (isset($payment['ui_mode']) && !empty($payment['ui_mode'])) {
							$args[] = [
								'label'		=> __('Payment Made Using', 'domain'),
								'value'		=> strtoupper($payment['ui_mode'])
							];
						}
						if (isset($payment['ui_mode']) && !empty($payment['ui_mode'])) {
							$payargs = [
								'label'		=> __('Payment ID#', 'domain'),
								'value'		=> get_post_meta($order_id, 'payment_id', true)
							];
							$payment_url = get_post_meta($order_id, 'payment_url', true);
							if ($payment_url && !in_array(strtolower($payment_status), ['complete'])) {
								$payargs['link'] = $payment_url;
							}
							$args[] = $payargs;
						}
						foreach($args as $row) :
							$isLink = isset($row['link']);
							?>
							<li class="fwp-outfit__items">
								<<?php echo esc_attr($isLink?'a':'span') ?> class="fwp-outfit__title" href="<?php echo esc_attr($isLink?$row['link']:'#') ?>"><?php echo esc_html($row['label']); ?> :</<?php echo esc_attr($isLink?'a':'span') ?>>
								<span class="fwp-outfit__price" style="text-transform: capitalize;"><?php echo esc_html($row['value']); ?></span>
							</li>
							<?php
							endforeach;
						?>
					</ul>
				</div>
				<div class="fwp-outfit__footer"></div>
			</div>
			<?php
		}
	}


	/**
	 * Create an Order
	 */
	public function createOrder($args) {
		$args = (object) wp_parse_args($args, ['request_type' => 'get_quotation']);
		$title_format = str_replace(
			['{{', '}}'], ['', ''],
			apply_filters('sos/project/system/getoption', 'order-format', '{{date-d M, Y H:i}}')
		);
		// if (strpos($title_format, 'client-') !== false) {
		// 	$title_format = str_replace('client-', '', $title_format);
		// 	switch ($title_format) {
		// 		case 'name':
		// 			$title_format = '';
		// 			break;
		// 		case 'name':
		// 			$title_format = '';
		// 			break;
		// 		default:
		// 			break;
		// 	}
		// }
		if (strpos($title_format, 'date-') !== false) {
			$title_format = str_replace('date-', '', $title_format);
			$title_format = wp_date($title_format);
		}
			
		$order_id = wp_insert_post([
			'post_title'			=> $title_format,
			'post_status'			=> ($args->request_type == 'get_quotation')?'publish':'draft', // pending
			'post_type'				=> $this->post_type,
			'comment_status'		=> 'closed'
		], true);
		if ($order_id && !is_wp_error($order_id)) {
			$is_updated = add_post_meta($order_id, 'order_infos', $args);
			$is_updated = add_post_meta($order_id, 'order_type', $args->request_type);
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
