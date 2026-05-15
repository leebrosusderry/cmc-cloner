<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'shipping-policy',
    'label'          => 'Shipping Policy',
    'description'    => 'Regions, methods, processing time, costs, tracking, and GMC-required shipping clauses.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a SHIPPING POLICY page for {{ten_web}}.

GOAL: Produce a Google Merchant Center (GMC) compliant Shipping Policy that is factually grounded in the source, rephrased for uniqueness, expanded to cover every clause GMC reviewers look for, and laid out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Support hours: {{gio_lam_viec}}
- Timezone: {{timezone}}    (auto-extracted from the parenthetical inside Support hours; empty when the user did not include one)
- Response time: {{response_time}}
- Primary brand color: {{primary_color}}
- Free-shipping threshold: {{free_shipping_threshold}}
- Below-threshold standard-shipping fee: {{below_threshold_shipping_fee}}
- Order cut-off time: {{shipping_cutoff_time}}
- Handling time: {{shipping_handling_time}}
- Transit time: {{shipping_transit_time}}
- Total estimated delivery: {{shipping_total_delivery}}

GMC SHIPPING STANDARDS (AUTHORITATIVE — these values MUST appear literally in the generated copy; they mirror the Google Merchant Center shipping configuration and override any different numbers in the source)
- Regions served: United States only. International shipping is NOT offered at this time.
- Currency: USD. All prices and shipping fees are displayed in USD.
- Order cut-off time: {{shipping_cutoff_time}}. Orders placed before the cut-off are processed the same business day; orders placed after the cut-off are processed the next business day.
- Timezone disclosure: if {{timezone}} is non-empty, the page MUST state once (typically at the end of section 2) that all stated times — cut-off, business hours, processing windows, and response times — refer to {{timezone}}. The literal value of {{timezone}} (e.g. "MST", "PST", "UTC+7") MUST appear verbatim in that disclosure sentence. If {{timezone}} is empty, omit the disclosure entirely (do NOT invent a timezone).
- Business days: as stated in the handling and transit values below. Closed on Sundays and U.S. public holidays unless the settings say otherwise.
- Handling time: {{shipping_handling_time}} from order confirmation to hand-off to the carrier.
- Transit time: {{shipping_transit_time}} from dispatch to delivery.
- Total estimated delivery: {{shipping_total_delivery}}, weekends and public holidays excluded.
- Shipping services offered: Standard Shipping only. There is NO expedited, next-day, overnight, international, or oversized-item service. Do NOT mention any of these service types anywhere in the copy — not even as "shown at checkout" or "available on request".
- Free shipping: orders with a subtotal at or above {{free_shipping_threshold}} receive free Standard Shipping.
- Flat fee: orders with a subtotal below {{free_shipping_threshold}} are charged a flat Standard Shipping fee of {{below_threshold_shipping_fee}}.
- No hidden or additional surcharges beyond the two clauses above. Do NOT write phrases like "additional charges may apply at checkout", "expedited charges shown at checkout", "oversized fees", or equivalents.

REQUIRED SECTIONS (all must appear, in this order)
1. Overview — state that {{ten_web}} ships to the United States only, in USD, via Standard Shipping. One-line summary of what this page covers.
2. Order Processing Time — handling time is {{shipping_handling_time}} from order confirmation to dispatch. MUST include the order cut-off: orders placed before {{shipping_cutoff_time}} are processed the same business day; orders placed after {{shipping_cutoff_time}}, on Sundays, or on U.S. public holidays are processed the next business day. If {{timezone}} is non-empty, append a one-time clarification sentence stating that all times on this page refer to {{timezone}} (literal value, e.g. "All times on this page refer to MST."). Do NOT promise same-day dispatch unconditionally.
3. Shipping Methods — Standard Shipping is the only method offered. Do NOT mention expedited, overnight, international, or oversized options. Generic phrasing only; do NOT claim specific carriers unless the source explicitly names one.
4. Estimated Delivery Time — transit time is {{shipping_transit_time}} from the date of dispatch; combined with handling, the total estimated delivery window is {{shipping_total_delivery}}, weekends and U.S. public holidays excluded. Frame as estimates, not guarantees, and state they start from the date of dispatch (not the date of order). Do NOT add a separate expedited delivery line.
5. Shipping Costs — MUST contain exactly these two clauses, in this order, and nothing that contradicts them:
   (a) Orders with a subtotal at or above {{free_shipping_threshold}} qualify for free Standard Shipping within the United States.
   (b) Orders with a subtotal below {{free_shipping_threshold}} are charged a flat Standard Shipping fee of {{below_threshold_shipping_fee}}.
   Close with one sentence stating all prices are in USD and that there are no additional expedited, international, or oversized surcharges. Both clauses are site-wide commitments configured in Settings and are REQUIRED on every generated Shipping Policy even if the source is silent on costs.
6. Order Tracking — state that a tracking number is emailed to the billing address once the order ships, and that tracking may take 24–48 hours to become visible after dispatch. Invite customers to use the Track Your Order page. Do NOT invent a specific carrier URL.
7. Address Accuracy — customer is responsible for providing a complete and correct shipping address. If an address is incorrect or incomplete and the package is returned to sender, {{ten_doanh_nghiep}} will contact the customer to arrange re-shipment at the customer's cost, OR cancel and refund the order minus any non-recoverable shipping fee.
8. Address Changes — customers should email {{email_web}} as soon as possible if they need to update a shipping address; changes are only possible before the order is dispatched, and before the {{shipping_cutoff_time}} cut-off on the day of dispatch.
9. International Shipping — state plainly that {{ten_doanh_nghiep}} currently ships to the United States only and does NOT offer international shipping. Do NOT write a customs-and-duties paragraph; it does not apply.
10. Delivery Exceptions & Delays — common causes (weather, carrier delays, remote destinations, peak-season volume) are outside {{ten_doanh_nghiep}}'s control; {{ten_doanh_nghiep}} will assist but cannot guarantee carrier transit times. Mention that the support team is reachable {{gio_lam_viec}} and replies {{response_time}}. PRESERVE the timezone abbreviation that already appears inside {{gio_lam_viec}} verbatim — do NOT paraphrase or strip it.
11. Lost, Stolen, or Damaged Shipments — what the customer should do: check with neighbours and the local carrier first, then email {{email_web}} within a reasonable period (e.g. within 7 days of the expected delivery date) with the order ID and photos of any damage. {{ten_doanh_nghiep}} will investigate and, where warranted, re-ship or refund. Packages marked as delivered by the carrier are considered delivered; {{ten_doanh_nghiep}} is not liable for theft after delivery.
12. Undeliverable / Returned-to-Sender Packages — if a package is returned to {{ten_doanh_nghiep}} as undeliverable, refused, or unclaimed, {{ten_doanh_nghiep}} will contact the customer to arrange re-shipment (additional fee) or cancellation (refund minus outbound shipping). Do NOT promise unconditional refunds.
13. P.O. Boxes, APO/FPO, and Remote Addresses — if the source states support, list it; otherwise say deliveries to P.O. Boxes and APO/FPO addresses are accepted only where the carrier supports them, and remote-area transit may sit at the upper end of the stated transit window ({{shipping_transit_time}}).
14. Back-orders & Split Shipments — if items are out of stock, {{ten_doanh_nghiep}} will notify the customer and offer to split the shipment at no extra cost, wait for all items, or cancel and refund. Only needed if the source mentions inventory handling; otherwise keep to one sentence.
15. Contact — short block using {{ten_doanh_nghiep}}, {{email_web}}, {{so_dien_thoai}}, and {{dia_chi}}.

STRICT RULES (GMC compliance)
- TIMEZONE PRESERVATION: every mention of {{gio_lam_viec}}, {{shipping_cutoff_time}}, or any other time on the page MUST preserve any timezone abbreviation that already appears in those values (e.g. "(MST)", "(PST)", "(UTC+7)"). Do NOT paraphrase, strip, or relocate the timezone parenthetical. If {{timezone}} is non-empty, the page MUST also contain exactly one disclosure sentence stating that all times refer to {{timezone}} (placed at the end of section 2). If {{timezone}} is empty, do NOT invent a timezone — output time clauses without one.
- The GMC SHIPPING STANDARDS block above is AUTHORITATIVE. If the source page states different numbers (e.g. "delivery varies", "7–14 days", "3–5 days"), IGNORE the source and use the standards above verbatim so the generated page matches Google Merchant Center exactly.
- Every generated page MUST literally contain the resolved values of: {{shipping_cutoff_time}}, {{shipping_handling_time}}, {{shipping_transit_time}}, {{shipping_total_delivery}}, {{free_shipping_threshold}}, and {{below_threshold_shipping_fee}} — plus the literal strings "United States", "USD", and "Standard Shipping". These are non-negotiable.
- RESPONSE-TIME PRESERVATION: the resolved value of {{response_time}} MUST appear verbatim wherever this page mentions response time. Do NOT paraphrase, reword, convert units, or substitute equivalent phrases (e.g. do NOT swap "1 business day" with "24 business hours", "next business day", "within a day", or any other variant). Insert the value exactly as configured.
- Do NOT invent carriers, tracking URLs, specific day counts outside the standards above, or delivery guarantees.
- Do NOT mention expedited, next-day, overnight, international, or oversized shipping anywhere — not as an option, not as "available at checkout", not as "additional charges may apply". The store offers Standard Shipping only.
- Do NOT write a customs-and-duties paragraph. International shipping is not offered.
- Do NOT promise "same-day dispatch", "guaranteed delivery by X date", "next-day delivery", "100% on-time", or any absolute delivery guarantee.
- Do NOT use urgency / marketing language ("limited time", "today only", "act now", "!!", ALL-CAPS sentences) or emojis.
- Frame delivery windows as estimates, always starting from the date of dispatch (not the date of order), and note that weekends and U.S. public holidays are excluded from business-day counts.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Clear, professional English. Rewrite wording; do not copy sentences verbatim from the source.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period. Use a generic policy name only (e.g. "Shipping Policy", "Shipping & Delivery", "Delivery Policy"). NEVER include either {{ten_web}} OR {{ten_doanh_nghiep}} in the H1 — name disclosure (brand voice for operational copy, LLC for liability statements) lives in the body sections per their respective rules. A clean "Shipping Policy" is GMC-standard.
- {{INTRO}} — 1–2 sentences lead paragraph. MUST mention that {{ten_web}} ships to the United States only with Standard Shipping, in USD.
- {{OVERVIEW}} — section 1 body (Overview). United States only; Standard Shipping only; prices in USD; one-line summary of the page. Recommended: include a compact summary line combining the resolved Handling, Transit, and Total delivery values (e.g. "Handling {{shipping_handling_time}} + Transit {{shipping_transit_time}} = {{shipping_total_delivery}}").
- {{PROCESSING_TIME}} — section 2 body (Order Processing Time). MUST state literally: handling time {{shipping_handling_time}}; order cut-off {{shipping_cutoff_time}}; orders placed before {{shipping_cutoff_time}} are processed the same business day, orders placed after the cut-off / on Sunday / on a U.S. public holiday are processed the next business day. If {{timezone}} is non-empty, append exactly one clarification sentence stating that all times on this page refer to {{timezone}} (literal value, e.g. "All times on this page refer to MST."). If {{timezone}} is empty, omit the clarification entirely. No same-day-dispatch promise.
- {{METHODS_CARRIERS}} — section 3 body (Shipping Methods). Standard Shipping ONLY. Do NOT list expedited, overnight, international, or oversized options.
- {{DELIVERY_TIME}} — section 4 body (Estimated Delivery Time). MUST state literally: transit time {{shipping_transit_time}} from date of dispatch; combined with handling, total estimated delivery is {{shipping_total_delivery}}; weekends and U.S. public holidays are excluded. Frame as estimates, not guarantees. Do NOT add any expedited delivery line.
- {{SHIPPING_COSTS}} — section 5 body (Shipping Costs). MUST contain, in this order: (a) free Standard Shipping for subtotals at or above {{free_shipping_threshold}}; (b) flat Standard Shipping fee of {{below_threshold_shipping_fee}} for subtotals below that threshold; (c) one closing sentence stating all prices are in USD and there are no additional expedited, international, or oversized surcharges. Write (a) and (b) as full sentences using the literal resolved values (do NOT output the "{{...}}" tokens verbatim).
- {{ORDER_TRACKING}} — section 6 body (Order Tracking). Tracking number emailed after dispatch; 24–48 h visibility lag; pointer to Track Your Order page.
- {{DELAYS_ISSUES}} — section 7 body (Delivery Exceptions, Delays, Lost / Stolen / Damaged, and Returned-to-Sender). Combine sections 10, 11, 12 of the required sections into this slot; include {{gio_lam_viec}} and {{response_time}} naturally. PRESERVE the timezone abbreviation that already appears inside {{gio_lam_viec}} (e.g. "(MST)") verbatim — do NOT paraphrase the business hours into a shorter form that strips the timezone. Include the "packages marked delivered are considered delivered" line.
- {{ADDRESS_CHANGES}} — section 8 body (Address Accuracy + Address Changes + International (not offered) + P.O. Box/APO/FPO + Back-orders). Combine sections 7, 8, 9, 13, 14 of the required sections into this slot, in that order, as short paragraphs or a tight bullet list. For the "Address Changes" part, remind customers that change requests must reach support before the {{shipping_cutoff_time}} cut-off on the dispatch day. For the "International" part, just state plainly that international shipping is not offered — do NOT include customs/duties language.
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics — EXCEPT the GMC SHIPPING STANDARDS (resolved cut-off, handling, transit, total-delivery, United States only, USD, Standard Shipping only) and the two Shipping Costs clauses, all of which are ALWAYS mandatory and must be written out in full.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}}. No markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
