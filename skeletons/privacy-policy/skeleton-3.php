<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Privacy Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--soft cmc-bg--soft"]
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

[section label="Privacy Overview" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-offerings-panel">
<span class="cmc-eyebrow">At a glance</span>
<h2>Overview</h2>
{{EFFECTIVE_DATE_LINE}}
{{OVERVIEW}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Privacy Grid" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head cmc-values-head--center">
<span class="cmc-eyebrow">Details</span>
<h2>How we handle your data</h2>
</div>

<div class="cmc-policy-grid">

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">01</div>
<h3>Information We Collect</h3>
{{INFO_COLLECTED}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">02</div>
<h3>How We Use Your Information</h3>
{{USE_OF_INFO}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">03</div>
<h3>Information Sharing &amp; Disclosure</h3>
{{SHARING}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">04</div>
<h3>Cookies &amp; Tracking Technologies</h3>
{{COOKIES_TRACKING}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">05</div>
<h3>Data Retention</h3>
{{DATA_RETENTION}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">06</div>
<h3>Your Rights</h3>
{{YOUR_RIGHTS}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">07</div>
<h3>Security Measures</h3>
{{SECURITY_MEASURES}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">08</div>
<h3>Children's Privacy</h3>
{{CHILDRENS_PRIVACY}}
</div>

<div class="cmc-policy-card">
<div class="cmc-policy-card__num">09</div>
<h3>International Users</h3>
{{INTERNATIONAL_USERS}}
</div>

<div class="cmc-policy-card cmc-policy-card--wide">
<div class="cmc-policy-card__num">10</div>
<h3>Changes to This Policy</h3>
{{POLICY_CHANGES}}
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
