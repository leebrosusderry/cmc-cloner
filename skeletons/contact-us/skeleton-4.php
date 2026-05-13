<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Contact Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-contact-section cmc-variant--editorial"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<span class="cmc-eyebrow">Contact [ten-web]</span>
<h1 class="cmc-page-title cmc-page-title--editorial">{{HEADING}}</h1>

[ux_text class="cmc-lead cmc-lead--editorial"]
{{INTRO}}
[/ux_text]

<div class="cmc-editorial-meta">
<span class="cmc-editorial-meta__item">[so-dien-thoai]</span>
<span class="cmc-editorial-meta__item">[email-web]</span>
<span class="cmc-editorial-meta__item">[dia-chi]</span>
</div>

[divider width="120px" margin="14px" align="left"]

<h2 class="cmc-editorial-h2">Where to find us</h2>
{{ADDRESS_BLOCK}}

<h2 class="cmc-editorial-h2">Email</h2>
{{PHONE_EMAIL_BLOCK}}

<h2 class="cmc-editorial-h2">Support hours</h2>
{{HOURS_BLOCK}}

<div class="cmc-editorial-pullquote">
{{RESPONSE_TIME}}
</div>

<h2 class="cmc-editorial-h2">How we help</h2>

<ol class="cmc-editorial-values">

<li>
<span class="cmc-editorial-values__label">Customer Support</span>
{{SUPPORT_CHANNELS}}
</li>

<li>
<span class="cmc-editorial-values__label">Returns, Refunds &amp; Order Questions</span>
{{ORDER_HELP}}
</li>

<li>
<span class="cmc-editorial-values__label">Business &amp; Press</span>
{{BUSINESS_INQUIRIES}}
</li>

</ol>

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Get in touch</h3>
{{CONTACT_BLOCK}}
<div class="cmc-contact-form-mount" data-mailto="[email-web]"></div>
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
