<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Terms Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--editorial"]
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
{{ACCEPTANCE}}
</div>

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="01">Eligibility</h2>
{{ELIGIBILITY}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="02">Account Responsibilities</h2>
{{ACCOUNT}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="03">Orders &amp; Acceptance</h2>
{{ORDERS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="04">Pricing &amp; Payment</h2>
{{PRICING_PAYMENT}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="05">Shipping &amp; Risk of Loss</h2>
{{SHIPPING_RISK}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="06">Returns &amp; Refunds</h2>
{{RETURNS_REFUNDS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="07">Intellectual Property</h2>
{{INTELLECTUAL_PROPERTY}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="08">User Conduct &amp; Prohibited Uses</h2>
{{USER_CONDUCT}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="09">Disclaimers</h2>
{{DISCLAIMERS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="10">Limitation of Liability</h2>
{{LIABILITY}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="11">Indemnification</h2>
{{INDEMNIFICATION}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="12">Governing Law</h2>
{{GOVERNING_LAW}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="13">Changes to Terms</h2>
{{CHANGES}}

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Questions about these terms?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
