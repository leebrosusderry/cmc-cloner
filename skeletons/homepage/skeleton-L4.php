<?php
/**
 * Homepage Skeleton L4 — Intent-Cards Navigator
 *
 * Visual rhythm:
 *   [1] Hero.Center        — centered headline + 1 CTA, NO split image
 *                             (relies on big mood image as section bg via CSS)
 *   [2] Intent.4Cards      — 4 mood-labeled cards as primary navigation
 *   [3] BrandStory.2col    — image-right
 *   [4] Featured Products
 *   [5] CTA.Flat
 *
 * Distinct from L1/L2/L3 because: hero is centered text-only on bg image,
 * 4-card mood navigator dominates above-the-fold.
 *
 * CSS classes:
 *   .nt-l4-hero (uses bg image via CSS) / .nt-l4-h1 / .nt-l4-lead / .nt-l4-cta-row
 *   .nt-l4-intent / .nt-l4-intent-card / .nt-l4-intent-label
 *   .nt-l4-story / .nt-l4-story-media / .nt-l4-h2 / .nt-l4-bullets
 *   .nt-l4-featured
 *   .nt-l4-cta / .nt-l4-cta-headline / .nt-l4-cta-para
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L4',
    'name' => 'Intent-Cards Navigator — Hero.Center / Intent.4Cards / BrandStory / Featured / CTA.Flat',

    'image_budget' => <<<'TXT'
* {{IMG_INTENT_1_URL}}  — square 1/1 OR 4/5 portrait. Mood / lifestyle for {NICHE} card 1.
* {{IMG_INTENT_1_ALT}}  — 6-10 words.
* {{IMG_INTENT_2_URL}}  — same shape as card 1. Different mood.
* {{IMG_INTENT_2_ALT}}  — 6-10 words.
* {{IMG_INTENT_3_URL}}  — same shape. Different mood.
* {{IMG_INTENT_3_ALT}}  — 6-10 words.
* {{IMG_INTENT_4_URL}}  — same shape. Different mood.
* {{IMG_INTENT_4_ALT}}  — 6-10 words.
* {{IMG_STORY_URL}}     — landscape 4/3, atelier / process.
* {{IMG_STORY_ALT}}     — 6-10 words.
TXT,

    'text_budget' => <<<'TXT'
* {{HERO_EYEBROW}}      — 2-4 words.
* {{HERO_HEADLINE}}     — 5-9 words. Centered H1, sentence case.
* {{HERO_LEAD}}         — 18-28 words.
* {{HERO_CTA_LABEL}}    — 2-3 words.

* {{INTENT_HEADING}}    — 4-6 words. Section heading above the 4 cards.
* {{INTENT_LABEL_1}}    — 2-4 words. Mood label, NOT product noun.
* {{INTENT_LABEL_2}}    — 2-4 words.
* {{INTENT_LABEL_3}}    — 2-4 words.
* {{INTENT_LABEL_4}}    — 2-4 words.

* {{STORY_EYEBROW}}     — 2-4 words.
* {{STORY_HEADLINE}}    — 5-8 words.
* {{STORY_PARA}}        — 40-60 words.
* {{STORY_BULLET_1}}    — 4-8 words.
* {{STORY_BULLET_2}}    — 4-8 words.
* {{STORY_BULLET_3}}    — 4-8 words.

* {{FEATURED_HEADING}}  — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.

* {{CTA_HEADLINE}}      — 5-9 words.
* {{CTA_PARA}}          — 12-20 words.
* {{CTA_BUTTON_LABEL}}  — 2-3 words.
TXT,

    'html' => <<<'HTML'
[section label="L4 Hero" padding="100px" padding__sm="50px" class="cmc-section nt-l4-hero"]
[row]
[col span="12" span__sm="12" offset="1" offset__sm="0" align="center"]
<span class="nt-eyebrow">{{HERO_EYEBROW}}</span>
<h1 class="nt-l4-h1">{{HERO_HEADLINE}}</h1>
<p class="nt-l4-lead">{{HERO_LEAD}}</p>
<div class="nt-l4-cta-row">[button text="{{HERO_CTA_LABEL}}" color="primary" radius="4" link="/shop/"]</div>
[/col]
[/row]
[/section]

[section label="L4 Intent Cards" padding="48px" padding__sm="28px" class="cmc-section nt-l4-intent"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l4-h2">{{INTENT_HEADING}}</h2></div>
[/col]
[/row]
[row]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l4-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_1_URL}}" alt="{{IMG_INTENT_1_ALT}}" /><span class="nt-intent-label nt-l4-intent-label">{{INTENT_LABEL_1}}</span></a>[/col]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l4-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_2_URL}}" alt="{{IMG_INTENT_2_ALT}}" /><span class="nt-intent-label nt-l4-intent-label">{{INTENT_LABEL_2}}</span></a>[/col]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l4-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_3_URL}}" alt="{{IMG_INTENT_3_ALT}}" /><span class="nt-intent-label nt-l4-intent-label">{{INTENT_LABEL_3}}</span></a>[/col]
[col span="3" span__sm="6"]<a class="nt-intent-card nt-l4-intent-card" href="/shop/"><img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_INTENT_4_URL}}" alt="{{IMG_INTENT_4_ALT}}" /><span class="nt-intent-label nt-l4-intent-label">{{INTENT_LABEL_4}}</span></a>[/col]
[/row]
[/section]

[section label="L4 Brand Story" padding="48px" padding__sm="28px" class="cmc-section nt-l4-story"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{STORY_EYEBROW}}</span>
<h2 class="nt-l4-h2">{{STORY_HEADLINE}}</h2>
<p>{{STORY_PARA}}</p>
<ul class="nt-l4-bullets">
<li>{{STORY_BULLET_1}}</li>
<li>{{STORY_BULLET_2}}</li>
<li>{{STORY_BULLET_3}}</li>
</ul>
[/col]

[col span="6" span__sm="12"]
<div class="nt-story-media nt-l4-story-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_STORY_URL}}" alt="{{IMG_STORY_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L4 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l4-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l4-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L4 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l4-cta"]
[row]
[col span="12"]
<div class="nt-l4-cta-wrap">
<h2 class="nt-l4-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l4-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="white" style="outline" radius="4" link="/shop/"]
</div>
[/col]
[/row]
[/section]
HTML
,
];
