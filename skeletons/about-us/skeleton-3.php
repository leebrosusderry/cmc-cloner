<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="About Hero" padding="40px" padding__sm="20px" class="cmc-section cmc-about-hero cmc-hero--soft cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">About [ten-web]</span>
<h1 class="cmc-page-title" style="text-align:center;margin-bottom:12px">{{HEADING}}</h1>

[ux_text text_align="center" font_size="1.15" class="cmc-lead"]
{{INTRO}}
[/ux_text]

[divider align="center" width="60px" margin="12px"]

<div class="cmc-hero-chips">
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Call:</span> [so-dien-thoai]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Email:</span> [email-web]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Visit:</span> [dia-chi]</span>
</div>

</div>

[/col]
[/row]
[/section]

[section label="Values" padding="28px" padding__sm="18px" class="cmc-section cmc-about-values"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head cmc-values-head--center">
<span class="cmc-eyebrow">What we stand for</span>
<h2>Our values</h2>
</div>

<div class="cmc-values-grid">

<div class="cmc-value-card">
<div class="cmc-value-card__num">01</div>
<h3>Quality</h3>
{{VALUE_QUALITY}}
</div>

<div class="cmc-value-card">
<div class="cmc-value-card__num">02</div>
<h3>Customer Care</h3>
{{VALUE_CARE}}
</div>

<div class="cmc-value-card">
<div class="cmc-value-card__num">03</div>
<h3>Trust</h3>
{{VALUE_TRUST}}
</div>

</div>

</div>

[/col]
[/row]
[/section]

[section label="Story & Mission" padding="28px" padding__sm="18px" class="cmc-section cmc-about-story cmc-variant--cards"]
[row]

[col span="6" span__sm="12"]
<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Our story</span>
<h2>Where we started</h2>
{{STORY}}
</div>
[/col]

[col span="6" span__sm="12"]
<div class="cmc-card-block cmc-card-block--rich">
<span class="cmc-eyebrow">Our mission</span>
<h2>What drives us</h2>
{{MISSION}}
</div>
[/col]

[/row]
[/section]

[section label="Offerings" padding="24px" padding__sm="16px" class="cmc-section cmc-about-offerings cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-offerings-panel cmc-offerings-panel--flat">
<span class="cmc-eyebrow">What we offer</span>
<h2>Curated for [nganh-hang]</h2>
{{OFFERINGS}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Commitment" padding="28px" padding__sm="18px" class="cmc-section cmc-about-commitment"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Our commitment</span>
<div class="cmc-commitment-quote">
{{COMMITMENT}}
</div>

</div>

[/col]
[/row]
[/section]

[section label="Get in Touch" padding="24px" padding__sm="16px" class="cmc-section cmc-about-cta-wrap"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-cta-banner cmc-cta-banner--split">
<div class="cmc-cta-banner__lead">
<h2>Let's talk</h2>
</div>
<div class="cmc-cta-banner__body">
{{CONTACT_BLOCK}}
</div>
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
