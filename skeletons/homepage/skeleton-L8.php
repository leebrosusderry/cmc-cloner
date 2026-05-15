<?php
/**
 * Homepage Skeleton L8 — Editorial Plus (rich layout)
 *
 * Visual rhythm:
 *   [1] Hero.Split          — 6/6, headline + 2 CTA / image
 *   [2] ValueStrip.3        — 3-icon strip (no images, just glyph + label + 1 line)
 *   [3] BrandStory.2col     — image-right
 *   [4] Intent.4Cards       — 4 mood-labeled cards
 *   [5] Featured Products
 *   [6] CTA.Split           — image + text-on-color
 *
 * Distinct from L1-L7 because: 6 sections, has BOTH the value strip AND
 * intent cards — designed for brands with multiple trust signals to
 * communicate (craft, materials, ethos, range).
 *
 * NOTE on ValueStrip: uses pure CSS glyphs (Unicode shapes) baked into
 * skeleton — no SVG/icon system. The class names will be styled via
 * pseudo-elements in child theme.
 *
 * CSS classes:
 *   .nt-l8-hero / .nt-l8-hero-media / .nt-l8-h1 / .nt-l8-lead / .nt-l8-cta-row
 *   .nt-l8-values / .nt-l8-value-card / .nt-l8-value-glyph / .nt-l8-value-title / .nt-l8-value-desc
 *   .nt-l8-story / .nt-l8-story-media / .nt-l8-h2 / .nt-l8-bullets
 *   .nt-l8-intent / .nt-l8-intent-card / .nt-l8-intent-label
 *   .nt-l8-featured
 *   .nt-l8-cta / .nt-l8-cta-media / .nt-l8-cta-headline / .nt-l8-cta-para
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L8',
    'name' => 'Editorial Plus — Hero / ValueStrip / BrandStory / Intent / Featured / CTA.Split (6 sections)',

    'image_budget' => <<<'TXT'
* {{IMG_HERO_URL}}      — landscape 16/9 OR portrait 3/4. Mood-driven for {NICHE}.
* {{IMG_HERO_ALT}}      — 6-10 words.
* {{IMG_STORY_URL}}     — landscape 4/3, atelier / process.
* {{IMG_STORY_ALT}}     — 6-10 words.
* {{IMG_INTENT_1_URL}}  — square 1/1 OR 4/5 portrait. Mood for card 1.
* {{IMG_INTENT_1_ALT}}  — 6-10 words.
* {{IMG_INTENT_2_URL}}  — same shape. Different mood.
* {{IMG_INTENT_2_ALT}}  — 6-10 words.
* {{IMG_INTENT_3_URL}}  — same shape. Different mood.
* {{IMG_INTENT_3_ALT}}  — 6-10 words.
* {{IMG_INTENT_4_URL}}  — same shape. Different mood.
* {{IMG_INTENT_4_ALT}}  — 6-10 words.
* {{IMG_CTA_URL}}       — landscape 3/2, ambient brand-feel.
* {{IMG_CTA_ALT}}       — 6-10 words.
TXT,

    'text_budget' => <<<'TXT'
* {{HERO_EYEBROW}}      — 2-4 words.
* {{HERO_HEADLINE}}     — 6-10 words.
* {{HERO_PARA}}         — 18-30 words.
* {{HERO_CTA1_LABEL}}   — 2-3 words.
* {{HERO_CTA2_LABEL}}   — 2-3 words.

* {{VALUE_1_TITLE}}     — 2-3 words. Trust attribute (e.g. "Made slowly", "Honest materials").
* {{VALUE_1_DESC}}      — 8-14 words. One-line elaboration.
* {{VALUE_2_TITLE}}     — 2-3 words.
* {{VALUE_2_DESC}}      — 8-14 words.
* {{VALUE_3_TITLE}}     — 2-3 words.
* {{VALUE_3_DESC}}      — 8-14 words.

* {{STORY_EYEBROW}}     — 2-4 words.
* {{STORY_HEADLINE}}    — 5-8 words.
* {{STORY_PARA}}        — 40-60 words.
* {{STORY_BULLET_1}}    — 4-8 words.
* {{STORY_BULLET_2}}    — 4-8 words.
* {{STORY_BULLET_3}}    — 4-8 words.

* {{INTENT_HEADING}}    — 4-6 words.
* {{INTENT_LABEL_1}}    — 2-4 words. Mood label, NOT product noun.
* {{INTENT_LABEL_2}}    — 2-4 words.
* {{INTENT_LABEL_3}}    — 2-4 words.
* {{INTENT_LABEL_4}}    — 2-4 words.

* {{FEATURED_HEADING}}  — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.


* {{CTA_HEADLINE}}      — 5-9 words.
* {{CTA_PARA}}          — 12-20 words.
* {{CTA_BUTTON_LABEL}}  — 2-3 words.
TXT,

    'html' => <<<'HTML'
[section label="L8 Hero" padding="60px" padding__sm="32px" class="cmc-section nt-l8-hero"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{HERO_EYEBROW}}</span>
<h1 class="nt-l8-h1">{{HERO_HEADLINE}}</h1>
<p class="nt-l8-lead">{{HERO_PARA}}</p>
<div class="nt-l8-cta-row">[button text="{{HERO_CTA1_LABEL}}" color="primary" radius="4" link="/shop/"] [button text="{{HERO_CTA2_LABEL}}" style="link" link="/about-us/"]</div>
[/col]

[col span="6" span__sm="12"]
<div class="nt-hero-media nt-l8-hero-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_HERO_URL}}" alt="{{IMG_HERO_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L8 Values" padding="36px" padding__sm="20px" class="cmc-section nt-l8-values"]
[row]
[col span="4" span__sm="12"]
<div class="nt-l8-value-card"><span class="nt-l8-value-glyph" aria-hidden="true">◆</span><h3 class="nt-l8-value-title">{{VALUE_1_TITLE}}</h3><p class="nt-l8-value-desc">{{VALUE_1_DESC}}</p></div>
[/col]
[col span="4" span__sm="12"]
<div class="nt-l8-value-card"><span class="nt-l8-value-glyph" aria-hidden="true">◇</span><h3 class="nt-l8-value-title">{{VALUE_2_TITLE}}</h3><p class="nt-l8-value-desc">{{VALUE_2_DESC}}</p></div>
[/col]
[col span="4" span__sm="12"]
<div class="nt-l8-value-card"><span class="nt-l8-value-glyph" aria-hidden="true">◉</span><h3 class="nt-l8-value-title">{{VALUE_3_TITLE}}</h3><p class="nt-l8-value-desc">{{VALUE_3_DESC}}</p></div>
[/col]
[/row]
[/section]

[section label="L8 Brand Story" padding="48px" padding__sm="28px" class="cmc-section nt-l8-story"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{STORY_EYEBROW}}</span>
<h2 class="nt-l8-h2">{{STORY_HEADLINE}}</h2>
<p>{{STORY_PARA}}</p>
<ul class="nt-l8-bullets">
<li>{{STORY_BULLET_1}}</li>
<li>{{STORY_BULLET_2}}</li>
<li>{{STORY_BULLET_3}}</li>
</ul>
[/col]

[col span="6" span__sm="12"]
<div class="nt-story-media nt-l8-story-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_STORY_URL}}" alt="{{IMG_STORY_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L8 Intent" padding="48px" padding__sm="28px" class="cmc-section nt-l8-intent"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l8-h2">{{INTENT_HEADING}}</h2></div>
[/col]
[/row]
[row]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l8-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_1_URL}}" alt="{{IMG_INTENT_1_ALT}}" /><span class="nt-intent-label nt-l8-intent-label">{{INTENT_LABEL_1}}</span></a>[/col]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l8-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_2_URL}}" alt="{{IMG_INTENT_2_ALT}}" /><span class="nt-intent-label nt-l8-intent-label">{{INTENT_LABEL_2}}</span></a>[/col]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l8-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_3_URL}}" alt="{{IMG_INTENT_3_ALT}}" /><span class="nt-intent-label nt-l8-intent-label">{{INTENT_LABEL_3}}</span></a>[/col]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l8-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_4_URL}}" alt="{{IMG_INTENT_4_ALT}}" /><span class="nt-intent-label nt-l8-intent-label">{{INTENT_LABEL_4}}</span></a>[/col]
[/row]
[/section]

[section label="L8 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l8-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l8-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L8 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l8-cta"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<div class="nt-cta-media nt-l8-cta-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_CTA_URL}}" alt="{{IMG_CTA_ALT}}" />
</div>
[/col]

[col span="6" span__sm="12"]
<div class="nt-l8-cta-wrap">
<h2 class="nt-l8-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l8-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="primary" radius="4" link="/shop/"]
</div>
[/col]

[/row]
[/section]
HTML
,
];
