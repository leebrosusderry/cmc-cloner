<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Cookie Policy" padding="36px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--minimal"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<h1 class="cmc-page-title">{{HEADING}}</h1>

[ux_text class="cmc-lead"]
{{INTRO}}
[/ux_text]

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

<hr>

<h2>Contact</h2>
{{CONTACT_BLOCK}}

</div>

[/col]
[/row]
[/section]
SKELETON;
