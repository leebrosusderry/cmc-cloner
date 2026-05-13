<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Shipping Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--editorial"]
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

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="01">Processing Time</h2>
{{PROCESSING_TIME}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="02">Shipping Methods &amp; Carriers</h2>
{{METHODS_CARRIERS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="03">Estimated Delivery Time</h2>
{{DELIVERY_TIME}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="04">Shipping Costs</h2>
{{SHIPPING_COSTS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="05">Order Tracking</h2>
{{ORDER_TRACKING}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="06">Delays, Lost, or Damaged Shipments</h2>
{{DELAYS_ISSUES}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="07">Address Changes</h2>
{{ADDRESS_CHANGES}}

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Questions about your order?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
