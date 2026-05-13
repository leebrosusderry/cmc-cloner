<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Contact Hero" padding="32px" padding__sm="20px" class="cmc-section cmc-contact-hero cmc-hero--split"]
[row]

[col span="7" span__sm="12"]

<span class="cmc-eyebrow">Contact [ten-web]</span>
<h1 class="cmc-page-title">{{HEADING}}</h1>
<span class="cmc-accent-bar"></span>

[ux_text class="cmc-lead" font_size="1.1"]
{{INTRO}}
[/ux_text]

<div class="cmc-hero-chips cmc-hero-chips--compact">
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Call:</span> [so-dien-thoai]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Email:</span> [email-web]</span>
</div>

[/col]

[col span="5" span__sm="12"]

<div class="cmc-quote-card cmc-quote-card--contact">
<span class="cmc-eyebrow">Quick reach</span>
<h3>Where to find us</h3>
{{ADDRESS_BLOCK}}
<div class="cmc-quote-card__divider"></div>
{{HOURS_BLOCK}}
</div>

[/col]
[/row]
[/section]

[section label="Contact Channels" padding="24px" padding__sm="16px" class="cmc-section cmc-contact-cards-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head">
<span class="cmc-eyebrow">How to reach us</span>
<h2>Pick your channel</h2>
</div>

<div class="cmc-contact-cards cmc-contact-cards--two">

<div class="cmc-contact-card">
<span class="cmc-contact-card__icon" aria-hidden="true">&#9742;</span>
<span class="cmc-eyebrow">Email</span>
<h3>Talk to support</h3>
{{PHONE_EMAIL_BLOCK}}
</div>

<div class="cmc-contact-card">
<span class="cmc-contact-card__icon" aria-hidden="true">&#10003;</span>
<span class="cmc-eyebrow">Response time</span>
<h3>What to expect</h3>
{{RESPONSE_TIME}}
</div>

</div>

</div>

[/col]
[/row]
[/section]

[section label="Topics" padding="24px" padding__sm="16px" class="cmc-section cmc-contact-topics-wrap cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head">
<span class="cmc-eyebrow">Help topics</span>
<h2>Find the right team</h2>
</div>

<div class="cmc-contact-topics">

<div class="cmc-contact-topic">
<h3>Customer Support</h3>
{{SUPPORT_CHANNELS}}
</div>

<div class="cmc-contact-topic">
<h3>Returns, Refunds &amp; Order Questions</h3>
{{ORDER_HELP}}
</div>

<div class="cmc-contact-topic">
<h3>Business &amp; Press Inquiries</h3>
{{BUSINESS_INQUIRIES}}
</div>

</div>

</div>

[/col]
[/row]
[/section]

[section label="Get in Touch" padding="24px" padding__sm="16px" class="cmc-section cmc-contact-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner cmc-cta-banner--split">
<div class="cmc-cta-banner__lead">
<h2>We're here to help</h2>
</div>
<div class="cmc-cta-banner__body">
{{CONTACT_BLOCK}}
<div class="cmc-contact-form-mount" data-mailto="[email-web]"></div>
</div>
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
