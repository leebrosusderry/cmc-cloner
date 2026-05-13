<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="DMCA Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-policy-hero cmc-hero--rich"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Legal &middot; [ten-web]</span>
<h1 class="cmc-page-title" style="text-align:center">{{HEADING}}</h1>

[ux_text class="cmc-lead" font_size="1.15" text_align="center"]
{{INTRO}}
[/ux_text]

<div class="cmc-doc-meta">
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Company:</span> [ten-doanh-nghiep]</span>
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Email:</span> [email-web]</span>
<span class="cmc-doc-meta__pill"><span class="cmc-doc-meta__key">Phone:</span> [so-dien-thoai]</span>
</div>

</div>

[/col]
[/row]
[/section]

[section label="DMCA Body" padding="28px" padding__sm="18px" class="cmc-section cmc-policy-section"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-policy-list">

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">01</div>
<div class="cmc-policy-item__body">
<h2>Overview</h2>
{{OVERVIEW}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">02</div>
<div class="cmc-policy-item__body">
<h2>Rights of Copyright Holders</h2>
{{RIGHTS}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">03</div>
<div class="cmc-policy-item__body">
<h2>How to File a DMCA Notice</h2>
{{HOW_TO_FILE}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">04</div>
<div class="cmc-policy-item__body">
<h2>Required Information</h2>
{{REQUIRED_INFO}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">05</div>
<div class="cmc-policy-item__body">
<h2>Counter-Notification Process</h2>
{{COUNTER_NOTICE}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">06</div>
<div class="cmc-policy-item__body">
<h2>Repeat Infringer Policy</h2>
{{REPEAT_INFRINGER}}
</div>
</div>

<div class="cmc-policy-item">
<div class="cmc-policy-item__num">07</div>
<div class="cmc-policy-item__body">
<h2>Designated Agent</h2>
{{DESIGNATED_AGENT}}
</div>
</div>

</div>

</div>

[/col]
[/row]
[/section]

[section label="DMCA Contact" padding="24px" padding__sm="16px" class="cmc-section cmc-policy-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner">
<h2>Report an infringement</h2>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
