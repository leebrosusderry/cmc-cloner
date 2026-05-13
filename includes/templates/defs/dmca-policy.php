<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'dmca-policy',
    'label'          => 'DMCA / Copyright Policy',
    'description'    => 'Copyright takedown notices, counter-notifications, and repeat-infringer policy.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a DMCA / COPYRIGHT POLICY page for an online store named {{ten_web}}.

GOAL: Produce a Google Merchant Center (GMC) compliant DMCA / Copyright Policy that is factually grounded in the source page, phrased differently to avoid duplicate content across cloned sites, and laid out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Primary brand color: {{primary_color}}

REQUIRED SECTIONS (all must appear, in this order)
1. Overview — {{ten_doanh_nghiep}} respects intellectual property rights and responds to valid notices of alleged copyright infringement in accordance with applicable law referenced in the source.
2. Rights of Copyright Holders — a short explanation of who may submit a notice.
3. How to File a DMCA Notice — a clear instruction that the notice must be submitted in writing to the designated contact.
4. Required Information — a numbered checklist of what a valid notice must contain (identification of the copyrighted work, identification and location of the allegedly infringing material, contact information of the notifier, a good-faith statement, a statement made under penalty of perjury, physical or electronic signature).
5. Counter-Notification Process — how an affected user may respond, what their counter-notice must include, and what happens after it is received.
6. Repeat Infringer Policy — {{ten_doanh_nghiep}} may disable or terminate accounts of repeat infringers in appropriate circumstances.
7. Designated Agent — where to send notices: use {{email_web}} and {{dia_chi}}. Do not invent a named agent if the source does not provide one.
8. Contact — a short block that uses {{ten_doanh_nghiep}}, {{email_web}}, {{so_dien_thoai}}, and {{dia_chi}}.

STRICT RULES
- Do NOT invent statutes, jurisdictions, named agents, or response timeframes not present in the source. If the source is vague, keep it vague.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Write in clear, professional English. No marketing fluff, no emojis, no exaggerations, no guarantees not stated in the source.
- Rewrite wording — do not copy sentences verbatim from the source.
- This is not legal advice; do not imply it is.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period.
- {{INTRO}} — 1–2 sentences lead paragraph.
- {{OVERVIEW}} — section 1 body (Overview). Short paragraph using {{ten_doanh_nghiep}}.
- {{RIGHTS}} — section 2 body (Rights of Copyright Holders). Who may submit a notice.
- {{HOW_TO_FILE}} — section 3 body (How to File). Notice must be written and sent to the designated contact.
- {{REQUIRED_INFO}} — section 4 body (Required Information). Numbered checklist (work, material, contact, good-faith statement, perjury statement, signature).
- {{COUNTER_NOTICE}} — section 5 body (Counter-Notification Process). What the response must include and next steps.
- {{REPEAT_INFRINGER}} — section 6 body (Repeat Infringer Policy). Short paragraph.
- {{DESIGNATED_AGENT}} — section 7 body (Designated Agent). Use {{email_web}} and {{dia_chi}}. Do not invent a named agent.
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON below. Fill every {{PLACEHOLDER}} in the skeleton with appropriate text. Do not add sections beyond the skeleton. Do not output markdown code fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
