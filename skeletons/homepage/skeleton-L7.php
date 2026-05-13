<?php
/**
 * Homepage Skeleton L7 — Mosaic Mid + Quote Stack
 *
 * Visual rhythm:
 *   [1] Hero.Split          — 6/6, headline + 2 CTA / image
 *   [2] BrandStory.2col     — image-LEFT, text-right (mirror of L1's order)
 *   [3] MoodMosaic.5tiles   — mosaic in the middle of the page
 *   [4] Featured Products
 *   [5] Testimonials.Stack  — 3 quotes stacked VERTICALLY (not grid),
 *                              each separated by hairline rule, alternating
 *                              left/right alignment for editorial rhythm
 *   [6] CTA.Flat
 *
 * Distinct from L1-L6 because: BrandStory image-left, mosaic in mid-page
 * (not above featured), testimonials are a vertical stack with alternating
 * alignment (more "review feed" feel than "review grid").
 *
 * CSS classes:
 *   .nt-l7-hero / .nt-l7-hero-media / .nt-l7-h1 / .nt-l7-lead / .nt-l7-cta-row
 *   .nt-l7-story / .nt-l7-story-media / .nt-l7-h2 / .nt-l7-bullets
 *   .nt-l7-mosaic-section / .nt-l7-mosaic
 *   .nt-l7-featured
 *   .nt-l7-test-stack / .nt-l7-test-row / .nt-l7-test-row.is-alt / .nt-l7-quote / .nt-l7-author
 *   .nt-l7-cta / .nt-l7-cta-headline / .nt-l7-cta-para
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L7',
    'name' => 'Mosaic Mid + Quote Stack — Hero.Split / BrandStory (img-left) / MoodMosaic / Featured / Testimonials.Stack / CTA.Flat',

    'image_budget' => <<<'TXT'
* {{IMG_HERO_URL}}      — landscape 16/9 OR portrait 3/4. Mood-driven for {NICHE}.
* {{IMG_HERO_ALT}}      — 6-10 words.
* {{IMG_STORY_URL}}     — landscape 4/3, atelier / process.
* {{IMG_STORY_ALT}}     — 6-10 words.
* {{IMG_MOSAIC_1_URL}}  — portrait 4/5 (spans 2 rows).
* {{IMG_MOSAIC_1_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_2_URL}}  — square 1/1.
* {{IMG_MOSAIC_2_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_3_URL}}  — square 1/1.
* {{IMG_MOSAIC_3_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_4_URL}}  — square 1/1.
* {{IMG_MOSAIC_4_ALT}}  — 6-10 words.
* {{IMG_MOSAIC_5_URL}}  — square 1/1.
* {{IMG_MOSAIC_5_ALT}}  — 6-10 words.
TXT,

    'text_budget' => <<<'TXT'
* {{HERO_EYEBROW}}      — 2-4 words.
* {{HERO_HEADLINE}}     — 6-10 words.
* {{HERO_PARA}}         — 18-30 words.
* {{HERO_CTA1_LABEL}}   — 2-3 words.
* {{HERO_CTA2_LABEL}}   — 2-3 words.

* {{STORY_EYEBROW}}     — 2-4 words.
* {{STORY_HEADLINE}}    — 5-8 words.
* {{STORY_PARA}}        — 40-60 words.
* {{STORY_BULLET_1}}    — 4-8 words.
* {{STORY_BULLET_2}}    — 4-8 words.
* {{STORY_BULLET_3}}    — 4-8 words.

* {{MOSAIC_TILE_1_LABEL}} — 2-4 words. Mood label.
* {{MOSAIC_TILE_1_SUB}}   — 2-4 words.
* {{MOSAIC_TILE_2_LABEL}} — 2-4 words.
* {{MOSAIC_TILE_3_LABEL}} — 2-4 words.
* {{MOSAIC_TILE_4_LABEL}} — 2-4 words.
* {{MOSAIC_TILE_5_LABEL}} — 2-4 words.

* {{FEATURED_HEADING}}  — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.

* {{TEST_HEADING}}      — 4-6 words.
* {{TEST_QUOTE_1}}      — 25-40 words. Slightly longer than grid format — stack benefits from depth.
* {{TEST_AUTHOR_1}}     — first name + city/country.
* {{TEST_QUOTE_2}}      — 25-40 words.
* {{TEST_AUTHOR_2}}     — same rule.
* {{TEST_QUOTE_3}}      — 25-40 words.
* {{TEST_AUTHOR_3}}     — same rule.

* {{CTA_HEADLINE}}      — 5-9 words.
* {{CTA_PARA}}          — 12-20 words.
* {{CTA_BUTTON_LABEL}}  — 2-3 words.
TXT,

    'html' => <<<'HTML'
[section label="L7 Hero" padding="60px" padding__sm="32px" class="cmc-section nt-l7-hero"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{HERO_EYEBROW}}</span>
<h1 class="nt-l7-h1">{{HERO_HEADLINE}}</h1>
<p class="nt-l7-lead">{{HERO_PARA}}</p>
<div class="nt-l7-cta-row">[button text="{{HERO_CTA1_LABEL}}" color="primary" radius="4" link="/shop/"] [button text="{{HERO_CTA2_LABEL}}" style="link" link="/about-us/"]</div>
[/col]

[col span="6" span__sm="12"]
<div class="nt-hero-media nt-l7-hero-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_HERO_URL}}" alt="{{IMG_HERO_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L7 Brand Story" padding="48px" padding__sm="28px" class="cmc-section nt-l7-story"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<div class="nt-story-media nt-l7-story-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_STORY_URL}}" alt="{{IMG_STORY_ALT}}" />
</div>
[/col]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{STORY_EYEBROW}}</span>
<h2 class="nt-l7-h2">{{STORY_HEADLINE}}</h2>
<p>{{STORY_PARA}}</p>
<ul class="nt-l7-bullets">
<li>{{STORY_BULLET_1}}</li>
<li>{{STORY_BULLET_2}}</li>
<li>{{STORY_BULLET_3}}</li>
</ul>
[/col]

[/row]
[/section]

[section label="L7 Mood Mosaic" padding="48px" padding__sm="28px" class="cmc-section nt-l7-mosaic-section"]
[row]
[col span="12"]
<div class="nt-mosaic nt-l7-mosaic"><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_1_URL}}" alt="{{IMG_MOSAIC_1_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_1_LABEL}}<small>{{MOSAIC_TILE_1_SUB}}</small></span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_2_URL}}" alt="{{IMG_MOSAIC_2_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_2_LABEL}}</span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_3_URL}}" alt="{{IMG_MOSAIC_3_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_3_LABEL}}</span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_4_URL}}" alt="{{IMG_MOSAIC_4_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_4_LABEL}}</span></a><a href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_MOSAIC_5_URL}}" alt="{{IMG_MOSAIC_5_ALT}}" /><span class="tile-label">{{MOSAIC_TILE_5_LABEL}}</span></a></div>
[/col]
[/row]
[/section]

[section label="L7 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l7-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l7-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L7 Testimonials Stack" padding="60px" padding__sm="32px" class="cmc-section nt-l7-testimonials"]
[row]
[col span="12" span__sm="12" offset="1" offset__sm="0"]
<div class="nt-feat-heading"><h2 class="nt-l7-h2">{{TEST_HEADING}}</h2></div>
<div class="nt-l7-test-stack">
<div class="nt-l7-test-row"><blockquote class="nt-l7-quote">"{{TEST_QUOTE_1}}"</blockquote><span class="nt-l7-author">— {{TEST_AUTHOR_1}}</span></div>
<div class="nt-l7-test-row is-alt"><blockquote class="nt-l7-quote">"{{TEST_QUOTE_2}}"</blockquote><span class="nt-l7-author">— {{TEST_AUTHOR_2}}</span></div>
<div class="nt-l7-test-row"><blockquote class="nt-l7-quote">"{{TEST_QUOTE_3}}"</blockquote><span class="nt-l7-author">— {{TEST_AUTHOR_3}}</span></div>
</div>
[/col]
[/row]
[/section]

[section label="L7 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l7-cta"]
[row]
[col span="12"]
<div class="nt-l7-cta-wrap">
<h2 class="nt-l7-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l7-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="white" style="outline" radius="4" link="/shop/"]
</div>
[/col]
[/row]
[/section]
HTML
,
];
