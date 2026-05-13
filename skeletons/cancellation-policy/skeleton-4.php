<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Cancellation Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--editorial"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<span class="cmc-eyebrow">Policy &middot; [ten-web]</span>
<h1 class="cmc-page-title cmc-page-title--editorial">{{HEADING}}</h1>

[ux_text class="cmc-lead cmc-lead--editorial"]
{{INTRO}}
[/ux_text]

<div class="cmc-editorial-meta">
<span class="cmc-editorial-meta__item">[ten-doanh-nghiep]</span>
<span class="cmc-editorial-meta__item">[email-web]</span>
<span class="cmc-editorial-meta__item">[so-dien-thoai]</span>
</div>

[divider width="120px" margin="14px" align="left"]

<div class="cmc-editorial-pullquote">
{{OVERVIEW}}
</div>

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="01">Cancellation Window</h2>
{{CANCEL_WINDOW}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="02">How to Cancel an Order</h2>
{{HOW_TO_CANCEL}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="03">Post-Shipment Cancellations</h2>
{{POST_SHIPMENT}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="04">Refund After Cancellation</h2>
{{REFUND_AFTER}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="05">Cancellation Fees</h2>
{{CANCEL_FEES}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="06">Order Modifications</h2>
{{ORDER_MODS}}

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Need to cancel an order?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
