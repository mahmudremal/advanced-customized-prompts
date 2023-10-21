<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */

namespace SOSPOPSPROJECT\inc;

use SOSPOPSPROJECT\inc\Traits\Singleton;
class Myaccount {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	public function setup_hooks() {
		add_action('woocommerce_account_dashboard', [$this, 'woocommerce_account_dashboard'], 10, 0);
		// add_filter('woocommerce_my_account_edit_address_title', [$this, 'woocommerce_my_account_edit_address_title'], 10, 2);
		add_filter('woocommerce_after_edit_address_form_billing', [$this, 'woocommerce_after_edit_address_form'], 10, 0);
		add_filter('woocommerce_after_edit_address_form_shipping', [$this, 'woocommerce_after_edit_address_form'], 10, 0);
		
		add_action('wp_ajax_sospopsproject/ajax/fields/names', [$this, 'custom_fields_names'], 10, 0);
		add_action('wp_ajax_nopriv_sospopsproject/ajax/fields/names', [$this, 'custom_fields_names'], 10, 0);
	}
	public function woocommerce_account_dashboard() {
		// $user = wp_get_current_user();
		$user_meta = get_user_meta(get_current_user_id(), '__sos_userdata', true);
		if(!$user_meta || !is_array($user_meta)) {$user_meta = [];}
		if(count($user_meta) <= 0) {return;}
		// print_r(
		// 	get_user_meta(get_current_user_id(), null, true)
		// );
		?>
		<div class="woomyac">
			<table class="woomyac__table">
				<?php foreach($user_meta as $row) : ?>
					<?php if(empty(trim($row['value']))) {continue;} ?>
					<tr>
						<th align="right" width="50%"><?php echo esc_html($row['title']); ?></th>
						<td align="left" width="50%"><?php echo esc_html($row['value']); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	}
	/**
	 * Load Class is type
	 * billing || shipping
	 */
	public function woocommerce_my_account_edit_address_title($page_title, $load_address) {
		// print_r([$page_title, $load_address]);
		return $page_title;
	}
	public function woocommerce_after_edit_address_form() {
	}
	public function custom_fields_names() {
		$fields_names = [
			[
				"First name * (Billing)",
				"billing_first_name"
			],
			[
				"Last name * (Billing)",
				"billing_last_name"
			],
			[
				"Company name (optional) (Billing)",
				"billing_company"
			],
			[
				"Country/Region * (Billing)",
				"billing_country"
			],
			[
				"Street address * (Billing)",
				"billing_address_1"
			],
			[
				"Flat, suite, unit, etc. \n(optional) (Billing)",
				"billing_address_2"
			],
			[
				"Town / City * (Billing)",
				"billing_city"
			],
			[
				"District * (Billing)",
				"billing_state"
			],
			[
				"Postcode / ZIP (optional) (Billing)",
				"billing_postcode"
			],
			[
				"Phone * (Billing)",
				"billing_phone"
			],
			[
				"Email address * (Billing)",
				"billing_email"
			],
			[
				"First name * (Shipping)",
				"shipping_first_name"
			],
			[
				"Last name * (Shipping)",
				"shipping_last_name"
			],
			[
				"Company name (optional) (Shipping)",
				"shipping_company"
			],
			[
				"Country/Region * (Shipping)",
				"shipping_country"
			],
			[
				"Street address * (Shipping)",
				"shipping_address_1"
			],
			[
				"Flat, suite, unit, etc. \n(optional) (Shipping)",
				"shipping_address_2"
			],
			[
				"Town / City * (Shipping)",
				"shipping_city"
			],
			[
				"State / County * (Shipping)",
				"shipping_state"
			],
			[
				"Postcode / ZIP * (Shipping)",
				"shipping_postcode"
			],
			[
				"Phone (optional) (Shipping)",
				"shipping_phone"
			],
		];
		wp_send_json_success([
			'hooks'			=> ['fields_names_loaded'],
			'fields_names'	=> $fields_names
		]);
	}
	
}
