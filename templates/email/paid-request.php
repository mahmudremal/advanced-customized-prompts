<?php
if(!$args) {die('Can\'t call it derectly!');}

$template = ($args->contentType == 'html')?'
<h2>You got a new paid request.</h2> Please find them below
{{print_all_fields}}
<br/>
<p>Payment informations:</p>
    {{print_invoice_table}}
    {{print_invoice_calculations}}
    {{print_payment_info}}

<p>Thanks and Congratulation on your new project.<br/>
<b>Best Wishes</b></p>
':'
You got a new paid request. Please find them below
{{print_all_fields}}

Payment informations:
    {{print_invoice_table}}
    {{print_invoice_calculations}}
    {{print_payment_info}}


Thanks and Congratulation on your new project.
Best Wishes
';

$template = apply_filters('sos/project/system/getoption', 'email-template-paid', false);
if ($template && !empty(trim($template))) {
    $args->template = $template;
}