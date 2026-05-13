<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Privacy Hero" padding="32px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--split"]
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
<span class="cmc-eyebrow">At a glance</span>
<h3>What this covers</h3>
{{EFFECTIVE_DATE_LINE}}
{{OVERVIEW}}
</div>

[/col]
[/row]
[/section]

[section label="Privacy Body" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-variant--cards"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">What we collect</span>
<h2>Information We Collect</h2>
{{INFO_COLLECTED}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">How we use</span>
<h2>How We Use Your Information</h2>
{{USE_OF_INFO}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Sharing</span>
<h2>Information Sharing &amp; Disclosure</h2>
{{SHARING}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Cookies</span>
<h2>Cookies &amp; Tracking Technologies</h2>
{{COOKIES_TRACKING}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Retention</span>
<h2>Data Retention</h2>
{{DATA_RETENTION}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Your rights</span>
<h2>Your Rights</h2>
{{YOUR_RIGHTS}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Security</span>
<h2>Security Measures</h2>
{{SECURITY_MEASURES}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Children</span>
<h2>Children's Privacy</h2>
{{CHILDRENS_PRIVACY}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">International</span>
<h2>International Users</h2>
{{INTERNATIONAL_USERS}}
</div>

<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Updates</span>
<h2>Changes to This Policy</h2>
{{POLICY_CHANGES}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Privacy Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner cmc-cta-banner--split">
<div class="cmc-cta-banner__lead">
<h2>Privacy questions or requests?</h2>
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
