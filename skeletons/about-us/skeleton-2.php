<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="About Hero" padding="32px" padding__sm="20px" class="cmc-section cmc-about-hero cmc-hero--split"]
[row]

[col span="7" span__sm="12"]

<span class="cmc-eyebrow">About [ten-web]</span>
<h1 class="cmc-page-title">{{HEADING}}</h1>
<span class="cmc-accent-bar"></span>

[ux_text class="cmc-lead" font_size="1.1"]
{{INTRO}}
[/ux_text]

<div class="cmc-hero-chips cmc-hero-chips--compact">
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Call:</span> [so-dien-thoai]</span>
<span class="cmc-hero-chip"><span class="cmc-hero-chip__key">Email:</span> [email-web]</span>
</div>

[/col]

[col span="5" span__sm="12"]

<div class="cmc-quote-card cmc-quote-card--mission">
<span class="cmc-eyebrow">Our mission</span>
<h3>What we stand for</h3>
{{MISSION}}
</div>

[/col]
[/row]
[/section]

[section label="Values" padding="24px" padding__sm="16px" class="cmc-section cmc-about-values"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-values-head">
<span class="cmc-eyebrow">Why [ten-web]</span>
<h2>Three reasons customers choose us</h2>
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

[section label="Story" padding="28px" padding__sm="18px" class="cmc-section cmc-about-story cmc-bg--soft"]
[row]
[col span="12"]

<div class="cmc-container">

<span class="cmc-eyebrow">Our story</span>
<h2>Where we started</h2>
{{STORY}}

</div>

[/col]
[/row]
[/section]

[section label="Offerings" padding="24px" padding__sm="16px" class="cmc-section cmc-about-offerings"]
[row]
[col span="12"]

<div class="cmc-container">

<div class="cmc-offerings-panel">
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

<div class="cmc-cta-banner">
<h2>Let's talk</h2>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
