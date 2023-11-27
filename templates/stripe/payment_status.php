<?php
/**
 * Checkout video clip shortner template.
 * 
 * @package SOSPOPSPROJECT
 */
global $SoS_Shortcodes;
$session_id = get_query_var('session_id');
$payment_status = get_query_var('payment_status');
$result = apply_filters('sos/project/payment/stripe/handlesuccess', $session_id);
// if($result['payment_status'] == 'paid') {echo 'Success';} else {print_r($result);}
if(isset($result['customer_details']) && isset($result['customer_details']['email']) && !empty($result['customer_details']['email'])) {
  $userInfo = get_user_by('email', $result['customer_details']['email']);
}
if(! isset($userInfo) || ! $userInfo) {
  $userInfo = get_user_by('id', get_current_user_id());
}
$userMeta = array_map(function($a){ return $a[0]; }, (array) get_user_meta($userInfo->ID));
$userInfo = (object) wp_parse_args($userInfo, ['meta' => (object) wp_parse_args($userMeta, apply_filters('sos/project/usermeta/defaults', (array) $userMeta)) ]);


get_header();

// print_r([$result]);
$SoS_Shortcodes->payment_stripe_info = $result;

if(isset($result['payment_status'])) {
  switch ($result['payment_status']) {
    case 'paid':
      /**
       * Payment succussfully made.
       */
      echo do_shortcode('[elementor-template id="1528"]');
      break;
    case 'unpaid':
      /**
       * Payment Still UnPaid made.
       */
      echo do_shortcode('[elementor-template id="1544"]');
      break;
    case 'canceled':
      /**
       * Payment Still UnPaid made.
       */
      echo do_shortcode('[elementor-template id="1547"]');
      break;
    default:
      /**
       * Another payment status
       */
      echo do_shortcode('[elementor-template id="1533"]');
      break;
  }
} else {
  /**
   * Failed your payment.
   */
  echo do_shortcode('[elementor-template id="1533"]');
}

get_footer();