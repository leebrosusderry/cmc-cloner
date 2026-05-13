<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Payment Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--editorial"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<span class="cmc-eyebrow">Policy &middot; [ten-web]</span>
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
{{OVERVIEW}}
</div>

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="01">Accepted Payment Methods</h2>
{{ACCEPTED_METHODS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="02">Currency</h2>
{{CURRENCY}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="03">Payment Security</h2>
{{SECURITY}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="04">Billing</h2>
{{BILLING}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="05">Failed or Declined Payments</h2>
{{FAILED_PAYMENTS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="06">Fraud Prevention</h2>
{{FRAUD_PREVENTION}}

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Questions about a payment?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
