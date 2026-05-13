<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Cookie Policy" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<h1 class="cmc-page-title" style="text-align:center;margin-bottom:8px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.05" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="12px"]

<h2>Overview</h2>
{{OVERVIEW}}

<h2>Types of Cookies We Use</h2>
{{COOKIE_TYPES}}

<h2>First-Party vs Third-Party Cookies</h2>
{{FIRST_THIRD_PARTY}}

<h2>Cookie Duration</h2>
{{COOKIE_DURATION}}

<h2>How to Manage or Disable Cookies</h2>
{{MANAGE_COOKIES}}

<h2>Consent</h2>
{{CONSENT}}

<h2>Changes to This Policy</h2>
{{POLICY_CHANGES}}

[gap height="6px"]

[ux_banner height="220px" bg_color="rgb(250,250,250)" border="1px solid #eee" radius="8" class="cmc-contact-banner"]
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
