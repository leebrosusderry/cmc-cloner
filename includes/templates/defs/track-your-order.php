<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'track-your-order',
    'label'          => 'Track Your Order',
    'description'    => 'Order tracking form + contact. Deliberately minimal (no tracking FAQ, no timeframes, no status glossary).',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a TRACK YOUR ORDER page for an online store named {{ten_web}}.

GOAL: Produce a Google Merchant Center (GMC) compliant order-tracking page that is DELIBERATELY MINIMAL. Only two things appear on the page: (1) the WooCommerce order-tracking form under the heading "Check Your Order Status"; (2) a Contact block at the bottom. Do NOT generate an Overview, a "When Tracking Is Available" section, a Where-to-find-order-details section, a tracking-statuses glossary, shipping timeframes, missing/delayed-packages guidance, or a lost-packages section — those topics belong on the Shipping Policy and FAQ pages.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Support hours: {{gio_lam_viec}}
- Timezone: {{timezone}}    (auto-extracted from the parenthetical inside Support hours; empty when not configured)
- Response time: {{response_time}}
- Primary brand color: {{primary_color}}

REQUIRED SECTIONS (ONLY these — do NOT add others)
1. Page header — the H1 and a single-sentence lead that invites the customer to enter their Order ID in the form below.
2. Check Your Order Status — a heading plus the literal shortcode [woocommerce_order_tracking]. The skeleton already contains this block; preserve it exactly as provided.
3. Contact — a short block telling the customer they can reach the {{ten_web}} support team at {{email_web}} or {{so_dien_thoai}} during {{gio_lam_viec}}, with a typical response time of {{response_time}}. PRESERVE any timezone abbreviation already inside {{gio_lam_viec}} (e.g. "(MST)") verbatim — do NOT paraphrase or strip it.

STRICT RULES
- TIMEZONE PRESERVATION: every mention of {{gio_lam_viec}} MUST keep any timezone abbreviation already inside that value (e.g. "(MST)", "(PST)", "(UTC+7)") verbatim. Never paraphrase the business hours into a shorter form that strips the timezone parenthetical.
- RESPONSE-TIME PRESERVATION: the resolved value of {{response_time}} MUST appear verbatim wherever this page mentions response time. Do NOT paraphrase, reword, convert units, or substitute equivalent phrases (e.g. do NOT swap "1 business day" with "24 business hours", "next business day", "within a day", or any other variant). Insert the value exactly as configured.
- The skeleton includes a literal [woocommerce_order_tracking] shortcode block. Preserve it exactly where it appears; never rewrite, translate, or paraphrase it, and never remove it.
- Do NOT describe the tracking form's fields ("enter your order number", "billing email") — the form above already does that.
- Do NOT invent carriers, tracking URLs, specific day counts, or delivery guarantees.
- Do NOT add any section beyond the three listed above. If the source page has extra content (FAQs, timelines, status tables), IGNORE it — this page is deliberately minimal.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Write in clear, professional English. No marketing fluff, no emojis, no exaggerations, no guarantees not stated in the source.
- Rewrite wording — do not copy sentences verbatim from the source.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period (e.g. "Track Your Order", "Order Tracking", "Where's My Order?"). NEVER include either {{ten_web}} OR {{ten_doanh_nghiep}} in the H1 — keep the title generic so the tracking form below reads as a clean utility page. Brand voice belongs in the body if needed.
- {{INTRO}} — exactly one short sentence inviting the customer to use the form below. No list, no multi-paragraph intro.
- If the source is rich, still keep this page minimal: everything else goes on Shipping Policy or FAQ.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON below. Fill every {{PLACEHOLDER}} in the skeleton. Do not add sections beyond the skeleton. Do not output markdown code fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
