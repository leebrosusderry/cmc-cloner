<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'faq',
    'label'          => 'FAQ',
    'description'    => 'Common questions covering orders, shipping, returns, and support. GMC-aligned — answers cannot contradict the Shipping or Return & Refund Policy pages.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce copywriter rewriting an FAQ page for {{ten_web}}.

GOAL: Produce a Google Merchant Center (GMC) compliant FAQ page covering the topics a shopper at a {{nganh_hang}} store would ask about. The FAQ is a TRUST page — its answers MUST NOT contradict the Shipping Policy page or the Return & Refund Policy page on the same site. Ground answers in the source page where possible, rephrase for uniqueness, and lay the content out using the supplied Flatsome skeleton (typically an accordion or Q&A list).

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Phone: {{so_dien_thoai}}
- Email: {{email_web}}
- Support hours: {{gio_lam_viec}}
- Timezone: {{timezone}}    (auto-extracted from the parenthetical inside Support hours; empty when not configured)
- Response time: {{response_time}}
- Primary brand color: {{primary_color}}

SUBJECT-NAMING RULE: every Q/A about a legal commitment (refund, return liability, lost-package responsibility, who pays for shipping) MUST name {{ten_doanh_nghiep}} as the responsible party — never "we" alone, and never the store name {{ten_web}}. Q/A about products / brand identity / customer-facing operations may use {{ten_web}} or "we" naturally.

GMC STANDARDS SNAPSHOT (AUTHORITATIVE — these resolved values MUST appear literally in answers wherever the topic comes up; if the source FAQ states different numbers, rules, or shipping-responsibility statements, IGNORE the source and use these values so the FAQ matches Google Merchant Center and the site's own Shipping / Return & Refund pages exactly)

Shipping (from the site's Shipping Policy)
- Regions served: United States only. No international shipping.
- Currency: USD.
- Shipping services offered: Standard Shipping only. There is NO expedited, overnight, or multi-option shipping.
- Order cut-off time: {{shipping_cutoff_time}}. Orders placed before the cut-off are processed the same business day; orders placed after the cut-off are processed the next business day.
- Handling time: {{shipping_handling_time}} from order confirmation to dispatch.
- Transit time: {{shipping_transit_time}} from dispatch to delivery.
- Total estimated delivery: {{shipping_total_delivery}}, weekends and U.S. public holidays excluded.
- Free shipping: orders with a subtotal at or above {{free_shipping_threshold}} receive free Standard Shipping.
- Flat fee: orders with a subtotal below {{free_shipping_threshold}} are charged a flat Standard Shipping fee of {{below_threshold_shipping_fee}}.
- Origin: we ship from a U.S.-based fulfilment location.

Returns & Refunds (from the site's Return & Refund Policy)
- Return window: {{return_window}} from the date of delivery.
- Returns scope: Defective AND non-defective products are both accepted. Do NOT narrow this to "defective or damaged only".
- Product condition required: Only new products — unopened, in original packaging, unused, with all tags attached.
- Return method: By mail only. Do NOT mention in-store returns or drop-off locations.
- Return shipping: once the return request is approved, a prepaid return shipping label is emailed to the customer (the label is NEVER pre-included inside the original outbound package). {{ten_doanh_nghiep}} covers 100% of return shipping. The customer does NOT pay for return shipping.
- Restocking fee: No restocking fee.
- Exchanges: Yes, accepted within the same {{return_window}} window from delivery.
- Refund method: original payment method, in USD.
- Refund processing time: {{refund_processing_time}}. The customer's bank may add 1–2 additional business days before the refund appears on the statement.
- Original outbound shipping fees are refundable only when the return is due to an error on {{ten_doanh_nghiep}}'s part.

Policy page names to link from FAQ answers
- The correct name for the returns page is exactly "Return & Refund Policy" (a SINGLE combined page). Do NOT write "Return Policy" or "Refund Policy" as separate page names — those pages no longer exist on the site. Use "See our Return & Refund Policy for full terms." as the standard pointer.
- The shipping page name is "Shipping Policy". Use "See our Shipping Policy for full timelines."
- The cancellation page name is "Cancellation Policy". Use "See our Cancellation Policy for full terms."
- The privacy page is "Privacy Policy". Cookie Policy is intentionally NOT referenced from FAQ — see the COOKIE SCOPE rule in STRICT RULES below.

REQUIRED SECTIONS
Produce between 10 and 14 question-answer pairs, grouped under the categories below. EVERY category must be represented by at least one Q/A (generic but accurate if the source is thin) because GMC reviewers look for each of these topics on the FAQ page:
1. Orders — placing, editing, and cancelling before dispatch. Cancellation request must reach support before the {{shipping_cutoff_time}} cut-off on the dispatch day; point to the Cancellation Policy for full terms.
2. Shipping & Delivery — MUST include at least the following four Q/A:
   (a) "Where do you ship to?" — United States only; no international shipping.
   (b) "When will my order ship and how long does delivery take?" — handling {{shipping_handling_time}}, transit {{shipping_transit_time}}, total {{shipping_total_delivery}}. Orders placed before {{shipping_cutoff_time}} are processed the same business day.
   (c) "What shipping options do you offer?" — Standard Shipping only. Do NOT mention expedited, overnight, or multiple shipping methods.
   (d) "Do you offer free shipping?" — Yes; orders with a subtotal at or above {{free_shipping_threshold}} qualify for free Standard Shipping. Below that threshold a flat Standard Shipping fee of {{below_threshold_shipping_fee}} applies. All prices in USD.
   Each answer MUST close by deferring to the Shipping Policy for full details.
3. Returns, Exchanges & Refunds — MUST include at least the following four Q/A:
   (a) "What is your return window?" — {{return_window}} from the date of delivery, for both defective and non-defective products, in new and unused condition in original packaging.
   (b) "Who pays for return shipping?" — {{ten_doanh_nghiep}} covers it. Once the return request is approved, a prepaid return shipping label is emailed to the customer (it is NEVER pre-included inside the original outbound package); the customer does NOT pay for return shipping. No restocking fee.
   (c) "Do you accept exchanges?" — Yes, exchanges are accepted within the same {{return_window}} window from delivery for new, unused products.
   (d) "How long does a refund take?" — Refunds are issued to the original payment method in USD {{refund_processing_time}}; the bank may add 1–2 additional business days before the refund appears on the statement.
   Each answer MUST close by deferring to the Return & Refund Policy for full terms.
4. Payments — MUST include at least the following Q/A:
   (a) "What payment methods are accepted?" — the answer MUST be exactly: "We accept the following payment methods: Visa, MasterCard, Stripe, and PayPal." Do NOT add hedges, alternative phrasings, additional methods, or extra sentences before/after this line — output it verbatim so it matches the Payment Method page exactly.
   At least one additional Payments Q/A MUST confirm USD billing and encrypted/HTTPS checkout and close by pointing to the Privacy Policy for how data is handled.
5. Security & Privacy — a short Q/A confirming the checkout uses industry-standard encryption (TLS/HTTPS) and that personal data is handled per the Privacy Policy (GDPR and CCPA rights covered). Close with a pointer to the Privacy Policy ONLY. Do NOT mention cookies, tracking pixels, cookie banner, cookie preferences, or any Cookie Policy page anywhere in the answer (some clones don't have that page, and the topic is out of scope for FAQ regardless).
6. Account — creating an account, password reset, optional guest checkout if the source mentions it.
7. Products — sizing, materials, availability, care (industry-appropriate for {{nganh_hang}}). Keep answers generic; do NOT invent SKUs, specific dimensions, or brand names.
8. Support — how to reach the team, business hours, expected response time. Use {{email_web}}, {{so_dien_thoai}}, and {{gio_lam_viec}} (preserving any timezone abbreviation already inside that value, e.g. "(MST)") with the response time of {{response_time}}.

QUESTION STYLE
- Each Question: short, natural, in the shopper's voice (e.g., "How long does shipping take?").
- Each Answer: 1 to 4 sentences, direct and factual. When the full answer lives on a policy page, the answer MUST end with a pointer such as "See our Shipping Policy for full timelines." / "See our Return & Refund Policy for full terms." / "See our Cancellation Policy for full terms." / "See our Privacy Policy for details." Do NOT pair the Privacy Policy pointer with a Cookie Policy pointer (see COOKIE SCOPE rule below).
- Use {{email_web}} and {{so_dien_thoai}} when pointing to support.
- Keep the overall tone neutral and informational — FAQ is a trust/compliance page, not a sales page.

STRICT RULES (GMC compliance)
- TIMEZONE PRESERVATION: every mention of {{shipping_cutoff_time}} or {{gio_lam_viec}} MUST keep any timezone abbreviation already inside those values (e.g. "(MST)", "(PST)", "(UTC+7)"). Never paraphrase or strip the timezone parenthetical. If {{timezone}} is non-empty, the FAQ MAY include exactly one short sentence (in the Support category or the first Shipping Q/A) stating that all stated times refer to {{timezone}} (literal value, e.g. "All times refer to MST."). If {{timezone}} is empty, do NOT invent one — just output the cut-off / hours without a timezone qualifier.
- AUTHORITATIVE VALUES: the GMC STANDARDS SNAPSHOT above is AUTHORITATIVE. If the source FAQ states different days, shipping methods, return scopes, or payment responsibilities, IGNORE the source and use the snapshot values. An FAQ that contradicts the Shipping Policy or Return & Refund Policy is treated by GMC as "Misrepresentation" and may cause account suspension.
- LITERAL VALUE INSERTION: every answer that touches a snapshot topic MUST contain the literal resolved value of the corresponding placeholder — {{shipping_cutoff_time}}, {{shipping_handling_time}}, {{shipping_transit_time}}, {{shipping_total_delivery}}, {{free_shipping_threshold}}, {{below_threshold_shipping_fee}}, {{return_window}}, {{refund_processing_time}} — and the literal strings "United States", "USD", "Standard Shipping". Do NOT output the "{{...}}" tokens verbatim; substitute the resolved values.
- RESPONSE-TIME PRESERVATION: the resolved value of {{response_time}} MUST appear verbatim wherever the FAQ mentions response time (typically in the Support section). Do NOT paraphrase, reword, convert units, or substitute equivalent phrases (e.g. do NOT swap "1 business day" with "24 business hours", "next business day", "within a day", or any other variant). Insert the value exactly as configured.
- FORBIDDEN phrases (do NOT produce any variant of these, because they contradict GMC or mislead shoppers):
   * any transit or total-delivery window that contradicts {{shipping_transit_time}} / {{shipping_total_delivery}} (e.g. faster or slower than the resolved values)
   * "depending on the selected shipping method", "shipping method you choose", "various shipping options", "standard, expedited, or overnight", or any phrasing implying multiple shipping services
   * "expedited shipping", "overnight shipping", "next-day delivery", "same-day delivery"
   * "international shipping", "we ship worldwide", "ships to [any non-US country]"
   * "various locations", "multiple warehouses worldwide", "ships from overseas" — we ship from a U.S. fulfilment location
   * "customers are responsible for return shipping", "you pay return shipping", "return shipping is deducted from your refund", "use your own label"
   * "restocking fee", "a small restocking fee applies" (there is NONE)
   * "defective or damaged only", "returns only for defective items" (GMC accepts BOTH defective and non-defective)
   * "exchanges are not offered", "we do not accept exchanges" (exchanges are Yes)
   * "Return Policy" or "Refund Policy" as if they are separate pages — the correct name is "Return & Refund Policy"
   * "instant refund", "same-day refund", or any refund window faster than {{refund_processing_time}}
   * any return-window value that contradicts {{return_window}} (e.g. "14 days" when the snapshot says "30 days")
- Do NOT invent specific days, hours, prices, discounts, carriers, countries, or products not present in the snapshot or the source. The accepted payment methods are fixed (Visa, MasterCard, Stripe, PayPal) per REQUIRED SECTIONS 4(a) and MUST be named verbatim there; do NOT introduce additional card brands, gateways, or wallets beyond those four.
- Do NOT promise "free shipping for all orders", "fastest delivery", "guaranteed delivery", "100%", "lowest price", or any absolute guarantee. Free standard shipping only applies at or above {{free_shipping_threshold}}.
- Do NOT use urgency/sales language ("limited time", "today only", "act now", "!!", ALL-CAPS sentences) or emojis.
- Do NOT claim certifications, awards, trust-seals, or third-party endorsements not present in the source.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- For legal-commitment Q/As (refund, return liability, lost packages, who pays return shipping), name {{ten_doanh_nghiep}} as the responsible party.
- If the source page contains any email, phone, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- For any question whose full answer is a policy (shipping / returns & refunds / cancellation / privacy), keep the FAQ answer consistent with the GMC STANDARDS SNAPSHOT and always close with a pointer to the relevant policy page by its correct name.
- COOKIE SCOPE (AUTHORITATIVE): the FAQ MUST NOT mention cookies, tracking cookies, cookie banner, cookie preferences, cookie consent, or link to a Cookie Policy page anywhere. Not all clones generate a Cookie Policy page, and the topic is out of FAQ scope regardless — privacy-adjacent questions are answered with a single pointer to the Privacy Policy. This applies to every category, including Security & Privacy.
- Clear, professional English. Rewrite wording; do not copy sentences verbatim from the source.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below is either the page header or one side of a Q/A pair — do NOT swap content across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period. Use a generic friendly title only (e.g. "Frequently Asked Questions", "FAQ", "Help Center FAQ", "Common Questions"). NEVER include either {{ten_web}} OR {{ten_doanh_nghiep}} in the H1 — FAQ is customer-facing but the heading itself should be generic and instantly recognisable. A clean "Frequently Asked Questions" is GMC-standard.
- {{INTRO}} — 1–2 sentences lead paragraph.
- {{Q_ORDER_*}} — questions in the Orders & Checkout category. Use the shopper's voice, end with "?".
- {{A_ORDER_*}} — matching answers, 1–4 sentences, factual. MUST cover cancelling-before-dispatch in at least one slot, referencing the {{shipping_cutoff_time}} cut-off (with timezone preserved) and pointing to the Cancellation Policy page.
- {{Q_SHIP_*}} / {{A_SHIP_*}} — Shipping & Delivery Q/A. Answers MUST reflect the GMC STANDARDS SNAPSHOT (United States only, Standard Shipping only, handling {{shipping_handling_time}}, transit {{shipping_transit_time}}, total {{shipping_total_delivery}}, cut-off {{shipping_cutoff_time}}, free-shipping threshold {{free_shipping_threshold}} with flat fee {{below_threshold_shipping_fee}} below it) and close with a pointer to the Shipping Policy. At least one Q/A MUST be the free-shipping question described in REQUIRED SECTIONS 2(d).
- {{Q_RETURN_*}} / {{A_RETURN_*}} — Returns, Exchanges & Refunds Q/A. Answers MUST reflect the GMC STANDARDS SNAPSHOT (return window {{return_window}}, defective AND non-defective, prepaid return label at no cost to the customer covered by {{ten_doanh_nghiep}}, no restocking fee, exchanges accepted within the same window, refund to original payment method {{refund_processing_time}} + 1–2 bank days) and close with a pointer to the Return & Refund Policy. At least one Q/A MUST be "How long does a refund take?" with the {{refund_processing_time}} answer.
- {{Q_PAY_*}} / {{A_PAY_*}} — Payments & Security Q/A. The FIRST Q_PAY slot MUST be "What payment methods are accepted?" and its A_PAY MUST be exactly the verbatim string "We accept the following payment methods: Visa, MasterCard, Stripe, and PayPal." (no extra sentences, no rephrasing, no closing pointer). At least one OTHER Payments Q/A MUST confirm USD billing and encrypted/HTTPS checkout and point to the Privacy Policy for how data is handled. Do NOT name additional card brands or gateways beyond Visa, MasterCard, Stripe, and PayPal.
- Every Q_* slot must contain a question ending with "?"; every A_* slot must contain the answer to the question directly above it. Do NOT leave a slot empty — if the source is thin on that category, write a generic but accurate Q/A appropriate for {{nganh_hang}} at the SCOPE breadth appended below, and keep it compliant with the STRICT RULES above (no invented days, prices, carriers, brands, or any FORBIDDEN phrase).

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}} in the skeleton with a question or an answer as appropriate. No markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
