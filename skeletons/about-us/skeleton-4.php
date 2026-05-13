<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return <<<'SKELETON'
[section label="About Editorial" padding="40px" padding__sm="20px" class="cmc-section cmc-about-story cmc-variant--editorial"]
[row]
[col span="12"]

<div class="cmc-container cmc-container--narrow">

<span class="cmc-eyebrow">About [ten-web]</span>
<h1 class="cmc-page-title cmc-page-title--editorial">{{HEADING}}</h1>

[ux_text class="cmc-lead cmc-lead--editorial"]
{{INTRO}}
[/ux_text]

<div class="cmc-editorial-meta">
<span class="cmc-editorial-meta__item">[so-dien-thoai]</span>
<span class="cmc-editorial-meta__item">[email-web]</span>
<span class="cmc-editorial-meta__item">[dia-chi]</span>
</div>

[divider width="120px" margin="14px" align="left"]

<h2 class="cmc-editorial-h2">Our story</h2>
{{STORY}}

<h2 class="cmc-editorial-h2">Our mission</h2>
{{MISSION}}

<div class="cmc-editorial-pullquote">
{{COMMITMENT}}
</div>

<h2 class="cmc-editorial-h2">What we offer</h2>
<p class="cmc-editorial-note">Curated for <strong>[nganh-hang]</strong>.</p>
{{OFFERINGS}}

<h2 class="cmc-editorial-h2">Why customers choose us</h2>

<ol class="cmc-editorial-values">

<li>
<span class="cmc-editorial-values__label">Quality</span>
{{VALUE_QUALITY}}
</li>

<li>
<span class="cmc-editorial-values__label">Customer Care</span>
{{VALUE_CARE}}
</li>

<li>
<span class="cmc-editorial-values__label">Trust</span>
{{VALUE_TRUST}}
</li>

</ol>

[gap height="8px"]

<div class="cmc-editorial-cta">
<h3>Get in touch</h3>
{{CONTACT_BLOCK}}
</div>

</div>

[/col]
[/row]
[/section]
SKELETON;
