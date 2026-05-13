<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Terms Hero" padding="32px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--split"]
[row]

[col span="7" span__sm="12"]

<span class="cmc-eyebrow">Legal &middot; [ten-web]</span>
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
<span class="cmc-eyebrow">Start here</span>
<h3>Acceptance of terms</h3>
{{ACCEPTANCE}}
</div>

[/col]
[/row]
[/section]

[section label="Terms Body" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-variant--cards"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Who can use</span>
<h2>Eligibility</h2>
{{ELIGIBILITY}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Your account</span>
<h2>Account Responsibilities</h2>
{{ACCOUNT}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Orders</span>
<h2>Orders &amp; Acceptance</h2>
{{ORDERS}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Payments</span>
<h2>Pricing &amp; Payment</h2>
{{PRICING_PAYMENT}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Delivery</span>
<h2>Shipping &amp; Risk of Loss</h2>
{{SHIPPING_RISK}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">After purchase</span>
<h2>Returns &amp; Refunds</h2>
{{RETURNS_REFUNDS}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Ownership</span>
<h2>Intellectual Property</h2>
{{INTELLECTUAL_PROPERTY}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Rules</span>
<h2>User Conduct &amp; Prohibited Uses</h2>
{{USER_CONDUCT}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">As-is</span>
<h2>Disclaimers</h2>
{{DISCLAIMERS}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Liability</span>
<h2>Limitation of Liability</h2>
{{LIABILITY}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Hold harmless</span>
<h2>Indemnification</h2>
{{INDEMNIFICATION}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Jurisdiction</span>
<h2>Governing Law</h2>
{{GOVERNING_LAW}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Updates</span>
<h2>Changes to Terms</h2>
{{CHANGES}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Terms Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner cmc-cta-banner--split">
<div class="cmc-cta-banner__lead">
<h2>Questions about these terms?</h2>
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
