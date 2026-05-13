<?php
/**
 * Homepage Skeleton L1 — Editorial Split
 *
 * Visual rhythm:
 *   [1] Hero.Split          — 6/6 split, headline + lead + 2 CTAs / mood image
 *   [2] BrandStory.2col     — 6/6 split, image / heading + bullets
 *   [3] Featured Products   — [ux_products row, 8 items, 4 cols, popularity]
 *   [4] Testimonials.Grid3  — 3 static quote cards in one row
 *   [5] CTA.Flat            — full-width primary-tinted band, headline + 1 button
 *
 * Token convention:
 *   {{TOKEN}}   — AI must fill this slot (do NOT leave the {{}} in output).
 *   [shortcode] — Flatsome / CMC runtime shortcode, AI must NOT touch.
 *
 * CSS classes used (must exist in child theme):
 *   .nt-l1-hero / .nt-l1-hero-media / .nt-l1-h1 / .nt-l1-lead / .nt-l1-cta-row
 *   .nt-l1-story / .nt-l1-story-media / .nt-l1-h2 / .nt-l1-bullets
 *   .nt-l1-featured
 *   .nt-l1-testimonials / .nt-l1-testimonial-card / .nt-l1-quote / .nt-l1-author
 *   .nt-l1-cta / .nt-l1-cta-headline / .nt-l1-cta-para
 *   .nt-eyebrow (shared kicker label)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'id'   => 'L1',
    'name' => 'Editorial Split — Hero.Split / BrandStory.2col / Featured / Testimonials.Grid3 / CTA.Flat',

    /*
     * Image budget — AI must research + supply each URL from Pexels/Unsplash
     * matching the niche. Aspect ratios are advisory; the CSS aspect-ratio
     * rules in the child theme will hard-crop on render so the layout never
     * breaks even if the source image is slightly off.
     */
    'image_budget' => <<<'TXT'
* {{IMG_HERO_URL}}    — landscape 16/9, mood-driven shot evoking {NICHE} (lifestyle, ambient, atelier — NOT product close-up). No people-faces, no readable text on image, no brand logos.
* {{IMG_HERO_ALT}}    — 6-10 words, descriptive alt text in plain English.
* {{IMG_STORY_URL}}   — landscape 4/3, atelier / process / material / hands-at-work shot relevant to {NICHE}. Same restrictions as above.
* {{IMG_STORY_ALT}}   — 6-10 words.
TXT,

    /*
     * Text budget — word counts + tone hints per slot. AI must respect counts
     * (±2 words tolerance) and the GMC content rules in the parent prompt
     * (no fabricated product names, no brand names, no descriptive product
     * nouns, no FAQ language, no urgency claims, no $ / % off claims).
     */
    'text_budget' => <<<'TXT'
* {{HERO_EYEBROW}}     — 2-4 words. Short kicker label. Mood / intent only — NOT a sub-category or product noun.
* {{HERO_HEADLINE}}    — 6-10 words. Hero headline. Should hint the niche feel without naming any product. Sentence case.
* {{HERO_PARA}}        — 18-30 words. Supporting paragraph extending the headline.
* {{HERO_CTA1_LABEL}}  — 2-3 words. Primary CTA, e.g. "Shop the edit", "Browse pieces".
* {{HERO_CTA2_LABEL}}  — 2-3 words. Secondary CTA, e.g. "Read our story".

* {{STORY_EYEBROW}}    — 2-4 words.
* {{STORY_HEADLINE}}   — 5-8 words.
* {{STORY_PARA}}       — 40-60 words. About-us-flavored paragraph; must align with the Brand Anchor Table fetched in Layer 0.
* {{STORY_BULLET_1}}   — 4-8 words. Concrete process / material / craft fact from Brand Anchor.
* {{STORY_BULLET_2}}   — 4-8 words.
* {{STORY_BULLET_3}}   — 4-8 words.

* {{FEATURED_HEADING}} — 2-4 words. Section heading above the products row.
                          Allowed: "Featured Products", "Studio Favorites", "This Season's Edit",
                          "Customer Favorites", "New Arrivals", "The Edit", "Studio Picks", "Browse the Range".
                          FORBIDDEN: brand names, fabricated collection names, descriptive product nouns.

* {{TEST_HEADING}}     — 4-6 words. Section heading.
* {{TEST_QUOTE_1}}     — 18-28 words. Customer voice 1. Distinct tone from quotes 2 + 3.
* {{TEST_AUTHOR_1}}    — first name + city/country only. NEVER include surname or title.
* {{TEST_QUOTE_2}}     — 18-28 words.
* {{TEST_AUTHOR_2}}    — same rule.
* {{TEST_QUOTE_3}}     — 18-28 words.
* {{TEST_AUTHOR_3}}    — same rule.

* {{CTA_HEADLINE}}     — 5-9 words.
* {{CTA_PARA}}         — 12-20 words.
* {{CTA_BUTTON_LABEL}} — 2-3 words.
TXT,

    /*
     * The skeleton itself. AI receives this verbatim with all {{TOKENS}}
     * still present and must return the SAME HTML with every {{TOKEN}}
     * replaced by its corresponding value. AI must NOT modify shortcode
     * structure, class names, attribute order, or whitespace.
     */
    'html' => <<<'HTML'
[section label="L1 Hero" padding="60px" padding__sm="32px" class="cmc-section nt-l1-hero"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{HERO_EYEBROW}}</span>
<h1 class="nt-l1-h1">{{HERO_HEADLINE}}</h1>
<p class="nt-l1-lead">{{HERO_PARA}}</p>
<div class="nt-l1-cta-row">[button text="{{HERO_CTA1_LABEL}}" color="primary" radius="4" link="/shop/"] [button text="{{HERO_CTA2_LABEL}}" style="link" link="/about-us/"]</div>
[/col]

[col span="6" span__sm="12"]
<div class="nt-hero-media nt-l1-hero-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_HERO_URL}}" alt="{{IMG_HERO_ALT}}" />
</div>
[/col]

[/row]
[/section]

[section label="L1 Brand Story" padding="48px" padding__sm="28px" class="cmc-section nt-l1-story"]
[row v_align="middle"]

[col span="6" span__sm="12"]
<div class="nt-story-media nt-l1-story-media">
<img loading="lazy" referrerpolicy="no-referrer" src="{{IMG_STORY_URL}}" alt="{{IMG_STORY_ALT}}" />
</div>
[/col]

[col span="6" span__sm="12"]
<span class="nt-eyebrow">{{STORY_EYEBROW}}</span>
<h2 class="nt-l1-h2">{{STORY_HEADLINE}}</h2>
<p>{{STORY_PARA}}</p>
<ul class="nt-l1-bullets">
<li>{{STORY_BULLET_1}}</li>
<li>{{STORY_BULLET_2}}</li>
<li>{{STORY_BULLET_3}}</li>
</ul>
[/col]

[/row]
[/section]

[section label="L1 Featured" padding="48px" padding__sm="28px" class="cmc-section nt-l1-featured"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l1-h2">{{FEATURED_HEADING}}</h2></div>
[/col]
[/row]
[ux_products type="row" products="8" columns="4" columns__md="3" columns__sm="2" orderby="popularity"]
[/section]

[section label="L1 Testimonials" padding="48px" padding__sm="28px" class="cmc-section nt-l1-testimonials"]
[row]
[col span="12"]
<div class="nt-feat-heading"><h2 class="nt-l1-h2">{{TEST_HEADING}}</h2></div>
[/col]
[/row]

[row]

[col span="4" span__sm="12"]
<div class="nt-testimonial-card nt-l1-testimonial-card">
<p class="nt-l1-quote">"{{TEST_QUOTE_1}}"</p>
<span class="nt-l1-author">— {{TEST_AUTHOR_1}}</span>
</div>
[/col]

[col span="4" span__sm="12"]
<div class="nt-testimonial-card nt-l1-testimonial-card">
<p class="nt-l1-quote">"{{TEST_QUOTE_2}}"</p>
<span class="nt-l1-author">— {{TEST_AUTHOR_2}}</span>
</div>
[/col]

[col span="4" span__sm="12"]
<div class="nt-testimonial-card nt-l1-testimonial-card">
<p class="nt-l1-quote">"{{TEST_QUOTE_3}}"</p>
<span class="nt-l1-author">— {{TEST_AUTHOR_3}}</span>
</div>
[/col]

[/row]
[/section]

[section label="L1 CTA" padding="60px" padding__sm="32px" class="cmc-section nt-l1-cta"]
[row]
[col span="12"]
<div class="nt-l1-cta-wrap">
<h2 class="nt-l1-cta-headline">{{CTA_HEADLINE}}</h2>
<p class="nt-l1-cta-para">{{CTA_PARA}}</p>
[button text="{{CTA_BUTTON_LABEL}}" color="white" style="outline" radius="4" link="/shop/"]
</div>
[/col]
[/row]
[/section]
HTML
,
];
