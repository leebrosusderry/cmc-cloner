<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Privacy Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--editorial"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<span class="cmc-eyebrow">Legal &middot; [ten-web]</span>
<h1 class="cmc-page-title cmc-page-title--editorial">{{HEADING}}</h1>

[ux_text class="cmc-lead cmc-lead--editorial"]
{{INTRO}}
[/ux_text]

<div class="cmc-editorial-meta">
<span class="cmc-editorial-meta__item">[ten-doanh-nghiep]</span>
<span class="cmc-editorial-meta__item">[email-web]</span>
<span class="cmc-editorial-meta__item">[so-dien-thoai]</span>
</div>

[divider width="120px" margin="14px" align="left"]

<div class="cmc-editorial-pullquote">
{{EFFECTIVE_DATE_LINE}}
{{OVERVIEW}}
</div>

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="01">Information We Collect</h2>
{{INFO_COLLECTED}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="02">How We Use Your Information</h2>
{{USE_OF_INFO}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="03">Information Sharing &amp; Disclosure</h2>
{{SHARING}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="04">Cookies &amp; Tracking Technologies</h2>
{{COOKIES_TRACKING}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="05">Data Retention</h2>
{{DATA_RETENTION}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="06">Your Rights</h2>
{{YOUR_RIGHTS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="07">Security Measures</h2>
{{SECURITY_MEASURES}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="08">Children's Privacy</h2>
{{CHILDRENS_PRIVACY}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="09">International Users</h2>
{{INTERNATIONAL_USERS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="10">Changes to This Policy</h2>
{{POLICY_CHANGES}}

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Privacy questions or requests?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
