<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Cookie Hero" padding="32px" padding__sm="18px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<h1 class="cmc-page-title" style="text-align:center;margin-bottom:8px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.08" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="12px"]

</div>

[/col]
[/row]
[/section]

[section label="Cookie Bands" padding="24px" padding__sm="14px" class="cmc-section cmc-policy-section cmc-variant--bands"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-band cmc-band--soft">
<h2>Overview</h2>
{{OVERVIEW}}
</div>

<div class="cmc-band">
<h2>Types of Cookies We Use</h2>
{{COOKIE_TYPES}}
</div>

<div class="cmc-band cmc-band--soft">
<h2>First-Party vs Third-Party Cookies</h2>
{{FIRST_THIRD_PARTY}}
</div>

<div class="cmc-band">
<h2>Cookie Duration</h2>
{{COOKIE_DURATION}}
</div>

<div class="cmc-band cmc-band--soft">
<h2>How to Manage or Disable Cookies</h2>
{{MANAGE_COOKIES}}
</div>

<div class="cmc-band">
<h2>Consent</h2>
{{CONSENT}}
</div>

<div class="cmc-band cmc-band--soft">
<h2>Changes to This Policy</h2>
{{POLICY_CHANGES}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Cookie Contact" padding="24px" padding__sm="14px" class="cmc-section cmc-contact-section"]
[row]
[col span="12"]

<div class="cmc-container">

<h3>Questions about cookies?</h3>
{{CONTACT_BLOCK}}

</div>

[/col]
[/row]
[/section]
SKELETON;
