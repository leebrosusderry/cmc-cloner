<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Cancellation Hero" padding="32px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--split"]
[row]

[col span="7" span__sm="12"]

<span class="cmc-eyebrow">Policy &middot; [ten-web]</span>
<h1 class="cmc-page-title">{{HEADING}}</h1>
<span class="cmc-accent-bar"></span>

[ux_text class="cmc-lead" font_size="1.1"]
{{INTRO}}
[/ux_text]

<div class="cmc-doc-meta cmc-doc-meta--compact">
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Email:</span> [email-web]</span>
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Phone:</span> [so-dien-thoai]</span>
</div>

[/col]

[col span="5" span__sm="12"]

<div class="cmc-quote-card cmc-quote-card--policy">
<span class="cmc-eyebrow">At a glance</span>
<h3>What this covers</h3>
{{OVERVIEW}}
</div>

[/col]
[/row]
[/section]

[section label="Cancellation Body" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-variant--cards"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Window</span>
<h2>Cancellation Window</h2>
{{CANCEL_WINDOW}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">How to</span>
<h2>How to Cancel an Order</h2>
{{HOW_TO_CANCEL}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">After shipping</span>
<h2>Post-Shipment Cancellations</h2>
{{POST_SHIPMENT}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Refund</span>
<h2>Refund After Cancellation</h2>
{{REFUND_AFTER}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Fees</span>
<h2>Cancellation Fees</h2>
{{CANCEL_FEES}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Modifications</span>
<h2>Order Modifications</h2>
{{ORDER_MODS}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Cancellation Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner cmc-cta-banner--split">
<div class="cmc-cta-banner__lead">
<h2>Need to cancel an order?</h2>
</div>
<div class="cmc-cta-banner__body">
{{CONTACT_BLOCK}}
</div>
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
