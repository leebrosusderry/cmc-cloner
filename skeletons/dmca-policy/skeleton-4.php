<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="DMCA Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-section cmc-variant--editorial"]
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
{{OVERVIEW}}
</div>

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="01">Rights of Copyright Holders</h2>
{{RIGHTS}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="02">How to File a DMCA Notice</h2>
{{HOW_TO_FILE}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="03">Required Information</h2>
{{REQUIRED_INFO}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="04">Counter-Notification Process</h2>
{{COUNTER_NOTICE}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="05">Repeat Infringer Policy</h2>
{{REPEAT_INFRINGER}}

<h2 class="cmc-editorial-h2 cmc-editorial-h2--numbered" data-num="06">Designated Agent</h2>
{{DESIGNATED_AGENT}}

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Report an infringement</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
