<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Track Your Order" padding="28px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-variant--cards"]
[row]
[col span="12"]

<div class="cmc-container">

<h1 class="cmc-page-title">{{HEADING}}</h1>
<span class="cmc-accent-bar"></span>

[ux_text font_size="1.05" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[gap height="10px"]

<div class="cmc-card-block cmc-tracking-form">
<h2>Check Your Order Status</h2>
[woocommerce_order_tracking]
</div>

<div class="cmc-contact-banner" style="text-align:center;padding:28px 24px;border:1px solid #eee;border-radius:8px;background:rgb(250,250,250)">
<h3>Need tracking help?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
