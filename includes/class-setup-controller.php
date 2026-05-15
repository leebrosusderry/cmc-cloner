<?php
/**
 * Setup Controller — renders the "CMC Cloner → Site Setup" screen and
 * handles its POST actions.
 *
 * Screen layout:
 *   1. General         — one click syncs blogname + primary product_cat
 *                        name/slug from Settings (ten_web, nganh_hang).
 *   2. Products        — Amazon search link-out, Title Rewriter link-out,
 *                        and the Product Image Rename toolbox.
 *   3. Homepage        — homepage prompt builder (V7). Substitutes
 *                        {{nganh-hang}}, {{ten-shop}}, {{primary-color}},
 *                        {{ten-website}}, and a fresh-per-render
 *                        {{layout-number-random}} (integer 1-30) into the
 *                        prompt template. Also exposes the custom-CSS
 *                        editor and Unsplash image search link-out.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Setup_Controller {

    public const MENU_SLUG     = 'cmc-cloner-setup';
    public const NONCE_ACTION  = 'cmc_cloner_setup';
    public const STATE_OPTION  = 'cmc_setup_state';
    public const CSS_OPTION    = 'cmc_cloner_custom_css';

    private const ACTION_RENAME_CATEGORY  = 'cmc_setup_rename_category';
    private const ACTION_SAVE_CSS         = 'cmc_setup_save_css';

    public static function init(): void {
        add_action( 'admin_init', [ self::class, 'handle_post' ] );
    }

    // ---------- Render ----------

    public static function render_page(): void {
        $state            = self::get_state();
        $settings         = CMC_Settings::get();
        $company          = (array) $settings['company_info'];
        $nganh_options    = CMC_Shortcodes::nganh_hang_options();
        $nganh_slug       = (string) ( $company['nganh_hang'] ?? '' );
        $nganh_label      = (string) ( $nganh_options[ $nganh_slug ] ?? $nganh_slug );
        $ten_web          = (string) ( $company['ten_web'] ?? '' );
        $ten_website      = (string) ( $company['ten_website'] ?? '' );
        $primary_color    = (string) ( $settings['primary_color'] ?? '#2ec4b6' );

        $site_title       = (string) get_option( 'blogname' );
        $product_cats     = self::product_categories();
        $primary_cat      = self::pick_primary_category( $product_cats );
        $woocommerce_on   = self::is_woocommerce_active();
        $custom_css       = self::get_custom_css();

        // Phase 3: read the layout dropdown choice from URL. Whitelist
        // against the actual list of skeletons on disk so a stale or
        // crafted value silently falls back to Random rather than 500ing.
        $homepage_skeletons = self::list_homepage_skeletons();
        $valid_layout_ids   = array_column( $homepage_skeletons, 'id' );
        $homepage_layout_choice = '';
        if ( isset( $_GET['cmc_layout'] ) ) {
            $candidate = (string) $_GET['cmc_layout'];
            if ( in_array( $candidate, $valid_layout_ids, true ) ) {
                $homepage_layout_choice = $candidate;
            }
        }

        $homepage_prompt_template = self::homepage_prompt_template();
        $built                    = self::build_homepage_prompt(
            $homepage_prompt_template,
            $nganh_label,
            $ten_web,
            $primary_color,
            $ten_website,
            $homepage_layout_choice
        );
        $homepage_prompt_filled  = $built['filled'];
        $homepage_prompt_missing = $built['missing'];

        $flash = self::consume_flash();

        include CMC_CLONER_DIR . 'includes/views/setup-page.php';
    }

    // ---------- Homepage prompt template ----------

    /**
     * Substitute Settings values into the homepage prompt template.
     *
     * Token mapping:
     *   {{nganh-hang}}            → {{<niche label>}}    (mustache wrapper kept)
     *   {{ten-shop}}              → {{<shop name>}}      (mustache wrapper kept)
     *   {{primary-color}}         → {{#hex>}}            (mustache wrapper kept)
     *   {{ten-website}}           → https://shop.com     (raw URL, trailing / stripped)
     *   {{layout-number-random}}  → integer in [1,30]    (fresh per render)
     *   {NICHE} / {SHOP_NAME} / {COLOR} → raw value      (body references)
     *
     * Empty values are skipped so the placeholder stays visible, signalling
     * what's missing. The seed is always emitted regardless of other inputs
     * — its only failure mode is wp_rand() being unavailable, which never
     * happens inside an admin page render.
     *
     * Returns [ 'filled' => string, 'missing' => array<string> ].
     */
    public static function build_homepage_prompt( string $template, string $nganh, string $shop, string $color, string $site_url = '', string $layout_override = '' ): array {
        $pairs   = [];
        $missing = [];

        if ( $nganh !== '' ) {
            $pairs['{{nganh-hang}}'] = '{{' . $nganh . '}}';
            $pairs['{NICHE}']        = $nganh;
        } else {
            $missing[] = 'Industry';
        }

        if ( $shop !== '' ) {
            $pairs['{{ten-shop}}'] = '{{' . $shop . '}}';
            $pairs['{SHOP_NAME}']  = $shop;
        } else {
            $missing[] = 'ten_web';
        }

        if ( $color !== '' ) {
            $pairs['{{primary-color}}'] = '{{' . $color . '}}';
            $pairs['{COLOR}']           = $color;
        } else {
            $missing[] = 'primary color';
        }

        if ( $site_url !== '' ) {
            $pairs['{{ten-website}}'] = rtrim( $site_url, '/' );
        } else {
            $missing[] = 'ten_website';
        }

        // V8: layout selection drives both the seed display AND the
        // skeleton injection. Pick a skeleton from skeletons/homepage/ —
        // the seed maps deterministically to a skeleton ID. With N
        // skeletons available, seed [1, 30] maps via (seed-1) % N → 0..N-1.
        // Phase 3: if the user chose a specific layout via the Setup-page
        // dropdown (?cmc_layout=L3), `load_homepage_skeleton()` short-
        // circuits the seed math and loads that exact file.
        $seed     = wp_rand( 1, 30 );
        $skeleton = self::load_homepage_skeleton( $seed, $layout_override );

        $pairs['{{layout-number-random}}'] = (string) $seed;

        if ( $skeleton !== null ) {
            $pairs['{{LAYOUT_ID}}']             = (string) $skeleton['id'];
            $pairs['{{LAYOUT_NAME}}']           = (string) $skeleton['name'];
            $pairs['{{SKELETON_IMAGE_BUDGET}}'] = (string) $skeleton['image_budget'];
            $pairs['{{SKELETON_TEXT_BUDGET}}']  = (string) $skeleton['text_budget'];
            $pairs['{{SKELETON_HTML}}']         = (string) $skeleton['html'];
        } else {
            // No skeleton on disk — keep the prompt visibly broken so the
            // user notices and re-installs the skeletons folder. Don't
            // silently fall back to V7 since the V8 template assumes
            // skeleton tokens are present.
            $missing[] = 'homepage skeleton (skeletons/homepage/)';
        }

        $filled = $pairs ? strtr( $template, $pairs ) : $template;
        return [ 'filled' => $filled, 'missing' => $missing ];
    }

    /**
     * Load a homepage skeleton. Two modes:
     *   - $override is a layout ID like "L3" → load that exact file.
     *   - $override is empty → seed-based pick: (seed-1) % count.
     *
     * Skeletons live at skeletons/homepage/skeleton-L*.php. Each file
     * returns an associative array. Files are sorted naturally so the
     * same seed always picks the same skeleton across reloads.
     */
    public static function load_homepage_skeleton( int $seed, string $override = '' ): ?array {
        $dir = CMC_CLONER_DIR . 'skeletons/homepage/';
        if ( ! is_dir( $dir ) ) {
            return null;
        }
        $files = glob( $dir . 'skeleton-L*.php' );
        if ( ! is_array( $files ) || empty( $files ) ) {
            return null;
        }
        sort( $files, SORT_NATURAL );

        $file = '';
        if ( $override !== '' && preg_match( '/^L\d+$/', $override ) ) {
            $candidate = $dir . 'skeleton-' . $override . '.php';
            if ( in_array( $candidate, $files, true ) ) {
                $file = $candidate;
            }
        }
        if ( $file === '' ) {
            $count = count( $files );
            $idx   = ( max( 1, $seed ) - 1 ) % $count;
            $file  = $files[ $idx ];
        }

        $skeleton = include $file;
        if ( ! is_array( $skeleton )
            || empty( $skeleton['id'] )
            || empty( $skeleton['html'] ) ) {
            return null;
        }
        $skeleton += [
            'name'         => $skeleton['id'],
            'image_budget' => '(no image budget specified)',
            'text_budget'  => '(no text budget specified)',
        ];
        return $skeleton;
    }

    /**
     * List every available homepage skeleton — used by the Setup-page
     * dropdown so it stays in sync with whatever files exist on disk.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public static function list_homepage_skeletons(): array {
        $dir = CMC_CLONER_DIR . 'skeletons/homepage/';
        if ( ! is_dir( $dir ) ) {
            return [];
        }
        $files = glob( $dir . 'skeleton-L*.php' );
        if ( ! is_array( $files ) || empty( $files ) ) {
            return [];
        }
        sort( $files, SORT_NATURAL );

        $list = [];
        foreach ( $files as $file ) {
            $skel = include $file;
            if ( ! is_array( $skel ) || empty( $skel['id'] ) ) {
                continue;
            }
            $list[] = [
                'id'   => (string) $skel['id'],
                'name' => (string) ( $skel['name'] ?? $skel['id'] ),
            ];
        }
        return $list;
    }

    /**
     * The Flatsome-homepage prompt template (V7 master prompt). Extend
     * this string to add sections — these tokens are substituted by
     * build_homepage_prompt() when the user clicks "Sinh prompt cho site
     * này" on the Site Setup screen:
     *
     *   {{nganh-hang}}            niche label   (mustache wrapper kept)
     *   {{ten-shop}}              shop name     (mustache wrapper kept)
     *   {{primary-color}}         hex color     (mustache wrapper kept)
     *   {{ten-website}}           site URL      (raw URL, used in fetch
     *                                            commands like
     *                                            {{ten-website}}/about-us)
     *   {{layout-number-random}}  integer 1-30  (fresh per render — drives
     *                                            the Layer 3 random-section
     *                                            picker so each click yields
     *                                            a different layout)
     *   {NICHE} / {SHOP_NAME} / {COLOR}    body references (raw value)
     *
     * Keep the tokens exactly as-written or the replacer will not find
     * them.
     */
    public static function homepage_prompt_template(): string {
        // V8 — skeleton-driven prompt. The page layout is no longer
        // chosen by the AI. Server-side, build_homepage_prompt() picks a
        // skeleton from skeletons/homepage/skeleton-L*.php based on seed
        // and substitutes:
        //   {{LAYOUT_ID}}              → e.g. "L1"
        //   {{LAYOUT_NAME}}            → human label
        //   {{SKELETON_IMAGE_BUDGET}}  → per-slot image research brief
        //   {{SKELETON_TEXT_BUDGET}}   → per-slot text length / tone hints
        //   {{SKELETON_HTML}}          → the literal Flatsome shortcode
        //                                 template with placeholder placeholders
        //
        // The AI's job collapses from "design a 5-6 section homepage"
        // (which kept converging to the same 8 layouts) to "fill the
        // tokens in this skeleton". Layout variety comes from the number
        // of skeletons, not seed math. Adding a new layout = drop in
        // skeleton-L9.php — no prompt edit, no AI re-tuning.
        return <<<'PROMPT'
# FLATSOME HOMEPAGE GENERATOR — V8 (Skeleton-driven)

Bạn là chuyên gia thiết kế web & content cho ecommerce store bán hàng trên Google Merchant Center, hiểu sâu Flatsome theme. Tạo 1 homepage Flatsome hoàn chỉnh theo các tham số dưới đây.

═══════════════════════════════════════════════
📥 INPUT
* NICHE: {{nganh-hang}}
* SHOP_NAME: {{ten-shop}}
* COLOR: {{primary-color}}
* SITE_URL: {{ten-website}}
* VARIANT_SEED: {{layout-number-random}}
* LAYOUT_ID: {{LAYOUT_ID}}
* LAYOUT_NAME: {{LAYOUT_NAME}}

EXISTING PAGES (AI fetch tự động):
* ABOUT_URL:   {{ten-website}}/about-us
* CONTACT_URL: {{ten-website}}/contact-us

Validation:
* Tất cả URL phải public, content thật ≥ 100 từ. Fetch fail = STOP, KHÔNG bịa.
* LAYOUT_ID phải có giá trị. Nếu rỗng → skeleton không load được → STOP, báo user "Skeleton folder missing".

═══════════════════════════════════════════════
🪢 LAYER 0 — BRAND ANCHOR FETCH PROTOCOL (chạy ĐẦU TIÊN, trước mọi layer khác)

Bước 1 — FETCH:
* 1.1. Fetch ABOUT_URL → đọc full text
* 1.2. Fetch CONTACT_URL → extract địa chỉ / email / phone / hours

Bước 2 — VERIFY:
Nếu bất kỳ URL nào trả về:
* HTTP error (404, 403, 500…)
* Empty body / placeholder ("Coming Soon", "Lorem ipsum")
* Length < 100 từ
* HTML rỗng nghi JS-rendered (<div id="root"></div> rỗng)
→ STOP NGAY. KHÔNG generate. KHÔNG bịa fact. In thông báo lỗi:
  "❌ Fetch failed: [URL]. Vui lòng:
   (a) Kiểm tra URL public trong tab ẩn danh
   (b) Đảm bảo About Us có content thật ≥ 100 từ
   (c) Nếu site dùng JS render → switch sang static HTML / SSR
   ⚠️ V7 KHÔNG có FALLBACK — content bịa = GMC Misrepresentation = disapproval account."

Bước 3 — EXTRACT (in bảng để user verify trước khi generate):
| Field                      | Source     | Extracted Value |
|----------------------------|------------|-----------------|
| Founded year               | About Us   | …               |
| Location                   | About Us   | …               |
| Team size / structure      | About Us   | …               |
| Tone keywords (3-5 từ)     | About Us   | …               |
| Process / craft language   | About Us   | …               |
| Materials mentioned        | About Us   | …               |
| Email                      | Contact    | …               |
| Phone                      | Contact    | …               |
| Studio / address           | Contact    | …               |
| Forbidden claims (suy luận)| Both       | …               |

Bước 4 — LOCK:
* Mọi fact trên homepage = subset của Brand Anchor table
* Cấm thêm fact mới (năm, người, nơi, vật liệu, quy trình) ngoài bảng
* Tone of voice toàn trang phải match "Tone keywords"
* Nếu About Us không nhắc claim X → homepage tuyệt đối không thêm claim X

Bước 5 — proceed Layer 1+
═══════════════════════════════════════════════
🔴 LAYER 1 — HARD RULES + TECHNICAL CONSTRAINTS (verify trước khi trả output)

* H0 — ❌ TUYỆT ĐỐI CẤM Q&A / FAQ / Accordion / Help / Answers section dưới MỌI hình thức, MỌI tên gọi, MỌI vị trí (kể cả cuối trang, sidebar, footer area).
   Cấm các biến thể tên: "FAQ", "Frequently Asked", "Frequently Asked Questions", "Answers", "Answered", "Answered Honestly", "Good to Know", "Things to Know", "Help", "Help Center", "Support", "Common Questions", "Questions", "Q&A", "Ask Us", "You Asked", "We're Here to Help", "Need Help?", "The Details", "Details", "Policies", "Fine Print".
   Cấm các shortcode/block: [accordion], [accordion-item], [tabs] dùng để chứa câu hỏi-trả lời, <details>, <summary>, toggle list với dấu "+" / "−" chứa nội dung returns / shipping / materials / sizing / support.
   Cấm mọi layout pattern: heading "answer/help style" + chuỗi câu hỏi dưới dạng toggle, dù có hay không có icon "+".
   Lý do: FAQ trên homepage đã được loại bỏ khỏi scope — mọi nội dung Q&A thuộc policy page riêng. Homepage xuất hiện FAQ/Answers dưới bất kỳ tên nào đều VIOLATE rule này.
   Verify: grep output cho "accordion", "answered", "frequently", "questions", "q&a", "help center", "good to know", "the details", "<details", "<summary" → phải = 0.

* H0b — ❌ TUYỆT ĐỐI CẤM Testimonials / Customer Reviews / Quote-from-Customer / Star Ratings dưới MỌI hình thức, MỌI tên gọi, MỌI vị trí. Cấm tự bịa lời khen của khách hàng — GMC coi review giả là Misrepresentation và đình chỉ account.
   Cấm biến thể tên section: "Testimonials", "Reviews", "Customer Reviews", "What Our Customers Say", "Customer Voices", "Loved By", "Praise", "Kind Words", "From Our Customers", "Customer Stories", "5-Star Reviews".
   Cấm pattern: blockquote chứa lời nói khách hàng + tên người + thành phố/quốc gia; quote-card grid 2-3-4 cột với speech-mark; "stars rating" cụm icon ★★★★★ kèm số rating; "Trusted by N customers" + counter.
   Cấm shortcodes: [testimonial], [testimonials_slider], [ux_stars rating="..."]; bất kỳ <blockquote> nào có pattern "... — Name, City".
   Cấm cả khi skeleton chính KHÔNG có testimonial slot — AI không được tự ý thêm.
   Lý do: clone site không có khách hàng thật để trích dẫn; mọi quote do AI tạo ra = review giả = GMC Misrepresentation. Trust signals hợp pháp duy nhất là brand-story facts đã verify trong About Us / Contact.
   Verify: grep output cho "testimonial", "review", "loved by", "kind words", "stars", "rating", "— [A-Z][a-z]+, [A-Z]" → phải = 0.

* H1 — Đúng 1 <h1> toàn trang, trong hero đầu tiên. Còn lại <h2>/<h3>.
* H2 — Zero shipping claims. Cấm "Free shipping", "Ships in X days", thời gian/vùng/ngưỡng ship, hãng vận chuyển. Nếu cần đề cập shipping ở bất kỳ chỗ nào → dùng "See our Shipping Policy page for options and timelines."
* H3 — Zero tên thương hiệu nổi tiếng (Nike, Adidas, Puma, Apple, Samsung, Gucci, LV, Chanel, Disney, Marvel, Pixar, Ray-Ban, Oakley, Converse, Vans, Getty, Shutterstock, Fisher-Price, Lego, Melissa & Doug, Gymshark, Lululemon…) trong text / alt / URL.
* H4 — Zero giá/khuyến mãi hardcoded ($49, 50% off, "today only", "limited time", "100% guaranteed", ALL-CAPS câu, "!!"). Dùng cụm trung tính: "Customer-favorite", "Crafted for…", "Built to last", "Explore new collection".
* H5 — Mọi <img> có loading="lazy" + referrerpolicy="no-referrer" + descriptive alt (không brand/shop name, không SEO-stuffing). Fallback qua CSS background trên wrapper (không dùng onerror JS — bị wp_kses strip).
* H6 — Zero tên sản phẩm/collection/SKU bịa đặt. AI KHÔNG được tự nghĩ ra tên như "Ora Collection", "Moon & Meadow Edit", "Cast Iron Kettlebell", "Adjustable Dumbbell Pair" trong headline / paragraph / testimonial attribution / button text / mosaic label. Lý do: GMC crawler đối chiếu homepage với product feed — tên AI bịa không có trong feed → Misrepresentation disapproval.
* H7 — Zero descriptive product nouns trong mọi visible text (hero headline/para, CTA, mosaic label, testimonial quote, brand story, value strip, CTA split).❌ CẤM mọi danh từ chỉ loại sản phẩm cụ thể, kể cả dạng chung:
* "wooden stacker", "plush toy", "teething ring", "tote bag", "clutch", "crossbody", "shoulder bag", "training tee", "dumbbell", "resistance band", "kettlebell", "lifting belt", "moisturizer", "serum", "necklace", "leash", "chew toy", "running shoe", "sneaker", "t-shirt", "hoodie", "mug", "candle", "backpack"…✅ CHỈ được dùng:
    * Category tổng của {NICHE} (tối đa 2 lần toàn trang): "handbags", "baby toys", "fitness gear", "skincare", "footwear", "jewelry", "home goods"…
    * Abstract nouns: "pieces", "essentials", "the range", "our work", "what we make", "studio favorites", "this season's edit", "the edit", "the line"
    * Emotional/process language: "crafted slowly", "built to last", "made with intent", "chosen with care", "designed around how you live"
    * Materials ở cấp rất chung (không gắn với sản phẩm): "natural materials", "quality fabrics", "honest materials" — KHÔNG "cast iron kettlebell", "full-grain leather tote"
* Lý do: Homepage content được GMC crawler đối chiếu với product feed. Nếu homepage nhắc "tote bag" nhưng feed chỉ có clutch → Misrepresentation. Ngay cả generic noun cũng phải phù hợp 100% catalog thật — an toàn nhất là không dùng chút nào.
* H8 — Button/link whitelist. Mọi [button link="..."], <a href="...">, .nt-mosaic > a, ShopBy card link, CTA link CHỈ được trỏ tới 1 trong 5 path:
    1. / — trang chủ
    2. /shop/ — danh sách sản phẩm (WooCommerce default, 99% store có)
    3. /about-us/ — trang giới thiệu
    4. /contact-us/ — trang liên hệ
    5. /cart/ — giỏ hàng (chỉ khi thực sự cần)
* ❌ TUYỆT ĐỐI CẤM:
    * /collections/{bất-kỳ-slug-nào}/
    * /category/{slug}/ hoặc /product-category/{slug}/
    * /product/{slug}/
    * /new-in/, /journal/, /makers/, /care/, /gifts/, /collections/ora/… — mọi slug AI tự nghĩ
    * Anchor hash #section trừ khi link tới anchor có thật trên cùng trang
    * # rỗng
* Lý do: AI không biết cấu trúc site thực. Link ngoài whitelist = 99% dead link → GMC Landing page not accessible → disapproval. /shop/ là WooCommerce default an toàn tuyệt đối.
* H9 — Single-category rule. Homepage chỉ được claim đúng 1 category = chính {NICHE}. Cấm chia nhỏ thành sub-categories.Block nào bị ảnh hưởng:
    * ShopBy.4Cards → đổi thành Intent.4Cards (4 card dùng label mood/intent, KHÔNG label sản phẩm). Tất cả 4 card link /shop/. Labels gợi ý: "For Everyday" / "For Gifting" / "New Additions" / "Studio Favorites" / "Discover the Range" / "In the Studio"
    * ImageMosaic.5tiles → đổi thành MoodMosaic.5tiles. Labels là mood/concept, KHÔNG sub-category. Tất cả 5 tile link /shop/. Gợi ý labels: "The Range" / "Made Slowly" / "For Everyday" / "New In" / "Studio Favorites" — hoặc thơ ca "Quietly Made" / "Carried Daily" / "In the Studio" / "This Season" / "See All"
    * Tabs.4 → giảm còn Tabs.2 max, dùng orderby khác nhau trên cùng 1 catalog. CẤM cat="{slug}" trong [ux_products]:
        * Tab 1: "Favorites" → [ux_products type="row" orderby="popularity" products="8"]
        * Tab 2: "New" → [ux_products type="row" orderby="date" order="desc" products="8"]
    * Featured.SingleCollection → gọi là "This Season's Edit" / "Studio Favorites", link /shop/. Cấm đặt tên collection riêng.
* Lý do: GMC đối chiếu category claims trên homepage với taxonomy thực tế. Homepage claim 5 sub-cat nhưng feed chỉ có 1 → Misrepresentation. 1 category = 100% khớp mọi shop trong niche.
───────────────────────────────────────────────
🖼️ LAYER 2 — IMAGE URL POLICY (zero-broken-link guarantee)

Mọi slot {{IMG_*_URL}} trong skeleton PHẢI là một URL ảnh thực, trực tiếp, 100% chắc chắn không 404. KHÔNG SVG placeholder, KHÔNG token {UPLOAD_URL}, KHÔNG URL trang web (chỉ direct CDN).

2.1. URL FORMAT — Picsum (DEFAULT, guaranteed 200, không bao giờ 404)

Mỗi slot ảnh PHẢI dùng đúng pattern này:

  https://picsum.photos/seed/{SEED}/{WIDTH}/{HEIGHT}

Trong đó:
* {SEED} = chuỗi ASCII lowercase, kebab-case, kết hợp niche-slug + slot + index để mỗi URL deterministic ra 1 ảnh khác nhau. Ví dụ (tham khảo):
    - hero landscape   → picsum.photos/seed/fashion-apparel-hero/1200/800
    - story image      → picsum.photos/seed/fashion-apparel-story/1200/900
    - mosaic tile 1-5  → picsum.photos/seed/fashion-apparel-mosaic-1/1200/1200 ... -5
    - cta split        → picsum.photos/seed/fashion-apparel-cta/1200/900
    - intent card 1-4  → picsum.photos/seed/fashion-apparel-intent-1/800/800 ... -4
* {WIDTH}/{HEIGHT}      = theo dimension table 2.3.
* Cùng SEED → cùng ảnh forever (deterministic, cache-friendly). Khác SEED → ảnh khác.

Lý do bắt buộc Picsum làm DEFAULT:
- Service public, uptime cao, KHÔNG bao giờ trả 404 với pattern URL đúng.
- Không phụ thuộc Pexels/Unsplash ID có thật (AI không thể fetch để verify → rủi ro 404 rất cao nếu dùng các source đó).
- Deterministic: clone 2 site cùng niche → cùng SEED → cùng ảnh; muốn variety thì đổi prefix SEED.

2.2. ALTERNATIVE SOURCES (chỉ khi CHẮC CHẮN URL còn sống — mặc định fallback về Picsum khi không chắc):
* Pexels CDN: https://images.pexels.com/photos/{ID}/pexels-photo-{ID}.jpeg?auto=compress&cs=tinysrgb&w=1200
* Unsplash CDN: https://images.unsplash.com/photo-{ID}?auto=format&fit=crop&w=1200&q=80

❌ KHÔNG dùng Pixabay date-path URLs — pattern dễ break.
❌ KHÔNG dùng URL trang web (/photos/, /s/photos/) — chỉ direct CDN.
❌ Nếu không chắc URL còn sống → BẮT BUỘC fallback Picsum, không guess.

2.3. DIMENSIONS — match slot type:
  Hero landscape   → 1200x800   (3:2)
  Hero portrait    → 900x1200   (3:4, kèm class is-portrait nếu skeleton dùng)
  Story image      → 1200x900   (4:3)
  Mosaic tile      → 1200x1200  (1:1)
  CTA split        → 1200x900   (4:3)
  Intent card      → 800x800    (1:1)

2.4. UNIQUENESS — mỗi slot có SEED khác nhau. Không 2 slot dùng cùng SEED (tránh trùng ảnh trên trang).

2.5. ALT TEXT FORMAT (cho mỗi {{IMG_*_ALT}}):
✅ Plain English 6-10 từ, mood/composition. Ví dụ: "soft morning light over neutral linen surface".
❌ Tên shop, tên brand, SEO-stuff, danh từ sản phẩm cụ thể, từ "Picsum" hay tên CDN.
───────────────────────────────────────────────
🧱 LAYER 3 — SKELETON (server-picked layout, AI ONLY fills tokens)

Server-side đã chọn skeleton {{LAYOUT_ID}} — {{LAYOUT_NAME}} cho lượt generate này.
AI KHÔNG được tự thiết kế lại layout. Nhiệm vụ: thay thế 100% các mustache-style placeholder (cú pháp: hai dấu ngoặc nhọn ôm chuỗi UPPERCASE_SNAKE_CASE) trong skeleton bên dưới bằng nội dung phù hợp.

❌ TUYỆT ĐỐI CẤM:
* Sửa thứ tự / tên / số lượng [section], [row], [col]
* Sửa tên class CSS (`nt-l1-*`, `nt-eyebrow`, `nt-hero-media`, …)
* Thêm/bớt attribute trên shortcode ([button text="..." color="..." ...])
* Wrap thêm <div> ngoài skeleton, đổi cấu trúc HTML
* Đổi shortcode `[ux_products …]` thành thứ khác
* Bỏ `loading="lazy"` hoặc `referrerpolicy="no-referrer"` trên <img>

✅ ĐƯỢC PHÉP:
* Thay placeholder bằng giá trị tuân thủ Image Budget + Text Budget bên dưới
* Đảm bảo nội dung tuân thủ Layer 1 (no brand names, no product nouns, etc.) và Brand Anchor (Layer 0)

📐 IMAGE BUDGET — mỗi {{IMG_*_URL}} phải đáp ứng:
{{SKELETON_IMAGE_BUDGET}}

📝 TEXT BUDGET — mỗi token text có ràng buộc word-count + tone:
{{SKELETON_TEXT_BUDGET}}

🧬 SKELETON (sao chép verbatim, chỉ thay placeholder):
─────────── BEGIN SKELETON ───────────
{{SKELETON_HTML}}
─────────── END SKELETON ───────────

🎨 LAYER 4 — CSS (đã setup 1 lần ở child theme — KHÔNG in lại trong output)

Mọi class CSS skeleton đang dùng (`.nt-l1-*`, `.nt-eyebrow`, `.nt-hero-media`, `.nt-story-media`, …) đã được paste vào child theme style.css. AI không cần in lại block CSS nào trong output.

───────────────────────────────────────────────
✅ LAYER 5 — SELF-CHECK (grep trước khi trả output)
* [ ] Count <h1> = 1 (skeleton đảm bảo — chỉ kiểm chứng AI không thêm <h1> ngoài skeleton)
* [ ] KHÔNG còn dãy ký tự "{{" nào trong output (mọi placeholder đã được fill — count "{{" = 0)
* [ ] Cấu trúc shortcode khớp 100% skeleton: số [section] / [row] / [col] không đổi, thứ tự không đổi, attribute không đổi
* [ ] Tên class CSS giữ nguyên (.nt-l*-*, .nt-eyebrow, .nt-hero-media …) — không rename, không thêm class mới
* [ ] Grep accordion | answered | frequently | questions | q&a | "help center" | "good to know" | "the details" | <details | <summary → = 0
* [ ] Grep testimonial | review | "loved by" | "kind words" | "★" | "5-star" | "— [A-Z][a-z]+, [A-Z]" → = 0 (no fake reviews; xem H0b)
* [ ] Grep free ship | ships in | $ | % off | today only | limited time | act now | !! → = 0
* [ ] Grep brand names (Nike, Adidas, Apple, Disney, Marvel, Ray-Ban, Oakley, Gucci, LV, Chanel, Puma, Samsung, Pixar, Converse, Vans, Fisher-Price, Lego, Melissa & Doug, Gymshark, Lululemon) → = 0
* [ ] Grep tên sản phẩm/collection bịa (Ora, Meadow, Moonbeam Edit, Core Training Tee…) → = 0
* [ ] Grep descriptive product nouns: stacker, plush, teething, tote, clutch, crossbody, shoulder bag, tee, dumbbell, band, kettlebell, belt, serum, moisturizer, sneaker, hoodie, mug, candle, backpack, leash → = 0
* [ ] Mọi `link="..."` / `href="..."` thuộc whitelist {/, /shop/, /about-us/, /contact-us/, /cart/} → 100%
* [ ] Không có [ux_products cat="..."]
* [ ] Mọi <img>: giữ nguyên `loading="lazy"` + `referrerpolicy="no-referrer"` từ skeleton, alt là plain English ≤ 10 từ, không chứa shop/brand
* [ ] Mọi URL ảnh tuân thủ Layer 2 (Picsum DEFAULT). Khi không chắc Pexels/Unsplash ID còn sống → fallback Picsum với SEED riêng. Không slot nào dùng SEED trùng slot khác.
* [ ] Visible text ≥ 250 từ (skeleton tối giản — không over-write content)

───────────────────────────────────────────────
📤 LAYER 6 — OUTPUT FORMAT (chỉ một thứ duy nhất: filled skeleton)

▸ Trả về DUY NHẤT block HTML/shortcode đã fill (Filled Skeleton), wrap trong một markdown code-fence ```html … ```. Không header, không disclaimer, không bảng nào, không text mô tả trước/sau code-fence.

❌ TUYỆT ĐỐI KHÔNG in các thứ này — chúng chỉ là tài liệu nội bộ cho AI tự dùng khi generate, KHÔNG bao giờ paste vào output:
   - "⚠️ IMAGE VERIFICATION DISCLAIMER" hay bất kỳ disclaimer nào
   - "🪢 Brand Anchor Table" hay bảng fact extract từ About Us/Contact (chỉ dùng để LOCK content nội bộ — không in)
   - "🎲 Layout Map" / dòng VARIANT_SEED=... → LAYOUT_ID=...
   - "🖼️ Image Research Notes" / bảng Slot · Concept brief · URL · Source · Reason
   - "🛠️ JSON-LD Setup hint" / RankMath / Yoast / SEOPress — plugin TỰ ĐỘNG inject Product + Organization JSON-LD qua `CMC_Schema` (hook `woocommerce_structured_data_product` + `wpseo_schema_graph`) và auto-sync Yoast Site Representation từ Settings. Không cần user setup tay, AI không cần nhắc.
   - "✅ Confirmation Table" / bảng (a)...(h) status
   - Bất kỳ HTML comment / commentary / preamble / postscript / dòng chú thích nào

✅ Output đúng hình dạng:
```html
[section ...]
... (filled skeleton, mọi {{PLACEHOLDER}} đã replace)
[/section]
```

Trong đó dòng đầu là ```html (3 backtick + "html"), tiếp đến filled skeleton bắt đầu bằng `[section ...]`, kết thúc bằng `[/section]`, dòng cuối là ``` (3 backtick đóng). KHÔNG ký tự nào trước ```html hoặc sau ``` đóng.

✅ Nội dung skeleton = copy verbatim {{SKELETON_HTML}}, replace 100% các {{PLACEHOLDER}} bằng giá trị tuân thủ Image Budget + Text Budget + Layer 1 rules + Brand Anchor lock (internal-only).

✅ Brand Anchor extract (Layer 0) vẫn BẮT BUỘC chạy nội bộ — chỉ là không in ra output cuối.

───────────────────────────────────────────────
🔧 LAYER 7 — EXECUTION ORDER (workflow)
[1] Validate INPUT (LAYOUT_ID có giá trị, URL hợp lệ)
[2] Layer 0: Fetch ABOUT_URL + CONTACT_URL → Brand Anchor table
    └── Fetch fail → STOP, báo user, KHÔNG generate (no fallback)
[3] Layer 2: Research image URLs theo từng slot trong Image Budget
[4] Layer 3: Đọc skeleton HTML, fill mỗi placeholder theo Text Budget + Layer 1 rules + Brand Anchor lock
[5] Layer 5: Self-check toàn bộ output (đặc biệt: count `{{` = 0, structure unchanged)
[6] Layer 6: In RA DUY NHẤT filled skeleton wrap trong ```html ... ``` markdown code-fence. KHÔNG in disclaimer / brand table / layout map / image notes / json-ld / confirmation table / commentary.

═══════════════════════════════════════════════


PROMPT;
    }

    // ---------- POST handlers ----------

    public static function handle_post(): void {
        if ( empty( $_POST['cmc_setup_action'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Forbidden', 403 );
        }
        check_admin_referer( self::NONCE_ACTION );

        $action = (string) $_POST['cmc_setup_action'];

        switch ( $action ) {
            case self::ACTION_RENAME_CATEGORY:
                self::handle_rename_category();
                break;
            case self::ACTION_SAVE_CSS:
                self::handle_save_css();
                break;
            default:
                self::redirect_back( 'error', 'Unknown action.' );
        }
    }

    /**
     * Rename the primary product category to the configured Industry
     * (Settings → Company Info → nganh_hang), promote the renamed term
     * to WooCommerce's default product category, and prune every other
     * `product_cat` term so the site is left with a single canonical
     * category that matches the niche.
     *
     * Order of operations matters:
     *   1. Pick the rename target — prefer WC's "Uncategorized" so
     *      brand-new products that auto-fall to it carry the new name
     *      immediately. Fall back to the most-populated category when
     *      Uncategorized is missing.
     *   2. Rename the target term (name + slug).
     *   3. Promote the renamed term to `default_product_cat` (option
     *      stores term_taxonomy_id) BEFORE deleting any other terms —
     *      WooCommerce's delete-term hook re-points orphaned products
     *      to whatever the current default is, so the default must
     *      already be the renamed term to avoid a transient window
     *      where products briefly land on a soon-to-be-deleted term.
     *   4. Delete every other `product_cat` term. wp_delete_term()
     *      auto-reassigns products that have no remaining categories
     *      to the default, which is now our renamed term.
     */
    /**
     * Form-handler thin wrapper: delegates to the pure-logic
     * `rename_category_for_industry()` and converts the structured
     * result into a redirect-with-flash. The pure method is exposed
     * separately so the Run-All orchestrator (AJAX) can reuse the
     * exact same logic without involving a redirect.
     */
    private static function handle_rename_category(): void {
        $r = self::rename_category_for_industry();
        self::redirect_back( $r['success'] ? 'success' : 'error', $r['message'] );
    }

    /**
     * Pure logic — rename the primary product_cat term to match the
     * configured Industry, promote it as the WC default, and delete
     * every other term. Returns a structured result the caller can
     * either redirect with (form handler) or echo as JSON (AJAX
     * orchestrator). Does NOT exit / die / redirect itself.
     *
     * @return array{success:bool, message:string, data:array{
     *   renamed_term_id:int, before:string, after:string, deleted:int, errors:list<string>
     * }}
     */
    public static function rename_category_for_industry(): array {
        $settings      = CMC_Settings::get();
        $company       = (array) $settings['company_info'];
        $nganh_slug    = (string) ( $company['nganh_hang'] ?? '' );
        $nganh_options = CMC_Shortcodes::nganh_hang_options();
        $nganh_label   = trim( (string) ( $nganh_options[ $nganh_slug ] ?? $nganh_slug ) );

        $bail = static function ( string $msg ): array {
            return [
                'success' => false,
                'message' => $msg,
                'data'    => [ 'renamed_term_id' => 0, 'before' => '', 'after' => '', 'deleted' => 0, 'errors' => [] ],
            ];
        };

        if ( ! self::is_woocommerce_active() ) {
            return $bail( 'WooCommerce not active; category unchanged.' );
        }
        if ( $nganh_label === '' ) {
            return $bail( 'Settings → Company Info → Industry is empty; category unchanged.' );
        }

        // (1) Pick rename target — prefer Uncategorized.
        $target = self::pick_uncategorized();
        if ( ! $target ) {
            $target = self::pick_primary_category();
        }
        if ( ! $target ) {
            return $bail( 'No product category found to rename.' );
        }

        // (2) Rename the target term.
        $target_slug = sanitize_title( $nganh_label );
        $args        = [ 'name' => sanitize_text_field( $nganh_label ) ];
        if ( $target_slug !== '' && $target_slug !== $target['slug'] ) {
            $args['slug'] = $target_slug;
        }

        $res = wp_update_term( $target['id'], 'product_cat', $args );
        if ( is_wp_error( $res ) ) {
            return $bail( 'Category rename failed: ' . $res->get_error_message() );
        }
        $renamed_term_id = (int) ( is_array( $res ) ? ( $res['term_id'] ?? $target['id'] ) : $target['id'] );

        // (3) Promote the renamed term to the WC default.
        $term_obj = get_term( $renamed_term_id, 'product_cat' );
        $tt_id    = ( $term_obj && ! is_wp_error( $term_obj ) ) ? (int) $term_obj->term_taxonomy_id : 0;
        if ( $tt_id > 0 ) {
            update_option( 'default_product_cat', $tt_id );
        }

        // (4) Delete every other product_cat term.
        $deleted_count = 0;
        $delete_errors = [];
        $all_terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'fields'     => 'ids',
            'number'     => 0,
        ] );
        if ( ! is_wp_error( $all_terms ) ) {
            foreach ( (array) $all_terms as $tid ) {
                $tid = (int) $tid;
                if ( $tid <= 0 || $tid === $renamed_term_id ) { continue; }
                $del = wp_delete_term( $tid, 'product_cat' );
                if ( is_wp_error( $del ) ) {
                    $delete_errors[] = sprintf( '#%d: %s', $tid, $del->get_error_message() );
                } elseif ( $del === true || $del > 0 ) {
                    $deleted_count++;
                }
            }
        }

        if ( function_exists( 'delete_transient' ) ) {
            delete_transient( 'wc_term_counts' );
        }
        clean_term_cache( $renamed_term_id, 'product_cat' );

        self::update_state( 'category', [
            'term_id' => $renamed_term_id,
            'name'    => $nganh_label,
            'at'      => time(),
        ] );

        $msg = sprintf(
            'Category "%s" → "%s"; promoted to default; %d other category(ies) deleted',
            $target['name'],
            $nganh_label,
            $deleted_count
        );
        $success = empty( $delete_errors );
        if ( ! $success ) {
            $msg .= ' • Some deletions failed: ' . implode( '; ', $delete_errors );
        }

        return [
            'success' => $success,
            'message' => $msg,
            'data'    => [
                'renamed_term_id' => $renamed_term_id,
                'before'          => (string) $target['name'],
                'after'           => $nganh_label,
                'deleted'         => $deleted_count,
                'errors'          => $delete_errors,
            ],
        ];
    }

    private static function handle_save_css(): void {
        $raw = (string) wp_unslash( $_POST['custom_css'] ?? '' );
        $css = self::sanitize_css( $raw );

        if ( strlen( $css ) > 200000 ) {
            self::redirect_back( 'error', 'Custom CSS is too large (max 200 KB).' );
        }

        if ( $css === '' ) {
            delete_option( self::CSS_OPTION );
            self::redirect_back( 'success', 'Custom CSS cleared.' );
        }

        update_option( self::CSS_OPTION, $css, false );
        self::redirect_back( 'success', 'Custom CSS saved.' );
    }

    /**
     * Strip any HTML/script tags while preserving valid CSS combinators.
     * Mirrors the approach used by WordPress Customizer's "Additional CSS".
     */
    private static function sanitize_css( string $css ): string {
        $css = wp_strip_all_tags( $css );
        return trim( $css );
    }

    // ---------- Detection ----------

    private static function category_matches_label( string $label ): bool {
        if ( $label === '' || ! taxonomy_exists( 'product_cat' ) ) {
            return false;
        }
        $term = get_term_by( 'name', $label, 'product_cat' );
        return $term && ! is_wp_error( $term );
    }

    /**
     * Pick the single "primary" product category to rename:
     *   - Prefer any slug != 'uncategorized'
     *   - Among those, prefer the one with the most products
     *   - Ties broken alphabetically by name
     *   - If only "uncategorized" exists, use it
     *
     * @param array|null $cats If supplied, must be the output of
     *                         product_categories(); otherwise fetched.
     * @return array{id:int,name:string,slug:string,count:int}|null
     */
    /**
     * Find the WooCommerce default "Uncategorized" product_cat term so
     * the Rename Category Name action can target it specifically. Match
     * is by slug ("uncategorized" — WC core slug, locale-independent),
     * not by name, so a translated display name like "Chưa phân loại"
     * still resolves correctly.
     *
     * @return array{id:int,name:string,slug:string,count:int}|null
     */
    private static function pick_uncategorized(): ?array {
        foreach ( self::product_categories() as $cat ) {
            if ( ( $cat['slug'] ?? '' ) === 'uncategorized' ) {
                return $cat;
            }
        }
        return null;
    }

    private static function pick_primary_category( ?array $cats = null ): ?array {
        $cats = $cats ?? self::product_categories();
        if ( empty( $cats ) ) {
            return null;
        }

        $real = array_values( array_filter(
            $cats,
            static fn( $c ) => ( $c['slug'] ?? '' ) !== 'uncategorized'
        ) );
        $pool = ! empty( $real ) ? $real : $cats;

        usort( $pool, static function ( $a, $b ) {
            $ca = (int) ( $a['count'] ?? 0 );
            $cb = (int) ( $b['count'] ?? 0 );
            if ( $ca !== $cb ) {
                return $cb <=> $ca;
            }
            return strcmp( (string) $a['name'], (string) $b['name'] );
        } );

        return $pool[0];
    }

    /**
     * @return array<int, array{id:int, name:string, slug:string, count:int}>
     */
    private static function product_categories(): array {
        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return [];
        }
        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'number'     => 200,
        ] );
        if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
            return [];
        }
        $out = [];
        foreach ( $terms as $t ) {
            $out[] = [
                'id'    => (int) $t->term_id,
                'name'  => (string) $t->name,
                'slug'  => (string) $t->slug,
                'count' => (int) $t->count,
            ];
        }
        return $out;
    }

    private static function is_woocommerce_active(): bool {
        return class_exists( 'WooCommerce' );
    }

    // ---------- State option ----------

    public static function get_state(): array {
        $s = get_option( self::STATE_OPTION, [] );
        return is_array( $s ) ? $s : [];
    }

    public static function get_custom_css(): string {
        $css = get_option( self::CSS_OPTION, '' );
        return is_string( $css ) ? $css : '';
    }

    private static function update_state( string $key, array $value ): void {
        $s           = self::get_state();
        $s[ $key ]   = $value;
        update_option( self::STATE_OPTION, $s );
    }

    private static function clear_state( string $key ): void {
        $s = self::get_state();
        unset( $s[ $key ] );
        update_option( self::STATE_OPTION, $s );
    }

    // ---------- Redirect / flash ----------

    private static function redirect_back( string $type, string $message ): void {
        set_transient( 'cmc_setup_flash_' . get_current_user_id(), [ 'type' => $type, 'message' => $message ], 30 );
        wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
        exit;
    }

    /**
     * @return array{type:string, message:string}|null
     */
    private static function consume_flash(): ?array {
        $key   = 'cmc_setup_flash_' . get_current_user_id();
        $flash = get_transient( $key );
        if ( ! is_array( $flash ) ) {
            return null;
        }
        delete_transient( $key );
        return [ 'type' => (string) $flash['type'], 'message' => (string) $flash['message'] ];
    }

    // ---------- Link-out URLs ----------

    public static function url_wc_image_sizes(): string {
        return admin_url( 'customize.php?autofocus[section]=woocommerce_product_images' );
    }

    public static function url_amazon_search( string $query ): string {
        $q = trim( $query );
        if ( $q === '' ) {
            return 'https://www.amazon.com/';
        }
        return 'https://www.amazon.com/s?k=' . rawurlencode( $q );
    }

    public static function url_unsplash_search( string $query ): string {
        $q = trim( $query );
        if ( $q === '' ) {
            return 'https://unsplash.com/';
        }
        return 'https://unsplash.com/s/photos/' . rawurlencode( $q );
    }

    // ---------- Constants exposed to the view ----------

    public static function action_rename_category(): string { return self::ACTION_RENAME_CATEGORY; }
    public static function action_save_css(): string        { return self::ACTION_SAVE_CSS; }
}
