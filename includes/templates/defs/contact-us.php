<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'contact-us',
    'label'          => 'Contact Us',
    'description'    => 'Business info, support hours, and how to reach the team.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce copywriter rewriting a CONTACT US page for {{ten_web}}.

GOAL: Produce a GMC-compliant Contact Us page that makes it easy to identify the business and reach support. Ground the content in the source page and phrase it differently from the source. Lay it out using the supplied Flatsome skeleton.

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

REQUIRED SECTIONS (all must appear, in this order)
1. Welcome Message — one short paragraph inviting the visitor to reach out.
2. Business Information — company name {{ten_doanh_nghiep}}, store name {{ten_web}}, address {{dia_chi}}, email {{email_web}}.
3. Customer Support Hours — one short line stating that the support team is available {{gio_lam_viec}}. PRESERVE any timezone abbreviation already present inside {{gio_lam_viec}} (e.g. "(MST)") verbatim — do NOT paraphrase or strip it.
4. Expected Response Time — one short line stating that messages are answered {{response_time}}.
5. How to Reach Us — invite the visitor to send a message using the short contact form below the copy (Name / Email / Subject / Message). Mention email as a direct alternative.
6. Closing — one short line thanking the visitor.

STRICT RULES
- TIMEZONE PRESERVATION: every mention of {{gio_lam_viec}} MUST preserve any timezone abbreviation already inside that value (e.g. "(MST)", "(PST)", "(UTC+7)"). Do NOT paraphrase or strip the timezone parenthetical. If {{timezone}} is non-empty, you MAY add a short clarification sentence near the support-hours line stating that all times refer to {{timezone}} (e.g. "All times refer to MST.").
- RESPONSE-TIME PRESERVATION: the resolved value of {{response_time}} MUST appear verbatim wherever this page mentions response time. Do NOT paraphrase, reword, convert units, or substitute equivalent phrases (e.g. do NOT swap "1 business day" with "24 business hours", "next business day", "within a day", or any other variant). Insert the value exactly as configured.
- Do NOT invent addresses, emails, or hours. If the source is silent, use the COMPANY CONTEXT values and generic language.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Clear, warm, professional English. No marketing fluff. No emojis. No exaggerations.
- Rewrite wording; do not copy sentences verbatim.
- PRESERVE VERBATIM: the LAYOUT SKELETON contains one <div class="cmc-contact-form-mount" data-mailto="[email-web]"></div> marker. Copy this <div> tag into your output unchanged — same tag, same class, same data-mailto attribute, empty body. Do NOT replace it with a real <form>, do NOT add child elements, do NOT remove it. The front-end hydrates it into a working contact form at runtime.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period. MAY include {{ten_web}} for brand voice (e.g. "Contact {{ten_web}}", "Get in Touch with {{ten_web}}") OR use a generic friendly title (e.g. "Contact Us", "Get in Touch", "We're Here to Help"). NEVER include {{ten_doanh_nghiep}} — the legal entity name does not belong in an H1 ("Contact <LLC name>" reads corporate-cold and confuses customers who shop the brand). The LLC name is disclosed in Section 2 (Business Information) per the rule above; that is its only place on this page.
- {{INTRO}} — section 1 body (Welcome Message). One short paragraph inviting contact. Drop in the store name naturally.
- {{RESPONSE_TIME}} — section 4 body (Expected Response Time). One short line stating messages are answered {{response_time}}. Do NOT duplicate the literal "{{response_time}}" variable — use the resolved phrase in a full sentence.
- {{ORDER_HELP}} — one short paragraph telling customers to email for help with an existing order. Emphasize email, never mention contact forms.
- {{SUPPORT_CHANNELS}} — one short paragraph summarizing how to reach the team (email primary; mention phone only if {{so_dien_thoai}} is present).
- {{BUSINESS_INQUIRIES}} — one short paragraph covering partnership, press, or wholesale inquiries. Keep it generic and industry-agnostic.
- If a topic is thin in the source, write a short generic sentence rather than leaving the slot empty.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}}. No markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
