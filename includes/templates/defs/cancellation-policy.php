<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'cancellation-policy',
    'label'          => 'Cancellation Policy',
    'description'    => 'Order cancellation window, process, fees, and refunds. Aligned with FAQ + Shipping Policy + Return & Refund Policy.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a CANCELLATION POLICY page for an online store named {{ten_web}}.

GOAL: Produce a Google Merchant Center (GMC) compliant Cancellation Policy that is factually grounded in the source page, phrased differently to avoid duplicate content across cloned sites, AND aligned literally with the FAQ + Shipping Policy + Return & Refund Policy generated for the same site. GMC reviewers compare these pages — any contradiction (e.g. FAQ says "before 2:00 PM PST cut-off" but Cancellation Policy says "within 1 hour of placing the order") is treated as Misrepresentation. The cancellation rule on this page MUST match the cut-off authority below verbatim.

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
- Cancellation refund time: {{cancellation_time}}   (bare duration; this page appends "after cancellation approval")
- Return refund processing time: {{refund_processing_time}}   (for reference only; this page does NOT use it because cancellations have no returned item)
- Order cut-off time: {{shipping_cutoff_time}}
- Return window: {{return_window}}
- Primary brand color: {{primary_color}}

GMC CANCELLATION STANDARDS (AUTHORITATIVE — these resolved values MUST appear literally in the generated copy; if the source FAQ states different cancel windows, fees, or refund timing, IGNORE the source and use the values below so this page matches the FAQ + Shipping Policy + Return & Refund Policy on the same site)
- Cancellation eligibility: orders that have NOT yet been dispatched can be cancelled.
- Cancellation cut-off: a cancellation request MUST reach support before {{shipping_cutoff_time}} on the dispatch day. Once an order has been dispatched, it can no longer be cancelled and falls under the Return & Refund Policy.
- Cancellation channel: customers cancel by emailing {{email_web}} (with the order ID and reason) or calling {{so_dien_thoai}} during {{gio_lam_viec}}; support replies {{response_time}}.
- Cancellation fee: No fees. {{ten_doanh_nghiep}} does not charge cancellation, processing, or restocking fees for orders cancelled before dispatch.
- Refund after cancellation: For approved cancellations before dispatch, refunds are issued to the original payment method in USD within {{cancellation_time}} after cancellation approval. The customer's bank may add 1–2 additional business days before the refund appears on the statement. NEVER write "after we receive the returned item" / "after returning the item" / "after the returned item arrives" on this page — those phrases describe the RETURN flow, not the cancellation flow, and create a GMC-flagged contradiction (cancellation = no returned item). Do NOT add a "no split / partial refund" sentence here — that detail belongs in the Cancellation Fees section (6), not in the refund-timing block.
- Order modifications (address, size, quantity, items): modification requests must reach support before {{shipping_cutoff_time}} on the dispatch day, by the same channels above; once dispatched, modifications follow the Return & Refund Policy.
- Currency: USD.

REQUIRED SECTIONS (all must appear, in this order)
1. Overview — what the policy covers (cancellations and pre-dispatch modifications) and who may cancel an order. State plainly that cancellations are accepted while the order has NOT yet been dispatched.
2. Cancellation Window — MUST literally state: "Cancellation requests must reach support before {{shipping_cutoff_time}} on the dispatch day. Orders not yet dispatched can be cancelled; once dispatched, the order is handled under the Return & Refund Policy." Do NOT invent alternative windows ("within 1 hour", "within 24 hours", "before midnight", etc.) even if the source page mentions them — the cut-off above is authoritative.
3. How to Cancel an Order — numbered steps: (a) email {{email_web}} with the order ID, customer name, and reason; OR call {{so_dien_thoai}} during {{gio_lam_viec}}; (b) support replies {{response_time}}; (c) once approved, the refund follows the Refund After Cancellation section below.
4. Post-Shipment Cancellations — once the order has been dispatched, it can NOT be cancelled. The customer should follow the Return & Refund Policy and use the prepaid return shipping label included in the package; the return window is {{return_window}} from the date of delivery. Always link out to the "Return & Refund Policy" by that exact name.
5. Refund After Cancellation — MUST literally include the sentence: "For approved cancellations before dispatch, refunds are issued to the original payment method in USD within {{cancellation_time}} after cancellation approval." Follow it with: "The customer's bank may add 1–2 additional business days before the refund appears on the statement." Stop there. Do NOT add a "no split / partial refund" sentence — that belongs in section 6 (Cancellation Fees). NEVER write the refund timing as "after we receive the returned item" / "after returning the item" / "after the returned item arrives" — there is no returned item in a cancellation flow.
6. Cancellation Fees — state literally that there are no cancellation, processing, or restocking fees for cancellations made before dispatch. Customers receive a full refund of the order amount paid. Add one short sentence stating that {{ten_doanh_nghiep}} does not split or partially refund pre-dispatch cancellations (the refund is always for the full order amount).
7. Order Modifications — address, size, quantity, or item swaps must reach support before {{shipping_cutoff_time}} on the dispatch day, via the same email/phone channels above. Modifications after dispatch are handled under the Return & Refund Policy.
8. Contact — short block using {{ten_doanh_nghiep}}, {{email_web}}, {{so_dien_thoai}}, and {{dia_chi}}. Mention {{gio_lam_viec}} verbatim (preserving any timezone abbreviation inside).

STRICT RULES (GMC compliance)
- TIMEZONE PRESERVATION: every mention of {{shipping_cutoff_time}} or {{gio_lam_viec}} MUST preserve any timezone abbreviation already inside those values (e.g. "(MST)", "(PST)", "(UTC+7)"). Never paraphrase or strip the timezone parenthetical. If {{timezone}} is non-empty, this page MAY include exactly one short clarification sentence (in section 2 or section 8) stating that all stated times refer to {{timezone}} (literal value, e.g. "All times refer to MST."). If {{timezone}} is empty, do NOT invent one.
- LITERAL VALUE INSERTION: every paragraph that touches a snapshot topic MUST contain the literal resolved values — {{shipping_cutoff_time}}, {{return_window}}, {{cancellation_time}}, {{response_time}}, {{gio_lam_viec}} — and the literal strings "United States", "USD", "Return & Refund Policy". Do NOT output the "{{...}}" tokens verbatim; substitute the resolved values. Use {{cancellation_time}} for refund timing on THIS page — never {{refund_processing_time}} (that value carries return-flow phrasing and creates a contradiction here).
- RESPONSE-TIME PRESERVATION: the resolved value of {{response_time}} MUST appear verbatim wherever this page mentions response time. Do NOT paraphrase, reword, convert units, or substitute equivalent phrases (e.g. do NOT swap "1 business day" with "24 business hours", "next business day", "within a day", or any other variant). Insert the value exactly as configured.
- CROSS-PAGE ALIGNMENT: the cancellation cut-off on this page MUST equal the cut-off used on Shipping Policy and FAQ. Do NOT invent a different cut-off (e.g. "within 24 hours of placing the order"). The single rule the entire site uses is "before {{shipping_cutoff_time}} on the dispatch day".
- SUBJECT-NAMING: legal-commitment statements (refund liability, fee waivers, modification approval) MUST name {{ten_doanh_nghiep}} as the responsible party — never "we" alone, never the store name {{ten_web}}.
- FORBIDDEN phrases (do NOT produce any variant of these — they contradict the cross-page rule or invent fees):
   * "within 1 hour of placing the order", "within 24 hours of placing the order", "before midnight", "within X minutes" — any time-from-placement window is wrong; the rule is time-of-day on the dispatch day.
   * "cancellation fee", "processing fee", "restocking fee" — there are NONE for pre-dispatch cancellations.
   * "partial refund", "credit only", "store credit only" — pre-dispatch cancellations get a full refund to the original payment method.
   * "non-refundable shipping after cancellation" — if the order has not dispatched, no shipping has been incurred, so the entire order amount is refunded.
   * "Return Policy" or "Refund Policy" as separate page names — the canonical name is "Return & Refund Policy".
   * any refund window faster or slower than {{cancellation_time}}.
   * "after we receive the returned item", "after returning the item", "after the returned item arrives", "after receipt of the returned item", "once the returned item is received" — all wrong on this page. A cancellation prevents dispatch, so NO ITEM is ever returned; refund timing here is measured from "cancellation approval", never from a return event.
- Do NOT invent monetary amounts, specific hours, days, restocking fees, or product categories not present in the COMPANY CONTEXT or GMC CANCELLATION STANDARDS above. If the source is vague or contradicts the standards, the standards win.
- Use {{ten_web}} for the store name and {{ten_doanh_nghiep}} for legal-commitment statements (refund liability, fee waivers, modification approval).
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Write in clear, professional English. No marketing fluff, no emojis, no exaggerations, no guarantees not stated in the standards.
- Rewrite wording — do not copy sentences verbatim from the source.
- Keep examples industry-appropriate for {{nganh_hang}}.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period.
- {{INTRO}} — 1–2 sentences lead paragraph stating that this page covers cancellations of orders not yet dispatched, with the cut-off being {{shipping_cutoff_time}} on the dispatch day.
- {{OVERVIEW}} — section 1 body (Overview). What the policy covers and who may cancel.
- {{CANCEL_WINDOW}} — section 2 body (Cancellation Window). MUST literally include "before {{shipping_cutoff_time}} on the dispatch day" — preserve timezone in the cut-off — and the once-dispatched → Return & Refund Policy hand-off.
- {{HOW_TO_CANCEL}} — section 3 body (How to Cancel). Numbered steps, mention {{email_web}}, {{so_dien_thoai}}, {{gio_lam_viec}} (timezone preserved), {{response_time}}.
- {{POST_SHIPMENT}} — section 4 body (Post-Shipment Cancellations). Reference the Return & Refund Policy by that exact name; mention the {{return_window}} return window.
- {{REFUND_AFTER}} — section 5 body (Refund After Cancellation). MUST literally include the sentence: "For approved cancellations before dispatch, refunds are issued to the original payment method in USD within {{cancellation_time}} after cancellation approval." Follow with the 1–2 bank-day caveat ("The customer's bank may add 1–2 additional business days before the refund appears on the statement."). Stop there — do NOT add a "no split / partial refund" sentence in this slot; that detail belongs in the Cancellation Fees slot (section 6). NEVER use {{refund_processing_time}} here; that placeholder carries "after we receive the returned item" phrasing which is wrong for cancellations.
- {{CANCEL_FEES}} — section 6 body (Cancellation Fees). State literally that there are no cancellation, processing, or restocking fees for pre-dispatch cancellations.
- {{ORDER_MODS}} — section 7 body (Order Modifications). Mention {{response_time}} and the {{shipping_cutoff_time}} cut-off (timezone preserved).
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics — EXCEPT the GMC CANCELLATION STANDARDS values, which are ALWAYS mandatory and must be written out in full.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON below. Fill every {{PLACEHOLDER}} in the skeleton with appropriate text. Do not add sections beyond the skeleton. Do not output markdown code fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
