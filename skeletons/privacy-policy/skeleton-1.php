<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Privacy Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--rich"]
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

[section label="Privacy Body" padding="28px" padding__sm="18px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-policy-list">

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">01</div>
<div class="cmc-policy-item__body">
<h2>Overview</h2>
{{EFFECTIVE_DATE_LINE}}
{{OVERVIEW}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">02</div>
<div class="cmc-policy-item__body">
<h2>Information We Collect</h2>
{{INFO_COLLECTED}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">03</div>
<div class="cmc-policy-item__body">
<h2>How We Use Your Information</h2>
{{USE_OF_INFO}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">04</div>
<div class="cmc-policy-item__body">
<h2>Information Sharing &amp; Disclosure</h2>
{{SHARING}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">05</div>
<div class="cmc-policy-item__body">
<h2>Cookies &amp; Tracking Technologies</h2>
{{COOKIES_TRACKING}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">06</div>
<div class="cmc-policy-item__body">
<h2>Data Retention</h2>
{{DATA_RETENTION}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">07</div>
<div class="cmc-policy-item__body">
<h2>Your Rights</h2>
{{YOUR_RIGHTS}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">08</div>
<div class="cmc-policy-item__body">
<h2>Security Measures</h2>
{{SECURITY_MEASURES}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">09</div>
<div class="cmc-policy-item__body">
<h2>Children's Privacy</h2>
{{CHILDRENS_PRIVACY}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">10</div>
<div class="cmc-policy-item__body">
<h2>International Users</h2>
{{INTERNATIONAL_USERS}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">11</div>
<div class="cmc-policy-item__body">
<h2>Changes to This Policy</h2>
{{POLICY_CHANGES}}
</div>
</div>

</div>

</div>

[/col]
[/row]
[/section]

[section label="Privacy Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner">
<h2>Privacy questions or requests?</h2>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
