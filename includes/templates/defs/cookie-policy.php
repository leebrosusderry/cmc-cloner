<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'cookie-policy',
    'label'          => 'Cookie Policy',
    'description'    => 'Cookie categories, purposes, and management options.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a COOKIE POLICY page for {{ten_web}}.

GOAL: Produce a GMC-compliant Cookie Policy grounded in the source, rephrased for uniqueness, and laid out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Primary brand color: {{primary_color}}

REQUIRED SECTIONS (all must appear, in this order)
1. Overview — what cookies are and why this site uses them.
2. Types of Cookies We Use — strictly necessary, functional/preference, analytics/performance, advertising/targeting. Describe each purpose in one or two sentences.
3. First-Party vs. Third-Party Cookies — short explanation; name third parties only if the source does.
4. Cookie Duration — session vs. persistent cookies.
5. How to Manage or Disable Cookies — browser settings overview (Chrome, Safari, Firefox, Edge) and the general opt-out approach.
6. Consent — how the user grants or withdraws consent on this site.
7. Changes to This Policy — how updates are announced.
8. Contact — short block using {{ten_doanh_nghiep}}, {{email_web}}, {{so_dien_thoai}}, and {{dia_chi}}.

STRICT RULES
- Do NOT invent third-party vendor names, retention durations, or jurisdictions not present in the source.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Clear, professional English. No fluff, no emojis.
- Rewrite wording; do not copy sentences verbatim.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period. Use a generic policy name only (e.g. "Cookie Policy", "Cookies"). NEVER include either {{ten_web}} OR {{ten_doanh_nghiep}} in the H1 — name disclosure belongs in body sections per their respective rules. An H1 like "<LLC name> Cookie Policy" reads corporate-cold; a clean "Cookie Policy" is GMC-standard.
- {{INTRO}} — 1–2 sentences lead paragraph.
- {{OVERVIEW}} — section 1 body (Overview). What cookies are and why this site uses them.
- {{COOKIE_TYPES}} — section 2 body. Strictly necessary / functional / analytics / advertising, 1–2 sentences each.
- {{FIRST_THIRD_PARTY}} — section 3 body (First-Party vs. Third-Party). Short explanation; name vendors only if source does.
- {{COOKIE_DURATION}} — section 4 body (Cookie Duration). Session vs. persistent.
- {{MANAGE_COOKIES}} — section 5 body (How to Manage or Disable). Browser-settings overview for Chrome / Safari / Firefox / Edge.
- {{CONSENT}} — section 6 body (Consent). How users grant or withdraw consent on this site.
- {{POLICY_CHANGES}} — section 7 body (Changes to This Policy). How updates are announced.
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}}. No markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
