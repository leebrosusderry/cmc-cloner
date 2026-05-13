<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Terms Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--rich"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Legal &middot; [ten-web]</span>
<h1 class="cmc-page-title" style="text-align:center">{{HEADING}}</h1>

[ux_text class="cmc-lead" font_size="1.15" text_align="center"]
{{INTRO}}
[/ux_text]

<div class="cmc-doc-meta">
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Company:</span> [ten-doanh-nghiep]</span>
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Email:</span> [email-web]</span>
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Phone:</span> [so-dien-thoai]</span>
</div>

</div>

[/col]
[/row]
[/section]

[section label="Terms Body" padding="28px" padding__sm="18px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-policy-list">

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">01</div>
<div class="cmc-policy-item__body">
<h2>Acceptance of Terms</h2>
{{ACCEPTANCE}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">02</div>
<div class="cmc-policy-item__body">
<h2>Eligibility</h2>
{{ELIGIBILITY}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">03</div>
<div class="cmc-policy-item__body">
<h2>Account Responsibilities</h2>
{{ACCOUNT}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">04</div>
<div class="cmc-policy-item__body">
<h2>Orders &amp; Acceptance</h2>
{{ORDERS}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">05</div>
<div class="cmc-policy-item__body">
<h2>Pricing &amp; Payment</h2>
{{PRICING_PAYMENT}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">06</div>
<div class="cmc-policy-item__body">
<h2>Shipping &amp; Risk of Loss</h2>
{{SHIPPING_RISK}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">07</div>
<div class="cmc-policy-item__body">
<h2>Returns &amp; Refunds</h2>
{{RETURNS_REFUNDS}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">08</div>
<div class="cmc-policy-item__body">
<h2>Intellectual Property</h2>
{{INTELLECTUAL_PROPERTY}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">09</div>
<div class="cmc-policy-item__body">
<h2>User Conduct &amp; Prohibited Uses</h2>
{{USER_CONDUCT}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">10</div>
<div class="cmc-policy-item__body">
<h2>Disclaimers</h2>
{{DISCLAIMERS}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">11</div>
<div class="cmc-policy-item__body">
<h2>Limitation of Liability</h2>
{{LIABILITY}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">12</div>
<div class="cmc-policy-item__body">
<h2>Indemnification</h2>
{{INDEMNIFICATION}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">13</div>
<div class="cmc-policy-item__body">
<h2>Governing Law</h2>
{{GOVERNING_LAW}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">14</div>
<div class="cmc-policy-item__body">
<h2>Changes to Terms</h2>
{{CHANGES}}
</div>
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
