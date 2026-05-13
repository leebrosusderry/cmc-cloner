<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Terms Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--soft cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Legal &middot; [ten-web]</span>
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

[section label="Terms Acceptance" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-offerings-panel">
<span class="cmc-eyebrow">Start here</span>
<h2>Acceptance of Terms</h2>
{{ACCEPTANCE}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Terms Grid" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head cmc-values-head--center">
<span class="cmc-eyebrow">Details</span>
<h2>Terms explained</h2>
</div>

<div class="cmc-policy-grid">

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">01</div>
<h3>Eligibility</h3>
{{ELIGIBILITY}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">02</div>
<h3>Account Responsibilities</h3>
{{ACCOUNT}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">03</div>
<h3>Orders &amp; Acceptance</h3>
{{ORDERS}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">04</div>
<h3>Pricing &amp; Payment</h3>
{{PRICING_PAYMENT}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">05</div>
<h3>Shipping &amp; Risk of Loss</h3>
{{SHIPPING_RISK}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">06</div>
<h3>Returns &amp; Refunds</h3>
{{RETURNS_REFUNDS}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">07</div>
<h3>Intellectual Property</h3>
{{INTELLECTUAL_PROPERTY}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">08</div>
<h3>User Conduct &amp; Prohibited Uses</h3>
{{USER_CONDUCT}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">09</div>
<h3>Disclaimers</h3>
{{DISCLAIMERS}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">10</div>
<h3>Limitation of Liability</h3>
{{LIABILITY}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">11</div>
<h3>Indemnification</h3>
{{INDEMNIFICATION}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">12</div>
<h3>Governing Law</h3>
{{GOVERNING_LAW}}
</div>

<div class="cmc-policy-card cmc-policy-card--wide">
<div class="cmc-policy-card__num">13</div>
<h3>Changes to Terms</h3>
{{CHANGES}}
</div>

</div>

</div>

[/col]
[/row]
[/section]

[section label="Terms Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner">
<h2>Questions about these terms?</h2>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
