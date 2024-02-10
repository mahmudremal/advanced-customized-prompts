<?php
/**
 * The OpenAI ChatGPT-3.
 * https://www.npmjs.com/package/openai
 * https://www.npmjs.com/package/chatgpt
 * 
 * @package SOSPopsProject
 */
namespace SOSPOPSPROJECT\inc;
use SOSPOPSPROJECT\inc\Traits\Singleton;

class Cart {
	use Singleton;
	public  $ajax;
	private $base;
	private $showedAlready;
	private $calculatedAlready;
	protected function __construct() {
		$this->ajax = [];
		$this->base = [];
		$this->showedAlready = [];
		$this->calculatedAlready = [];
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_action('wp_ajax_nopriv_sospopsproject/ajax/cart/add', [$this, 'ajax_add_to_cart'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/cart/add', [$this, 'ajax_add_to_cart'], 10, 0);

		// add_action('woocommerce_cart_calculate_fees', [$this, 'woocommerce_cart_calculate_fees'], 10, 0);
		add_filter('woocommerce_cart_item_name', [$this, 'display_additional_charges'], 10, 3);
		// add_filter('woocommerce_order_item_name', [$this, 'woocommerce_order_item_name'], 10, 3);
		
		add_action('woocommerce_before_calculate_totals', [$this, 'woocommerce_calculate_totals'], 10, 1);

		add_filter('woocommerce_add_cart_item_data', [$this, 'woocommerce_add_cart_item_data'], 10, 4);
		// add_filter('woocommerce_get_item_data', [$this, 'woocommerce_get_item_data'], 10, 4);
	}

	public function ajax_add_to_cart() {
		global $SoS_Email;global $SoS_Order;
		if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
			wp_send_json_error('Missing required data.');
		}
		$this->ajax = [
			'hooks' => ['popup_submitting_failed'], 'email_sent' => false,
			'message' => __('Something went wrong. Please try again.', 'sospopsprompts')
		];
		$product_id = intval($_POST['product_id']);
		$quantity = intval($_POST['quantity']);
		// $product = wc_get_product($product_id);
		// if (!$product || !$product->is_purchasable()) {
		// 	wp_send_json_error('Invalid product or product is not purchasable.');
		// }
		
		try {
			$dataset = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['dataset']))), true);
			$charges = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['charges']))), true);

			$is_updated = true;
			if (is_user_logged_in()) {
				$is_updated = update_user_meta(get_current_user_id(), '__sos_userdata', $dataset);
				foreach($dataset as $row) {
					if (isset($row['name']) && !empty(trim($row['name'])) && !empty(trim($row['value']))) {
						$is_added = update_user_meta(get_current_user_id(), str_replace([' '], [''], $row['name']), $row['value']);
					}
				}
			}
			if (isset($_POST['product_type'])) {
				$args = [
					'dataset'		=> $dataset,
					'charges'		=> $charges,
					'request_type'	=> $_POST['product_type'],
					'product_id'	=> $_POST['product_id'],
				];
				$order_id = $SoS_Order->createOrder($args);
				if ($order_id) {
					$args['order_id'] = $order_id;
				}
				if ($_POST['product_type'] == 'get_quotation') {
					try {
						$email_sent = apply_filters('sos_send_email', '', $args);
						if ($email_sent !== false && !empty($email_sent)) {
							$this->ajax['email_sent'] = true;
							// $this->ajax['email_template'] = $email_sent;
							// $this->ajax['redirectTo'] = site_url('/contact-us/');
						}
					} catch (\ErrorException $th) {
						//throw $th;
						$this->ajax['message'] = $th->getMessage;
					}
				// } elseif ($_POST['product_type'] == 'add') {
					// $this->ajax['redirectTo'] = wc_get_checkout_url();
				} else {
					if ($order_id) {
						$this->ajax['order_created'] = $order_id;
						$this->ajax['email_sent'] = true;
						$payment_link = apply_filters('sos/project/payment/stripe/paymentlink', [
							'order_id'	=> $order_id,
							'quantity'	=> 1,
							'price_data' => [
								'currency' => apply_filters('sos/project/system/getoption', 'stripe-currency', 'usd'),
								'unit_amount' => (int) ($SoS_Email->get_calculation(['charges' => $charges, 'product_id' => $_POST['product_id']])->total * 100), // Unit amount in cent | number_format($calculated_amount, 2 ),
								'product_data' => [
									'name' => apply_filters('sos/project/system/getoption', 'stripe-productname', __('SoS Charges', 'domain')),
									'description' => apply_filters('sos/project/system/getoption', 'stripe-productdesc', __('Payment for', 'domain') . ' ' . get_option('blogname', 'SoS')),
									'images' => [
										apply_filters('sos/project/system/getoption', 'stripe-productimg', esc_url(SOSPOPSPROJECT_BUILD_URI . '/icons/Online payment_Flatline.svg'))
									],
								],
							]
						], true);
						if ($payment_link && !empty($payment_link)) {
							$this->ajax['payment_link'] = $payment_link;
							$this->ajax['redirectTo'] = $payment_link;
						}
					}
				}
			}
			if ($is_updated || $this->ajax['email_sent']) {
				$this->ajax['message'] = __('Successfully updated your information.', 'domain');
				$this->ajax['hooks'] = ['addedToCartSuccess'];
				if (isset($this->ajax['payment_link']) && $this->ajax['payment_link'] && !empty($this->ajax['payment_link'])) {
					$this->ajax['hooks'] = ['addedToCartToCheckout'];
				}
				wp_send_json_success($this->ajax);
			}
		} catch (\Exception $e) {
			// Handle the exception here
			$this->ajax['message'] = 'Error: ' . $e->getMessage();
		}
		wp_send_json_error($this->ajax);
	}
	public function woocommerce_add_cart_item_data($cart_item_data, $product_id, $variation_id, $quantity) {
		if (!isset($_POST['dataset']) || !isset($_POST['dataset'])) {return $cart_item_data;}
		$dataset = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['dataset']))), true);
		$charges = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['charges']))), true);
		
		$cart_item_data['custom_teddey_bear_makeup'] = $charges;
		$cart_item_data['custom_teddey_bear_data'] = $dataset;
		
		return $cart_item_data;
	}
	public function woocommerce_get_item_data($item_data, $cart_item) {
		if (isset($cart_item['custom_teddey_bear_makeup'])) {
			$item_data[] = [
				'key' => 'Custom Makeup Charges',
				'value' => $cart_item['custom_teddey_bear_makeup']
			];
		}
		if (isset($cart_item['custom_teddey_bear_data'])) {
			$item_data[] = [
				'key' => 'Custom Teddy Bear Data',
				'value' => $cart_item['custom_teddey_bear_data']
			];
		}
		return $item_data;
	}
	public function woocommerce_cart_calculate_fees() {
		if (is_admin() && !defined('DOING_AJAX')) {return;}
		$cart = WC()->cart;
		
		foreach($cart->get_cart() as $cart_item_key => $cart_item) {
			if (array_key_exists('custom_teddey_bear_makeup', $cart_item)) {
				// $additional_cost = 0;
				// print_r($cart->get_cart());
				foreach($cart_item['custom_teddey_bear_makeup'] as $fee) {
					$cart->add_fee($fee['item'], ($fee['price'] * $cart_item['quantity']), true, 'standard');
					// $additional_cost += ($fee['price'] * $cart_item['quantity']);
				}
				// $cart_item['data']->set_price($cart_item['data']->get_price() + $additional_cost);
			}
		}
	}
	public function woocommerce_order_item_name($item_name, $order_item, $order) {
		$meta_data = $order_item->get_meta_data();
		if ($meta_data && !empty($meta_data)) {
            $item_name .= '<br><small class="additional-charges">';
            foreach ($meta_data as $meta) {
                $key = $meta->key;$value = $meta->value;
                $item_name .= esc_html($key).': '.wc_price($value).', ';
            }
            // Remove the trailing comma and space
            $item_name = rtrim($item_name, ', ');
            $item_name .= '</small>';
        }

    	return $item_name;
	}
	public function display_additional_charges($item_name, $cart_item, $cart_item_key) {
		// if (isset($cart_item['_additional_charges_applied'])) {return $item_name;}
		if (isset($cart_item['custom_teddey_bear_makeup']) && !in_array($cart_item_key, $this->showedAlready)) {
			foreach($cart_item['custom_teddey_bear_makeup'] as $fee) {
				if (!empty($fee['price']) && is_numeric($fee['price'])) {
					$item_name .= '<br><small class="additional-charges">'.esc_html($fee['item']).': '.wc_price($fee['price']).' x '.esc_html(number_format_i18n($cart_item['quantity'], 0)).'</small>';
				}
			}
			// $cart_item['_additional_charges_applied'] = true;
			$this->showedAlready[] = $cart_item_key;
		}
		return $item_name;
	}
	public function woocommerce_calculate_totals($cart) {
		if (is_admin() && !defined('DOING_AJAX')) {return;}
	
		foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
			if (array_key_exists('custom_teddey_bear_makeup', $cart_item) && !in_array($cart_item_key, $this->calculatedAlready)) {
				$additional_cost = 0;
				foreach($cart_item['custom_teddey_bear_makeup'] as $fee) {
					if (!empty($fee['price']) && is_numeric($fee['price'])) {
						$additional_cost += ($fee['price'] * $cart_item['quantity']);
					}
				}
				if ($additional_cost > 0) {
					$cart_item['data']->set_price($cart_item['data']->get_price() + $additional_cost);
				}
				$this->calculatedAlready[] = $cart_item_key;
			}
		}
	}

	public function custom_upload_audio_video($file) {
		$upload_dir = wp_upload_dir();$custom_dir = 'custom_popup';
		$target_dir = $upload_dir['basedir'].'/'.$custom_dir.'/';
		if (!file_exists($target_dir)) {mkdir($target_dir, 0755, true);}
		$file_name = $file['name'];$file_tmp = $file['tmp_name'];$file_type = $file['type'];
		$allowed_regex = '/^(audio|video|text)\/(.*?)/i';
		if (!preg_match($allowed_regex, $file_type)) {
			throw new \Exception(__('Error: Only audio and video files are allowed.', 'sospopsprompts'));
		}
		$max_file_size = 400 * 1024 * 1024;
		if ($file['size'] > $max_file_size) {
			throw new \Exception(__('Error: File size exceeds the maximum limit of 400 MB.', 'sospopsprompts'));
		}
		$target_file = $target_dir . $file_name;
		if (!move_uploaded_file($file_tmp, $target_file)) {
			throw new \Exception(__('Error uploading file.', 'sospopsprompts'));
		}
		return true;
	}
  
}
