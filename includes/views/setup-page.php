<?php
/**
 * View: CMC Cloner → Site Setup
 *
 * Provided by CMC_Setup_Controller::render_page():
 *   @var array  $tasks                     Task state map
 *   @var string $site_title                Current blogname
 *   @var string $nganh_slug                Current nganh-hang (slug form)
 *   @var string $nganh_label               Readable label for nganh-hang
 *   @var string $ten_web                   Configured website name (Settings)
 *   @var string $primary_color             Configured primary color (hex)
 *   @var array  $product_cats              [[id,name,slug,count],...]
 *   @var array|null $primary_cat           Category that General will rename
 *   @var bool   $woocommerce_on
 *   @var string $custom_css                Stored site-wide custom CSS
 *   @var string $homepage_prompt_template  The prompt template with tokens
 *   @var array|null $flash                 {type,message} or null
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$nganh_query_slug  = $nganh_slug !== '' ? $nganh_slug : sanitize_title( $nganh_label );
$amazon_url        = $nganh_label !== '' ? CMC_Setup_Controller::url_amazon_search( $nganh_label ) : '';
$unsplash_url      = $nganh_query_slug !== '' ? CMC_Setup_Controller::url_unsplash_search( $nganh_query_slug ) : '';
// The Size Guide step is bidirectional: it CREATES the page when the
// industry matches apparel keywords AND cleans up (drafts page + strips
// footer link) when the industry no longer matches. So the row needs
// to be visible whenever there's something to do — either the industry
// applies, or a leftover page is still hanging around from a prior
// clone. The cheap "applies + page-exists" probe avoids cluttering the
// UI on truly-clean sites that never touched apparel.
$size_guide_applies      = CMC_Template_Registry::applies_to_industry(
    'size-guide',
    trim( $nganh_slug . ' ' . $nganh_label )
);
$size_guide_page_exists  = (bool) get_page_by_path( 'size-guide', OBJECT, 'page' );
if ( ! $size_guide_page_exists ) {
    global $wpdb;
    $size_guide_page_exists = (bool) $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status IN ('draft','pending','private') AND post_name=%s LIMIT 1",
        'page',
        'size-guide'
    ) );
}
$size_guide_visible = $size_guide_applies || $size_guide_page_exists;
?>
<div class="wrap cmc-wrap cmc-setup-wrap">

    <h1 class="cmc-title">
        <span>CMC Cloner &mdash; Site Setup</span>
    </h1>
    <p class="description">One-time tasks to run after cloning pages on a fresh site. Each card either applies the change directly or jumps you to the right screen.</p>

    <?php if ( $flash ) : ?>
        <div class="notice notice-<?php echo esc_attr( $flash['type'] === 'error' ? 'error' : 'success' ); ?> is-dismissible cmc-notice">
            <p><?php echo esc_html( $flash['message'] ); ?></p>
        </div>
    <?php endif; ?>

    <!-- ============ 1. Sản phẩm ============ -->
    <div class="cmc-card cmc-setup-card">
        <div class="cmc-card__header">
            <h2>
                <span class="cmc-setup-check" aria-hidden="true">&#9711;</span>
                1. Sản phẩm
            </h2>
            <p class="description">Source products from Amazon, rewrite titles, and rename product image files &mdash; all from one place.</p>
        </div>
        <div class="cmc-card__body">
            <!-- 1. Wipe all products ------------------------------------- -->
            <div class="cmc-setup-subblock cmc-products-eraser-card">
                <h3>Xoá toàn bộ sản phẩm</h3>
                <p class="description">
                    Force-delete every product, variation, and directly-attached image.
                    <?php CMC_UI::help( '
                        <p>Removes every <strong>product</strong>, every <strong>variation</strong>, and every image <strong>directly attached</strong> to them.</p>
                        <p>Categories, tags, orders, and reviews are <strong>kept untouched</strong>.</p>
                        <p>Deletion bypasses Trash and is <strong>not reversible</strong>.</p>
                    ' ); ?>
                </p>
                <?php if ( ! $woocommerce_on ) : ?>
                    <p class="cmc-setup-warn">WooCommerce is not active &mdash; this task is disabled.</p>
                <?php else : ?>
                    <div class="cmc-products-eraser">
                        <div class="cmc-setup-row">
                            <button type="button" class="button button-secondary cmc-btn-products-scan">Scan</button>
                            <span class="cmc-products-eraser-counts cmc-setup-meta" aria-live="polite"></span>
                        </div>

                        <div class="cmc-setup-row cmc-products-eraser-confirm" hidden>
                            <label for="cmc-products-eraser-confirm-input" class="cmc-products-eraser-confirm-label">
                                Type <strong>OK</strong> to enable deletion:
                            </label>
                            <input type="text" id="cmc-products-eraser-confirm-input" class="regular-text" autocomplete="off" placeholder="OK">
                            <button type="button" class="button cmc-btn-products-delete" disabled>
                                Xoá toàn bộ sản phẩm
                            </button>
                        </div>

                        <p class="cmc-products-eraser-msg" aria-live="polite"></p>
                        <div class="cmc-products-eraser-progress" hidden>
                            <div class="cmc-products-eraser-progress__bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span style="width:0%"></span>
                            </div>
                            <div class="cmc-products-eraser-progress__meta"></div>
                        </div>
                        <div class="cmc-products-eraser-results"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Amazon search ----------------------------------------- -->
            <div class="cmc-setup-subblock">
                <h3>Find products on Amazon</h3>
                <p class="description">
                    Opens an Amazon search pre-filled with your current industry
                    (<strong><?php echo esc_html( $nganh_label !== '' ? $nganh_label : '(not set)' ); ?></strong>).
                </p>
                <?php if ( $amazon_url !== '' ) : ?>
                    <p>
                        <button type="button" class="button cmc-btn-pod-setup">Run Woo POD Setup</button>
                        <a href="<?php echo esc_url( $amazon_url ); ?>" target="_blank" rel="noopener" class="button button-primary">
                            Open Amazon search &rarr;
                        </a>
                        <span class="cmc-pod-setup-status cmc-setup-meta" aria-live="polite"></span>
                    </p>
                    <p class="description" style="margin-top:6px;">
                        <small>The <strong>Run Woo POD Setup</strong> button calls the four <code>237NDMOE2MY4</code> plugin endpoints (setup &rarr; skip wp-config check &rarr; create INI &rarr; update) in sequence. Auto-marked as done after it succeeds once for this domain; re-runs automatically after the site is cloned to a new home_url.</small>
                    </p>
                <?php else : ?>
                    <p class="cmc-setup-warn">Pick an Industry in Settings first.</p>
                <?php endif; ?>
            </div>

            <!-- 3. Run All — sequential orchestrator --------------------- -->
            <?php if ( $woocommerce_on ) : ?>
            <div class="cmc-setup-subblock cmc-run-all-card">
                <h3>🚀 Run All Setup Tasks</h3>
                <p class="description">
                    One click runs all product-setup tasks below in sequence — uncheck any you want to skip. Each step's status + log shows live.
                    <?php CMC_UI::help( '
                        <p>Steps execute in the order shown (dependencies matter):</p>
                        <ol>
                            <li><strong>Rename Category Name</strong> — leaves the site with a single niche category.</li>
                            <li><strong>Rewrite Title &amp; Description</strong> — generic GMC-friendly titles before image filenames are derived from them.</li>
                            <li><strong>SKU Normalize</strong> — internal SKU format.</li>
                            <li><strong>Normalize Variation Attributes</strong> — cleans messy variation names left by Amazon imports (<code>01black</code> &rarr; <code>Black</code>, <code>02heathergrey</code> &rarr; <code>Heather Grey</code>, <code>Light Pink 1</code> merged with <code>Light Pink</code>). Applies to every <code>pa_*</code> taxonomy (color, size, material, capacity, and generic). Saves the originals as term-meta so the rename is revertible.</li>
                            <li><strong>Seed Product Reviews</strong> — auto-picks up to 5 random products that currently have zero reviews.</li>
                            <li><strong>Product Image Rename</strong> — filenames derive from the rewritten titles; uses the first (now-only) category.</li>
                            <li><strong>Regenerate Image Thumbnails</strong> — brute-force rebuilds metadata + size variants for every product image (same engine as the &quot;Repair image metadata&quot; button), so freshly renamed files have working srcsets.</li>
                            <li><strong>Heal Stale Attachment GUIDs</strong> — patches the <code>wp_posts.guid</code> column for every renamed attachment so plugins that override <code>wp_get_attachment_url</code> with <code>$post-&gt;guid</code> (FIFU / show-link-image) stop returning the pre-rename filename. Without this, CTX Feed / GMC keep emitting the old Amazon filenames even after rename succeeds.</li>
                            <li><strong>Build Sub-Categories</strong> — one AI call proposes 5–8 GMC-friendly sub-categories under the current niche category, then plugin distributes products into them by title-keyword matching (multi-cat allowed when a product fits more than one). Reviewers flag stores that show only a single broad category; this splits the catalog so the storefront looks browsable. Empty sub-cats AI proposed but no products matched are auto-pruned. Revertible: every reassigned product gets its prior <code>product_cat</code> list backed up in <code>_cmc_orig_cats</code>; plugin-created sub-cats carry <code>_cmc_auto_created_subcat=1</code>. The Revert button below the Run-All block undoes the step cleanly.</li>
                            <li><strong>Ensure Size Guide Page</strong> — runs only when the configured industry matches apparel-adjacent niches (fashion / apparel / footwear / kids / maternity / …). Creates an empty <code>size-guide</code> page if missing, runs the AI to fill the standard size chart wrapper, then auto-attaches the page link to the Flatsome footer block. Re-runs always overwrite the previous content. <strong>Bidirectional cleanup</strong>: cloning to a non-apparel niche on the next pass demotes the page to draft and strips the footer link — zero manual work.</li>
                        </ol>
                        <p><strong>Resume support</strong>: closing the tab mid-run halts the loop, but every step that did run leaves its postmeta markers (<code>_cmc_title_rewritten_at</code>, etc.) so clicking Run All again skips the already-finished products and picks up where you left off.</p>
                        <p><strong>Per-step errors are non-fatal</strong> — one failed product doesn\'t halt the orchestrator; the failure count surfaces in that step\'s log line at the end.</p>
                    ' ); ?>
                </p>
                <div class="cmc-run-all cmc-run-all--split">
                    <div class="cmc-run-all__col cmc-run-all__col--left">
                        <div class="cmc-run-all__opts">
                            <label class="cmc-run-all__select-all"><input type="checkbox" class="cmc-run-all-select-all"> <strong>Select All</strong></label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="cat"    checked> Rename Category Name</label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="title"  checked> Rewrite Title &amp; Description <span class="cmc-run-all__meta">(AI, ~5 min)</span></label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="sku"    checked> SKU Normalize</label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="varnorm" checked> Normalize Variation Attributes <span class="cmc-run-all__meta">(cleans <code>01black</code>, dupes, casing)</span></label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="review"> Seed Product Reviews <span class="cmc-run-all__meta">(5 random, no-review only)</span></label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="image"  checked> Product Image Rename <span class="cmc-run-all__meta">(~3 min)</span></label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="regen"  checked> Regenerate Image Thumbnails <span class="cmc-run-all__meta">(~2 min)</span></label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="guidheal" checked> Heal Stale Attachment GUIDs <span class="cmc-run-all__meta">(fixes CTX Feed / GMC URLs)</span></label>
                            <label><input type="checkbox" class="cmc-run-all-step" value="subcats"> Build Sub-Categories <span class="cmc-run-all__meta">(AI proposes 5-8 sub-cats + distributes products — opt-in)</span></label>
                            <?php if ( $size_guide_visible ) : ?>
                            <label><input type="checkbox" class="cmc-run-all-step" value="sizeguide" checked> Sync Size Guide Page <span class="cmc-run-all__meta">(auto-create on apparel niches, auto-cleanup otherwise)</span></label>
                            <?php endif; ?>
                        </div>
                        <div class="cmc-setup-row cmc-run-all__actions">
                            <button type="button" class="button button-primary button-hero cmc-btn-run-all">Start All</button>
                            <button type="button" class="button cmc-btn-run-all-cancel" hidden>Cancel</button>
                            <span class="cmc-run-all-summary cmc-setup-meta" aria-live="polite"></span>
                        </div>
                    </div>

                    <div class="cmc-run-all__col cmc-run-all__col--right">
                        <div class="cmc-run-all-overall" hidden>
                            <div class="cmc-run-all-overall__label">Overall progress</div>
                            <div class="cmc-run-all-overall__bar"><span style="width:0%"></span></div>
                            <div class="cmc-run-all-overall__meta"></div>
                        </div>

                        <ol class="cmc-run-all-timeline">
                            <li data-step="cat"    class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Rename Category Name</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="title"  class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Rewrite Title &amp; Description</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="sku"    class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">SKU Normalize</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="varnorm" class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Normalize Variation Attributes</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="review" class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Seed Product Reviews</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="image"  class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Product Image Rename</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="regen"  class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Regenerate Image Thumbnails</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="guidheal" class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Heal Stale Attachment GUIDs</span><span class="cmc-run-all-log"></span></li>
                            <li data-step="subcats" class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Build Sub-Categories</span><span class="cmc-run-all-log"></span></li>
                            <?php if ( $size_guide_visible ) : ?>
                            <li data-step="sizeguide" class="is-pending"><span class="cmc-run-all-icon">⭕</span><span class="cmc-run-all-name">Sync Size Guide Page</span><span class="cmc-run-all-log"></span></li>
                            <?php endif; ?>
                        </ol>

                        <div class="cmc-setup-row cmc-subcats-revert-row">
                            <button type="button" class="button button-link-delete cmc-btn-revert-subcats">Revert Sub-Categories</button>
                            <span class="cmc-setup-meta cmc-revert-subcats-status" aria-live="polite"></span>
                            <p class="description" style="margin:6px 0 0;">
                                <small>Undoes the "Build Sub-Categories" step: restores each product's pre-distribution <code>product_cat</code> list from postmeta backup, then deletes only plugin-created sub-cat terms. Safe to re-run — idempotent.</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 4. WooCommerce image sizes ------------------------------ -->
            <div class="cmc-setup-subblock">
                <h3>WooCommerce Image Sizes</h3>
                <p class="description">Opens the Customizer so you can adjust WooCommerce product image sizes.</p>
                <p>
                    <a href="<?php echo esc_url( admin_url( 'customize.php?return=%2Fwp-admin%2Findex.php' ) ); ?>" target="_blank" rel="noopener" class="button button-primary">
                        Open Image Size settings &rarr;
                    </a>
                </p>
            </div>

            <!-- 5. Manual / individual tools (collapsed by default) ----- -->
            <details class="cmc-setup-subblock cmc-setup-tools">
                <summary>
                    <span class="cmc-setup-tools__chev" aria-hidden="true">▶</span>
                    <span class="cmc-setup-tools__title">Manual / individual tools</span>
                    <span class="cmc-setup-tools__hint">Woo POD Setup · Rename Category · Rewrite Title &amp; Description · SKU Normalize · Seed Reviews · Image Rename</span>
                </summary>
                <div class="cmc-setup-tools__body">

            <!-- 5a. Woo POD Setup (manual fallback) ---------------------- -->
            <div class="cmc-setup-subblock">
                <h3>Woo POD Setup</h3>
                <p class="description">Opens the Woo POD plugin setup screen — use this if the automated Run-All step failed and you need to click the 4 buttons manually.</p>
                <p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=237NDMOE2MY4%2Ffull_woo_plugin_info.php' ) ); ?>" target="_blank" rel="noopener" class="button button-primary">
                        Open Woo POD Setup &rarr;
                    </a>
                </p>
            </div>

            <!-- 2a2. Rename Category Name -------------------------------- -->
            <?php
            $uncategorized_term = null;
            foreach ( $product_cats as $_pc ) {
                if ( ( $_pc['slug'] ?? '' ) === 'uncategorized' ) { $uncategorized_term = $_pc; break; }
            }
            $category_target_term = $uncategorized_term ?: $primary_cat;
            ?>
            <div class="cmc-setup-subblock">
                <h3>Rename Category Name</h3>
                <p class="description">
                    Rename <strong>Uncategorized</strong> → <strong><?php echo esc_html( $nganh_label !== '' ? $nganh_label : '(not set)' ); ?></strong>, set it as default, and delete every other category.
                    <?php CMC_UI::help( '
                        <p>Renames the default <strong>Uncategorized</strong> product category to your current Industry, pulled from <code>Settings → Industry</code>.</p>
                        <p>After the rename:</p>
                        <ul>
                            <li>The renamed term is promoted to the <strong>default product category</strong>.</li>
                            <li><strong>Every other product category is deleted</strong> so the site is left with a single canonical category.</li>
                            <li>Products that lose all their categories are auto-reassigned to the renamed term by WooCommerce.</li>
                            <li>The slug is updated in lockstep.</li>
                        </ul>
                        <p>If <code>Uncategorized</code> has already been renamed, the most-populated remaining category is targeted instead.</p>
                    ' ); ?>
                </p>
                <?php if ( ! $woocommerce_on ) : ?>
                    <p class="cmc-setup-warn">WooCommerce is not active &mdash; this task is disabled.</p>
                <?php elseif ( ! $category_target_term ) : ?>
                    <p class="cmc-setup-warn">No product category found to rename.</p>
                <?php elseif ( $nganh_label === '' ) : ?>
                    <p class="cmc-setup-warn">Pick an Industry in Settings first.</p>
                <?php else : ?>
                    <?php
                    $other_cat_count = 0;
                    foreach ( $product_cats as $_pc ) {
                        if ( (int) ( $_pc['id'] ?? 0 ) !== (int) ( $category_target_term['id'] ?? 0 ) ) {
                            $other_cat_count++;
                        }
                    }
                    ?>
                    <dl class="cmc-setup-preview">
                        <div class="cmc-setup-preview__row">
                            <dt>Category</dt>
                            <dd>
                                <code><?php echo esc_html( $category_target_term['name'] ); ?></code>
                                <span class="cmc-setup-meta">(slug <code><?php echo esc_html( $category_target_term['slug'] ); ?></code>)</span>
                                <span class="cmc-setup-arrow" aria-hidden="true">&rarr;</span>
                                <code><?php echo esc_html( $nganh_label ); ?></code>
                                <span class="cmc-setup-meta">(slug <code><?php echo esc_html( sanitize_title( $nganh_label ) ); ?></code>)</span>
                            </dd>
                        </div>
                        <?php if ( $other_cat_count > 0 ) : ?>
                            <div class="cmc-setup-preview__row">
                                <dt>Other categories</dt>
                                <dd>
                                    <strong><?php echo (int) $other_cat_count; ?></strong> will be deleted
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                    <form method="post">
                        <?php wp_nonce_field( CMC_Setup_Controller::NONCE_ACTION ); ?>
                        <input type="hidden" name="cmc_setup_action" value="<?php echo esc_attr( CMC_Setup_Controller::action_rename_category() ); ?>">
                        <p>
                            <button type="submit" class="button button-primary">
                                Rename Category Name
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>

            <!-- 2a3. AI Title + Description Rewriter --------------------- -->
            <div class="cmc-setup-subblock cmc-title-rewriter-card">
                <h3>Rewrite Product Title &amp; Description</h3>
                <p class="description">
                    Rewrite every product's title AND full description with AI to pass GMC review — strips brand leakage, model numbers, character / franchise names, licensing wording, ALL-CAPS, promotional spam.
                    <?php CMC_UI::help( '
                        <p>One AI call per product produces both fields together (saves cost vs. two separate calls).</p>
                        <p><strong>Title</strong> — 40–100 characters, Title Case, format <code>&lt;Product type&gt; &lt;descriptors&gt;</code>.</p>
                        <p><strong>Description</strong> — 100–300 words, simple HTML structure:</p>
                        <ul>
                            <li>1 short intro paragraph (what the product is).</li>
                            <li>4–6 bulleted attributes (material, size, key feature, suitable for).</li>
                            <li>1 short closing paragraph (typical use / care).</li>
                        </ul>
                        <p>Allowed HTML tags only: <code>&lt;p&gt;</code>, <code>&lt;ul&gt;</code>, <code>&lt;li&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;em&gt;</code>, <code>&lt;br&gt;</code>. Anything else (script, style, img, iframe, h1–h6, table) is stripped.</p>
                        <p>Targets the typical GMC failure modes for Amazon-scraped catalogues: trademark leakage ("Nike Air Max 90"), model numbers ("iPhone 15 Pro"), characters ("Mickey", "Pokémon", "Star Wars"), licensing patterns ("officially licensed", "X-style"), celebrity names, promotional fluff.</p>
                        <p>The product URL <strong>slug</strong> is also re-derived from the new title (<code>/product/&lt;new-slug&gt;/</code>). WordPress automatically records the previous slug and 301-redirects every old URL to the new one, so existing inbound links (Google index, ads, social shares) keep working.</p>
                        <p><strong>Tech adjectives ban</strong> — words like <code>Ultrasonic</code>, <code>NanoTech</code>, <code>HyperFlex</code> are dropped from titles because manual GMC reviewers misread them as proprietary brand tokens, even when they are real English words. Need to ban more words for your niche? Add them via the <code>cmc_title_rewriter_banned_words</code> filter.</p>
                        <p>Originals saved per product (<code>_cmc_original_title</code>, <code>_cmc_original_description</code>, <code>_cmc_original_slug</code>) — <strong>Revert</strong> restores all three exactly. Already-rewritten products are skipped on a re-run; revert + rerun if the AI output needs another pass.</p>
                    ' ); ?>
                </p>
                <?php if ( ! $woocommerce_on ) : ?>
                    <p class="cmc-setup-warn">WooCommerce is not active &mdash; this task is disabled.</p>
                <?php else : ?>
                    <div class="cmc-title-rewriter">
                        <div class="cmc-setup-row">
                            <button type="button" class="button button-primary cmc-btn-title-rewrite">Rewrite Title &amp; Description</button>
                            <button type="button" class="button cmc-btn-title-revert">Revert to Originals</button>
                            <span class="cmc-title-rewriter-counts cmc-setup-meta" aria-live="polite"></span>
                        </div>

                        <p class="cmc-title-rewriter-msg" aria-live="polite"></p>

                        <div class="cmc-title-rewriter-progress" hidden>
                            <div class="cmc-title-rewriter-progress__bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span style="width:0%"></span>
                            </div>
                            <div class="cmc-title-rewriter-progress__meta"></div>
                        </div>

                        <div class="cmc-title-rewriter-results"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2b2. SKU Normalize --------------------------------------- -->
            <div class="cmc-setup-subblock cmc-sku-normalizer-card">
                <h3>SKU Normalize</h3>
                <p class="description">
                    Rewrite Amazon ASIN-shaped SKUs into an internal SKU derived from your Industry.
                    <?php CMC_UI::help( '
                        <p>Detects Amazon-style SKUs (<code>B0XXXXXXXX</code>, <code>B0XXXXXXXX_parent</code>, <code>B0XXXXXXXX_VAR1</code>) and rewrites them into an internal SKU derived from <code>Settings → Industry</code> — e.g. <code>HBG-00001</code> for products and <code>HBG-00001-V00001</code> for variations.</p>
                        <p>The original SKU is saved per product so you can revert if needed.</p>
                    ' ); ?>
                </p>
                <?php if ( ! $woocommerce_on ) : ?>
                    <p class="cmc-setup-warn">WooCommerce is not active &mdash; this task is disabled.</p>
                <?php else : ?>
                    <div class="cmc-sku-normalizer">
                        <div class="cmc-setup-row">
                            <button type="button" class="button button-primary cmc-btn-sku-apply">Normalize SKUs</button>
                            <button type="button" class="button cmc-btn-sku-revert">Revert to originals</button>
                            <span class="cmc-sku-normalizer-counts cmc-setup-meta" aria-live="polite"></span>
                        </div>

                        <p class="cmc-sku-normalizer-msg" aria-live="polite"></p>

                        <div class="cmc-sku-normalizer-progress" hidden>
                            <div class="cmc-sku-normalizer-progress__bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span style="width:0%"></span>
                            </div>
                            <div class="cmc-sku-normalizer-progress__meta"></div>
                        </div>

                        <div class="cmc-sku-normalizer-results"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2b3. Seed Product Reviews -------------------------------- -->
            <?php
            // Defensive: compute the reviews-per-product constant here so the
            // view renders even if, for any reason, the class file failed to
            // load before this template (opcache / partial deploy / autoload
            // hiccup). Without this guard the page crashed with a fatal the
            // moment the description paragraph was rendered.
            $cmc_reviews_per_product = class_exists( 'CMC_Review_Seeder' ) ? (int) CMC_Review_Seeder::REVIEWS_PER_PRODUCT : 3;
            ?>
            <div class="cmc-setup-subblock cmc-review-seeder-card">
                <h3>Seed Product Reviews</h3>
                <p class="description">
                    Adds <strong><?php echo (int) $cmc_reviews_per_product; ?> US-sounding reviews</strong> per selected product so GMC's rating check passes.
                    <?php CMC_UI::help( '
                        <p>Distribution: <strong>5★ × 70%</strong>, <strong>4★ × 25%</strong>, <strong>3★ × 5%</strong>.</p>
                        <p>Real customer reviews are <strong>never touched</strong> — only reviews created here carry a marker and can be removed in one click.</p>
                    ' ); ?>
                </p>
                <?php if ( ! class_exists( 'CMC_Review_Seeder' ) ) : ?>
                    <p class="cmc-setup-warn">Review seeder is not loaded yet. Reload the page after a full plugin reactivation.</p>
                <?php elseif ( ! $woocommerce_on ) : ?>
                    <p class="cmc-setup-warn">WooCommerce is not active &mdash; this task is disabled.</p>
                <?php else : ?>
                    <div class="cmc-review-seeder">
                        <div class="cmc-setup-row">
                            <button type="button" class="button button-secondary cmc-btn-review-scan">Scan products</button>
                            <span class="cmc-review-seeder-counts cmc-setup-meta" aria-live="polite"></span>
                        </div>

                        <div class="cmc-review-seeder-filters" hidden>
                            <label>
                                <input type="search" class="cmc-review-seeder-filter" placeholder="Filter by title or SKU…" />
                            </label>
                            <label style="margin-left:14px">
                                <input type="checkbox" class="cmc-review-seeder-hide-existing" checked>
                                Hide products that already have reviews
                            </label>
                            <label style="margin-left:14px">
                                <input type="checkbox" class="cmc-review-seeder-select-all">
                                Select all visible
                            </label>
                        </div>

                        <div class="cmc-review-seeder-list" hidden>
                            <table class="widefat striped cmc-review-seeder-table">
                                <thead>
                                    <tr>
                                        <th style="width:28px"></th>
                                        <th>Product</th>
                                        <th style="width:140px">SKU</th>
                                        <th style="width:90px">Reviews</th>
                                        <th style="width:80px">Status</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="cmc-setup-row">
                            <button type="button" class="button button-primary cmc-btn-review-seed" disabled>Seed reviews</button>
                            <button type="button" class="button cmc-btn-review-polish" hidden>Polish with AI</button>
                            <button type="button" class="button cmc-btn-review-remove" hidden>Remove seeded reviews</button>
                        </div>

                        <p class="cmc-review-seeder-msg" aria-live="polite"></p>

                        <div class="cmc-review-seeder-progress" hidden>
                            <div class="cmc-review-seeder-progress__bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span style="width:0%"></span>
                            </div>
                            <div class="cmc-review-seeder-progress__meta"></div>
                        </div>

                        <div class="cmc-review-seeder-results"></div>
                    </div>
                <?php endif; ?>
            </div>

            

            <!-- 2e. Product image rename --------------------------------- -->
            <div class="cmc-setup-subblock cmc-image-rename-card">
                <h3>Product Image Rename</h3>
                <p class="description">
                    Rename every product image — featured, gallery, and variations — AND rewrite each image's ALT to match the rewritten title.
                    <?php CMC_UI::help( '
                        <p>GMC inspects both filename and ALT for brand / copyright tokens; aligning them with the rewritten title keeps the entire image asset surface consistent.</p>
                        <p><strong>Filename pattern</strong> (derived from product title slug):</p>
                        <ul>
                            <li><code>canvas-tote-bag-light-green.jpg</code> (featured)</li>
                            <li><code>canvas-tote-bag-light-green-2.jpg</code> (gallery #2)</li>
                            <li><code>canvas-tote-bag-light-green-v1.jpg</code> (variation #1)</li>
                        </ul>
                        <p><strong>ALT pattern</strong> (derived from product title):</p>
                        <ul>
                            <li><code>Canvas Tote Bag Light Green</code> (featured)</li>
                            <li><code>Canvas Tote Bag Light Green - Image 2</code> (gallery)</li>
                            <li><code>Canvas Tote Bag Light Green - Variation 1</code> (variation)</li>
                        </ul>
                        <p>Each product\'s base slug is cached in <code>_cmc_img_base_slug</code> post-meta so re-running is idempotent. Size variants, scaled files, <code>_thumbnail_id</code> on each variation post, <code>post_content</code> URLs, postmeta + options URL embeds, and the attachment ALT + post_title are all updated in lockstep — <strong>variations stay fully functional</strong> because attachment IDs do not change.</p>
                        <p>Shared / library images are skipped automatically; already-renamed images get just an ALT refresh.</p>
                        <p><em>Prerequisite:</em> rewrite product titles first so both filename and ALT inherit the cleaned title verbatim.</p>
                    ' ); ?>
                </p>
                <?php if ( ! $woocommerce_on ) : ?>
                    <p class="cmc-setup-warn">WooCommerce is not active &mdash; this task is disabled.</p>
                <?php elseif ( empty( $product_cats ) ) : ?>
                    <p class="cmc-setup-warn">No product categories found.</p>
                <?php else : ?>
                    <div class="cmc-img-rename">
                        <div class="cmc-setup-row">
                            <label for="cmc-img-rename-cat">Category</label>
                            <select id="cmc-img-rename-cat" class="regular-text">
                                <?php foreach ( $product_cats as $cat ) : ?>
                                    <option value="<?php echo (int) $cat['id']; ?>">
                                        <?php echo esc_html( sprintf( '%s (%s) — %d products', $cat['name'], $cat['slug'], $cat['count'] ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="cmc-setup-row">
                            <label>
                                <input type="checkbox" id="cmc-img-rename-subcats" checked>
                                Include sub-categories
                            </label>
                        </div>

                        <div class="cmc-setup-row">
                            <button type="button" class="button button-secondary cmc-btn-img-scan">Scan</button>
                            <button type="button" class="button button-primary cmc-btn-img-rename" hidden disabled>Rename selected</button>
                            <button type="button" class="button cmc-btn-img-revert" hidden>Revert to originals</button>
                            <button type="button" class="button cmc-btn-img-purge" title="Force-trigger every cache plugin purge hook (LiteSpeed Cache + LSIO, WP Rocket, W3 Total Cache, WP Super Cache, Cloudflare WP plugin, etc.) plus the WP object cache. Use this when you still see old image URLs in data-large_image / data-o_* attributes after a rename.">Force purge caches</button>
                            <button type="button" class="button cmc-btn-img-meta-repair" title="Brute-force regenerates metadata + size variants for every product image (featured, gallery, variations). Same as running the Regenerate Thumbnails plugin — but scoped to product images only, so it finishes 10× faster on a typical store. Use this when product images on the storefront show as broken (&quot;Could not load image&quot; in DevTools) even though the JPG opens fine in a new tab.">Repair image metadata</button>
                        </div>

                        <p class="cmc-img-rename-msg" aria-live="polite"></p>
                        <p class="cmc-img-meta-repair-msg" aria-live="polite"></p>
                        <div class="cmc-img-meta-repair-progress" hidden>
                            <div class="cmc-img-meta-repair-progress__bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span style="width:0%"></span>
                            </div>
                            <div class="cmc-img-meta-repair-progress__meta"></div>
                        </div>

                        <div class="cmc-setup-row cmc-img-diagnose-row">
                            <label for="cmc-img-diagnose-needle" style="min-width:0;">Diagnose one image:</label>
                            <input type="text" id="cmc-img-diagnose-needle" class="regular-text" placeholder="attachment ID, product slug, or filename fragment">
                            <button type="button" class="button cmc-btn-img-diagnose">Diagnose</button>
                        </div>
                        <pre class="cmc-img-diagnose-out" style="white-space:pre-wrap;background:#f6f7f7;padding:10px;border:1px solid #c3c4c7;max-height:400px;overflow:auto;display:none;"></pre>
                        <div class="cmc-img-rename-progress" hidden>
                            <div class="cmc-img-rename-progress__bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <span style="width:0%"></span>
                            </div>
                            <div class="cmc-img-rename-progress__meta"></div>
                        </div>
                        <div class="cmc-img-rename-results"></div>
                    </div>
                <?php endif; ?>
            </div>

                </div><!-- /.cmc-setup-tools__body -->
            </details><!-- /.cmc-setup-tools -->

        </div>
    </div>

    <!-- ============ 2. Homepage ============ -->
    <div class="cmc-card cmc-setup-card cmc-homepage-card">
        <div class="cmc-card__header">
            <h2>
                <span class="cmc-setup-check" aria-hidden="true">&#9711;</span>
                2. Homepage
            </h2>
            <p class="description">Generate the homepage AI prompt, paste custom CSS, and source hero images &mdash; all scoped to the current site.</p>
        </div>
        <div class="cmc-card__body">

            <!-- 3a. Homepage prompt -------------------------------------- -->
            <div class="cmc-setup-subblock">
                <h3>Homepage Prompt</h3>
                <p class="description">
                    Click <strong>Sinh prompt cho site này</strong> to substitute
                    <code>{{nganh-hang}}</code>, <code>{{ten-shop}}</code>, <code>{{primary-color}}</code>
                    (and the body references <code>{NICHE}</code>, <code>{SHOP_NAME}</code>, <code>{COLOR}</code>)
                    with the current <code>Settings</code> values, then press <strong>Copy</strong> to paste the result into your AI tool.
                </p>

                <!-- Layout chooser (Phase 3) ----------------------------- -->
                <form method="get" class="cmc-setup-row cmc-homepage-layout-form">
                    <?php
                    // Preserve the current admin page slug; WP routes admin
                    // pages by `?page=...`. Without this hidden field the
                    // submit button would drop us back to the dashboard.
                    foreach ( [ 'page' ] as $passthrough ) :
                        if ( isset( $_GET[ $passthrough ] ) ) : ?>
                            <input type="hidden" name="<?php echo esc_attr( $passthrough ); ?>" value="<?php echo esc_attr( (string) $_GET[ $passthrough ] ); ?>">
                        <?php endif;
                    endforeach;
                    ?>
                    <label for="cmc-homepage-layout" class="cmc-setup-meta">Layout:</label>
                    <select id="cmc-homepage-layout" name="cmc_layout" onchange="this.form.submit()">
                        <option value="" <?php selected( $homepage_layout_choice, '' ); ?>>Random — pick by seed each click</option>
                        <?php foreach ( $homepage_skeletons as $skel ) : ?>
                            <option value="<?php echo esc_attr( $skel['id'] ); ?>" <?php selected( $homepage_layout_choice, $skel['id'] ); ?>>
                                <?php echo esc_html( $skel['id'] . ' — ' . $skel['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="button button-small">Apply layout</button></noscript>
                    <span class="cmc-setup-meta">
                        <?php if ( $homepage_layout_choice !== '' ) : ?>
                            Pinned to <code><?php echo esc_html( $homepage_layout_choice ); ?></code> — every reload renders this skeleton.
                        <?php else : ?>
                            Random — each reload picks a layout via seed [1, 30] across <?php echo count( $homepage_skeletons ); ?> skeletons.
                        <?php endif; ?>
                    </span>
                </form>

                <div class="cmc-setup-row">
                    <button type="button"
                            class="button button-primary cmc-btn-prompt-generate"
                            data-target="#cmc-homepage-prompt"
                            data-source="#cmc-homepage-prompt-source"
                            data-missing="<?php echo esc_attr( implode( ', ', $homepage_prompt_missing ) ); ?>">
                        Sinh prompt cho site này
                    </button>
                    <span class="cmc-inline-msg cmc-prompt-generate-msg" aria-live="polite"></span>
                </div>

                <script type="text/template" id="cmc-homepage-prompt-source"><?php
                    // Raw text island: <script type="text/template"> is parsed as
                    // "raw text" by the HTML parser, so we output the string verbatim
                    // (no esc_html entity encoding). The only risk is the literal
                    // sequence "</script" appearing in the body; the template does
                    // not contain it.
                    echo $homepage_prompt_filled;
                ?></script>

                <div class="cmc-code-wrap cmc-homepage-prompt-wrap">
                    <textarea id="cmc-homepage-prompt"
                              class="cmc-homepage-prompt cmc-code large-text"
                              rows="16"
                              spellcheck="false"
                              placeholder="Click &quot;Sinh prompt cho site này&quot; to fill placeholders..."><?php echo esc_textarea( $homepage_prompt_template ); ?></textarea>
                    <button type="button" class="button button-small cmc-copy-btn" data-target="#cmc-homepage-prompt">Copy</button>
                </div>
                <p class="description">
                    Edit the template directly in the box above if you want a one-off variation; the <strong>Sinh prompt</strong> button always rebuilds from the stored template in <code>CMC_Setup_Controller::homepage_prompt_template()</code>.
                </p>
            </div>

            <!-- 3b. Custom CSS ------------------------------------------ -->
            <div class="cmc-setup-subblock">
                <h3>Custom CSS</h3>
                <p class="description">
                    Printed in <code>&lt;head&gt;</code> on every page, after the theme stylesheet, so it wins specificity-for-specificity against Flatsome defaults. Ideal for AI-generated homepage CSS that pairs with cloned HTML.
                </p>
                <form method="post">
                    <?php wp_nonce_field( CMC_Setup_Controller::NONCE_ACTION ); ?>
                    <input type="hidden" name="cmc_setup_action" value="<?php echo esc_attr( CMC_Setup_Controller::action_save_css() ); ?>">
                    <div class="cmc-setup-row">
                        <label for="cmc-setup-custom-css" class="screen-reader-text">Custom CSS</label>
                        <textarea
                            id="cmc-setup-custom-css"
                            name="custom_css"
                            class="large-text code"
                            rows="14"
                            spellcheck="false"
                            placeholder="/* e.g. */
.home .section-title { letter-spacing: .02em; }
.home .col-inner { border-radius: 12px; }"
                        ><?php echo esc_textarea( $custom_css ); ?></textarea>
                        <p class="description">HTML tags are stripped on save. Clearing the box and saving removes the stored CSS. Max 200&nbsp;KB.</p>
                    </div>
                    <div class="cmc-setup-row">
                        <button type="submit" class="button button-primary">Save CSS</button>
                        <?php if ( $custom_css !== '' ) : ?>
                            <span class="cmc-setup-meta"><?php echo esc_html( sprintf( '%s characters stored', number_format_i18n( strlen( $custom_css ) ) ) ); ?></span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- 3c. Homepage images ------------------------------------- -->
            <div class="cmc-setup-subblock">
                <h3>Homepage images</h3>
                <p class="description">
                    Free stock photos from Unsplash for your current industry
                    (<strong><?php echo esc_html( $nganh_label !== '' ? $nganh_label : '(not set)' ); ?></strong>).
                </p>
                <?php if ( $unsplash_url !== '' ) : ?>
                    <p>
                        <a href="<?php echo esc_url( $unsplash_url ); ?>" target="_blank" rel="noopener" class="button button-primary">
                            Open Unsplash photos &rarr;
                        </a>
                    </p>
                <?php else : ?>
                    <p class="cmc-setup-warn">Pick an Industry in Settings first.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</div>
