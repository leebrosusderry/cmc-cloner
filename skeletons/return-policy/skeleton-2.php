<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Return Hero" padding="32px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--split"]
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

[section label="Return Body" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-variant--cards"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Who qualifies</span>
<h2>Return Eligibility</h2>
{{ELIGIBILITY}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Window</span>
<h2>Return Window</h2>
{{RETURN_WINDOW}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Exclusions</span>
<h2>Non-Returnable Items</h2>
{{NON_RETURNABLE}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Exchange</span>
<h2>Exchange Option</h2>
{{EXCHANGE}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Method</span>
<h2>Return Method</h2>
{{RETURN_SHIPPING}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">How to start</span>
<h2>How to Initiate a Return</h2>
{{HOW_TO_RETURN}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Refund</span>
<h2>Refund Information</h2>
{{REFUND_LINK}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Return Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner cmc-cta-banner--split">
<div class="cmc-cta-banner__lead">
<h2>Need help with a return?</h2>
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
