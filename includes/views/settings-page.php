<?php
/**
 * Settings page view.
 *
 * @var array $s                   Current settings (output of CMC_Settings::get()).
 * @var array $nganh_hang_options  slug => label map for the industry dropdown.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$openai_key_plain = CMC_Settings::get_api_key( 'openai' );
$gemini_key_plain = CMC_Settings::get_api_key( 'gemini' );
?>
<div class="wrap cmc-wrap">
    <h1 class="cmc-title">
        <span class="cmc-brand-dot" style="background:<?php echo esc_attr( $s['primary_color'] ); ?>"></span>
        CMC Cloner &mdash; Settings
    </h1>

    <?php if ( ! empty( $_GET['saved'] ) ) : ?>
        <div class="notice notice-success is-dismissible cmc-notice"><p>Settings saved.</p></div>
    <?php endif; ?>

    <form method="post" action="" class="cmc-settings-form" autocomplete="off">
        <?php wp_nonce_field( CMC_Settings::NONCE_ACTION ); ?>

        <!-- Site basics — Primary color + Founding year + Layout Skeleton -->
        <?php
        $sk_mode   = (string) ( $s['skeleton_variant_mode']   ?? 'auto' );
        $sk_number = (int)    ( $s['skeleton_variant_number'] ?? CMC_Settings::SKELETON_VARIANT_MIN );
        $sk_value  = $sk_mode === 'auto' ? 'auto' : (string) $sk_number;
        ?>
        <section class="cmc-card">
            <header class="cmc-card__header">
                <h2>Site basics</h2>
                <p class="description">Brand color, founding year, and the layout skeleton number used by every generated page.</p>
            </header>
            <div class="cmc-card__body">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="primary_color">Primary color
                                <?php CMC_UI::help( 'Brand accent color injected into generated pages as the CSS variable <code>--cmc-primary</code>. Drives buttons, links, badges, and the Live Chat panel theme.' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="primary_color" id="primary_color" class="cmc-color-field" value="<?php echo esc_attr( $s['primary_color'] ); ?>" data-default-color="#2ec4b6" />
                            <p class="description">Brand accent color for buttons, links and Live Chat.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="founding_year">Founding year
                                <?php CMC_UI::help( '
                                    <p>The reference year <code>{{ten_web}}</code> was founded — used by the About Us prompt as <code>Founded in &lt;year&gt;</code> alongside the "newly launched" framing.</p>
                                    <p>The plugin <strong>always</strong> generates the displayed year as <code>(this value) − random 2–6 months</code>, year component only — the random subtraction adds natural variance so cloned sites don\'t all advertise the exact same year. Most random offsets keep the displayed year equal to the value you set here; offsets that cross the January boundary drop one year (intentional).</p>
                                    <p><strong>Leave empty</strong> to use today as the reference date instead — the same subtraction still applies, so the About Us page <em>always</em> carries a founding-year line (a GMC trust requirement).</p>
                                    <p>4-digit year; values outside <code>1900 … next year</code> are silently rejected and the empty-field behaviour kicks in.</p>
                                ' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                   name="founding_year"
                                   id="founding_year"
                                   class="small-text"
                                   inputmode="numeric"
                                   pattern="\d{4}"
                                   maxlength="4"
                                   value="<?php echo esc_attr( $s['founding_year'] ?? '' ); ?>"
                                   placeholder="<?php echo esc_attr( wp_date( 'Y' ) ); ?>" />
                            <p class="description">4-digit year. Leave empty to use the current year.</p>
                        </td>
                    </tr>
                    <?php
                    $google_fonts          = CMC_Settings::google_fonts_options();
                    $flatsome_font_current = (string) ( $s['flatsome_font_family'] ?? '' );
                    $flatsome_active       = CMC_Settings::flatsome_active();
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="flatsome_font_family">Google Font
                                <?php CMC_UI::help( '
                                    <p>Pick a Google Font and the next Save will apply it to Flatsome\'s typography settings — body text, headings, navigation, and alt — in one shot.</p>
                                    <p>The list is curated to ~80 fonts that render well on storefronts. Type to search; leave empty to keep whatever Flatsome is already using.</p>
                                    <p>Behind the scenes this writes <code>type_texts</code>, <code>type_headings</code>, <code>type_nav</code>, and <code>type_alt</code> theme_mods and clears Kirki\'s font-file cache so the new family loads on the next request.</p>
                                ' ); ?>
                            </label>
                        </th>
                        <td>
                            <div class="cmc-combobox" data-cmc-combobox>
                                <select name="flatsome_font_family" id="flatsome_font_family" class="cmc-combobox__select">
                                    <option value="">— Keep current Flatsome font —</option>
                                    <?php foreach ( $google_fonts as $slug => $label ) : ?>
                                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $flatsome_font_current, $slug ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="description">
                                <?php if ( $flatsome_active ) : ?>
                                    Type to search. Click Save Settings to apply to Flatsome.
                                <?php else : ?>
                                    <em>Flatsome theme not active — selection will be saved but not applied until Flatsome is the active theme.</em>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="skeleton_variant">Layout skeleton
                                <?php CMC_UI::help( '
                                    <p>A single skeleton variant (1–4) is applied site-wide so every generated page shares a consistent visual rhythm.</p>
                                    <p>Choose <strong>Auto</strong> to keep the number randomised for this site, pick a specific number to lock it, or use <strong>Re-randomize</strong> to pick a fresh one.</p>
                                    <p><em>Changing this setting only affects pages generated from now on — existing pages keep the skeleton they were generated with.</em></p>
                                    <p>Saving with <strong>Auto</strong> selected after having chosen a manual number will pick a fresh random number.</p>
                                ' ); ?>
                            </label>
                        </th>
                        <td>
                            <select name="skeleton_variant" id="skeleton_variant">
                                <option value="auto" <?php selected( $sk_value, 'auto' ); ?>>Auto (currently <?php echo (int) $sk_number; ?>)</option>
                                <?php for ( $i = CMC_Settings::SKELETON_VARIANT_MIN; $i <= CMC_Settings::SKELETON_VARIANT_MAX; $i++ ) : ?>
                                    <option value="<?php echo (int) $i; ?>" <?php selected( $sk_value, (string) $i ); ?>>Skeleton <?php echo (int) $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="button" id="cmc-skeleton-randomize" class="button button-secondary">Re-randomize</button>
                            <span class="cmc-skeleton-randomize-result" aria-live="polite"></span>
                            <p class="description">Same skeleton variant for the whole site. Affects only pages generated after saving.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </section>

        <!-- Shortcodes & Company Info -->
        <section class="cmc-card">
            <header class="cmc-card__header">
                <h2>Shortcodes &amp; Company Info</h2>
                <p class="description">
                    When enabled, CMC Cloner registers
                    <code>[dia-chi]</code>
                    <code>[so-dien-thoai]</code>
                    <code>[email-web]</code>
                    <code>[ten-web]</code>
                    <code>[ten-website]</code>
                    <code>[ten-doanh-nghiep]</code>
                    <code>[nganh-hang]</code>
                    and fills them from the fields below. Turn OFF to let your legacy info plugin keep owning these shortcodes.
                </p>
                <p class="description">
                    AI-generated pages embed these as shortcodes inside the HTML, so changing a value in Settings instantly updates every cloned page without regeneration.
                    <?php CMC_UI::help( '
                        <p>When enabled, CMC Cloner registers <code>[dia-chi]</code>, <code>[so-dien-thoai]</code>, <code>[email-web]</code>, <code>[ten-web]</code>, <code>[ten-website]</code>, <code>[ten-doanh-nghiep]</code>, <code>[nganh-hang]</code> and fills them from the fields below. Turn OFF to let your legacy info plugin keep owning these shortcodes.</p>
                        <p>AI-generated pages embed these shortcodes inline (e.g. <code>Reach us at [email-web]</code>), so changing a value in Settings instantly updates every cloned page without regeneration. Leave any field blank if you don\'t have that info yet — the shortcode renders as an empty string on the frontend.</p>
                    ' ); ?>
                </p>
            </header>
            <div class="cmc-card__body">
                <table class="form-table" role="presentation">
                    <tr class="cmc-row-accent">
                        <th scope="row">
                            <label for="ci_nganh_hang"><code>[nganh-hang]</code> Industry
                                <span class="cmc-badge-required" aria-hidden="true">Required</span>
                                <?php CMC_UI::help( '
                                    <p>The single most important field on a fresh clone — every AI prompt and generated page is framed around this niche. Pick the wrong industry and every policy, FAQ, and homepage section ends up off-topic for the actual store.</p>
                                    <p>Start typing to search. Groups are parsed from the <code>// ====</code> dividers in <code>config/nganh-hang-options.php</code>; the list can also be extended via the <code>cmc_nganh_hang_options</code> filter.</p>
                                ' ); ?>
                            </label>
                        </th>
                        <td>
                            <div class="cmc-combobox" data-cmc-combobox>
                                <select name="company_info[nganh_hang]" id="ci_nganh_hang" class="cmc-combobox__select">
                                    <?php foreach ( $nganh_hang_grouped as $group_label => $group_items ) : ?>
                                        <?php if ( $group_label !== '' ) : ?>
                                            <optgroup label="<?php echo esc_attr( $group_label ); ?>">
                                        <?php endif; ?>
                                        <?php foreach ( $group_items as $slug => $label ) : ?>
                                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $s['company_info']['nganh_hang'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
                                        <?php endforeach; ?>
                                        <?php if ( $group_label !== '' ) : ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="description">Pick the niche this site sells in — drives every AI prompt and generated page.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable built-in shortcodes
                            <?php CMC_UI::help( 'When enabled, this plugin registers and resolves the shortcodes above. Turn OFF if a legacy plugin owns them on this site.' ); ?>
                        </th>
                        <td>
                            <label class="cmc-switch">
                                <input type="checkbox" name="enable_builtin_shortcodes" value="1" <?php checked( $s['enable_builtin_shortcodes'], 1 ); ?> />
                                <span class="cmc-switch__track" aria-hidden="true"></span>
                                <span class="cmc-switch__text">
                                    <?php echo $s['enable_builtin_shortcodes'] ? 'Enabled — CMC Cloner owns the shortcodes.' : 'Disabled — legacy plugin still owns the shortcodes.'; ?>
                                </span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_ten_web"><code>[ten-web]</code> Site name</label></th>
                        <td><input type="text" name="company_info[ten_web]" id="ci_ten_web" class="regular-text" value="<?php echo esc_attr( $s['company_info']['ten_web'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_ten_website"><code>[ten-website]</code> Tên website</label></th>
                        <td><input type="text" name="company_info[ten_website]" id="ci_ten_website" class="regular-text" value="<?php echo esc_attr( $s['company_info']['ten_website'] ?? '' ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_ten_doanh_nghiep"><code>[ten-doanh-nghiep]</code> Business name</label></th>
                        <td><input type="text" name="company_info[ten_doanh_nghiep]" id="ci_ten_doanh_nghiep" class="regular-text" value="<?php echo esc_attr( $s['company_info']['ten_doanh_nghiep'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_email_web"><code>[email-web]</code> Email</label></th>
                        <td><input type="email" name="company_info[email_web]" id="ci_email_web" class="regular-text" value="<?php echo esc_attr( $s['company_info']['email_web'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_so_dien_thoai"><code>[so-dien-thoai]</code> Phone</label></th>
                        <td><input type="text" name="company_info[so_dien_thoai]" id="ci_so_dien_thoai" class="regular-text" value="<?php echo esc_attr( $s['company_info']['so_dien_thoai'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_dia_chi"><code>[dia-chi]</code> Address</label></th>
                        <td><input type="text" name="company_info[dia_chi]" id="ci_dia_chi" class="regular-text" value="<?php echo esc_attr( $s['company_info']['dia_chi'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ci_dinh_huong_san_pham">Store Focus <span class="description">(optional)</span>
                                <?php CMC_UI::help( '
                                    <p>Optional narrower niche your store focuses on. When filled, every AI-generated page is written at this focus level and ignores sub-niche framing from the source page.</p>
                                    <p>Leave blank to write at the broader industry level above. <strong>Industry-agnostic</strong> — works for any vertical.</p>
                                    <p>Examples: <code>men\'s running shoes</code>, <code>handmade leather wallets</code>, <code>organic baby clothing</code>.</p>
                                ' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                   name="company_info[dinh_huong_san_pham]"
                                   id="ci_dinh_huong_san_pham"
                                   class="large-text"
                                   value="<?php echo esc_attr( $s['company_info']['dinh_huong_san_pham'] ); ?>"
                                   placeholder="e.g. men's running shoes, handmade leather wallets, organic baby clothing" />
                            <p class="description">Optional narrower niche. Leave blank to use the Industry above.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </section>

        <!-- Service Commitments -->
        <section class="cmc-card">
            <header class="cmc-card__header">
                <h2>Service Commitments</h2>
                <p class="description">
                    Timeframes and hours used across Contact, Refund, Return, Cancellation, Shipping, and Track-Order pages.
                    <?php CMC_UI::help( '
                        <p>Also exposed as the shortcodes <code>[gio-lam-viec]</code>, <code>[response-time]</code>, <code>[rma-time]</code>, <code>[refund-time]</code> so every page shows the same wording from a single source.</p>
                        <p>Leave a field blank to use the default shown as placeholder.</p>
                    ' ); ?>
                </p>
            </header>
            <div class="cmc-card__body">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ci_gio_lam_viec"><code>[gio-lam-viec]</code> Business hours</label></th>
                        <td>
                            <input type="text"
                                   name="company_info[gio_lam_viec]"
                                   id="ci_gio_lam_viec"
                                   class="large-text"
                                   value="<?php echo esc_attr( $s['company_info']['gio_lam_viec'] ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SERVICE_DEFAULTS['gio_lam_viec'] ); ?>" />
                            <p class="description">When support is reachable. <?php CMC_UI::help( 'Shown in Contact Us, Shipping Policy, Track Your Order, and any other page that references working hours. Include a timezone abbreviation (e.g. <code>(MST)</code>, <code>(PST)</code>) — the parenthetical is preserved verbatim across all pages.' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_response_time"><code>[response-time]</code> Customer response time</label></th>
                        <td>
                            <input type="text"
                                   name="company_info[response_time]"
                                   id="ci_response_time"
                                   class="large-text"
                                   value="<?php echo esc_attr( $s['company_info']['response_time'] ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SERVICE_DEFAULTS['response_time'] ); ?>" />
                            <p class="description">How quickly support replies to emails. <?php CMC_UI::help( '
                                <p>GMC recommends <strong>1–2 business days</strong> or faster. Used in Contact Us, Refund Policy, Return Policy, Track Your Order.</p>
                                <p>The literal value of this field is inserted verbatim into every page that references it (no paraphrase) — keep it self-contained, e.g. <code>within 1 business day</code>.</p>
                            ' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_rma_issuance_time"><code>[rma-time]</code> RMA / return-label issuance</label></th>
                        <td>
                            <input type="text"
                                   name="company_info[rma_issuance_time]"
                                   id="ci_rma_issuance_time"
                                   class="large-text"
                                   value="<?php echo esc_attr( $s['company_info']['rma_issuance_time'] ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SERVICE_DEFAULTS['rma_issuance_time'] ); ?>" />
                            <p class="description">Time between approval and sending the RMA / prepaid return label. <?php CMC_UI::help( 'Used in Refund Policy and Return Policy. Inserted verbatim — keep self-contained, e.g. <code>within 2 business days of approval</code>.' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_refund_processing_time"><code>[refund-time]</code> Refund processing time (returns)</label></th>
                        <td>
                            <input type="text"
                                   name="company_info[refund_processing_time]"
                                   id="ci_refund_processing_time"
                                   class="large-text"
                                   value="<?php echo esc_attr( $s['company_info']['refund_processing_time'] ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SERVICE_DEFAULTS['refund_processing_time'] ); ?>" />
                            <p class="description">How long a RETURN refund takes — measured from when the returned item arrives at the warehouse. <?php CMC_UI::help( 'Used in Return &amp; Refund Policy. Inserted verbatim — e.g. <code>within 5 business days after we receive the returned item</code>.' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_cancellation_refund_time"><code>[cancellation-time]</code> Cancellation refund time</label></th>
                        <td>
                            <input type="text"
                                   name="company_info[cancellation_refund_time]"
                                   id="ci_cancellation_refund_time"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['company_info']['cancellation_refund_time'] ?? '' ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SERVICE_DEFAULTS['cancellation_refund_time'] ); ?>" />
                            <p class="description">How long a CANCELLATION refund takes — measured from cancellation approval (no returned item involved). <?php CMC_UI::help( '<p>Used in Cancellation Policy. The page template appends "after cancellation approval" automatically, so enter only the bare duration: <code>5 business days</code>, <code>3–5 business days</code>, etc.</p><p>Separate from <code>[refund-time]</code> because the cancellation flow has no returned item to wait on — using the same string there would produce a GMC-flagged contradiction ("refund after we receive the returned item" on a page about pre-dispatch cancellations).</p>' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ci_return_window">Return window</label></th>
                        <td>
                            <input type="text"
                                   name="company_info[return_window]"
                                   id="ci_return_window"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['company_info']['return_window'] ?? '' ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SERVICE_DEFAULTS['return_window'] ); ?>" />
                            <p class="description">Period during which a customer may initiate a return. <?php CMC_UI::help( '
                                <p>Counted from the date of delivery. Plain duration only (e.g. <code>30 days</code>, <code>14 days</code>, <code>100 nights</code>) — the templates append "from the date of delivery" automatically.</p>
                                <p>Used by Return &amp; Refund Policy and FAQ.</p>
                                <p>Default <code>30 days</code> fits clothing / general merchandise. Food shops typically use <code>14 days</code>, mattress shops <code>100 nights</code>, electronics <code>30 days</code>.</p>
                            ' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="free_shipping_threshold">Free-shipping threshold</label></th>
                        <td>
                            <input type="text"
                                   name="free_shipping_threshold"
                                   id="free_shipping_threshold"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['free_shipping_threshold'] ?? '$75' ); ?>"
                                   placeholder="$75" />
                            <p class="description">Subtotal threshold for free shipping. <?php CMC_UI::help( 'Order subtotal at or above which standard shipping is free. Enforced in every Shipping Policy generation as a required clause. Free-form text (e.g. <code>$75</code>, <code>USD 100</code>, <code>€50</code>).' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="below_threshold_shipping_fee">Below-threshold shipping fee</label></th>
                        <td>
                            <input type="text"
                                   name="below_threshold_shipping_fee"
                                   id="below_threshold_shipping_fee"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['below_threshold_shipping_fee'] ?? '$4.99' ); ?>"
                                   placeholder="$4.99" />
                            <p class="description">Flat fee charged below the threshold. <?php CMC_UI::help( 'Flat standard-shipping fee for orders below the free-shipping threshold above. Injected into every Shipping Policy generation as the exact amount (e.g. <code>$4.99</code>, <code>USD 5</code>, <code>€3.50</code>).' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="shipping_cutoff_time">Order cut-off time</label></th>
                        <td>
                            <input type="text"
                                   name="shipping_cutoff_time"
                                   id="shipping_cutoff_time"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['shipping_cutoff_time'] ?? '' ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SHIPPING_DEFAULTS['shipping_cutoff_time'] ); ?>" />
                            <p class="description">Daily cut-off after which orders roll to the next business day. <?php CMC_UI::help( 'Must match your Google Merchant Center cut-off exactly (e.g. <code>2:00 PM PST</code>, <code>3:00 PM EST</code>). The timezone abbreviation is also used in the page\'s one-time timezone clarification.' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="shipping_handling_time">Handling time</label></th>
                        <td>
                            <input type="text"
                                   name="shipping_handling_time"
                                   id="shipping_handling_time"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['shipping_handling_time'] ?? '' ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SHIPPING_DEFAULTS['shipping_handling_time'] ); ?>" />
                            <p class="description">Days from order confirmation to dispatch. <?php CMC_UI::help( 'Business days from order confirmation to hand-off to the carrier. Must match your GMC handling time (e.g. <code>1–3 business days (Mon–Sat)</code>).' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="shipping_transit_time">Transit time</label></th>
                        <td>
                            <input type="text"
                                   name="shipping_transit_time"
                                   id="shipping_transit_time"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['shipping_transit_time'] ?? '' ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SHIPPING_DEFAULTS['shipping_transit_time'] ); ?>" />
                            <p class="description">Days the carrier needs after dispatch. <?php CMC_UI::help( 'Business days the carrier needs from dispatch to delivery. Must match your GMC transit time (e.g. <code>5–10 business days (Mon–Sat)</code>).' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="shipping_total_delivery">Total estimated delivery</label></th>
                        <td>
                            <input type="text"
                                   name="shipping_total_delivery"
                                   id="shipping_total_delivery"
                                   class="regular-text"
                                   value="<?php echo esc_attr( $s['shipping_total_delivery'] ?? '' ); ?>"
                                   placeholder="<?php echo esc_attr( CMC_Settings::SHIPPING_DEFAULTS['shipping_total_delivery'] ); ?>" />
                            <p class="description">End-to-end estimate. <?php CMC_UI::help( 'Should equal handling + transit (e.g. <code>6–13 business days from order placement</code>). Shown on the Shipping Policy page as the customer-facing delivery window.' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </section>

        <section class="cmc-section">
            <header class="cmc-section__head">
                <h2>Live Chat</h2>
                <p class="cmc-section__desc">A floating chat panel on every front-end page — a GMC trust signal.
                    <?php CMC_UI::help( '
                        <p>Keeps the site looking reachable to a Merchant Center reviewer.</p>
                        <p>Submissions show a friendly success notification but are <strong>not delivered</strong> anywhere (no email, no DB) so cloning a site needs zero SMTP setup.</p>
                        <p>Style auto-derives from <strong>Primary Color</strong>; the panel header shows <code>[ten-shop]</code> + <code>[gio-lam-viec]</code>.</p>
                    ' ); ?>
                </p>
            </header>
            <div class="cmc-section__body">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Enable Live Chat</th>
                        <td>
                            <label>
                                <input type="checkbox" name="livechat_enabled" value="1" <?php checked( ! empty( $s['livechat_enabled'] ) ); ?> />
                                Show the floating chat panel on the front end
                            </label>
                            <p class="description">Show the floating chat panel on the front end. <?php CMC_UI::help( 'Uncheck to fully hide the panel (no button, no markup) — useful for sites that already use Tawk/Crisp or are still in development.' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="livechat_button_label">Button label</label></th>
                        <td>
                            <input type="text"
                                   name="livechat_button_label"
                                   id="livechat_button_label"
                                   class="regular-text"
                                   maxlength="40"
                                   value="<?php echo esc_attr( $s['livechat_button_label'] ?? '' ); ?>"
                                   placeholder="(leave empty for icon-only)" />
                            <p class="description">Optional text beside the chat icon. <?php CMC_UI::help( 'e.g. <code>Need help?</code>, <code>Chat with us</code>. Leave empty for the default circular icon-only button.' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </section>

        <!-- AI Provider -->
        <section class="cmc-card">
            <header class="cmc-card__header">
                <h2>AI Provider</h2>
                <p class="description">Choose the AI backend used to rewrite page content.
                    <?php CMC_UI::help( 'API keys are encrypted at rest and persist across site clones — no need to re-enter on a freshly cloned site.' ); ?>
                </p>
            </header>
            <div class="cmc-card__body">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ai_provider">Provider</label></th>
                        <td>
                            <select name="ai_provider" id="ai_provider">
                                <option value="openai" <?php selected( $s['ai_provider'], 'openai' ); ?>>OpenAI</option>
                                <option value="gemini" <?php selected( $s['ai_provider'], 'gemini' ); ?>>Google Gemini</option>
                            </select>
                        </td>
                    </tr>

                    <tr class="cmc-provider-row cmc-provider-openai">
                        <th scope="row"><label for="openai_api_key">OpenAI API Key</label></th>
                        <td>
                            <input type="password"
                                   name="openai_api_key"
                                   id="openai_api_key"
                                   class="regular-text"
                                   autocomplete="new-password"
                                   placeholder="<?php echo esc_attr( $openai_key_plain ? CMC_Crypto::mask( $openai_key_plain ) : 'sk-...' ); ?>" />
                            <p class="description">Leave blank to keep the existing key. <?php CMC_UI::help( 'The masked placeholder above shows the saved key — visible only as masked text, never in plaintext. The key is encrypted at rest with a plugin-static seed so it survives DB clones to other sites.' ); ?></p>
                        </td>
                    </tr>
                    <tr class="cmc-provider-row cmc-provider-openai">
                        <th scope="row"><label for="openai_model">OpenAI Model</label></th>
                        <td>
                            <select name="openai_model" id="openai_model">
                                <?php foreach ( [ 'gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini' ] as $m ) : ?>
                                    <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $s['openai_model'], $m ); ?>><?php echo esc_html( $m ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr class="cmc-provider-row cmc-provider-gemini">
                        <th scope="row"><label for="gemini_api_key">Gemini API Key</label></th>
                        <td>
                            <input type="password"
                                   name="gemini_api_key"
                                   id="gemini_api_key"
                                   class="regular-text"
                                   autocomplete="new-password"
                                   placeholder="<?php echo esc_attr( $gemini_key_plain ? CMC_Crypto::mask( $gemini_key_plain ) : 'AIza...' ); ?>" />
                            <p class="description">Leave blank to keep the existing key.</p>
                        </td>
                    </tr>
                    <tr class="cmc-provider-row cmc-provider-gemini">
                        <th scope="row"><label for="gemini_model">Gemini Model</label></th>
                        <td>
                            <select name="gemini_model" id="gemini_model">
                                <?php foreach ( [ 'gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-2.0-flash' ] as $m ) : ?>
                                    <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $s['gemini_model'], $m ); ?>><?php echo esc_html( $m ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="max_tokens">Max Tokens</label></th>
                        <td>
                            <input type="number" name="max_tokens" id="max_tokens" value="<?php echo esc_attr( $s['max_tokens'] ); ?>" min="256" max="16384" step="128" />
                            <p class="description">Token cap per generation. <?php CMC_UI::help( 'Upper bound on tokens per generation. Raise for longer pages. <code>4096</code> fits most policy pages; raise to <code>8192–16384</code> for long FAQ or homepage rewrites.' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="temperature">Temperature</label></th>
                        <td>
                            <input type="number" name="temperature" id="temperature" value="<?php echo esc_attr( $s['temperature'] ); ?>" min="0" max="2" step="0.1" />
                            <p class="description">Lower = more deterministic, higher = more creative. <?php CMC_UI::help( '<code>0.4–0.8</code> recommended for policy pages. <code>0.7–1.0</code> for marketing copy / FAQ. <code>0.0–0.3</code> for legal-heavy text where stability matters.' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test connection</th>
                        <td>
                            <button type="button" id="cmc-test-api" class="button button-secondary">Test API</button>
                            <span class="cmc-test-result" aria-live="polite"></span>
                            <p class="description">Sends a 1-token ping to verify the saved key works. <?php CMC_UI::help( 'Save Settings first if you have just edited the key — Test reads the encrypted key from the DB, not the input box.' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </section>

        <p class="submit">
            <button type="submit" name="cmc_cloner_save" value="1" class="button button-primary button-hero">Save Settings</button>
        </p>
    </form>
</div>
