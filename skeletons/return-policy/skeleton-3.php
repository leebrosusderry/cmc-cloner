<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Return Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--soft cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Policy &middot; [ten-web]</span>
<h1 class="cmc-page-title" style="text-align:center;margin-bottom:12px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.15" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="12px"]

<div class="cmc-doc-meta">
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Company:</span> [ten-doanh-nghiep]</span>
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Email:</span> [email-web]</span>
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Phone:</span> [so-dien-thoai]</span>
</div>

</div>

[/col]
[/row]
[/section]

[section label="Return Overview" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-offerings-panel">
<span class="cmc-eyebrow">At a glance</span>
<h2>Overview</h2>
{{OVERVIEW}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Return Grid" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head cmc-values-head--center">
<span class="cmc-eyebrow">Details</span>
<h2>How returns work</h2>
</div>

<div class="cmc-policy-grid">

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">01</div>
<h3>Return Eligibility</h3>
{{ELIGIBILITY}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">02</div>
<h3>Return Window</h3>
{{RETURN_WINDOW}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">03</div>
<h3>Non-Returnable Items</h3>
{{NON_RETURNABLE}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">04</div>
<h3>Exchange Option</h3>
{{EXCHANGE}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">05</div>
<h3>Return Method</h3>
{{RETURN_SHIPPING}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">06</div>
<h3>How to Initiate a Return</h3>
{{HOW_TO_RETURN}}
</div>

<div class="cmc-policy-card cmc-policy-card--wide">
<div class="cmc-policy-card__num">07</div>
<h3>Refund Information</h3>
{{REFUND_LINK}}
</div>

</div>

</div>

[/col]
[/row]
[/section]

[section label="Return Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner">
<h2>Need help with a return?</h2>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
