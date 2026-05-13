<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Track Your Order" padding="36px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--minimal"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<h1 class="cmc-page-title">{{HEADING}}</h1>

[ux_text class="cmc-lead"]
{{INTRO}}
[/ux_text]

<div class="cmc-tracking-form">
<h2>Check Your Order Status</h2>
[woocommerce_order_tracking]
</div>

<hr>

<h2>Contact</h2>
{{CONTACT_BLOCK}}

</div>

[/col]
[/row]
[/section]
SKELETON;
