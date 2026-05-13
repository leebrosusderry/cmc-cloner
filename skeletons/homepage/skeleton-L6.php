<?php
/**
 * Homepage Skeleton L6 — Compact Pro
 *
 * Visual rhythm:
 *   [1] Hero.Split        — 6/6 split, headline + 2 CTAs / mood image
 *   [2] Featured Products
 *   [3] BrandStory.Wide   — full-width centered text-only (no image)
 *   [4] CTA.Flat
 *
 * Distinct from L1-L5 because: ONLY 4 sections (smallest layout, fastest
 * to load), no testimonials section, no mosaic, no intent cards. Targets
 * shops that want minimal homepage with fast TTFB and clean GMC scan.
 *
 * CSS classes:
 *   .nt-l6-hero / .nt-l6-hero-media / .nt-l6-h1 / .nt-l6-lead / .nt-l6-cta-row
 *   .nt-l6-featured
 *   .nt-l6-story / .nt-l6-h2 / .nt-l6-story-lead
 *   .nt-l6-cta / .nt-l6-cta-headline / .nt-l6-cta-para
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L6',
    'name' => 'Compact Pro — Hero.Split / Featured / BrandStory.Wide / CTA.Flat (4 sections)',

    'image_budget' => <<<'TXT'
* {{IMG_HERO_URL}}      — landscape 16/9 OR portrait 3/4. Mood-driven for {NICHE}, no faces, no logos.
* {{IMG_HERO_ALT}}      — 6-10 words.
TXT,

    'text_budget' => <<<'TXT'
* {{HERO_EYEBROW}}      — 2-4 words.
* {{HERO_HEADLINE}}     — 6-10 words.
* {{HERO_PARA}}         — 18-30 words.
* {{HERO_CTA1_LABEL}}   — 2-3 words.
* {{HERO_CTA2_LABEL}}   — 2-3 words.

* {{FEATURED_HEADING}}  — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.

* {{STORY_EYEBROW}}     — 2-4 words.
* {{STORY_HEADLINE}}    — 6-10 words.
* {{STORY_PARA}}        — 60-100 words. Editorial paragraph aligned with Brand Anchor — this is the only story slot in L6, so allow more depth.

* {{CTA_HEADLINE}}      — 5-9 words.
* {{CTA_PARA}}          — 12-20 words.
* {{CTA_BUTTON_LABEL}}  — 2-3 words.
TXT,

    'html' => <<<'HTML'
[section label="L6 Hero" padding="60px" padding__sm="32px" class="cmc-section nt-l6-hero"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{HERO_EYEBROW}}</span>
<h1 class="nt-l6-h1">{{HERO_HEADLINE}}</h1>
<p class="nt-l6-lead">{{HERO_PARA}}</p>
<div class="nt-l6-cta-row">[button text="{{HERO_CTA1_LABEL}}" color="primary" radius="4" link="/shop/"] [button text="{{HERO_CTA2_LABEL}}" style="link" link="/about-us/"]</div>
[/col]

[col span="6" span__sm="12"]
<div class="nt-hero-media nt-l6-hero-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_HERO_URL}}" alt="{{IMG_HERO_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L6 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l6-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l6-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L6 Brand Story" padding="60px" padding__sm="32px" class="cmc-section nt-l6-story"]
[row]
[col span="12" span__sm="12" offset="1" offset__sm="0" align="center"]
<span class="nt-eyebrow">{{STORY_EYEBROW}}</span>
<h2 class="nt-l6-h2">{{STORY_HEADLINE}}</h2>
<p class="nt-l6-story-lead">{{STORY_PARA}}</p>
[/col]
[/row]
[/section]

[section label="L6 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l6-cta"]
[row]
[col span="12"]
<div class="nt-l6-cta-wrap">
<h2 class="nt-l6-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l6-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="white" style="outline" radius="4" link="/shop/"]
</div>
[/col]
[/row]
[/section]
HTML
,
];
