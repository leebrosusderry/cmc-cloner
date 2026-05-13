<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Contact Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-contact-hero cmc-hero--soft cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Contact [ten-web]</span>
<h1 class="cmc-page-title" style="text-align:center;margin-bottom:12px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.15" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="12px"]

<div class="cmc-hero-chips">
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Call:</span> [so-dien-thoai]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Email:</span> [email-web]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Visit:</span> [dia-chi]</span>
</div>

</div>

[/col]
[/row]
[/section]

[section label="Contact Info Panel" padding="28px" padding__sm="18px" class="cmc-section cmc-contact-cards-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-offerings-panel">
<div class="cmc-values-head">
<span class="cmc-eyebrow">All the details</span>
<h2>How to reach us</h2>
</div>

<div class="cmc-contact-cards">

<div class="cmc-contact-card cmc-contact-card--flat">
<span class="cmc-contact-card__icon" aria-hidden="true">&#9873;</span>
<span class="cmc-eyebrow">Address</span>
{{ADDRESS_BLOCK}}
</div>

<div class="cmc-contact-card cmc-contact-card--flat">
<span class="cmc-contact-card__icon" aria-hidden="true">&#9742;</span>
<span class="cmc-eyebrow">Email</span>
{{PHONE_EMAIL_BLOCK}}
</div>

<div class="cmc-contact-card cmc-contact-card--flat">
<span class="cmc-contact-card__icon" aria-hidden="true">&#9201;</span>
<span class="cmc-eyebrow">Hours</span>
{{HOURS_BLOCK}}
</div>

</div>
</div>

</div>

[/col]
[/row]
[/section]

[section label="Topics" padding="24px" padding__sm="16px" class="cmc-section cmc-contact-topics-wrap cmc-variant--bands"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head cmc-values-head--center">
<span class="cmc-eyebrow">Help topics</span>
<h2>Find the right channel</h2>
</div>

<div class="cmc-band cmc-band--soft">
<h3>Response Time</h3>
{{RESPONSE_TIME}}
</div>

<div class="cmc-band">
<h3>Customer Support</h3>
{{SUPPORT_CHANNELS}}
</div>

<div class="cmc-band cmc-band--soft">
<h3>Returns, Refunds &amp; Order Questions</h3>
{{ORDER_HELP}}
</div>

<div class="cmc-band">
<h3>Business &amp; Press Inquiries</h3>
{{BUSINESS_INQUIRIES}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Get in Touch" padding="24px" padding__sm="16px" class="cmc-section cmc-contact-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner">
<h2>We're here to help</h2>
{{CONTACT_BLOCK}}
<div class="cmc-contact-form-mount" data-mailto="[email-web]"></div>
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
