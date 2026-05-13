<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Track Your Order" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<h1 class="cmc-page-title" style="text-align:center;margin-bottom:8px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.05" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="12px"]

<div class="cmc-tracking-form">
<h2>Check Your Order Status</h2>
[woocommerce_order_tracking]
</div>

[gap height="10px"]

<div class="cmc-contact-banner" style="text-align:center;padding:32px 24px;border:1px solid #eee;border-radius:8px;background:rgb(250,250,250)">
<h3>Need help with tracking?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
