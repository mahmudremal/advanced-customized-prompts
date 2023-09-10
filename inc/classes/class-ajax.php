<?php
/**
 * Block Patterns
 *
 * @package SOSPopsProject
 */

namespace SOSPOPSPROJECT\inc;

use SOSPOPSPROJECT\inc\Traits\Singleton;

class Ajax {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	protected function setup_hooks() {
		add_action('wp_ajax_sospopsproject/datastore/get_autocomplete', [$this, 'get_autocomplete'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/datastore/get_autocomplete', [$this, 'get_autocomplete'], 10, 0);

		add_action('wp_ajax_sospopsproject/ajax/search/product', [$this, 'search_product'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/ajax/search/product', [$this, 'search_product'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/submit/popup', [$this, 'submit_popup'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/ajax/submit/popup', [$this, 'submit_popup'], 10, 0);

		add_action('wp_ajax_sospopsproject/ajax/edit/product', [$this, 'edit_product'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/save/product', [$this, 'save_product'], 10, 0);

		add_action('wp_ajax_sospopsproject/ajax/search/popup', [$this, 'search_popup'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/ajax/search/popup', [$this, 'search_popup'], 10, 0);
		
		add_action('wp_ajax_sospopsproject/ajax/update/orderitem', [$this, 'update_orderitem'], 10, 0);

		add_action('wp_ajax_nopriv_sospopsproject/ajax/suggested/names', [$this, 'suggested_names'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/suggested/names', [$this, 'suggested_names'], 10, 0);

		add_action('wp_ajax_nopriv_sospopsproject/ajax/update/zipcode', [$this, 'update_zipcode'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/update/zipcode', [$this, 'update_zipcode'], 10, 0);
		
		add_action('wp_ajax_nopriv_sospopsproject/ajax/add/order', [$this, 'add_order'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/add/order', [$this, 'add_order'], 10, 0);

		add_action('woocommerce_new_order', [$this, 'initiate_payment_process']);

	}
	public function get_autocomplete() {
		global $wpdb;
		switch ($_GET['term']) {
			case 'product':
				$_GET['query'] = '%'.$_GET['query'].'%';
				$res = $wpdb->get_results(
					"SELECT ttx.term_id, trm.name FROM {$wpdb->prefix}term_taxonomy ttx left join {$wpdb->prefix}terms trm on trm.term_id=ttx.term_id where ttx.taxonomy='listing_product' and trm.name like '%a%' order by ttx.term_id desc limit 0, 50;"
				);
				break;
			case 'location':
				$_GET['query'] = '%'.$_GET['query'].'%';
				$res = $wpdb->get_results(
					"SELECT post.post_title, pstm.meta_value as name FROM {$wpdb->prefix}posts post left join {$wpdb->prefix}postmeta pstm on pstm.post_id=post.ID WHERE post.post_type='listing' and pstm.meta_key='_friendly_address' and pstm.meta_value like '{$_GET['query']}' order by post.post_title desc limit 0, 50;"
				);
				break;
			default:
				$res = [];
				break;
		}
		
		// $res = [];for ($i=0; $i < 10; $i++) {$res[] = ['name'=>'Result '.$i,'value'=>'result_'.$i];}
		wp_send_json_success($res, 200);
	}
	public function search_product() {
		global $wpdb;global  $woocommerce;global $teddyProduct;
		// check_ajax_referer('sospopsproject/teddybearpopupaddon/verify/nonce', '_nonce', true);
		$dataset = $request = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode(isset($_POST['dataset'])?$_POST['dataset']:'{}'))), true);
		
		$_product = true; // wc_get_product($request['product_id']);
		$productData = ($_product && !is_wp_error($_product))?[
			'id'		=> '', // $_product->get_id(),
			'type'		=> '', // $_product->get_type(),
			'title'		=> '', // get_the_title($_product->get_id()),
			'name'		=> '', // $_product->get_name(),
			'slug'		=> '', // $_product->get_slug(),
			'link'		=> '', // get_permalink($_product->get_id()),
			'price'		=> '', // $_product->get_price(),
			'currency'	=> '', // get_woocommerce_currency_symbol(),
			'priceHtml'	=> '', // $_product->get_price_html()
		]:[];

		$json = [
			'hooks' => ['gotproductpopupresult'],
			'header' => ['product_photo' => 'empty'],
			'user' => [
				'sellerLoggedIn' => is_user_logged_in(),
				'telephone' => null,
				'userLoggedIn' => is_user_logged_in(),
				'userName' => is_user_logged_in()?wp_get_current_user()->display_name:null
			],
			'country' => false,
			'product' => [
				'id'		=> $productData['id'],
				'price'		=> $productData['price'],
				'currency'	=> $productData['currency'],
				'priceHtml'	=> $productData['priceHtml'],
				'name'		=> $productData['name'],
				'link'		=> $productData['link'],
				'slug'		=> $productData['slug'],
				'type'		=> $productData['type'],
				'is_parent' => false,
				'toast'		=> false, // '<strong>' . count($requested) . '</strong> people requested this service in the last 10 minutes!',
				'thumbnail'	=> ['1x' => '', '2x' => ''],
				'custom_fields' => $teddyProduct->get_post_meta($dataset['product_id'],'_sos_custom_popup',true)
			],
		];


		$json['product']['custom_fields'] = ($json['product']['custom_fields'] && !empty($json['product']['custom_fields']))?(array)$json['product']['custom_fields']:[];
		foreach($json['product']['custom_fields'] as $i => $_prod) {
			$json['product']['custom_fields'][$i]['headerbgurl'] = ($_prod['headerbg']=='')?false:wp_get_attachment_url($_prod['headerbg']);
			if(isset($_prod['options'])) {
				$_prod['options'] = (!empty($_prod['options']))?(array)$_prod['options']:[];
				foreach($_prod['options'] as $j => $option) {
					if(isset($option['image']) && !empty($option['image'])) {
						$json['product']['custom_fields'][$i]['options'][$j]['imageUrl'] = wp_get_attachment_url($option['image']);
					}
					if(isset($option['thumb']) && !empty($option['thumb'])) {
						$json['product']['custom_fields'][$i]['groups'][$k]['options'][$l]['thumbUrl'] = wp_get_attachment_url($option['thumb']);
					}
				}
			}
			if(isset($_prod['groups'])) {
				foreach($_prod['groups'] as $k => $group) {
					if(isset($group['options'])) {
						foreach($group['options'] as $l => $option) {
							if(isset($option['image']) && !empty($option['image'])) {
								$json['product']['custom_fields'][$i]['groups'][$k]['options'][$l]['imageUrl'] = wp_get_attachment_url($option['image']);
							}
							if(isset($option['thumb']) && !empty($option['thumb'])) {
								$json['product']['custom_fields'][$i]['groups'][$k]['options'][$l]['thumbUrl'] = wp_get_attachment_url($option['thumb']);
							}
						}
					}
				}
			}
		}
		wp_send_json_success($json, 200);
	}
	public function submit_popup() {
		// check_ajax_referer('sospopsproject/teddybearpopupaddon/verify/nonce', '_nonce', true);
		$request = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['dataset']))), true);
		$json = [
			'hooks' => ['popup_submitting_done'],
			'message' => __('Popup submitted successfully. Hold on unil you\'re redirecting to searh results.', 'sospopsprompts')
		];
		
		if(isset($request['product']) && !empty($request['product'])) {
			$request['product'] = (int) $request['product'];
			$term_link = get_term_link($request['product'], 'listing_product');
			if(!$term_link || is_wp_error($term_link)) {$term_link = false;}
			$json['redirectedTo'] = $term_link;
		}
		if(isset($request['field']["9002"]) && ! is_user_logged_in()) {
			$user_email = $request['field']["9002"];
			$user_name = $request['field']["9003"];
			$user_pass = $request['field']["9004"];
			$user = get_user_by_email($user_email);
			if($user) {
				$user_id = $user->ID;
				wp_set_current_user($user_id, $user->user_login);
				wp_set_auth_cookie($user_id);
				do_action('wp_login', $user->user_login, $user);
			} else {
				$user_id = username_exists($user_name);
				if(!$user_id && false == email_exists($user_email)) {
					$user_id = wp_create_user($user_name, $user_pass, $user_email);
					if(!is_wp_error($user_id)) {
						$user = get_user_by('id', $user_id);
						wp_set_current_user($user_id, $user->user_login);
						wp_set_auth_cookie($user_id);
						do_action('wp_login', $user->user_login, $user);
					}
					
				} else {
					$random_password = __('User already exists.  Password inherited.', 'textdomain');
				}
				
			}
		}
		
		wp_send_json_success($json, 200);
	}
	public function search_popup() {
		global $wpdb;
		$json = ['hooks' => ['popup_searching_done']];
		// check_ajax_referer('sospopsproject/teddybearpopupaddon/verify/nonce', '_nonce', true);
		$request = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['formdata']))), true);
		
		wp_send_json_error($json);
	}
	public function save_product() {
		$result = [];
		$result['hooks'] = ['product_updated'];$result['message'] = __('Popup updated Successfully', 'sospopsprompts');
		$request = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['dataset']))), true);
		$result['json'] = $request;
		$product_id = $_POST['product_id'];
		update_post_meta($product_id, '_sos_custom_popup', $request);
		wp_send_json_success($result, 200);
	}
	public function edit_product() {
		global $teddyProduct;$json = [];
		$json['product'] = $teddyProduct->get_post_meta($_POST['product_id'], '_sos_custom_popup', true);
		$json['hooks'] = ['gotproductpopupresult'];
		$json['product'] = ($json['product'] && !empty($json['product']))?(array)$json['product']:[];
		foreach($json['product'] as $i => $_prod) {
			$json['product'][$i]['headerbgurl'] = ($_prod['headerbg']=='')?false:wp_get_attachment_url($_prod['headerbg']);
			if(isset($_prod['options'])) {
				$_prod['options'] = (!empty($_prod['options']))?(array)$_prod['options']:[];
				foreach($_prod['options'] as $j => $option) {
					if(isset($option['image']) && !empty($option['image'])) {
						$json['product'][$i]['options'][$j]['imageUrl'] = wp_get_attachment_url($option['image']);
					}
				}
			}
			if(isset($_prod['groups'])) {
				foreach($_prod['groups'] as $k => $group) {
					if(isset($group['options'])) {
						foreach($group['options'] as $l => $option) {
							if(isset($option['image']) && !empty($option['image'])) {
								$json['product'][$i]['groups'][$k]['options'][$l]['imageUrl'] = wp_get_attachment_url($option['image']);
							}
							if(isset($option['thumb']) && !empty($option['thumb'])) {
								$json['product'][$i]['groups'][$k]['options'][$l]['thumbUrl'] = wp_get_attachment_url($option['thumb']);
							}
						}
					}
				}
			}
		}
		$json['info'] = ['prod_title' => get_the_title($_POST['product_id'])];
		wp_send_json_success($json, 200);
	}
	public function merge_customfields($fields) {
		$fieldID = 9000;
		if(!$fields || $fields == "") {return $fields;}
		$fields = (array) $fields;
		$fields[] = [
			'fieldID' => $fieldID,
			'type' => 'text',
			'headerbg' => '',
			'heading' => 'Where do you need the {{product.name}}?',
			'subtitle' => 'The postcode or town for the address where you want the {{product.name}}.',
			'placeholder' => 'Enter your postcode or town',
			'steptitle' => '',
			'headerbgurl' => false,
		];
		$fieldID++;
		$fields[] = [
			'fieldID' => $fieldID,
			'type' => 'confirm',
			'headerbg' => '',
			'steptitle' => '',
			'heading' => 'Great - we\'ve got pros ready and available.',
			'icon'	=> '<span class="fa fa-check"></span>',
			'headerbgurl' => false,
		];
		if(!is_user_logged_in()) {
			$fieldID++;
			$fields[] = [
				'fieldID' => $fieldID,
				'type' => 'text',
				'headerbg' => '',
				'heading' => 'Which email address would you like quotes sent to?',
				'subtitle' => 'Give here your email address that will help us to create an account for you or auto login for an existing account.',
				'placeholder' => 'Email-address',
				'steptitle' => '',
				'headerbgurl' => false,
			];
			$fieldID++;
			$fields[] = [
				'fieldID' => $fieldID,
				'type' => 'text',
				'headerbg' => '',
				'heading' => 'Your full name?',
				'subtitle' => '',
				'steptitle' => '',
				'label' => 'Please tell us your name:',
				'placeholder' => 'Full name',
				'footer'	=> sprintf(__('By continuing, you confirm your agreement to our %sTerms & Conditions%s', 'sospopsprompts'), '<a href="'.esc_url(get_privacy_policy_url()).'" target="_blank">', '</a>'),
				'headerbgurl' => false,
				'extra_fields' => [
					[
						'fieldID' => ($fieldID+1),
						'type' => 'checkbox',
						'subtitle' => '',
						'headerbgurl' => false,
						'options'	=> [
							['label' => 'I am happy to receive occasional marketing emails.', 'input' => false, 'next' => false]
						]
					]
				]
			];
			$fieldID++;$fieldID++;
			$fields[] = [
				'fieldID' => $fieldID,
				'type' => 'password',
				'headerbg' => '',
				'heading' => 'Give here the password',
				'steptitle' => '',
				'subtitle' => 'Password help to keep your account secure for anonymouse attack and third party tracking.',
				'placeholder' => '%^8;fd&!87"af$',
				'headerbgurl' => false,
			];
			$fieldID++;
			$fields[] = [
				'fieldID' => $fieldID,
				'type' => 'text',
				'headerbg' => '',
				'heading' => 'What is your phone number?',
				'subtitle' => 'Professionals will be able to contact you directly to find out more about your request.',
				'placeholder' => 'Phone number. Eg. +880 1814-118 328',
				'steptitle' => '',
				'headerbgurl' => false,
			];
		} else {
			$fieldID = ($fieldID + 4);
		}
		return $fields;
	}

	public function update_orderitem() {
		global $teddyProduct;
		$json = ['hooks' => ['order_item_update_failed'], 'message' => __('Something went wrong. Please review your request again.', 'sospopsprompts')];
		if(!isset($_GET['order_id']) || empty($_GET['order_id']) || !isset($_GET['item_id']) || empty($_GET['item_id']) || !isset($_GET['teddyname']) || empty($_GET['teddyname'])) {
			wp_send_json_error($json);
		}

		$order_id = $_GET['order_id'];
		$item_id = $_GET['item_id'];
		$order = wc_get_order($order_id);
		foreach($order->get_items() as $order_item_id => $order_item) {
			if($order_item_id != $item_id) {continue;}
			$product_id = $order_item->get_product_id();
			$popup_meta = $teddyProduct->get_post_meta($product_id, '_sos_custom_popup', true);
			foreach($popup_meta as $i => $field) {
				if($field['type'] == 'info') {
					$item_meta_data = $order_item->get_meta('custom_teddey_bear_data', true);
					if(!$item_meta_data) {continue;}
					foreach($item_meta_data['field'] as $i => $iRow) {
						foreach($iRow as $j => $jRow) {
							if($field['steptitle'] == $jRow['title'] && $j == 0) {
								if(
									isset($item_meta_data['field'][$i][0]['value'])
									// && isset($item_meta_data['field'][$i][1]['value'])
									// && isset($item_meta_data['field'][$i][2]['value'])
									// && isset($item_meta_data['field'][$i][3]['value'])
								) {
									$item_meta_data['field'][$i][0]['value'] = $_GET['teddyname'];
									global $wpdb;
									$wpdb->update(
										"{$wpdb->prefix}woocommerce_order_itemmeta",
										[
											'meta_value'		=> maybe_serialize($item_meta_data)
										],
										[
											'meta_key'		=> 'custom_teddey_bear_data',
											'order_item_id'	=> $item_id
										],
										['%s'],
										['%s', '%d']
									);
									$json['message'] = __('Successfully Updated your teddy bear name.', 'sospopsprompts');
									$json['message'] = ['order_item_update_success'];
									wp_send_json_success($json, 200);
								}
							}
						}
					}
				}
			}
		}
		wp_send_json_error($json);
	}
	public function suggested_names() {
		$args = ['names' => [], 'hooks' => ['namesuggestionloaded']];
		$filteredKeys = array_keys(SOSPOPSPROJECT_OPTIONS);
		$filteredData = [];
		foreach($filteredKeys as $key) {
			if(strpos($key, 'teddy-name-') !== false) {
				$filteredData[] = SOSPOPSPROJECT_OPTIONS[$key];
			}
		}
		foreach($filteredData as $i => $name) {
			$args['names'][] = $name;
		}
		wp_send_json_success($args);
	}
	public function update_zipcode() {
		$args = ['message' => __('Something went wrong. Failed to update zip code', 'domain'), 'hooks' => ['zipcodeupdatefailed']];
		if(isset($_POST['_zipcode']) && !empty($_POST['_zipcode'])) {
			if(is_user_logged_in()) {
				update_user_meta(get_current_user_id(), '_zip_code', $_POST['_zipcode']);
				$args = ['message' => __('Zip code updated!', 'domain'), 'hooks' => ['zipcodeupdated']];
				wp_send_json_success($args);
			}
		}
		wp_send_json_success($args);
	}
	public function add_order() {
		$json = ['message' => __('Something went wrong!', 'domain'), 'hooks' => []];
		$cart_item_data = [
			'product_id' => 0,
			'quantity' => 1,
			'custom_data' => [
				'title' => 'Custom Item',
			  	'price' => 100
			],
			'billing_first_name' => 'John',
			'billing_last_name' => 'Doe',
			'billing_company' => '',
			'billing_email' => 'johndoe@example.com',
			'billing_phone' => '+1234567890',
			'billing_address_1' => '123 Main Street',
			'billing_address_2' => '',
			'billing_city' => 'Anytown',
			'billing_state' => 'CA',
			'billing_postcode' => '12345',
			'billing_country' => 'US',
			'shipping_first_name' => 'John',
			'shipping_last_name' => 'Doe',
			'shipping_company' => '',
			'shipping_email' => 'johndoe@example.com',
			'shipping_phone' => '+1234567890',
			'shipping_address_1' => '123 Main Street',
			'shipping_address_2' => '',
			'shipping_city' => 'Anytown',
			'shipping_state' => 'CA',
			'shipping_postcode' => '12345',
			'shipping_country' => 'US',
		];
		
		ob_start();
		do_action('woocommerce_before_checkout_form');
		wc_get_template('checkout/form-checkout.php');
		do_action('woocommerce_after_checkout_form');
		$checkout_form = ob_get_clean();
		
		$json['message'] = $checkout_form;
		wp_send_json_success($json);
	}
	public function create_custom_order__($cart_item_data) {
		// Get the cart item data.
		$product_id = $cart_item_data['product_id'];
		$quantity = $cart_item_data['quantity'];
		$custom_data = $cart_item_data['custom_data'];
	  
		// Create the order.
		$order = wc_create_order();
		
		// Add the custom line item to the order.
		// $order_item = $this->create_custom_order_item([
		// 	'name'		=> $custom_data['title'],
		// 	'price'		=> $custom_data['price']
		// ]);
		$order_item = new \WC_Order_Item();
		$order_item->set_product_id(0);
		$order_item->set_name('Custom product');
		$order_item->set_price(20);
		$order->add_item($order_item);
	  
		// Add shipping.
		// $shipping = new WC_Order_Item_Shipping();
		// $shipping->set_method_title('Free shipping');
		// $shipping->set_method_id('free_shipping:1'); // set an existing Shipping method ID
		// $shipping->set_total(0); // optional
		// $order->add_item($shipping);
	  
		// Add billing and shipping addresses.
		$address = [
			'first_name' => $cart_item_data['billing_first_name'],
			'last_name'  => $cart_item_data['billing_last_name'],
			'company'    => $cart_item_data['billing_company'],
			'email'      => $cart_item_data['billing_email'],
			'phone'      => $cart_item_data['billing_phone'],
			'address_1'  => $cart_item_data['billing_address_1'],
			'address_2'  => $cart_item_data['billing_address_2'],
			'city'       => $cart_item_data['billing_city'],
			'state'      => $cart_item_data['billing_state'],
			'postcode'   => $cart_item_data['billing_postcode'],
			'country'    => $cart_item_data['billing_country'],
		];
		
		$order->set_address($address, 'billing');
		$order->set_address($address, 'shipping');
		
		// Add payment method.
		$order->set_payment_method('stripe');
		$order->set_payment_method_title('Credit/Debit card');
		
		// Order status.
		$order->set_status('wc-pending', 'Order is created programmatically');
		if(is_user_logged_in()) {$order->set_customer_id(get_current_user_id());}
	  
		// Calculate and save.
		$order->calculate_totals();
		$order->save();
	}
	public function create_custom_order_item($data) {
		$data = wp_parse_args($data, [
			'product_id' => 0, 'title' => '', 'price' => ''
		]);
		$order_item = new \WC_Order_Item();
		// $order_item->set_product_id(intval($data['product_id']));
		// $order_item->set_name($data['title']);
		// $order_item->set_price($data['price']);
		$order_item->set_product_id(0);
		$order_item->set_name('Custom product');
		$order_item->set_price(20);
		return $order_item;
	}
	// Step 1: Add Custom Cart Item
	public function add_custom_cart_item() {
		// You can retrieve the title and price from your data sent to admin-ajax.php
		$title = $_POST['title'];
		$price = $_POST['price'];

		// Create an array for the custom cart item
		$custom_cart_item_data = array(
			'data' => wc_get_product(), // You can use wc_get_product() to create a generic product.
			'quantity' => 1,
			'price' => $price,
			'custom_title' => $title,
		);

		// Add the custom cart item to the cart
		WC()->cart->add_to_cart($custom_cart_item_data);
	}
	// Step 2: Create an Order
	public function create_custom_order() {
		// Create an order
		$order = wc_create_order();

		// Step 3: Add Custom Cart Items to the Order
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			$order->add_product($cart_item['data'], $cart_item['quantity'], array(
				'subtotal' => $cart_item['line_subtotal'],
				'total' => $cart_item['line_total'],
				'subtotal_tax' => $cart_item['line_subtotal_tax'],
				'total_tax' => $cart_item['line_tax'],
				'title' => $cart_item['custom_title'], // Custom title
			));
		}

		// Step 4: Set Order Details (Billing, Shipping, Customer info, etc.)
		// You can set these details as needed based on your requirements.

		// Step 5: Save the Order
		$order->save();

		// Optionally, clear the cart if needed
		WC()->cart->empty_cart();
	}

}
