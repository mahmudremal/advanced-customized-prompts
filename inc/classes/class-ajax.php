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

		add_action('wp_ajax_sospopsproject/ajax/suggested/categories', [$this, 'suggested_categories'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/ajax/suggested/categories', [$this, 'suggested_categories'], 10, 0);

		add_action('wp_ajax_sospopsproject/ajax/edit/product', [$this, 'edit_product'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/save/product', [$this, 'save_product'], 10, 0);

		add_action('wp_ajax_sospopsproject/ajax/search/popup', [$this, 'search_popup'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/ajax/search/popup', [$this, 'search_popup'], 10, 0);
		
		add_action('wp_ajax_sospopsproject/ajax/search/category', [$this, 'search_category'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/ajax/search/category', [$this, 'search_category'], 10, 0);
		
		add_action('wp_ajax_sospopsproject/ajax/update/orderitem', [$this, 'update_orderitem'], 10, 0);

		add_action('wp_ajax_nopriv_sospopsproject/ajax/suggested/names', [$this, 'suggested_names'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/suggested/names', [$this, 'suggested_names'], 10, 0);

		add_action('wp_ajax_nopriv_sospopsproject/ajax/hero/autocomplete', [$this, 'hero_autocomplete'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/hero/autocomplete', [$this, 'hero_autocomplete'], 10, 0);
		
		add_action('wp_ajax_nopriv_sospopsproject/ajax/add/order', [$this, 'add_order'], 10, 0);
		add_action('wp_ajax_sospopsproject/ajax/add/order', [$this, 'add_order'], 10, 0);
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
		
		// $res = [];for ($i=0; $i < 10; $i++) {$res[] = ['name'=>'Result '.$i, 'value'=>'result_'.$i];}
		wp_send_json_success($res, 200);
	}
	public function hero_autocomplete() {
		$json = ['hooks' => ['hero_autocomplete_failed']];
		if (isset($_POST['query'])) {
			$posts = get_posts([
				's' => $_POST['query']
			]);
			$suggestions = array();
			foreach($posts as $post) {
				$suggestions[] = [
					'label'		=> get_the_title($post),
					'url'		=> get_the_permalink($post),
					'category'	=> get_the_category($post->ID)[0]->name
				];
			}
			$json['hooks'] = ['hero_autocomplete_success'];
			$json['suggestions'] = $suggestions;
			wp_send_json_success($json);
		}
		wp_send_json_error($json);
	}
	public function search_product() {
		global $wpdb;global  $woocommerce;global $SoS_Product;
		// check_ajax_referer('sospopsproject/sospopupaddon/verify/nonce', '_nonce', true);
		$dataset = $request = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode(isset($_POST['dataset'])?$_POST['dataset']:'{}'))), true);
		$json = ['hooks' => ['error_getting_service'], 'message' => __('Something error happening. Failed to load service data.', 'domain')];
		$_post = get_post($dataset['product_id']);
		if ($_post && !is_wp_error($_post)) {
			$service = [
				'id'		=> $_post->ID,
				'type'		=> $_post->post_type,
				'title'		=> $_post->post_title,
				'name'		=> $_post->post_title,
				'excerpt'	=> $_post->post_excerpt,
				'slug'		=> get_the_permalink($_post),
				'link'		=> get_the_permalink($_post),
				'price'		=> get_post_meta($_post->ID, 'prices', true),
				'currency'	=> get_post_meta($_post->ID, 'currency', true),
				'priceHtml'	=> get_post_meta($_post->ID, 'currency', true),
				'duration'	=> get_post_meta($_post->ID, 'duration', true),
				'priceType'	=> get_post_meta($_post->ID, 'pricing_type', true),
			];
			$zip_code = isset($_POST['zip_code'])?$_POST['zip_code']:false;
			if (
				($zip_code && !empty($zip_code))
					|| 
				(is_user_logged_in() && $zip_code = get_user_meta(get_current_user_id(), '_zip_code', true))
			) {
				$terms = wp_get_post_terms($_post->ID, 'area', ['fields' => 'names']);
				if (!has_term($zip_code, 'area', $_post)) {
					$service['not_in_area'] = true;
					$service['not_in_area_message'] = sprintf(
						__('This Service not available in your location %s while this service only available on these following locations %s.', 'domain'),
						'<b>' . $zip_code . '</b>',
						'<b>' . implode('</b>, <b>', $terms) . '</b>'
					);
				}
			}
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
					...$service,
					'price'		=> floatval($service['price']),
					'is_parent' => false,
					'toast'		=> false, // '<strong>' . count($requested) . '</strong> people requested this service in the last 10 minutes!',
					'thumbnail'	=> ['1x' => get_the_post_thumbnail($_post), '2x' => get_the_post_thumbnail($_post, 'full')],
					'custom_fields' => $SoS_Product->get_post_meta($dataset['product_id'], '_sos_custom_popup', true),
					'existing_data'	=> is_user_logged_in()?get_user_meta(get_current_user_id(), '__sos_userdata', true):[],
					'service_variations' => get_post_meta($_post->ID, '_sos_custom_services', true)
				],
			];
			$json['product']['custom_fields'] = ($json['product']['custom_fields'] && !empty($json['product']['custom_fields']))?(array)$json['product']['custom_fields']:[];
			$json['product']['existing_data'] = !empty($json['product']['existing_data'])?$json['product']['existing_data']:[];
			foreach($json['product']['custom_fields'] as $i => $_prod) {
				$json['product']['custom_fields'][$i]['headerbgurl'] = ($_prod['headerbg']=='')?false:wp_get_attachment_url($_prod['headerbg']);
				if (isset($_prod['options'])) {
					$_prod['options'] = (!empty($_prod['options']))?(array)$_prod['options']:[];
					foreach($_prod['options'] as $j => $option) {
						if (isset($option['image']) && !empty($option['image'])) {
							$json['product']['custom_fields'][$i]['options'][$j]['imageUrl'] = wp_get_attachment_url($option['image']);
						}
						if (isset($option['thumb']) && !empty($option['thumb'])) {
							$json['product']['custom_fields'][$i]['groups'][$k]['options'][$l]['thumbUrl'] = wp_get_attachment_url($option['thumb']);
						}
					}
				}
				if (isset($_prod['groups'])) {
					foreach($_prod['groups'] as $k => $group) {
						if (isset($group['options'])) {
							foreach($group['options'] as $l => $option) {
								if (isset($option['image']) && !empty($option['image'])) {
									$json['product']['custom_fields'][$i]['groups'][$k]['options'][$l]['imageUrl'] = wp_get_attachment_url($option['image']);
								}
								if (isset($option['thumb']) && !empty($option['thumb'])) {
									$json['product']['custom_fields'][$i]['groups'][$k]['options'][$l]['thumbUrl'] = wp_get_attachment_url($option['thumb']);
								}
							}
						}
					}
				}
			}
			wp_send_json_success($json);
		} else {
			$json['message'] = __('Service not found. It seems service stagged or removed.', 'domain');
		}
		wp_send_json_error($json);
	}
	public function submit_popup() {
		// check_ajax_referer('sospopsproject/sospopupaddon/verify/nonce', '_nonce', true);
		$request = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['dataset']))), true);
		$json = [
			'hooks' => ['popup_submitting_done'],
			'message' => __('Popup submitted successfully. Hold on unil you\'re redirecting to searh results.', 'sospopsprompts')
		];
		
		if (isset($request['product']) && !empty($request['product'])) {
			$request['product'] = (int) $request['product'];
			$term_link = get_term_link($request['product'], 'listing_product');
			if (!$term_link || is_wp_error($term_link)) {$term_link = false;}
			$json['redirectedTo'] = $term_link;
		}
		if (isset($request['field']["9002"]) && !is_user_logged_in()) {
			$user_email = $request['field']["9002"];
			$user_name = $request['field']["9003"];
			$user_pass = $request['field']["9004"];
			$user = get_user_by_email($user_email);
			if ($user) {
				$user_id = $user->ID;
				wp_set_current_user($user_id, $user->user_login);
				wp_set_auth_cookie($user_id);
				do_action('wp_login', $user->user_login, $user);
			} else {
				$user_id = username_exists($user_name);
				if (!$user_id && false == email_exists($user_email)) {
					$user_id = wp_create_user($user_name, $user_pass, $user_email);
					if (!is_wp_error($user_id)) {
						$user = get_user_by('id', $user_id);
						wp_set_current_user($user_id, $user->user_login);
						wp_set_auth_cookie($user_id);
						do_action('wp_login', $user->user_login, $user);
					}
					
				} else {
					$random_password = __('User already exists.  Password inherited.', 'domain');
				}
				
			}
		}
		
		wp_send_json_success($json, 200);
	}
	public function search_popup() {
		global $wpdb;
		$json = ['hooks' => ['popup_searching_done']];
		// check_ajax_referer('sospopsproject/sospopupaddon/verify/nonce', '_nonce', true);
		$request = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', stripslashes(html_entity_decode($_POST['formdata']))), true);
		
		wp_send_json_error($json);
	}
	public function search_category() {
		global $SoS_Service;global $wpdb;$json = ['hooks' => ['categorylistsfalied'], 'parent' => []];
		if (isset($_POST['category_id'])) {
			$category = get_term($_POST['category_id']);
			if ($category && !is_wp_error($category)) {
				$catChilds = get_term_children($category->term_id, $SoS_Service->taxonomy);
				if ($catChilds && !is_wp_error($catChilds)) {
					$category->childrens = [];
					foreach($catChilds as $term_id) {
						$term = get_term($term_id);
						$image_meta = get_term_meta($term_id, 'texonomy_featured_image', true);
						$image_url = ($image_meta && !is_wp_error($image_meta) && !empty($image_meta))?wp_get_attachment_thumb_url($image_meta):false;
						$category->childrens[] = [
							'term_id'	=> $term->term_id,
							'name'		=> $term->name,
							'count'		=> $term->count,
							'parent'	=> $term->parent,
							'url'		=> get_term_link($term),
							'services'	=> $this->get_term_posts($term),
							'thumbnail'	=> $image_url
						];
					}
				}
				$category->services = $this->get_term_posts(get_term($category->term_id));
				$json['parent'] = $category;
				$json['hooks'] = ['categorylistsloaded'];
				wp_send_json_success($json);
			}
		}
		$json['message'] = __('Failed to load category information. Instead of, we\'re redirecting to you category screen.', 'sospopsprompts');
		wp_send_json_error($json);
	}
	public function get_term_posts($term) {
		global $SoS_Service;$posts = [];
		$args = [
			'order'				=> strtoupper($_REQUEST['order']??'desc'),
			'post_type' 		=> $SoS_Service->post_type,
			's'					=> $_REQUEST['search']??false,
			// 'numberposts'		=> $_REQUEST['per_page']??12,
			'posts_per_page'	=> $_REQUEST['per_page']??12,
			'orderby'			=> 'menu_order title',
			// 'nopaging'		=> true,
			'tax_query' 		=> [
				[
					'taxonomy'	=> $SoS_Service->taxonomy,
					'field'		=> 'term_id',
					'terms'		=> [$term->term_id]
				]
			]
		];
		// print_r($args);wp_die();
		$queries = new \WP_Query($args);
		if ($queries->have_posts()) {
			while ($queries->have_posts()) {
				$queries->the_post();
				$posts[] = [
					'title'		=> get_the_title(),
					'url'		=> get_the_permalink(),
					'thumbnail'	=> get_the_post_thumbnail_url(get_the_ID(), 'thumbnail')
				];
			}
			wp_reset_postdata();
			return $posts;
		}
		return false;
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
		global $SoS_Product;$json = [];
		$json['product'] = $SoS_Product->get_post_meta($_POST['product_id'], '_sos_custom_popup', true);
		$json['hooks'] = ['gotproductpopupresult'];
		$json['info'] = ['prod_title' => get_the_title($_POST['product_id'])];
		$json['product'] = (isset($json['product']) && !empty($json['product']))?(array)$json['product']:[];
		// $json['product'] = [];
		// wp_send_json_success($json, 200);
		foreach($json['product'] as $i => $_prod) {
			$json['product'][$i]['headerbgurl'] = ($_prod['headerbg']=='')?false:wp_get_attachment_url($_prod['headerbg']);
			if (isset($_prod['options'])) {
				$_prod['options'] = (!empty($_prod['options']))?(array)$_prod['options']:[];
				foreach($_prod['options'] as $j => $option) {
					if (isset($option['image']) && !empty($option['image'])) {
						$json['product'][$i]['options'][$j]['imageUrl'] = wp_get_attachment_url($option['image']);
					}
				}
			}
			if (isset($_prod['groups'])) {
				foreach($_prod['groups'] as $k => $group) {
					if (isset($group['options'])) {
						foreach($group['options'] as $l => $option) {
							if (isset($option['image']) && !empty($option['image'])) {
								$json['product'][$i]['groups'][$k]['options'][$l]['imageUrl'] = wp_get_attachment_url($option['image']);
							}
							if (isset($option['thumb']) && !empty($option['thumb'])) {
								$json['product'][$i]['groups'][$k]['options'][$l]['thumbUrl'] = wp_get_attachment_url($option['thumb']);
							}
						}
					}
				}
			}
		}
		wp_send_json_success($json, 200);
	}
	public function merge_customfields($fields) {
		$fieldID = 9000;
		if (!$fields || $fields == "") {return $fields;}
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
		if (!is_user_logged_in()) {
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
		global $SoS_Product;
		$json = ['hooks' => ['order_item_update_failed'], 'message' => __('Something went wrong. Please review your request again.', 'sospopsprompts')];
		if (!isset($_GET['order_id']) || empty($_GET['order_id']) || !isset($_GET['item_id']) || empty($_GET['item_id']) || !isset($_GET['teddyname']) || empty($_GET['teddyname'])) {
			wp_send_json_error($json);
		}

		$order_id = $_GET['order_id'];
		$item_id = $_GET['item_id'];
		$order = wc_get_order($order_id);
		foreach($order->get_items() as $order_item_id => $order_item) {
			if ($order_item_id != $item_id) {continue;}
			$product_id = $order_item->get_product_id();
			$popup_meta = $SoS_Product->get_post_meta($product_id, '_sos_custom_popup', true);
			foreach($popup_meta as $i => $field) {
				if ($field['type'] == 'info') {
					$item_meta_data = $order_item->get_meta('custom_teddey_bear_data', true);
					if (!$item_meta_data) {continue;}
					foreach($item_meta_data['field'] as $i => $iRow) {
						foreach($iRow as $j => $jRow) {
							if ($field['steptitle'] == $jRow['title'] && $j == 0) {
								if (
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
			if (strpos($key, 'teddy-name-') !== false) {
				$filteredData[] = SOSPOPSPROJECT_OPTIONS[$key];
			}
		}
		foreach($filteredData as $i => $name) {
			$args['names'][] = $name;
		}
		wp_send_json_success($args);
	}
	public function add_order() {
		$json = ['message' => __('Error happening while trying to add information to cart.', 'domain')];
		$product_id = 2735;$quantity = 1;
		
		if (class_exists('WooCommerce')) {
			$cart = WC()->cart;
			$cart->add_to_cart($product_id, $quantity);
			$json['redirectTo'] = wc_get_checkout_url();
			$json['message'] = __('Successfully added your ninformation & in a while, you\'ll be redirected to order confirmation screen. Please hold on a couple of seconds.', 'domain');
			$json['hooks'] = ['addedToCartSuccess'];
			wp_send_json_success($json);
		}
		wp_send_json_error($json);
	}
	public function suggested_categories() {
		global $SoS_Service;
		$json = ['hooks' => ['suggested_categories_failed']];
		$terms = get_terms([
			'number'		=> $_REQUEST['per_page']??12,
			'search'		=> $_REQUEST['search']??'',
			'taxonomy'   	=> $SoS_Service->taxonomy,
			'hide_empty' 	=> true,
		]);
		if ($terms && !is_wp_error($terms)) {
			$terms_data = [];
			foreach($terms as $term) {
				$posts = $this->get_term_posts($term);
				$options = [];
				foreach($posts as $post) {
					$options[] = [
						'text'		=> esc_html($this->stripeSpecialChars($post['title'])),
						'value'		=> $post['url'],
					];
				}
				$term_data = [
					'closable'		=> 'close',
					'label'			=> $term->name,
					'options'		=> $options
				];
				$terms_data[] = $term_data;
			}
			$json['terms'] = $terms_data;
			$json['hooks'] = ['suggested_categories_success'];
			wp_send_json_success($json);
		}
		wp_send_json_error($json);
	}

	public function stripeSpecialChars($string) {
		return str_replace([
			'&#8211;'
		], [
			'–'
		], $string);
	}
}
