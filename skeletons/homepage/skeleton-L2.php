<?php
/**
 * Homepage Skeleton L2 — Mosaic-Led Dark Hero
 *
 * Visual rhythm:
 *   [1] Hero.FullBleed     — dark band, oversized H1 left, mood image right
 *   [2] MoodMosaic.5tiles  — 5-tile grid (1 portrait spans 2 rows + 4 small)
 *   [3] BrandStory.2col    — text-left, image-right
 *   [4] Featured Products
 *   [5] Testimonials.Grid3
 *   [6] CTA.Flat
 *
 * Distinct from L1 because: dark Hero, mosaic comes BEFORE story (mosaic-led
 * mood board), 6 sections instead of 5.
 *
 * CSS classes:
 *   .nt-l2-hero (dark) / .nt-l2-hero-media / .nt-l2-h1 / .nt-l2-lead
 *   .nt-l2-mosaic-section / .nt-l2-mosaic / .nt-l2-mosaic .tile-label
 *   .nt-l2-story / .nt-l2-story-media / .nt-l2-h2 / .nt-l2-bullets
 *   .nt-l2-featured / .nt-l2-testimonials / .nt-l2-testimonial-card
 *   .nt-l2-cta / .nt-l2-cta-headline / .nt-l2-cta-para
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L2',
    'name' => 'Mosaic-Led Dark — Hero.FullBleed / MoodMosaic.5tiles / BrandStory / Featured / Testimonials / CTA.Flat',

    'image_budget' => <<<'TXT'
* {{IMG_HERO_URL}}      — landscape 16/9 OR portrait 3/4. Mood-driven hero shot for {NICHE} on dark / moody surface (so dark hero band feels cohesive). No faces, no text on image, no logos.
* {{IMG_HERO_ALT}}      — 6-10 words.
* {{IMG_MOSAIC_1_URL}}  — portrait 4/5 (this tile spans 2 rows in the grid). Hero mosaic shot, strongest visual.
* {{IMG_MOSAIC_1_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_2_URL}}  — square 1/1. Detail / texture / material close-up.
* {{IMG_MOSAIC_2_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_3_URL}}  — square 1/1. Lifestyle / ambient.
* {{IMG_MOSAIC_3_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_4_URL}}  — square 1/1. Process / studio shot.
* {{IMG_MOSAIC_4_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_5_URL}}  — square 1/1. Accent / quiet detail.
* {{IMG_MOSAIC_5_ALT}}  — 6-10 words.
* {{IMG_STORY_URL}}     — landscape 4/3. Atelier / hands-at-work shot.
* {{IMG_STORY_ALT}}     — 6-10 words.
TXT,

    'text_budget' => <<<'TXT'
* {{HERO_EYEBROW}}      — 2-4 words.
* {{HERO_HEADLINE}}     — 6-10 words. Hero H1, sentence case, evocative.
* {{HERO_PARA}}         — 18-30 words.
* {{HERO_CTA_LABEL}}    — 2-3 words. ONE CTA on this hero (dark bands look cleaner with single CTA).

* {{MOSAIC_TILE_1_LABEL}} — 2-4 words. Hero mosaic label, mood-driven.
* {{MOSAIC_TILE_1_SUB}}   — 2-4 words. Sub-label / kicker on tile 1 only.
* {{MOSAIC_TILE_2_LABEL}} — 2-4 words.
* {{MOSAIC_TILE_3_LABEL}} — 2-4 words.
* {{MOSAIC_TILE_4_LABEL}} — 2-4 words.
* {{MOSAIC_TILE_5_LABEL}} — 2-4 words.

* {{STORY_EYEBROW}}     — 2-4 words.
* {{STORY_HEADLINE}}    — 5-8 words.
* {{STORY_PARA}}        — 40-60 words. Aligned with Brand Anchor Table.
* {{STORY_BULLET_1}}    — 4-8 words.
* {{STORY_BULLET_2}}    — 4-8 words.
* {{STORY_BULLET_3}}    — 4-8 words.

* {{FEATURED_HEADING}}  — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.

* {{TEST_HEADING}}      — 4-6 words.
* {{TEST_QUOTE_1}}      — 18-28 words.
* {{TEST_AUTHOR_1}}     — first name + city/country.
* {{TEST_QUOTE_2}}      — 18-28 words.
* {{TEST_AUTHOR_2}}     — same rule.
* {{TEST_QUOTE_3}}      — 18-28 words.
* {{TEST_AUTHOR_3}}     — same rule.

* {{CTA_HEADLINE}}      — 5-9 words.
* {{CTA_PARA}}          — 12-20 words.
* {{CTA_BUTTON_LABEL}}  — 2-3 words.
TXT,

    'html' => <<<'HTML'
[section label="L2 Hero" padding="80px" padding__sm="40px" dark="true" class="cmc-section nt-l2-hero"]
[row v_align="middle"]

[col span="7" span__sm="12"]
<span class="nt-eyebrow">{{HERO_EYEBROW}}</span>
<h1 class="nt-l2-h1">{{HERO_HEADLINE}}</h1>
<p class="nt-l2-lead">{{HERO_PARA}}</p>
<div class="nt-l2-cta-row">[button text="{{HERO_CTA_LABEL}}" color="white" style="outline" radius="4" link="/shop/"]</div>
[/col]

[col span="5" span__sm="12"]
<div class="nt-hero-media nt-l2-hero-media on-dark">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_HERO_URL}}" alt="{{IMG_HERO_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L2 Mood Mosaic" padding="48px" padding__sm="28px" class="cmc-section nt-l2-mosaic-section"]
[row]
[col span="12"]
<div class="nt-mosaic nt-l2-mosaic"><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_1_URL}}" alt="{{IMG_MOSAIC_1_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_1_LABEL}}<small>{{MOSAIC_TILE_1_SUB}}</small></span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_2_URL}}" alt="{{IMG_MOSAIC_2_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_2_LABEL}}</span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_3_URL}}" alt="{{IMG_MOSAIC_3_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_3_LABEL}}</span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_4_URL}}" alt="{{IMG_MOSAIC_4_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_4_LABEL}}</span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_5_URL}}" alt="{{IMG_MOSAIC_5_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_5_LABEL}}</span></a></div>
[/col]
[/row]
[/section]

[section label="L2 Brand Story" padding="48px" padding__sm="28px" class="cmc-section nt-l2-story"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{STORY_EYEBROW}}</span>
<h2 class="nt-l2-h2">{{STORY_HEADLINE}}</h2>
<p>{{STORY_PARA}}</p>
<ul class="nt-l2-bullets">
<li>{{STORY_BULLET_1}}</li>
<li>{{STORY_BULLET_2}}</li>
<li>{{STORY_BULLET_3}}</li>
</ul>
[/col]

[col span="6" span__sm="12"]
<div class="nt-story-media nt-l2-story-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_STORY_URL}}" alt="{{IMG_STORY_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L2 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l2-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l2-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L2 Testimonials" padding="48px" padding__sm="28px" class="cmc-section nt-l2-testimonials"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l2-h2">{{TEST_HEADING}}</h2></div>
[/col]
[/row]
[row]
[col span="4" span__sm="12"]
<div class="nt-testimonial-card nt-l2-testimonial-card">
<p class="nt-l2-quote">"{{TEST_QUOTE_1}}"</p>
<span class="nt-l2-author">— {{TEST_AUTHOR_1}}</span>
</div>
[/col]
[col span="4" span__sm="12"]
<div class="nt-testimonial-card nt-l2-testimonial-card">
<p class="nt-l2-quote">"{{TEST_QUOTE_2}}"</p>
<span class="nt-l2-author">— {{TEST_AUTHOR_2}}</span>
</div>
[/col]
[col span="4" span__sm="12"]
<div class="nt-testimonial-card nt-l2-testimonial-card">
<p class="nt-l2-quote">"{{TEST_QUOTE_3}}"</p>
<span class="nt-l2-author">— {{TEST_AUTHOR_3}}</span>
</div>
[/col]
[/row]
[/section]

[section label="L2 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l2-cta"]
[row]
[col span="12"]
<div class="nt-l2-cta-wrap">
<h2 class="nt-l2-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l2-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="white" style="outline" radius="4" link="/shop/"]
</div>
[/col]
[/row]
[/section]
HTML
,
];
