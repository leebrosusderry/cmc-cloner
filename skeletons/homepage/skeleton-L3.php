<?php
/**
 * Homepage Skeleton L3 — Story-First Editorial
 *
 * Visual rhythm:
 *   [1] BrandStory.Wide   — full-width centered text-only intro (no image)
 *                            — kicker + H1 + lead + 2 CTAs
 *   [2] Featured Products — products promoted EARLY (story sets context)
 *   [3] StudioStory.Split — image-left / text-right narrative section
 *   [4] CTA.Split         — image + text-on-color band
 *
 * Distinct from L1/L2 because: H1 is text-only (no hero image), products
 * appear in slot [2] (not [3]), studio story comes AFTER products as
 * "the people behind", CTA uses Split variant (not Flat).
 *
 * CSS classes:
 *   .nt-l3-intro / .nt-l3-h1 / .nt-l3-lead / .nt-l3-cta-row
 *   .nt-l3-featured
 *   .nt-l3-studio / .nt-l3-studio-media / .nt-l3-h2
 *   .nt-l3-cta / .nt-l3-cta-media / .nt-l3-cta-headline / .nt-l3-cta-para
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L3',
    'name' => 'Story-First Editorial — BrandStory.Wide / Featured / StudioStory.Split / CTA.Split',

    'image_budget' => <<<'TXT'
* {{IMG_STUDIO_URL}}    — landscape 4/3, atelier / process shot showing craft for {NICHE}. No faces close up, no logos.
* {{IMG_STUDIO_ALT}}    — 6-10 words.
* {{IMG_CTA_URL}}       — landscape 3/2, ambient brand-feel shot (lifestyle, surface, mood) for {NICHE}.
* {{IMG_CTA_ALT}}       — 6-10 words.
TXT,

    'text_budget' => <<<'TXT'
* {{INTRO_EYEBROW}}     — 2-4 words.
* {{INTRO_HEADLINE}}    — 6-10 words. The page H1, sentence case, evocative.
* {{INTRO_LEAD}}        — 30-50 words. Editorial intro paragraph that sets the tone (no product nouns, no brand names).
* {{INTRO_CTA1_LABEL}}  — 2-3 words.
* {{INTRO_CTA2_LABEL}}  — 2-3 words.

* {{STUDIO_EYEBROW}}    — 2-4 words.
* {{STUDIO_HEADLINE}}   — 5-8 words.
* {{STUDIO_PARA}}       — 50-80 words. Atelier / process / craft narrative aligned with Brand Anchor.
* {{STUDIO_BULLET_1}}   — 4-8 words.
* {{STUDIO_BULLET_2}}   — 4-8 words.
* {{STUDIO_BULLET_3}}   — 4-8 words.

* {{FEATURED_HEADING}}  — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.

* {{CTA_HEADLINE}}      — 5-9 words.
* {{CTA_PARA}}          — 12-20 words.
* {{CTA_BUTTON_LABEL}}  — 2-3 words.
TXT,

    'html' => <<<'HTML'
[section label="L3 Intro" padding="80px" padding__sm="40px" class="cmc-section nt-l3-intro"]
[row]
[col span="10" span__sm="12" offset="1" offset__sm="0" align="center"]
<span class="nt-eyebrow">{{INTRO_EYEBROW}}</span>
<h1 class="nt-l3-h1">{{INTRO_HEADLINE}}</h1>
<p class="nt-l3-lead">{{INTRO_LEAD}}</p>
<div class="nt-l3-cta-row">[button text="{{INTRO_CTA1_LABEL}}" color="primary" radius="4" link="/shop/"] [button text="{{INTRO_CTA2_LABEL}}" style="link" link="/about-us/"]</div>
[/col]
[/row]
[/section]

[section label="L3 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l3-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l3-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L3 Studio Story" padding="48px" padding__sm="28px" class="cmc-section nt-l3-studio"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<div class="nt-story-media nt-l3-studio-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_STUDIO_URL}}" alt="{{IMG_STUDIO_ALT}}" />
</div>
[/col]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{STUDIO_EYEBROW}}</span>
<h2 class="nt-l3-h2">{{STUDIO_HEADLINE}}</h2>
<p>{{STUDIO_PARA}}</p>
<ul class="nt-l3-bullets">
<li>{{STUDIO_BULLET_1}}</li>
<li>{{STUDIO_BULLET_2}}</li>
<li>{{STUDIO_BULLET_3}}</li>
</ul>
[/col]

[/row]
[/section]

[section label="L3 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l3-cta"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<div class="nt-cta-media nt-l3-cta-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_CTA_URL}}" alt="{{IMG_CTA_ALT}}" />
</div>
[/col]

[col span="6" span__sm="12"]
<div class="nt-l3-cta-wrap">
<h2 class="nt-l3-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l3-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="primary" radius="4" link="/shop/"]
</div>
[/col]

[/row]
[/section]
HTML
,
];
