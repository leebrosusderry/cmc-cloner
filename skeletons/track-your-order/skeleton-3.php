<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Track Your Order" padding="28px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-variant--bands"]
[row]
[col span="12"]

<div class="cmc-container">

<h1 class="cmc-page-title" style="text-align:center;margin-bottom:8px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.08" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="10px"]

<div class="cmc-band cmc-tracking-form">
<h2>Check Your Order Status</h2>
[woocommerce_order_tracking]
</div>

<div class="cmc-band cmc-band--soft">
<h3>Need help with tracking?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
