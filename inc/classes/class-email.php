<?php
/**
 * LoadmorePosts
 *
 * @package SOSPopsProject
 */

namespace SOSPOPSPROJECT\inc;

use SOSPOPSPROJECT\inc\Traits\Singleton;
class Email {
	use Singleton;
	protected function __construct() {
		$this->setup_hooks();
	}
	public function setup_hooks() {
		add_filter('sos_send_email', [$this, 'sos_send_email'], 10, 2);
	}
	public function sos_send_email($content, $args) {
		$args = (object) wp_parse_args($args, [
			'dataset'		=> [],
			'charges'		=> [],
			'request_type'	=> 'get_quotation',
			'to'			=> apply_filters('sos/project/system/getoption', 'email-reciever', ''),
			'subject'		=> apply_filters('sos/project/system/getoption', 'email-subject', 'A new request has been sent from the customer below.'),
			'template'		=> apply_filters('sos/project/system/getoption', 'email-template', ''),
			'message'		=> '',
			'attachments'	=> [],
			'contentType'	=> 'html', // plain
			'headers'		=> ['Content-Type: text/html; charset=UTF-8']
		]);
		if (empty($args->to) || strpos($args->to, '@') === false) {
			throw new \ErrorException(__('Recipients not found.', 'sossprompts'));
		}
		/**
		 * Generating template from prebuild template
		 */
		$args->message = $args->template = $this->generate_template($args);

		foreach($args->attachments as $i => $path) {
			if (!$path || empty($path) || !file_exists($path) || is_dir($path)) {
				unset($args->attachments[$i]);
			}
		}
		if (empty($args->subject)) {
			$args->subject = __('A new quotation request sent by...', 'domain');
		}
		// if (count($args->attachments) <= 0) {
		// 	throw new \ErrorException(__('Certificate not found or this could happens probably if path not matching or permission issue.', 'sossprompts'));
		// }
		$email_sent = wp_mail($args->to, $args->subject, $args->message, $args->headers, $args->attachments);
		if ($email_sent) {
			return $email_sent;
		} else {
			throw new \ErrorException(__('Something went wrong. Email not sent.', 'sossprompts'));
		}
		return false;
	}
	private function generate_template($args) {
		$isHTML = ($args->contentType == 'html');
		if ($args->request_type == 'get_quotation') {
			include SOSPOPSPROJECT_DIR_PATH . '/templates/email/quotation-request.php';
		} else {
			include SOSPOPSPROJECT_DIR_PATH . '/templates/email/paid-request.php';
		}
		/**
		 * Invoice Table area.
		 */
		$all_fields = ($isHTML)?'<table border="0">':'';
		foreach($args->dataset as $_i => $_row) {
			if ($_row->value == __('Select Your Service', 'domain')) {
				$_row->value = '';
			}
			if ($args->contentType == 'html') {
				$all_fields .= '
				<tr data-key="' . $_row->key . '">
					<th>' . $_row->title . '</th>
					<td>:</td>
					<td>' . $_row->value . '</td>
				</tr>';
			} else {
				$all_fields .= "$_row->title : $_row->value \n";
			}
		}
		$all_fields .= ($isHTML)?'</table>':'';
		/**
		 * Invoice Table area.
		 */
		$invoice_table = ($isHTML)?'<table border="0">':'';
		foreach($args->charges as $_key => $_value) {
			if ($_value == __('Select Your Service', 'domain')) {
				$_value = '';
			}
			if ($args->contentType == 'html') {
				$invoice_table .= '
				<tr>
					<th>' . $_key . '</th>
					<td>:</td>
					<td>' . $_value . '</td>
				</tr>';
			} else {
				$invoice_table .= "$_key : $_value \n";
			}
		}
		$invoice_table .= ($isHTML)?'</table>':'';
		/**
		 * Invoice Table area.
		 */
		$calculated = $this->get_calculation($args);
		if ($args->contentType == 'html') {
			$invoice_calculations = '
			<table border="0">
				<tr><th>' . esc_html__('Subtotal', 'domain') . '</th><td>:</td><td>' . $calculated->subtotal . '</td></tr>
				<tr><th>' . esc_html__('Tax', 'domain') . '</th><td>:</td><td>' . $calculated->tax . '</td></tr>
				<tr><th>' . esc_html__('Total', 'domain') . '</th><td>:</td><td>'. $calculated->total . '</td></tr>
			</table>
			';
		} else {
			$invoice_calculations = "Subtotal: $calculated->subtotal \nTax: $calculated->tax\nTotal: $calculated->total";
		}
		$payment_info = '';
		// Payment info table goes here.
		if ($args->request_type == 'get_quotation') {
			$payment_info = __('No payments made yet.', 'domain');
		} else {
			$payment_info = __('Payment has been done before request sent. Transection ID# 12345678', 'domain');
		}
		$allStrings = [
			'{{print_all_fields}}'              => $all_fields,
			'{{print_invoice_table}}'           => $invoice_table,
			'{{print_invoice_calculations}}'    => $invoice_calculations,
			'{{print_payment_info}}'            => $payment_info,
		];
		$args->template = str_replace(array_keys($allStrings), array_values($allStrings), $args->template);
		return $args->template;
		
	}
	public function get_calculation($args) {
		$args = (object) $args;
		$calculated = (object) ['total' => 0, 'subtotal' => 0, 'tax' => 0];
		$basePrice = get_post_meta($args->product_id, 'prices', true);
		if ($basePrice && is_numeric($basePrice) && (float) $basePrice > 0) {
			$calculated->subtotal += (float) $basePrice;
		}
		foreach($args->charges as $_i => $_row) {
			$_row = (object) $_row;
			if ($_row->price && is_numeric($_row->price) && (float) $_row->price > 0) {
				$calculated->subtotal += (float) $_row->price;
				// $calculated->tax += (((float) $_value / 100) * 12.5); // 12.5 is tax percentage. For example
			}
		}
		$calculated->total = ($calculated->subtotal + $calculated->tax);
		return $calculated;
	}
}
