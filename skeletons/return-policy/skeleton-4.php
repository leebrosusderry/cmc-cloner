<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="Return Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--editorial"]
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

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="01">Return Eligibility</h2>
{{ELIGIBILITY}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="02">Return Window</h2>
{{RETURN_WINDOW}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="03">Non-Returnable Items</h2>
{{NON_RETURNABLE}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="04">Exchange Option</h2>
{{EXCHANGE}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="05">Return Method</h2>
{{RETURN_SHIPPING}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="06">How to Initiate a Return</h2>
{{HOW_TO_RETURN}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="07">Refund Information</h2>
{{REFUND_LINK}}

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Need help with a return?</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
