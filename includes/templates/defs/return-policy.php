<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'return-policy',
    'label'          => 'Return & Refund Policy',
    'description'    => 'Combined Return & Refund Policy that matches the single Return-policy URL required by Google Merchant Center.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer producing a single combined RETURN & REFUND POLICY page for an online store named {{ten_web}}.

GOAL: Produce one Google Merchant Center (GMC) compliant "Return & Refund Policy" page that replaces the old split Return Policy + Refund Policy pages. GMC requires ONE return-policy URL, so this page is the single source of truth for everything related to returns, exchanges, and refunds. The content must be factually grounded in the source page, rephrased for uniqueness, strictly aligned with the GMC RETURN STANDARDS block below, and laid out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Response time: {{response_time}}
- RMA issuance time: {{rma_issuance_time}}
- Primary brand color: {{primary_color}}

GMC RETURN STANDARDS (AUTHORITATIVE — these values MUST appear literally in the generated copy; they mirror the Google Merchant Center return configuration and override any different numbers, rules, or shipping-responsibility statements in the source)
- Regions served: United States only. Currency: USD. All refunds are issued in USD to the original payment method.
- Returns scope: Defective and non-defective products are both accepted.
- Exchanges: Yes, exchanges are accepted within the same return window.
- Product condition required: Only new products — unopened, in original packaging, unused, with all tags attached. Items that show signs of use or damage are not eligible.
- Return window: {{return_window}} from the date of delivery.
- Return method: By mail only. Do NOT mention in-store returns, drop-off locations, or carrier retail networks anywhere on the page.
- Return label: A prepaid return shipping label is EMAILED to the customer within {{rma_issuance_time}} AFTER {{ten_doanh_nghiep}} has reviewed and approved the return request. The label is NEVER pre-included inside the original package (the original package leaves the warehouse before there is any return). {{ten_doanh_nghiep}} covers 100% of return shipping — the customer does NOT pay for return shipping under any circumstance.
- Restocking fee: No restocking fee. Customers get a full refund of the product price for eligible returns.
- Refund method: Refunds are issued to the original payment method used at checkout (credit card, debit card, PayPal, etc.).
- Refund processing time: {{refund_processing_time}} after {{ten_doanh_nghiep}} receives and inspects the returned item. Banks may add an additional 1–2 business days before the refund appears on the statement.
- Original outbound shipping fees are refundable only when the return is caused by an error on {{ten_doanh_nghiep}}'s part (wrong, defective, or damaged item). Otherwise the original shipping fee is non-refundable; the product price is refunded in full.

REQUIRED SECTIONS (all must appear, in this order — this is a COMBINED page covering both returns and refunds)
1. Overview — one-paragraph summary stating {{ten_web}} ships to the United States, offers a {{return_window}} return window on new, unused products, accepts exchanges, provides a prepaid return label at no cost to the customer, charges no restocking fee, and refunds to the original payment method {{refund_processing_time}}. Mention this page is the single source of truth for both returns and refunds.
2. Return Eligibility — write full sentences conveying: only new, unused products in their original packaging with all tags attached are eligible; items showing signs of use or damage are not; both defective and non-defective products are accepted. Do NOT emit "Only new products" as a clipped fragment / bullet label — weave it into a complete sentence.
3. Return Window — the literal value of {{return_window}} (e.g. "30 days", "100 nights") MUST appear verbatim inside the prose, framed as a hard cutoff measured from the date of delivery. Do NOT round or paraphrase the duration. Express as a sentence ("Returns must be initiated within {{return_window}} from the date of delivery."), NOT as a label like "30 days return window".
4. Non-Returnable / Non-Refundable Items — explicit list from the source (gift cards, final-sale items, custom/personalized products, items showing signs of use or damage, etc.). If source is silent, provide a short generic list.
5. Exchange Option — affirm in a full sentence that exchanges are accepted within the same {{return_window}} window from delivery, for new and unused products. Do NOT reduce exchanges to "handled as a return followed by a new order".
6. Return Method, Label & RMA — write FULL SENTENCES (never 2-3 word fragments) conveying: returns are accepted by mail; once the return request is approved, the RMA number and a prepaid return shipping label are EMAILED to the customer within {{rma_issuance_time}} (the prepaid label is NEVER pre-included inside the original outbound package, because the original package ships before any return is initiated); {{ten_doanh_nghiep}} covers 100% of return shipping; there is no restocking fee. Do NOT mention in-store returns, retail-network returns, drop-off locations, or carrier counters — even to deny them. The page must read as a positive statement of the mail-based process; never explain what we don't accept.
7. How to Initiate a Return or Exchange — numbered step-by-step process: (a) email {{email_web}} with the order ID and reason within the {{return_window}} window from delivery; (b) support replies {{response_time}}; (c) once the return request is approved, the RMA number and prepaid return shipping label are EMAILED to the customer within {{rma_issuance_time}}; (d) pack the item in its original packaging and ship back via the emailed prepaid label by mail; (e) once received and inspected, a refund or exchange is processed.
8. Refund Method & Processing Time — MUST state literally: refunds are issued to the original payment method in USD, {{refund_processing_time}} after {{ten_doanh_nghiep}} receives and inspects the returned item; the customer's bank may add 1–2 additional business days before the refund appears on the statement.
9. Shipping Cost Refunds — one short paragraph: the product price is refunded in full (no restocking fee); the original outbound shipping fee is refundable only when the return is caused by an error on {{ten_doanh_nghiep}}'s part (wrong, defective, or damaged item); return shipping is always free via the prepaid label.
10. Contact — short block using {{ten_doanh_nghiep}}, {{email_web}}, {{so_dien_thoai}}, and {{dia_chi}}.

STRICT RULES (GMC compliance)
- The GMC RETURN STANDARDS block above is AUTHORITATIVE. If the source page states different numbers, shipping responsibility, restocking fees, or refund timing, IGNORE the source and use the standards above so the generated page matches Google Merchant Center exactly.
- VERBATIM literals (these strings MUST appear word-for-word at least once, woven naturally into prose):
   * "United States"
   * "USD"
   * "{{return_window}} from the date of delivery"
   * "{{refund_processing_time}}" (in the refund processing-time context)
- SEMANTIC content requirements (each MUST be expressed as a complete sentence — NEVER as a 2-3 word fragment or bullet label; reviewers flag clipped fragments like "Only new products", "By mail only", "30 days return window" as unprofessional). Write full sentences that convey:
   * Eligibility — only new, unused products in their original packaging with all tags attached are accepted; items showing signs of use or damage are not eligible.
   * Return method — returns are accepted by mail. State this as a positive sentence ("Returns are processed by mail."); do NOT mention in-store drop-offs, retail-network returns, or carrier counters even to deny them — the GMC RETURN STANDARDS block forbids naming those channels anywhere on the page.
   * Prepaid return label — once the return request is approved, a prepaid return shipping label is emailed to the customer (it is NEVER pre-included inside the original outbound package, because the original package ships before any return is initiated).
   * Cost responsibility — {{ten_doanh_nghiep}} covers 100% of return shipping; the customer never pays.
   * No restocking fee — every eligible return is refunded in full at the product price, with no restocking deduction.
   * Refund destination — refunds are issued to the original payment method used at checkout (credit card, debit card, PayPal, etc.).
- Fragment ban (hard): do NOT emit the policy facts as clipped headings, table-cell labels, or 2-3 word phrases. Every fact lives inside a grammatically complete sentence with subject + verb + clear object. Two such facts may share one sentence when natural ("Returns are processed by mail, and the prepaid label is emailed once the request is approved.").
- RESPONSE-TIME PRESERVATION: the resolved value of {{response_time}} MUST appear verbatim wherever this page mentions response time. Do NOT paraphrase, reword, convert units, or substitute equivalent phrases (e.g. do NOT swap "1 business day" with "24 business hours", "next business day", "within a day", or any other variant). Insert the value exactly as configured.
- Do NOT write any sentence stating the customer is responsible for return shipping costs, must use their own carrier, must pay for a return label, or that return shipping is deducted from the refund. The prepaid return label is provided by {{ten_doanh_nghiep}} at no cost to the customer.
- Do NOT mention in-store returns, drop-off locations, expedited returns, or international returns — the store accepts returns by mail from U.S. customers only.
- Do NOT invent a different return-window day count, carrier name, tracking URL, or restocking percentage.
- Do NOT split the content into references to a separate "Refund Policy" page — this IS the combined Return & Refund Policy. Refund method, timing, and shipping-refund rules live on this page.
- Do NOT promise refund windows shorter than "{{refund_processing_time}}" (e.g. "instant", "same-day refunds") or longer than stated. The allowed phrasing is exactly "{{refund_processing_time}}" or a close equivalent.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Clear, professional English. No fluff, no emojis, no guarantees not in the standards.
- Rewrite wording; do not copy sentences verbatim from the source.
- Examples must suit a {{nganh_hang}} store.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period. Must reflect this is a combined "Return & Refund Policy" page (e.g. "Return & Refund Policy", "Returns and Refunds").
- {{INTRO}} — 1–2 sentences lead paragraph. MUST mention United States, {{return_window}} return window, prepaid return label at no cost to the customer, and refunds to the original payment method.
- {{OVERVIEW}} — section 1 body (Overview). Use the GMC RETURN STANDARDS values in a natural paragraph. Explicitly state this page covers BOTH returns and refunds.
- {{ELIGIBILITY}} — section 2 body (Return Eligibility). Write as full sentences (no 2-3 word fragments / table-cell labels). Must convey: only new, unused products in their original packaging with all tags attached are accepted; items showing signs of use or damage are not eligible; both defective and non-defective products are accepted.
- {{RETURN_WINDOW}} — section 3 body (Return Window). Include "{{return_window}} from the date of delivery" literally.
- {{NON_RETURNABLE}} — section 4 body (Non-Returnable / Non-Refundable Items). Combined list covering items that cannot be returned AND items that cannot be refunded.
- {{EXCHANGE}} — section 5 body (Exchange Option). Affirm in a full sentence that exchanges are accepted within the same {{return_window}} window from delivery for new, unused products.
- {{RETURN_SHIPPING}} — section 6 body (Return Method, Label & RMA). Write FULL SENTENCES (no 2-3 word fragments). Must convey: returns are accepted by mail; once the return request is approved, the prepaid return shipping label is emailed to the customer within {{rma_issuance_time}} (NEVER pre-included in the original outbound package); {{ten_doanh_nghiep}} covers 100% of return shipping and the customer never pays; no restocking fee applies. Do NOT mention in-store drop-offs, retail-network returns, or drop-off locations — even as denials.
- {{HOW_TO_RETURN}} — section 7 body (How to Initiate a Return or Exchange). Numbered steps; mention {{response_time}}, {{rma_issuance_time}}, and the mail-only return method.
- {{REFUND_LINK}} — this slot is repurposed for the COMBINED page: fill it with the full Refund Method & Processing Time section PLUS the Shipping Cost Refunds paragraph (required sections 8 + 9). MUST state literally: refunds to the original payment method in USD, "{{refund_processing_time}}"; bank may add 1–2 business days; product price refunded in full (no restocking fee); original outbound shipping fee refundable only on store error; return shipping always free via prepaid label. Do NOT write "see our Refund Policy page" — there is no separate Refund Policy page anymore.
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics — EXCEPT the GMC RETURN STANDARDS (window, condition, methods, label, restocking fee, scope, exchanges, refund method, refund timing), which are ALWAYS mandatory and must be written out in full.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}}. Do not add sections, markdown fences, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
