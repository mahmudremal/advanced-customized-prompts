<?php
if(!$args) {die('Can\'t call it derectly!');}

// print_r();

$args->template = ($args->contentType == 'html')?'
<h2>You got a new quotation request.</h2> Please find them below
{{print_all_fields}}
<br/>
<p>Possible services Information:</p>
    {{print_invoice_table}}
    {{print_invoice_calculations}}

<p>Thanks and Congratulation on your new connects.<br/>
<b>Best Wishes</b></p>
':'
You got a new quotation request. Please find them below
{{print_all_fields}}

Possible services Information:
    {{print_invoice_table}}
    {{print_invoice_calculations}}

Thanks and Congratulation on your new connects.
Best Wishes
';
