<?php
if(!$args) {die('Can\'t call it derectly!');}

$args->template = ($args->contentType == 'html')?'
<h2>You got a new quotation request.</h2> Please find them below
{{print_all_fields}}
<br/>
<p>Possible services Information:</p>
    {{print_invoice_table}}
    {{print_invoice_calculations}}

<p>Find the details:</p>
    {{print_order_link}}
    
<p>Thanks and Congratulation on your new connects.<br/>
<b>Best Wishes</b></p>
':'
You got a new quotation request. Please find them below
{{print_all_fields}}

Possible services Information:
    {{print_invoice_table}}
    {{print_invoice_calculations}}

Find the details:
    {{print_order_link}}
    
Thanks and Congratulation on your new connects.
Best Wishes
';


$template = apply_filters('sos/project/system/getoption', 'email-template-quota', false);
if ($template && !empty(trim($template))) {
    $args->template = $template;
}