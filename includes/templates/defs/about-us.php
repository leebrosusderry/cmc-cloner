<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'slug'           => 'about-us',
    'label'          => 'About Us',
    'description'    => 'Mission, story, what the store sells, and customer commitments.',
    'skeletons'      => [ 'skeleton-1', 'skeleton-2', 'skeleton-3', 'skeleton-4' ],
    'default_prompt' => <<<'PROMPT'
ROLE: You are a senior e-commerce copywriter rewriting an ABOUT US page for {{ten_web}}.

GOAL: Produce a GMC-compliant About Us page that clearly identifies the business and what it sells, grounded in the source page and rephrased for uniqueness. Lay it out using the supplied Flatsome skeleton.

COMPANY CONTEXT (use these literal values naturally in the copy — do not invent alternatives)
- Store name: {{ten_web}}
- Legal business name: {{ten_doanh_nghiep}}
- Industry: {{nganh_hang}}
- Address: {{dia_chi}}
- Email: {{email_web}}
- Phone: {{so_dien_thoai}}
- Primary brand color: {{primary_color}}
- Founding year (configured in Settings; may be blank): {{founding_year}}

{{INDUSTRY_LOCK}}

FOUNDING + NEWNESS POLICY (AUTHORITATIVE — GMC and many reviewers expect a "Founded in <year>" line as a trust signal, so the page MUST always carry one; while still clearly signalling that {{ten_web}} is a newly launched business — never claim age, never claim duration, never copy a year from the SOURCE PAGE)

The placeholder {{founding_year}} is GUARANTEED to resolve to a valid 4-digit year on every render: it is either the year the user configured in Settings → Branding → Founding year, OR an auto-fallback computed by the plugin (today minus a random 2–6 month offset). It is NEVER empty. Use {{founding_year}} verbatim — exactly once, inside the Our Story section.

REQUIRED — write exactly ONE founding sentence inside the Our Story section, in this shape:
  "Founded in {{founding_year}}, {{ten_web}} is a newly launched [shop / studio / brand], still in our early days."

Permitted variations of the same shape (pick one, do NOT stack them):
  • "Founded in {{founding_year}}, {{ten_web}} is a young brand still finding its footing."
  • "{{ten_web}} was launched in {{founding_year}} — we are a new team, and every order teaches us something."
  • "Established in {{founding_year}}, {{ten_web}} is in its first chapter."
  • "We started {{ten_web}} in {{founding_year}}, fresh and just getting under way."

The year MUST be {{founding_year}} verbatim — never a different year, never "in early {{founding_year}}", never a quarter or month inside that year.

Universal rules:
- The founding sentence appears EXACTLY ONCE on the page, only inside the Our Story section. Do NOT repeat it in the H1, intro, mission, commitment, or "Why customers choose us".
- NEVER copy a year from the SOURCE PAGE below (the source may carry "since 2018" or "established in 2014" — IGNORE those numbers entirely; use ONLY {{founding_year}}).
- NEVER write longevity claims that contradict newness: no "years of experience", no "trusted for years", no "a decade of care", no "thousands of happy customers", no "industry veterans". Even with a founding year configured, the surrounding wording stays in newness / early-days territory.
- NEVER write a founding month, founding day, founding quarter, or precise founding date. Year only.
- NEVER omit the founding sentence — it is a hard GMC trust requirement on this page. If for any reason {{founding_year}} appeared blank, write "Founded in <current year>, ..." rather than skipping the sentence (this is a defensive fallback only — the plugin guarantees the placeholder is always populated).

OFFERINGS POLICY (AUTHORITATIVE — works for ANY industry. GMC cross-checks every specific product noun against the product feed; any sub-type the feed does not literally contain is a Misrepresentation violation)

DEFINITION — "specific product sub-type" = any noun, in any language or niche, that:
  (a) names a concrete retail item or item-class more specific than {{nganh_hang}};
  (b) could appear as a leaf-level node in the Google Product Taxonomy or as a row in a WooCommerce product feed;
  (c) naturally follows phrasing like "I bought a ___", "a box of ___", "our ___ are on sale".
If a noun passes any of these three tests, it is BANNED from this page regardless of industry.

DIAGNOSTIC BEFORE YOU WRITE EACH SENTENCE:
  1. Read the sentence back and ask: "Could a GMC reviewer grep the feed for this exact noun and either find it or not find it?" If yes → rewrite with an abstract noun.
  2. Count specific sub-type nouns in the sentence. Target = 0. Maximum allowed per page = 0.
  3. Replace any offender with words from the ALLOWED list below.

BANNED PATTERNS (regardless of niche):
- Enumeration lists of any kind: "X, Y, and Z", "including A, B, and C", "such as P, Q, and R", "from <thing> to <thing>", "offering everything from <thing> to <thing>". The list-shape itself is banned even before the nouns are checked.
- Sub-type nouns with modifiers: the noun is still banned even when dressed up ("premium <thing>", "handcrafted <thing>", "artisan <thing>", "ergonomic <thing>", "organic <thing>", "<material> <thing>", "<colour> <thing>").
- Compound sub-types that try to dodge the rule: "<thing>-<thing>", "<thing> and <thing> sets", "<thing>-style <thing>" are all still bans.
- Invented collection / line / edit / capsule / drop names ("Moonlight Collection", "Starter Edit", "Ora Line", "Spring Capsule", "Core Range"). Collection names are ONLY allowed when they appear literally in the source page — otherwise omit.
- Specific materials tied to an item: a generic material at category-level is fine ("natural materials", "honest fabrics", "quality finishes"), but the moment it is attached to an item it becomes banned ("<material> <thing>" is banned even though "<material>" alone would be allowed).
- Demographic / use-case narrowing that implies a sub-type ("for runners", "for toddlers", "for cyclists", "for new mums") unless that framing is literally part of {{nganh_hang}}.

ALLOWED PHRASING (use these — they are industry-agnostic):
- The industry-level category exactly as given in {{nganh_hang}}, used at most ONCE on the page in the What We Offer section, and a SEPARATE quota inside the Value Strip phrase banks below.
- Abstract set nouns: "the range", "the collection", "our selection", "our line-up", "our edit", "pieces", "essentials", "favourites", "studio favourites", "what we carry", "what we make", "what we stock", "everyday essentials".
- Emotional / process language (curator voice — see VOICE RULE below): "carefully curated", "thoughtfully chosen", "selected with care", "picked with intent", "chosen one piece at a time", "designed around everyday life", "honest selection", "considered choices".
  ⚠️ Maker-coded phrases REMOVED ("made with intent", "honest design / craft", "thoughtfully made", "handcrafted") — switch to the curator-voice equivalents above. These are permitted ONLY when the maker exception in VOICE RULE applies.
  ⚠️ Banned from this list (sound industry-coded toward hardgoods / furniture / construction): "built to last", "durable construction", "honest materials", "quality fabrics". Do NOT use these — even on a niche where they would technically apply — because they leak the wrong industry on Fashion / Beauty / Food / Pet / Kids / Jewelry / Tech sites.
- Neutral verbs: "we carry", "we offer", "we make", "we stock", "we curate" — but always followed by an abstract set noun from the list above, NEVER by a list of specific items.

REQUIRED PARAGRAPH SHAPE for "What We Offer" (AUTHORITATIVE — this paragraph is the GMC reviewer's primary anchor for "what does this store sell?". It MUST clearly name the industry AND read as written for THIS industry specifically, not as a generic template. Target length ≈ 50–90 words; 2–3 short sentences total.)

Sentence 1 — INDUSTRY NAMING (mandatory).
  Name {{nganh_hang}} exactly once, declaring it the store's focus / scope. Pick ONE shape from the list below (vary the choice across pages so two cloned sites in different niches don't open identically):
  • "At {{ten_web}}, our focus is {{nganh_hang}}."
  • "{{ten_web}} works in one category: {{nganh_hang}}."
  • "Everything we carry lives in {{nganh_hang}}."
  • "Our shop is built around {{nganh_hang}}."
  • "{{nganh_hang}} is the whole of what we do."
  • "We focus on one thing: {{nganh_hang}}."
  Do NOT append "and X, Y, and Z" or list any sub-categories — single-category statement only.

Sentence 2 — APPROACH + INDUSTRY-TINTED ATTRIBUTES (mandatory).
  Describe HOW the offering is CHOSEN / SELECTED / STOCKED / SOURCED. Compose using curator process language ("chosen with care", "selected with attention", "picked with intent", "stocked with care", "sourced thoughtfully") PLUS 2–3 attribute words drawn from YOUR OWN STEP 1 list D (the industry context-words you compiled in the INDUSTRY DOMAIN LOCK block above). These 2–3 attributes are what makes the sentence read as "for {{nganh_hang}}" specifically — without ever naming a concrete product.
  VOICE RULE applies here as much as to the Value Strip — see VOICE RULE block below for the full retail-voice / maker-voice distinction. Default is CURATOR; only use maker verbs when the exception applies.
  Base pattern (adapt the wording, do NOT copy verbatim):
    "[Each / Every] [SUBJECT NOUN] is [process verb] with attention to [attribute 1], [attribute 2], and [attribute 3]."
  SUBJECT NOUN — pick the noun that fits {{nganh_hang}} most naturally; do NOT default to "item" everywhere. Common subject nouns by offering modality:
    • Physical retail products → "item", "piece"
    • Apparel / fashion        → "piece"
    • Liquid / skincare        → "formula"
    • Food / beverage          → "item", "recipe", "batch"
    • Subscription / box       → "box", "delivery"
    • Digital download / file  → "template", "title", "download", "file"
    • Online course / lesson   → "lesson", "session", "module"
    • Software / app           → "release", "feature set"
    • Service                  → "session", "engagement", "project"
  Worked examples — these show what STEP 1 list D contributes for different industries. DO NOT copy these literally; YOU must derive your own list D for {{nganh_hang}} and pick attributes from it.
    • {{nganh_hang}} = "Fashion & Apparel"      → "Each piece is chosen with attention to fabric, fit, and how it wears over time."
    • {{nganh_hang}} = "Skincare"               → "Each formula is chosen with attention to feel, finish, and everyday wearability."
    • {{nganh_hang}} = "Pet Supplies"           → "Each item is chosen with attention to comfort, safety, and everyday durability."
    • {{nganh_hang}} = "Office Supplies"        → "Each item is chosen with attention to function, finish, and how it fits into a working space."
    • {{nganh_hang}} = "Food / Pantry"          → "Each item is chosen with attention to flavor, sourcing, and everyday use in the kitchen."
    • {{nganh_hang}} = "Digital Templates / Printables" → "Each template is designed with attention to clarity, usability, and how it adapts to different sizes and use-cases."  ← maker exception applies: digital templates are designed by the store itself, so "designed" is honest.
    • {{nganh_hang}} = "Online Courses / Tutorials"     → "Each lesson is built with attention to pacing, practical examples, and how skills carry over to real projects."  ← maker exception applies: courses are produced by the store itself.
    • {{nganh_hang}} = "Subscription Boxes"             → "Each box is curated with attention to discovery, balance, and how the contents work together over the month."
  CRITICAL — DO NOT pick attribute words from the wrong industry. If you list "fabric, fit, and wearability" on a Pet Supplies site, that's an industry leak and a hard failure. Re-derive list D for "{{nganh_hang}}" before composing this sentence. If unsure about the SUBJECT NOUN, default to "item" — it's safe across most physical products.

Sentence 3 — USE-CASE (optional, ≤1 short sentence; skip cleanly if it would feel forced).
  Describe WHEN / WHERE customers reach for the offering. Compose using YOUR OWN STEP 1 list B (the use-case scenarios you compiled). Do NOT narrow to a demographic that the source doesn't establish.
  Base patterns:
    • "These are chosen for [use-case 1], [use-case 2], and [use-case 3]."
    • "Our customers reach for them on [use-case 1] and [use-case 2]."
  Worked examples (again — derive your own list B per render, do not copy these):
    • Fashion & Apparel → "These pieces are chosen for casual routines, workdays, travel, and relaxed weekends."
    • Skincare         → "These are reached for in everyday routines, both morning and night."
    • Pet Supplies     → "Picked for daily care, mealtimes, and quiet rest at home."

LOGIC + COHERENCE CHECK (run mentally before returning):
- Sentence 1 names {{nganh_hang}} → Sentence 2 explains HOW we choose it → Sentence 3 explains WHEN/WHERE customers use it. Each sentence must build on the previous one — do NOT swap the order, do NOT repeat the same idea in two sentences.
- The 2–3 attributes in Sentence 2 MUST plausibly belong to "{{nganh_hang}}". If a GMC reviewer asked "could you actually evaluate <attribute> for <{{nganh_hang}}>?", the answer must be yes for all 2–3.
- The whole paragraph must read like a sentence-pair written specifically for this store. If you can imagine the SAME paragraph on a different-industry store, rewrite Sentence 2 with stronger industry-tinted attributes.

BANNED in this paragraph regardless of framing:
- Specific product / item nouns (apply the 3-test diagnostic from OFFERINGS POLICY above).
- Enumeration lists with concrete products ("X, Y, and Z").
- Brand / collection names not in the source.
- Specific materials tied to items.
- Demographic narrowing not in the source.

VALUE STRIP VOCABULARY BANKS (GUIDANCE — phrases pre-vetted as truly industry-agnostic)

The three value slots ({{VALUE_QUALITY}}, {{VALUE_CARE}}, {{VALUE_TRUST}}) MUST be composed in natural, lightly varied prose. Each slot draws on the relevant bank below for VOCABULARY — these phrases have been pre-vetted to be safe across every industry (no leakage toward home / furniture / construction / fashion-specific / beauty / tech / etc.). Use them as your composition vocabulary; you may quote one verbatim per slot, or weave the spirit of one into a natural sentence — whichever reads better.

IMPORTANT — do NOT repeat the industry name {{nganh_hang}} inside the value slots. The industry is already named in the What We Offer section and the Intro / Mission paragraphs; repeating it in every value slot reads as keyword-stuffing and feels mechanical. Keep the value slots ABSTRACT — use neutral subjects like "we", "our pieces", "our team", "everything we stock", "every order", "every reply" instead of "our {{nganh_hang}} pieces".

VOICE RULE (AUTHORITATIVE — applies to every value slot AND to Sentence 2 of "What We Offer")

The store voice is RETAIL / SHOP / CURATOR. The store CHOOSES, SELECTS, STOCKS, CARRIES, CURATES what it sells. The store does NOT make, craft, forge, or produce the items it sells unless the SOURCE PAGE or the {{nganh_hang}} value explicitly establishes the store as a maker / artisan / handmade brand.

BANNED maker-coded language (regardless of how it's combined):
- "craft" / "craftsmanship" / "handcrafted" / "handmade" / "small-batch"
- "thoughtfully made" / "made with intent" / "made with care" — anywhere the verb "made" implies in-house production rather than sourcing
- "forged" / "artisan" / "artisanal"
- "from our workshop" / "in our studio we make" / "our team builds"
- "honest craft" / "considered making" / "the work we make"

WHY: 99% of online stores in this plugin's target market RESELL / DROPSHIP / CURATE products — they do not manufacture. Claiming "thoughtful craftsmanship" on a curator store is a GMC Misrepresentation hazard (claiming a quality assurance the store cannot actually back). The retail voice ("carefully chosen", "selected with care", "stocked with attention", "picked with intent") is honest AND industry-agnostic.

PREFERRED retail verbs (use these everywhere instead):
- "chosen", "carefully chosen", "selected", "picked", "curated", "stocked", "carried", "sourced"
- The OBJECT is what the store sells, NOT the verb subject (i.e. "we choose <items>", not "we craft <items>").

EXCEPTION — maker voice is permitted only when:
  (a) {{nganh_hang}} explicitly contains a maker-coded term: "handmade", "artisan", "craft", "custom", "made-to-order", "bespoke", "small-batch", "handcrafted", OR
  (b) the SOURCE PAGE clearly states the store makes / crafts / produces its own items in-house (e.g. "in our studio in Brooklyn we make every piece by hand").
In that case AND ONLY in that case, the words "make / craft / handmade / artisan" are permitted and natural.

BANK A — VALUE_QUALITY (focus: how items are CHOSEN / SELECTED / STOCKED — curator voice, NOT maker voice)
  1. carefully chosen across the board
  2. chosen with attention
  3. selected with care
  4. picked with intent
  5. curated with attention to detail
  6. considered choices, one at a time
  7. everyday quality you can rely on
  8. selected one piece at a time
  9. the same care from start to finish in what we stock
  10. attention put into what makes the cut
  ⚠️ Maker-voice phrases REMOVED on purpose ("made with intent", "thoughtfully made", "honest craft from start to finish") — see VOICE RULE above. If you find yourself reaching for those, switch to one of the curator-voice phrases above instead.

BANK B — VALUE_CARE (focus: customer support / service)
  1. easy to reach when you need us
  2. quick to respond
  3. ready to help, from the first message
  4. real people answering, not scripts
  5. clear answers, fast
  6. straightforward support
  7. simple, honest responses to every question
  8. always reachable through email or phone
  9. helpful from start to finish
  10. patient and clear in every reply

BANK C — VALUE_TRUST (focus: trust / transparency / secure checkout)
  1. clear from start to finish, no surprises
  2. honest in every detail
  3. transparent every step of the way
  4. secure checkout you can trust
  5. plain, honest communication throughout
  6. nothing hidden in the fine print
  7. straightforward in every transaction
  8. clarity from order to delivery
  9. trust built on transparent details
  10. honest expectations, honest follow-through

BANK USAGE RULES:
- Draw on each slot's bank for vocabulary. You MAY quote a phrase verbatim, OR compose a natural sentence in its spirit — whichever reads better. Do NOT invent your own phrasing outside the spirit of the bank (e.g. don't reach for "built to last" / "durable construction" / "honest materials" — those are banned by ALLOWED PHRASING above).
- Each slot is 1–2 short sentences. Aim for natural reading, not a fixed pattern. Skip a second sentence if you can't write it cleanly.
- Do NOT reuse the same phrase across two slots.
- Do NOT name {{nganh_hang}} inside any value slot — keep the value strip abstract. (Industry mention quota for the page is satisfied by What We Offer + Intro / Mission elsewhere.)
- Do NOT add a specific product noun anywhere in any value slot (e.g. "our backpacks", "our serum" stays banned everywhere).

REQUIRED SECTIONS (all must appear, in this order)
1. Mission / Brand Promise — one short paragraph stating what the store stands for. No time anchors.
2. Our Story — origin and inspiration. MUST contain exactly one founding sentence per the FOUNDING + NEWNESS POLICY above ("Founded in {{founding_year}}, {{ten_web}} is a newly launched …" or one of the approved variations). {{founding_year}} is always populated — never omit the founding sentence. One short paragraph; draw only on facts present in the source for everything else.
3. What We Offer — MUST follow the OFFERINGS POLICY above. One short paragraph that names the {{nganh_hang}} category at most once and otherwise uses abstract / emotional / process language. No specific product-type nouns, no invented collection names, no item enumeration.
4. Our Commitment — quality, customer care, fair pricing, secure checkout, and responsive support.
5. Why Customers Choose Us — three concise reasons, framed as benefits not slogans. No time anchors. No specific product sub-type nouns here either — stay at the same abstract breadth as section 3.
6. Contact / Call to Action — invite readers to get in touch via {{email_web}} or {{so_dien_thoai}}.

STRICT RULES
- Founding + newness framing: follow the FOUNDING + NEWNESS POLICY above. Exactly one founding sentence in Our Story ("Founded in {{founding_year}}, {{ten_web}} is a newly launched …" or one of the approved variations). The page MUST always carry the founding sentence — {{founding_year}} is always populated. NEVER write a year other than {{founding_year}}; never copy a year from the source; never write a month, day, quarter, "anniversary", or "first year".
- Offerings framing: follow the OFFERINGS POLICY above. No specific product sub-type nouns ANYWHERE on the page (not in the story, not in the commitment, not in the "why choose us" reasons, not in the CTA), and no invented collection names. The only specific category label allowed is {{nganh_hang}}, used at most once in the What We Offer section.
- Do NOT invent founder names, awards, employee counts, store locations, customer counts, or milestones that are not in the source.
- Do NOT claim multi-year experience, multi-year track record, "trusted for years", "years of experience", "serving thousands", or anything that contradicts the newness framing or that is unprovable.
- Use {{ten_web}} for the store name — never the source page's store name, which belongs to a different business.
- If the source page contains any email, address, or store-name literals, DO NOT copy them; substitute the COMPANY CONTEXT values above.
- Clear, warm, professional English. No marketing fluff ("world-class", "best in the industry"). No emojis. No exaggerations.
- Rewrite wording; do not copy sentences verbatim.

SLOT MAP (every {{PLACEHOLDER}} in the LAYOUT SKELETON below must be filled with the content described here — do NOT swap topics across slots)
- {{HEADING}} — page H1. 3–8 words, no full sentence, no trailing period.
- {{INTRO}} — 1–2 sentences framing the page and the store. Plain text or a single <p>.
- {{STORY}} — section 2 body (Our Story). One short paragraph that positions {{ten_web}} as newly launched / in its early days. MUST contain exactly one founding sentence per the FOUNDING + NEWNESS POLICY above: "Founded in {{founding_year}}, {{ten_web}} is a newly launched [shop / studio / brand], still in our early days." (or one of the approved variations). {{founding_year}} is always populated by the plugin — never omit the founding sentence. Never write a year other than {{founding_year}}, never write a month or day, do not invent founder names or cities.
- {{MISSION}} — section 1 body (Mission / Brand Promise). One short paragraph stating what the store stands for.
- {{OFFERINGS}} — section 3 body (What We Offer). This is the GMC reviewer's primary anchor for "what does this store sell?" and the section that MUST read as written for THIS industry specifically. Follow the REQUIRED PARAGRAPH SHAPE for "What We Offer" above STRICTLY:
    Sentence 1: name {{nganh_hang}} once via one of the 6 industry-naming shapes.
    Sentence 2: process language + 2–3 attribute words DERIVED FROM YOUR STEP 1 LIST D (industry context-words you compiled in INDUSTRY DOMAIN LOCK). The attribute words are what make the paragraph industry-specific; if they're generic, the paragraph leaks.
    Sentence 3 (optional): use-case from your STEP 1 list B.
  Apply OFFERINGS POLICY at the same time: zero specific product sub-type nouns (3-test diagnostic), zero enumeration lists, zero invented collection names. Target ≈50–90 words.
- {{VALUE_QUALITY}} — section 5, first reason. 1–2 short sentences framed as a customer benefit related to product quality. Draw on BANK A (VALUE_QUALITY) for vocabulary per the VALUE STRIP VOCABULARY BANKS rules above — quote verbatim or compose in the bank's spirit. CRITICAL: follow VOICE RULE — use curator verbs ("chosen", "selected", "picked", "stocked", "curated"), NOT maker verbs ("made", "crafted", "handmade") unless the maker exception applies. Do NOT name {{nganh_hang}} inside this slot. NEVER attach a specific item noun ("our <thing>", "our premium <thing>" is forbidden regardless of what <thing> is).
- {{VALUE_CARE}} — section 5, second reason. 1–2 short sentences about customer care / support. Draw on BANK B (VALUE_CARE) for vocabulary per the VALUE STRIP VOCABULARY BANKS rules. Do NOT name {{nganh_hang}} inside this slot. No product sub-type nouns.
- {{VALUE_TRUST}} — section 5, third reason. 1–2 short sentences about trust / secure checkout / honest communication. Draw on BANK C (VALUE_TRUST) for vocabulary per the VALUE STRIP VOCABULARY BANKS rules. Do NOT name {{nganh_hang}} inside this slot. No product sub-type nouns.
- {{COMMITMENT}} — section 4 body (Our Commitment). One short paragraph. Do not invent guarantees absent from the source.
- If a topic is thin in the source, write a short generic line consistent with the SCOPE rule rather than leaving the slot empty or inventing specifics.

OUTPUT
Return ONLY the final page content using the exact Flatsome UX Builder shortcode layout provided in LAYOUT SKELETON. Fill every {{PLACEHOLDER}}. No markdown fences, titles, or commentary.

LAYOUT SKELETON
{{SKELETON}}

SOURCE PAGE TO REWRITE
{{PAGE_HTML}}
PROMPT,
];
