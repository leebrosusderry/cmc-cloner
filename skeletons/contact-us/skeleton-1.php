<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Contact Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-contact-hero cmc-hero--rich"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Contact [ten-web]</span>
<h1 class="cmc-page-title" style="text-align:center">{{HEADING}}</h1>

[ux_text class="cmc-lead" font_size="1.15" text_align="center"]
{{INTRO}}
[/ux_text]

<div class="cmc-hero-chips">
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Call:</span> [so-dien-thoai]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Email:</span> [email-web]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Visit:</span> [dia-chi]</span>
</div>

</div>

[/col]
[/row]
[/section]

[section label="Contact Cards" padding="24px" padding__sm="16px" class="cmc-section cmc-contact-cards-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-contact-cards">

<div class="cmc-contact-card">
<span class="cmc-contact-card__icon" aria-hidden="true">&#9873;</span>
<span class="cmc-eyebrow">Address</span>
<h3>Visit us</h3>
{{ADDRESS_BLOCK}}
</div>

<div class="cmc-contact-card">
<span class="cmc-contact-card__icon" aria-hidden="true">&#9742;</span>
<span class="cmc-eyebrow">Reach us</span>
<h3>Email</h3>
{{PHONE_EMAIL_BLOCK}}
</div>

<div class="cmc-contact-card">
<span class="cmc-contact-card__icon" aria-hidden="true">&#9201;</span>
<span class="cmc-eyebrow">Hours</span>
<h3>Support window</h3>
{{HOURS_BLOCK}}
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
<span class="cmc-eyebrow">How we help</span>
<h2>Find the right channel</h2>
</div>

<div class="cmc-contact-topics">

<div class="cmc-contact-topic">
<h3>Response Time</h3>
{{RESPONSE_TIME}}
</div>

<div class="cmc-contact-topic">
<h3>Customer Support</h3>
{{SUPPORT_CHANNELS}}
</div>

<div class="cmc-contact-topic">
<h3>Returns, Refunds &amp; Order Questions</h3>
{{ORDER_HELP}}
</div>

<div class="cmc-contact-topic">
<h3>Business &amp; Press</h3>
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
