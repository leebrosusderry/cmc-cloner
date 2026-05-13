<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'payment-policy',
    'label'          => 'Payment Method',
    'description'    => 'Accepted payment methods, currency, security, billing, and fraud handling.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a PAYMENT METHOD page for an online store named {{ten_web}}.

GOAL: Produce a Google Merchant Center (GMC) compliant Payment Method page that is factually grounded in the source page, phrased differently to avoid duplicate content across cloned sites, and laid out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Response time: {{response_time}}
- Primary brand color: {{primary_color}}

REQUIRED SECTIONS (all must appear, in this order)
1. Overview — scope of this page and how it relates to the Terms of Service (Pricing & Payment section) and the Refund Policy.
2. Accepted Payment Methods — only list methods named in the source (Visa, Stripe, MasterCard, PayPal). Do NOT invent methods; do NOT list processors the source never mentions.
3. Currency — the currency displayed at checkout and whether conversion is handled by the customer's bank or the processor (only what the source states).
4. Payment Security — high-level description of how transactions are secured (encryption in transit, tokenization, PCI DSS compliance) without naming specific vendors the source does not name.
5. Billing — when the customer is charged (at order placement vs. shipment), what the descriptor on the statement looks like if the source says, and that billing address must match the payment method.
6. Failed or Declined Payments — what happens when a charge is declined, retries offered (if any), and how the customer can resolve the issue. Mention that support replies {{response_time}}.
7. Fraud Prevention — a short plain-language note that suspicious or unauthorized transactions may be held, reviewed, or cancelled, and that the customer may be contacted for verification.
8. Contact — a short block that uses {{ten_doanh_nghiep}}, {{email_web}}, {{so_dien_thoai}}, and {{dia_chi}}.

STRICT RULES
- RESPONSE-TIME PRESERVATION: the resolved value of {{response_time}} MUST appear verbatim wherever this page mentions response time. Do NOT paraphrase, reword, convert units, or substitute equivalent phrases (e.g. do NOT swap "1 business day" with "24 business hours", "next business day", "within a day", or any other variant). Insert the value exactly as configured.
- Do NOT invent payment processors, card networks, currencies, tax rates, or fraud-detection vendors not present in the source.
- Do NOT promise real-time fraud detection, chargeback protection, or security certifications the source does not claim.
- Do NOT write language that removes the customer's right to dispute a charge or request a refund under the Refund Policy.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Write in clear, professional English. No marketing fluff, no emojis, no exaggerations, no guarantees not stated in the source.
- Rewrite wording — do not copy sentences verbatim from the source.
- Keep examples industry-appropriate for {{nganh_hang}}.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period.
- {{INTRO}} — 1–2 sentences lead paragraph.
- {{OVERVIEW}} — section 1 body (Overview). Scope; point to the Terms of Service (Pricing & Payment) and Refund Policy where relevant.
- {{ACCEPTED_METHODS}} — section 2 body (Accepted Payment Methods). Only methods named in the source.
- {{CURRENCY}} — section 3 body (Currency). Which currency at checkout; who handles conversion.
- {{SECURITY}} — section 4 body (Payment Security). Encryption / tokenization / PCI DSS in plain language.
- {{BILLING}} — section 5 body (Billing). When charged, statement descriptor, billing-address match.
- {{FAILED_PAYMENTS}} — section 6 body (Failed or Declined Payments). Mention {{response_time}} naturally.
- {{FRAUD_PREVENTION}} — section 7 body (Fraud Prevention). Verification hold language.
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON below. Fill every {{PLACEHOLDER}} in the skeleton with appropriate text. Do not add sections beyond the skeleton. Do not output markdown code fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
