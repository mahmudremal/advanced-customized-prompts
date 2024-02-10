<h2><?php echo esc_html(__('Payments still pending', 'domain')); ?></h2>
<?php
$_list = [];
$payment_id = get_post_meta($order_id, 'payment_id', true);
if ($payment_id && !empty($payment_id)) {
    $_list[] = [
        'label'     => __('Payment ID:', 'domain'),
        'value'     => $payment_id
    ];
}
$payment_url = get_post_meta($order_id, 'payment_url', true);
if ($payment_url && !empty($payment_url)) {
    $_list[] = [
        'label'     => __('Payment Link:', 'domain'),
        'value'     => __('Goto Link', 'domain'),
        'link'      => $payment_url
    ];
}
?>
<ul>
<?php
foreach($_list as $_row) {
    $isLink = isset($_row['link']);
    ?>
    <li>
        <b><?php echo esc_html($_row['label']); ?> </b>
        <<?php echo esc_attr($isLink?'a':'span') ?> href="<?php echo esc_attr($isLink?$_row['link']:'#') ?>" target="<?php echo esc_attr($isLink?'_blank':'_self') ?>"><?php echo esc_html($_row['value']); ?></<?php echo esc_attr($isLink?'a':'span') ?> href="<?php echo esc_attr($isLink?$row['link']:'#') ?>">
    </li>
    <?php
}
?>
</ul>