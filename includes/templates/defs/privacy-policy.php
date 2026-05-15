<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'privacy-policy',
    'label'          => 'Privacy Policy',
    'description'    => 'Data collection, use, sharing, cookies, and user rights.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce policy writer rewriting a PRIVACY POLICY page for {{ten_web}}.

GOAL: Produce a GMC-compliant Privacy Policy grounded in the source, rephrased for uniqueness, and laid out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Primary brand color: {{primary_color}}

EFFECTIVE DATE POLICY (AUTHORITATIVE — GMC reviewers expect a visible Effective Date / Last Updated line on Privacy Policy; this page MUST carry exactly one such line)
- The skeleton ALREADY contains a hardcoded Effective Date paragraph rendered immediately above the {{OVERVIEW}} slot — it appears in the LAYOUT SKELETON below as the literal HTML "<p class=\"cmc-effective-date\"><strong>Effective Date:</strong> {{effective_date}}</p>" (with {{effective_date}} pre-substituted by the system). DO NOT remove, move, paraphrase, restyle, or duplicate that line. Output the skeleton's Effective Date paragraph EXACTLY as it appears.
- Do NOT write your own "Effective Date:" / "Last Updated:" / "As of:" / "In force since:" line inside any slot ({{OVERVIEW}}, {{INTRO}}, {{POLICY_CHANGES}}, etc.). The skeleton's hardcoded paragraph is the ONLY date marker the page may carry.
- Do NOT write any OTHER date, month, year, quarter, season, "since <date>" / "as of <date>" / "in force since <date>", or version number anywhere on the page. The skeleton's pre-substituted Effective Date paragraph is the single, sufficient date marker.
- NEVER copy a date from the SOURCE PAGE below. Sources commonly carry "Effective October 2023" or "Last updated: March 2024" — IGNORE them, do not translate, do not substitute, do not echo into any slot.
- The "Changes to This Policy" section MUST describe HOW updates are communicated (revised policy posted on this page, visitors encouraged to review) WITHOUT mentioning any date, revision frequency ("every 6 months"), version number, or "see Effective Date above" reference. The hardcoded Effective Date paragraph in the skeleton stands on its own.

REQUIRED SECTIONS (all must appear, in this order)
1. Overview — the skeleton has already rendered the Effective Date paragraph immediately above this slot, so DO NOT repeat it. The {{OVERVIEW}} slot starts directly with 1–2 plain sentences stating who the policy applies to and the scope of the data activities it covers. Then add ONE additional sentence introducing the data controller in FULL-SENTENCE form (see DATA CONTROLLER DISCLOSURE rule below) — for example: "The data controller for personal data collected through this site is {{ten_doanh_nghiep}}, the legal entity behind {{ten_web}}." Do NOT use the terse label format "Controller: {{ten_doanh_nghiep}}".
2. Information We Collect — personal data (name, email, address, payment info) and automatic data (IP, device, browser, usage).
3. How We Use Your Information — fulfilling orders, customer support, marketing (only if source says), service improvement, legal compliance. MUST state the legal basis (contract, legitimate interests, consent, legal obligation) for each purpose so GDPR Article 6 is covered.
4. Information Sharing & Disclosure — service providers, legal requirements, business transfers. Name categories only, not specific vendors unless source names them.
5. Cookies & Tracking Technologies — SELF-CONTAINED 2–3 sentences describing what cookies are used for (essential cart / session cookies, optional analytics or advertising cookies if the source mentions them) and how visitors can disable cookies through their browser settings. Do NOT write "see our Cookie Policy", "refer to our Cookie Policy", "for more details, refer to our Cookie Policy", or any equivalent pointer to a Cookie Policy page — that creates a dangling text reference (no working link) and GMC reviewers flag it as a broken policy hand-off. Everything the visitor needs to know about cookies on this site MUST live inside this section's prose.
6. Data Retention — how long personal data is kept (only if source states).
7. Your Privacy Rights (GDPR & CCPA/CPRA) — dedicated section that MUST include:
   a) A GDPR/EEA-UK sub-block for visitors in the European Economic Area, United Kingdom, and Switzerland listing the rights: access, rectification, erasure, restriction, objection, data portability, withdraw consent at any time, and lodge a complaint with their local supervisory authority. State that requests can be made by emailing {{email_web}}. Do NOT name a specific supervisory authority, do NOT appoint a named EU/UK representative, and do NOT invent certifications (e.g. Privacy Shield, ISO).
   b) A CCPA/CPRA sub-block for California residents covering right to know, delete, correct, opt-out of sale/sharing, and limit use of sensitive personal information, PLUS an explicit "Do Not Sell or Share My Personal Information" statement directing them to email {{email_web}} to exercise the right. Do NOT invent a third-party global privacy control vendor.
   c) A general line that all other users may also email {{email_web}} to exercise applicable rights under their local law.
   Keep the language plain. Never invent a jurisdiction the source does not mention.
8. Security Measures — administrative, technical, and physical safeguards in plain language.
9. Children's Privacy — MUST include a baseline COPPA clause stating that the site is not directed to children under 13, that we do not knowingly collect personal information from children under 13, and that a parent or guardian who believes their child has provided information can email {{email_web}} to request deletion. Use the age in the source if higher (e.g. 16), default to 13 if source is silent. If the configured industry targets children (e.g. kids, baby, toddler, nursery, maternity), this section MUST expand per the [COPPA — KIDS/BABY NICHE — EXPANDED REQUIREMENTS] block appended below.
10. International Users & Data Transfers — note if data may be processed in another country, and if so state that appropriate safeguards (e.g. Standard Contractual Clauses, equivalent contractual measures) are used for transfers of personal data out of the EEA/UK. Keep it generic; do NOT claim a specific certification or scheme unless the source names it.
11. Changes to This Policy — how updates are communicated. Describe the notification mechanism (e.g. the revised policy will be posted on this page, and visitors are encouraged to review it from time to time). Do NOT state a date, a revision frequency, or a version number.
12. Contact — write 2–3 short FULL SENTENCES (no terse labels). One sentence MUST identify the data controller with the legal entity name, e.g. "{{ten_doanh_nghiep}} is the data controller for the personal data described in this policy." Another sentence MUST give the privacy contact channel as a full sentence, e.g. "For privacy questions or to exercise your rights, email {{email_web}} or call {{so_dien_thoai}}, and reach us by mail at {{dia_chi}}." Do NOT output bare "Controller: …", "Privacy: …", "Email: …", "Phone: …", "Address: …" key-value labels — those concatenate to "Controller{Name}", "Privacy{email}" when wpautop strips spacing and break GMC review.

STRICT RULES
- DATA CONTROLLER DISCLOSURE: the page MUST identify the data controller exactly once in the Overview AND once in the Contact section, in FULL-SENTENCE form using {{ten_doanh_nghiep}}. Examples of CORRECT phrasing (any of these patterns is acceptable):
   • "The data controller for personal data collected through this site is {{ten_doanh_nghiep}}, the legal entity behind {{ten_web}}."
   • "{{ten_doanh_nghiep}} is the data controller for personal data processed in connection with the {{ten_web}} store."
   • "Personal data described in this policy is controlled by {{ten_doanh_nghiep}}."
- CONTACT FORMAT — full-sentence ONLY: every contact-information line in this policy (Overview controller line, Children's Privacy parental contact, Section 7 GDPR/CCPA request channel, Section 12 Contact block) MUST be a full sentence in flowing prose. Do NOT emit terse label-and-value pairs of any of these forms:
   • "Controller: {{ten_doanh_nghiep}}"               ← BANNED — wpautop will strip the colon-space and produce "Controller{Name}"
   • "Privacy: {{email_web}}"                          ← BANNED — "Privacy" is not a valid label, and the value concatenates
   • "Email: {{email_web}}", "Phone: {{so_dien_thoai}}", "Address: {{dia_chi}}"  ← BANNED for the same wpautop concatenation reason
   • Any "<Word>: <value>" pair stacked as bare lines without surrounding sentence text
   Instead, write inline sentences such as: "For privacy questions or to exercise your rights under GDPR or CCPA/CPRA, email {{email_web}} or call {{so_dien_thoai}}; written correspondence may be sent to {{dia_chi}}." Inline punctuation (commas, semicolons, "or") is mandatory — never drop a value next to a label without prose around it.
- EFFECTIVE DATE POLICY applies. The skeleton already prints the single Effective Date paragraph immediately above the {{OVERVIEW}} slot — leave it untouched and do NOT emit your own Effective Date / Last Updated / As of line anywhere. NO other date, month, year, quarter, season, or version number anywhere else on the page. Any date-like line copied from the SOURCE PAGE must be dropped, not translated or substituted.
- NO TEXT-ONLY POINTERS TO A COOKIE POLICY: anywhere in the page, do NOT emit phrases such as "see our Cookie Policy", "refer to our Cookie Policy", "For more details, please refer to our Cookie Policy", "consult our Cookie Policy", "review our Cookie Policy", or any sentence that ends with a Cookie Policy reference without a working link. The Privacy Policy MUST be self-contained on cookies — describe purposes (cart / session / analytics) and the browser opt-out path inline. Pointer-without-link sentences are flagged by GMC reviewers as broken policy hand-offs.
- Do NOT invent vendor names, jurisdictions, compliance certifications, or retention periods absent from the source.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Clear, professional English. No fluff, no emojis, no legal guarantees beyond the source.
- Rewrite wording; do not copy sentences verbatim.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no trailing period. Use a generic policy name only (e.g. "Privacy Policy", "Privacy Notice"). NEVER include either {{ten_web}} OR {{ten_doanh_nghiep}} in the H1 — the data-controller disclosure (which MUST name {{ten_doanh_nghiep}} for GDPR/CCPA compliance) lives in the Overview + Contact body sections per the DATA CONTROLLER DISCLOSURE rule above. An H1 like "<LLC name> Privacy Policy" reads corporate-cold; a clean "Privacy Policy" is GMC-standard.
- {{INTRO}} — 1–2 sentences lead paragraph.
- {{OVERVIEW}} — section 1 body (Overview). The Effective Date paragraph is already rendered by the skeleton immediately above this slot, so DO NOT include any "Effective Date:" / "Last Updated:" / "As of:" line here. Start directly with 1–2 plain sentences stating who the policy applies to and the scope of data activities it covers, followed by the data-controller disclosure sentence. NO date, month, year, or version number anywhere in this slot — even if the source page has one.
- {{INFO_COLLECTED}} — section 2 body (Information We Collect). Personal data + automatic data.
- {{USE_OF_INFO}} — section 3 body (How We Use Your Information). Fulfilling orders, support, marketing (only if source says), improvement, legal. MUST cite the GDPR legal basis (contract, legitimate interests, consent, legal obligation) for each purpose.
- {{SHARING}} — section 4 body (Information Sharing & Disclosure). Categories, not specific vendors unless source names them.
- {{COOKIES_TRACKING}} — section 5 body (Cookies & Tracking). Write 2–3 SELF-CONTAINED sentences. Cover: (a) the purposes cookies are used for (e.g. keeping items in the cart, remembering session / login state, anonymous traffic analytics) — only mention advertising / third-party tracking cookies if the source page explicitly does; (b) that visitors can review or disable cookies via their browser settings, and that disabling essential cookies may affect checkout and account features. STRICT BAN: do NOT write "see our Cookie Policy", "refer to our Cookie Policy", "For more details, please refer to our Cookie Policy", "Cookie Policy page", or any other text-only pointer to a separate Cookie Policy. Such pointers ship without a working link and GMC reviewers flag them as broken policy references.
- {{DATA_RETENTION}} — section 6 body (Data Retention). Only state durations the source gives.
- {{YOUR_RIGHTS}} — section 7 body (Your Privacy Rights — GDPR & CCPA/CPRA). MUST contain three parts in this order: (a) a GDPR/EEA-UK/Swiss paragraph listing access, rectification, erasure, restriction, objection, portability, withdraw consent, and the right to lodge a complaint with the local supervisory authority — requests via {{email_web}}; do NOT name a specific authority, a designated EU/UK representative, or any compliance certification; (b) a California CCPA/CPRA paragraph covering right to know, delete, correct, opt-out of sale/sharing, and limit use of sensitive personal information, plus an explicit "Do Not Sell or Share My Personal Information" line directing residents to email {{email_web}}; do NOT invent a third-party global privacy control vendor; (c) a short line telling users elsewhere they may also email {{email_web}} to exercise applicable rights under their local law.
- {{SECURITY_MEASURES}} — section 8 body (Security Measures). Plain-language safeguards.
- {{CHILDRENS_PRIVACY}} — section 9 body (Children's Privacy). Baseline COPPA language for every site: not directed to under-13, we do not knowingly collect their data, parents may contact {{email_web}} for deletion. Use the age in the source if higher (e.g. 16); default to 13 if source is silent. If the configured industry is a kids/baby/toddler/nursery/maternity niche, FOLLOW the additional [COPPA — KIDS/BABY NICHE — EXPANDED REQUIREMENTS] block at the end of this prompt.
- {{INTERNATIONAL_USERS}} — section 10 body (International Users & Data Transfers). Only if source mentions cross-border processing; otherwise a short generic sentence. If the site is likely to have EEA/UK visitors, note that transfers outside the EEA/UK rely on appropriate safeguards such as Standard Contractual Clauses or equivalent contractual measures. Do NOT claim Privacy Shield, a Binding Corporate Rules programme, or any certification unless the source says so.
- {{POLICY_CHANGES}} — section 11 body (Changes to This Policy). Describe the mechanism — revised policy will be posted on this page, visitors encouraged to review periodically. NO date, NO revision frequency (no "every 6 months", "annually"), NO version number, NO "see Effective Date above" reference. The standalone Effective Date line at the top of the Overview is the only date marker the page carries.
- If a topic is thin in the source, write a short generic sentence rather than inventing specifics.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}}. No markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
