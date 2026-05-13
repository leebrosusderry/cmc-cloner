<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Cookie Policy" padding="28px" padding__sm="16px" class="cmc-section cmc-policy-section cmc-variant--cards"]
[row]
[col span="12"]

<div class="cmc-container">

<h1 class="cmc-page-title">{{HEADING}}</h1>
<span class="cmc-accent-bar"></span>

[ux_text font_size="1.05" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[gap height="10px"]

<div class="cmc-card-block">
<h2>Overview</h2>
{{OVERVIEW}}
</div>

<div class="cmc-card-block">
<h2>Types of Cookies We Use</h2>
{{COOKIE_TYPES}}
</div>

<div class="cmc-card-block">
<h2>First-Party vs Third-Party Cookies</h2>
{{FIRST_THIRD_PARTY}}
</div>

<div class="cmc-card-block">
<h2>Cookie Duration</h2>
{{COOKIE_DURATION}}
</div>

<div class="cmc-card-block">
<h2>How to Manage or Disable Cookies</h2>
{{MANAGE_COOKIES}}
</div>

<div class="cmc-card-block">
<h2>Consent</h2>
{{CONSENT}}
</div>

<div class="cmc-card-block">
<h2>Changes to This Policy</h2>
{{POLICY_CHANGES}}
</div>

[ux_banner height="200px" bg_color="rgb(250,250,250)" border="1px solid #eee" radius="8" class="cmc-contact-banner"]
[text_box width="86" position_x="50" position_y="50" text_align="center"]
<h3>Questions about cookies?</h3>
{{CONTACT_BLOCK}}
[/text_box]
[/ux_banner]

</div>

[/col]
[/row]
[/section]
SKELETON;
