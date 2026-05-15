<?php
/**
 * Homepage Skeleton L5 — Magazine Stack
 *
 * Visual rhythm:
 *   [1] Hero.FullBleed     — dark band, oversized H1 / image right
 *   [2] BrandStory.2col    — image-right, text-left
 *   [3] Featured Products
 *   [4] Process.5Steps     — 5 numbered horizontal steps showing the process
 *                              (this is the L5 signature section, not in any
 *                               other skeleton)
 *   [5] CTA.Split
 *
 * Distinct from L1-L4 because: unique numbered Process strip showing craft
 * narrative — designed for brands that want to emphasize HOW things are
 * made, not just WHAT.
 *
 * CSS classes:
 *   .nt-l5-hero (dark) / .nt-l5-hero-media / .nt-l5-h1 / .nt-l5-lead
 *   .nt-l5-story / .nt-l5-story-media / .nt-l5-h2
 *   .nt-l5-featured
 *   .nt-l5-process / .nt-l5-step-grid / .nt-l5-step / .nt-l5-step-num / .nt-l5-step-label
 *   .nt-l5-cta / .nt-l5-cta-media / .nt-l5-cta-headline / .nt-l5-cta-para
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L5',
    'name' => 'Magazine Stack — Hero.FullBleed / BrandStory / Featured / Process.5Steps / CTA.Split',

    'image_budget' => <<<'TXT'
* {{IMG_HERO_URL}}      — landscape 16/9 OR portrait 3/4. Mood-driven for {NICHE} on a darker / moodier surface.
* {{IMG_HERO_ALT}}      — 6-10 words.
* {{IMG_STORY_URL}}     — landscape 4/3, atelier / process / studio.
* {{IMG_STORY_ALT}}     — 6-10 words.
* {{IMG_CTA_URL}}       — landscape 3/2, ambient brand-feel.
* {{IMG_CTA_ALT}}       — 6-10 words.
TXT,

    'text_budget' => <<<'TXT'
* {{HERO_EYEBROW}}      — 2-4 words.
* {{HERO_HEADLINE}}     — 6-10 words.
* {{HERO_PARA}}         — 18-30 words.
* {{HERO_CTA_LABEL}}    — 2-3 words.

* {{STORY_EYEBROW}}     — 2-4 words.
* {{STORY_HEADLINE}}    — 5-8 words.
* {{STORY_PARA}}        — 50-80 words.

* {{FEATURED_HEADING}}  — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.

* {{PROCESS_HEADING}}   — 4-7 words. Section heading above the 5 steps.
* {{PROCESS_LEAD}}      — 14-22 words.
* {{STEP_1_LABEL}}      — 2-4 words. Verb-first: "Source", "Sketch", "Cut", "Stitch".
* {{STEP_1_DESC}}       — 8-14 words.
* {{STEP_2_LABEL}}      — 2-4 words.
* {{STEP_2_DESC}}       — 8-14 words.
* {{STEP_3_LABEL}}      — 2-4 words.
* {{STEP_3_DESC}}       — 8-14 words.
* {{STEP_4_LABEL}}      — 2-4 words.
* {{STEP_4_DESC}}       — 8-14 words.
* {{STEP_5_LABEL}}      — 2-4 words.
* {{STEP_5_DESC}}       — 8-14 words.


* {{CTA_HEADLINE}}      — 5-9 words.
* {{CTA_PARA}}          — 12-20 words.
* {{CTA_BUTTON_LABEL}}  — 2-3 words.
TXT,

    'html' => <<<'HTML'
[section label="L5 Hero" padding="80px" padding__sm="40px" dark="true" class="cmc-section nt-l5-hero"]
[row v_align="middle"]

[col span="7" span__sm="12"]
<span class="nt-eyebrow">{{HERO_EYEBROW}}</span>
<h1 class="nt-l5-h1">{{HERO_HEADLINE}}</h1>
<p class="nt-l5-lead">{{HERO_PARA}}</p>
<div class="nt-l5-cta-row">[button text="{{HERO_CTA_LABEL}}" color="white" style="outline" radius="4" link="/shop/"]</div>
[/col]

[col span="5" span__sm="12"]
<div class="nt-hero-media nt-l5-hero-media on-dark">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_HERO_URL}}" alt="{{IMG_HERO_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L5 Brand Story" padding="48px" padding__sm="28px" class="cmc-section nt-l5-story"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{STORY_EYEBROW}}</span>
<h2 class="nt-l5-h2">{{STORY_HEADLINE}}</h2>
<p>{{STORY_PARA}}</p>
[/col]

[col span="6" span__sm="12"]
<div class="nt-story-media nt-l5-story-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_STORY_URL}}" alt="{{IMG_STORY_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L5 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l5-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l5-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L5 Process" padding="60px" padding__sm="32px" class="cmc-section nt-l5-process"]
[row]
[col span="12" span__sm="12" offset="1" offset__sm="0" align="center"]
<h2 class="nt-l5-h2">{{PROCESS_HEADING}}</h2>
<p class="nt-l5-process-lead">{{PROCESS_LEAD}}</p>
[/col]
[/row]
[row]
[col span="12"]
<div class="nt-l5-step-grid">
<div class="nt-l5-step"><span class="nt-l5-step-num">01</span><h3 class="nt-l5-step-label">{{STEP_1_LABEL}}</h3><p class="nt-l5-step-desc">{{STEP_1_DESC}}</p></div>
<div class="nt-l5-step"><span class="nt-l5-step-num">02</span><h3 class="nt-l5-step-label">{{STEP_2_LABEL}}</h3><p class="nt-l5-step-desc">{{STEP_2_DESC}}</p></div>
<div class="nt-l5-step"><span class="nt-l5-step-num">03</span><h3 class="nt-l5-step-label">{{STEP_3_LABEL}}</h3><p class="nt-l5-step-desc">{{STEP_3_DESC}}</p></div>
<div class="nt-l5-step"><span class="nt-l5-step-num">04</span><h3 class="nt-l5-step-label">{{STEP_4_LABEL}}</h3><p class="nt-l5-step-desc">{{STEP_4_DESC}}</p></div>
<div class="nt-l5-step"><span class="nt-l5-step-num">05</span><h3 class="nt-l5-step-label">{{STEP_5_LABEL}}</h3><p class="nt-l5-step-desc">{{STEP_5_DESC}}</p></div>
</div>
[/col]
[/row]
[/section]

[section label="L5 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l5-cta"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<div class="nt-cta-media nt-l5-cta-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_CTA_URL}}" alt="{{IMG_CTA_ALT}}" />
</div>
[/col]

[col span="6" span__sm="12"]
<div class="nt-l5-cta-wrap">
<h2 class="nt-l5-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l5-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="primary" radius="4" link="/shop/"]
</div>
[/col]

[/row]
[/section]
HTML
,
];
