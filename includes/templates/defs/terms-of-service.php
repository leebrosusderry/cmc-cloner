<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'terms-of-service',
    'label'          => 'Terms of Service',
    'description'    => 'Acceptance, orders, payment, billing, conduct, and liability (merged with Billing Terms).',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a TERMS OF SERVICE page for {{ten_web}}.

GOAL: Produce a GMC-compliant Terms of Service grounded in the source, rephrased for uniqueness, and laid out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Primary brand color: {{primary_color}}

EFFECTIVE DATE POLICY (AUTHORITATIVE — GMC reviewers expect a visible Effective Date / Last Updated line on Terms of Service; this page MUST carry exactly one such line, set to the day this page is generated, and NO other date anywhere on the page)
- The very first line of the {{ACCEPTANCE}} slot MUST be the literal sentence: "Effective Date: {{effective_date}}" on its own paragraph (a single short line, no surrounding commentary, no "as of", no "Last revised:", no parenthetical year ranges). Use a <p> element, not a heading.
- After that one line, leave a blank line and continue the Acceptance of Terms paragraph normally.
- The Effective Date value MUST be {{effective_date}} verbatim — never any other date. Never paraphrase, never split into "Effective Month" + "Effective Year" lines, never re-format.
- NEVER write any OTHER date, month, year, quarter, season, "since <date>", "as of <date>", "in force since <date>", or version number anywhere else on the page. Only the single Effective Date line above is permitted.
- NEVER copy a date from the SOURCE PAGE below. Sources commonly carry "Effective October 2023" or "Last updated: March 2024" — IGNORE them, do not translate, do not substitute. Use ONLY {{effective_date}}.
- The "Changes to Terms" section MUST describe HOW updates take effect (revised terms posted on this page; continued use after posting constitutes acceptance) WITHOUT mentioning any date, revision frequency ("every 6 months"), version number, or "see Effective Date above" — the standalone Effective Date line at the top is sufficient on its own.

REQUIRED SECTIONS (all must appear, in this order)
1. Acceptance of Terms — start with the literal "Effective Date: {{effective_date}}" line per the EFFECTIVE DATE POLICY above, then a blank line, then state that using the site constitutes acceptance of these terms.
2. Eligibility — minimum age and capacity to enter a contract.
3. Account Responsibilities — accurate information, account security, notification of unauthorized use.
4. Orders & Acceptance — offer/acceptance mechanics. MUST state explicitly that placing an order constitutes an offer to purchase; the order becomes a binding contract only when {{ten_doanh_nghiep}} sends an order confirmation after payment processes successfully. {{ten_doanh_nghiep}} reserves the right to accept, refuse, or cancel any order for reasons including but not limited to product availability, suspected fraud, or pricing discrepancies — and in any such case the customer is refunded in full.
5. Pricing & Payment (Billing Terms) — absorbs everything formerly on a separate Billing Terms page. MUST cover, in this order: (a) prices are subject to change without notice; (b) the currency shown at checkout (use USD unless the source states another currency); (c) accepted payment methods — only list methods the source names (e.g. major credit cards, PayPal); do not invent processors; (d) payment authorization is placed at the time of order and the card is captured after order confirmation; (e) the customer confirms authority to use the payment method and the accuracy of the billing address and information; (f) applicable taxes, duties, or fees are handled only as stated in the source; (g) if a payment is declined, the order is not processed and the customer may retry; (h) recurring billing — state that {{ten_doanh_nghiep}} does not offer recurring or subscription billing unless the source explicitly says otherwise; (i) chargebacks filed without first contacting {{ten_doanh_nghiep}} at {{email_web}} may delay resolution; (j) billing disputes should be reported to {{email_web}} within the timeframe the source states, or promptly if the source is silent. When covering pricing errors, MUST state that {{ten_doanh_nghiep}} will contact the customer to confirm the corrected price OR cancel and refund the order before any charge is processed — never that the customer has no recourse.
6. Shipping & Risk of Loss — reference the Shipping Policy; title/risk passes on delivery.
7. Returns & Refunds — reference the Return Policy and Refund Policy.
8. Intellectual Property — site content, trademarks, logos are the property of {{ten_doanh_nghiep}} unless stated otherwise.
9. User Conduct & Prohibited Uses — no fraud, scraping, reverse engineering, unlawful use.
10. Disclaimers — "as is" and "as available" without warranties beyond those in the source.
11. Limitation of Liability — capped and excluded damages within the scope of the source.
12. Indemnification — user indemnifies {{ten_doanh_nghiep}} for breaches.
13. Governing Law — jurisdiction named in the source; do not invent one.
14. Changes to Terms — right to update and how changes take effect.
15. Contact — short block using {{ten_doanh_nghiep}}, {{email_web}}, {{so_dien_thoai}}, and {{dia_chi}}.

STRICT RULES
- EFFECTIVE DATE POLICY applies. Exactly ONE Effective Date line at the very top of the Acceptance of Terms section, set to "Effective Date: {{effective_date}}" verbatim. NO other date, month, year, quarter, season, or version number anywhere else on the page. Any date-like line copied from the SOURCE PAGE must be dropped, not translated or substituted.
- Do NOT invent jurisdictions, statutes, caps, or dollar limits not present in the source.
- GMC pricing compliance (critical — violation causes merchant disapproval):
  * NEVER write "we are not liable for pricing errors", "we are not responsible for typos in price", "we reserve the right to charge the corrected price", or any clause that lets the merchant unilaterally change the charge without customer consent.
  * When addressing pricing errors, use wording equivalent to: "If a pricing error is discovered after an order is placed, we will contact you to confirm the corrected price or cancel and refund your order before any charge is processed."
  * The customer must always retain the right to cancel and receive a full refund when prices change.
- Do NOT include disclaimers that override the customer's right to cancel, return, or receive a refund as defined in the Refund Policy and Return Policy.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Clear, professional English. No fluff, no emojis.
- Rewrite wording; do not copy sentences verbatim.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period. Use a generic policy name only (e.g. "Terms of Service", "Terms and Conditions"). NEVER include either {{ten_web}} OR {{ten_doanh_nghiep}} in the H1 — the contract-party disclosure (which MUST name {{ten_doanh_nghiep}} as the binding-contract party) lives in the body sections per the Orders & Acceptance rule above. A clean "Terms of Service" is GMC-standard.
- {{INTRO}} — 1–2 sentences lead paragraph.
- {{ACCEPTANCE}} — section 1 body (Acceptance of Terms). MUST begin with the literal line "Effective Date: {{effective_date}}" on its own paragraph (no surrounding commentary, no "as of", no "Last revised"), then a blank line, then a short sentence stating that using or accessing the site constitutes acceptance of these terms.
- {{ELIGIBILITY}} — section 2 body (Eligibility). Minimum age and legal capacity.
- {{ACCOUNT}} — section 3 body (Account Responsibilities).
- {{ORDERS}} — section 4 body (Orders & Acceptance). MUST state: placing an order is an offer; the contract forms when {{ten_doanh_nghiep}} confirms the order after successful payment; {{ten_doanh_nghiep}} reserves the right to accept, refuse, or cancel any order (with a full refund in that case) for reasons including availability, fraud, or pricing discrepancies.
- {{PRICING_PAYMENT}} — section 5 body (Pricing & Payment / Billing Terms). MUST cover, in order: prices subject to change; checkout currency (USD unless source differs); accepted payment methods (only those named in the source); authorization at order + capture after confirmation; customer confirms billing accuracy and authority; taxes/duties per source; declined payments = order not processed; no recurring/subscription billing unless source says so; chargebacks filed without contacting {{email_web}} may delay resolution; billing disputes via {{email_web}} within the source's stated window. MUST follow the GMC pricing-error rule: contact the customer to confirm the corrected price OR cancel and refund before any charge.
- {{SHIPPING_RISK}} — section 6 body (Shipping & Risk of Loss). Reference the Shipping Policy.
- {{RETURNS_REFUNDS}} — section 7 body (Returns & Refunds). Reference Return + Refund Policies.
- {{INTELLECTUAL_PROPERTY}} — section 8 body (Intellectual Property). Use {{ten_doanh_nghiep}}.
- {{USER_CONDUCT}} — section 9 body (User Conduct & Prohibited Uses).
- {{DISCLAIMERS}} — section 10 body (Disclaimers). "As is" / "as available".
- {{LIABILITY}} — section 11 body (Limitation of Liability). Do not override the customer's refund/cancel rights.
- {{INDEMNIFICATION}} — section 12 body (Indemnification).
- {{GOVERNING_LAW}} — section 13 body (Governing Law). Use source's jurisdiction; do not invent one.
- {{CHANGES}} — section 14 body (Changes to Terms). Describe the mechanism (revised terms posted on this page; continued use after posting constitutes acceptance). NO date, NO revision frequency, NO version number, NO "see Effective Date above" reference. The standalone Effective Date line at the top of the page is the only date marker.
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}}. No markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
