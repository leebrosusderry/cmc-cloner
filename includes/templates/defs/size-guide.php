<?php
/**
 * Size Guide — niche-specific GMC-required page for apparel / footwear /
 * jewelry / kids fashion clones.
 *
 * Architecture note:
 *   - `is_optional = true` means the bulk-generate UI hides this template
 *     when the configured industry doesn't match `applies_to_keywords`.
 *   - The size chart itself lives STATIC in the skeleton (industry-standard
 *     measurements — XS through XXL with bust / waist / hip in inches +
 *     cm). The AI only fills the heading / intro / outro slots so each
 *     cloned site gets a uniquely worded wrapper around the same factual
 *     table. Numbers are facts, not opinions — GMC does not flag standard
 *     measurement charts as duplicate content.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'             => 'size-guide',
    'label'            => 'Size Guide',
    'description'      => 'GMC-compliant size chart for apparel / footwear / kids fashion stores. Auto-skipped on non-fashion clones.',
    'is_optional'      => true,
    // Keyword match (case-insensitive substring) against the configured
    // {{nganh_hang}} value. Any hit → page is offered for this site.
    // Tuning notes:
    //   - "wear" covers "streetwear", "footwear", "menswear", "swimwear"
    //   - "shirt" / "pant" / "jean" / "dress" / "suit" catch specific
    //     apparel niches that don't include the word "fashion".
    //   - "shoe" matches "shoes", "shoe store", etc.
    //   - "baby" / "kids" / "maternity" pull in age-segmented apparel.
    'applies_to_keywords' => [
        'fashion', 'apparel', 'clothing', 'wear', 'shoe', 'shirt',
        'pant', 'jean', 'dress', 'suit', 'sock', 'underwear',
        'baby', 'kids', 'maternity',
    ],
    'skeletons'        => [ 'skeleton-1' ],
    'default_prompt'   => <<<'PROMPT'
ROLE: You are a senior e-commerce copywriter producing a SIZE GUIDE page for the online store named {{ten_web}}.

GOAL: Fill the LAYOUT SKELETON below. The skeleton already contains a STATIC size chart (industry-standard measurements in inches + cm) — your only job is to wrap that chart with a short, generic-but-helpful heading, intro, and outro. NEVER replace, reformat, or reproduce the chart; output it verbatim as it appears in the skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}

STRICT RULES
- Output ONLY the final Flatsome UX Builder shortcode layout from LAYOUT SKELETON, with every {{PLACEHOLDER}} filled in. No markdown fences, no commentary, no preface.
- The size chart inside the skeleton MUST appear verbatim — same rows, same columns, same numbers. Do NOT add a 7th size, do NOT rephrase a column header, do NOT translate units.
- Plain language in heading / intro / outro. NO marketing fluff ("perfect fit", "premium quality"), NO shipping or return promises, NO guarantees, NO emojis.
- DO NOT name any specific clothing item, brand, character, or franchise.
- DO NOT mention any sub-niche of {{nganh_hang}} (e.g. write nothing dress-specific if the chart applies to all clothing).
- Use {{ten_web}} for the store name; never copy the source page's store name.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON must be filled exactly per these rules)
- {{HEADING}} — page H1, 3 to 5 words, no trailing period. Examples: "Size Guide", "Size Chart and Fit Guide", "Sizing Information", "How to Find Your Size".
- {{INTRO}} — 2 to 3 short sentences (≈40–70 words). Position the chart below as a general reference. State that it covers most everyday items in our line-up, and that customers who fall between sizes should choose the larger size for a more relaxed fit. Use {{ten_web}} naturally once. Plain prose, no lists.
- {{OUTRO}} — 1 to 2 short sentences (≈25–40 words). Tell customers the best way to measure themselves (soft tape, over light clothing, snug but not tight), and that they can email {{email_web}} with any sizing question. Plain prose, no lists.

OUTPUT
Return ONLY the filled-in LAYOUT SKELETON below. Do NOT add markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE (only used as a fallback for tone — most stores will have no existing size-guide source; the static chart is the source of truth either way)
{{PAGE_HTML}}
PROMPT,
];
