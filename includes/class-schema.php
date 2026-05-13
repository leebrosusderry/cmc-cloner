<?php
/**
 * Augment WooCommerce's Product structured data so it satisfies the
 * GMC / Rich Results / audit-tool requirements that WC core leaves blank.
 *
 * What WC core emits out of the box: name, url, description, image,
 * sku, offers, aggregateRating, review. What audit tools (and Google
 * Merchant Center) flag as missing for a generic storefront:
 *   - `brand`         — required by GMC.
 *   - `mpn`           — required by GMC if no GTIN.
 *   - `category`      — recommended; helps Google classify the listing.
 *   - `itemCondition` — required by GMC (NewCondition / UsedCondition / RefurbishedCondition).
 *   - `gtin13`        — recommended by GMC where available.
 *
 * Two write paths are wired here:
 *
 *   1. `woocommerce_structured_data_product` (priority 99, after WC core)
 *      — injects the missing fields into WC's own `<script type=
 *      "application/ld+json">` block. This keeps every other field
 *      (offers, reviews, image) sourced from WC, so future WC
 *      improvements flow through unchanged.
 *
 *   2. `wpseo_schema_graph` — injects a Product node into Yoast SEO's
 *      schema-graph (the `<script type="application/ld+json"
 *      class="yoast-schema-graph">` block). Yoast Free does NOT emit a
 *      Product node out of the box (it's a Yoast WooCommerce SEO
 *      premium feature), and audit tools that scan only the Yoast graph
 *      report "Không có JSON-LD / missing Product schema" even when
 *      WC's parallel block is correct. Adding the node into Yoast's
 *      graph closes that gap so both audit paths see Product data.
 *
 * Per-product overrides via postmeta (no UI — set via REST/CLI/db):
 *   - `_cmc_brand`     → schema brand
 *   - `_cmc_gtin13`    → schema gtin13
 *   - `_cmc_condition` → 'NewCondition' / 'UsedCondition' / 'RefurbishedCondition'
 *                        or a full https://schema.org/... URL
 *
 * Filters (override per call):
 *   - cmc_product_schema_brand     ($brand,     WC_Product)
 *   - cmc_product_schema_mpn       ($mpn,       WC_Product)
 *   - cmc_product_schema_category  ($category,  WC_Product)
 *   - cmc_product_schema_condition ($condition, WC_Product)  // full URL
 *   - cmc_product_schema_gtin13    ($gtin13,    WC_Product)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Schema {

    /**
     * Trademarks Google Merchant Center treats as protected — emitting any
     * of these as a `brand` on a generic clone shop instantly trips the
     * "brand misrepresentation" / "trademark infringement" flag and gets
     * the merchant account suspended.
     *
     * The check is a case-insensitive substring match: "Nike Outlet",
     * "Cheap Adidas Co", and "iPhone Repair Hub" all match. Stored in
     * lowercase to keep the comparison branch-free.
     *
     * Curated to the top names per category that a generic dropshipping
     * shop is most likely to accidentally pick up via niche templates
     * or operator typos. Extend at runtime via the
     * `cmc_product_schema_protected_brands` filter.
     */
    private const PROTECTED_BRANDS = [
        // Fashion / footwear
        'nike', 'adidas', 'puma', 'reebok', 'new balance', 'under armour',
        'north face', 'patagonia', 'columbia', 'lululemon', "levi's", 'levi',
        'calvin klein', 'tommy hilfiger', 'ralph lauren', 'polo ralph',
        'zara', 'h&m', 'hm', 'uniqlo', 'gap', 'old navy', 'forever 21',
        'vans', 'converse', 'gucci', 'prada', 'louis vuitton', 'chanel',
        'hermes', 'burberry', 'dior', 'fendi', 'versace', 'balenciaga',
        // Electronics / tech
        'apple', 'iphone', 'ipad', 'macbook', 'airpods',
        'samsung', 'galaxy', 'sony', 'lg ', 'panasonic',
        'microsoft', 'xbox', 'surface', 'google pixel', 'pixel',
        'huawei', 'xiaomi', 'oppo', 'vivo', 'oneplus', 'nokia',
        'lenovo', 'thinkpad', 'dell', 'alienware', 'hp ', 'asus', 'acer',
        'bose', 'jbl', 'beats', 'sennheiser', 'logitech',
        'canon', 'nikon', 'gopro', 'fitbit', 'garmin',
        // Home / furniture / retail
        'ikea', 'williams sonoma', 'pottery barn', 'west elm',
        'crate & barrel', 'crate and barrel', 'bed bath', 'home depot',
        "lowe's", 'lowes', 'wayfair', 'overstock',
        // Beauty / cosmetics
        'sephora', 'ulta', 'mac cosmetics', 'maybelline', "l'oreal",
        'loreal', 'estee lauder', 'clinique', 'lancome', 'nyx',
        'glossier', 'fenty', 'kylie cosmetics', 'urban decay',
        // Toys / kids / entertainment
        'lego', 'disney', 'pokemon', 'mattel', 'barbie', 'hasbro',
        'fisher-price', 'fisher price', 'hot wheels', 'nerf',
        'nintendo', 'playstation', 'sega', 'pokémon',
        // Food / beverage
        'starbucks', 'nestle', 'nestlé', 'coca-cola', 'cocacola', 'pepsi',
        'hershey', 'kraft', 'heinz', 'mcdonald', 'kfc', 'burger king',
        // Marketplaces (impersonation risk)
        'amazon', 'walmart', 'target', 'costco', 'ebay', 'etsy',
        'aliexpress', 'alibaba', 'shopee', 'lazada', 'temu', 'shein',
        // Watches / luxury / sport
        'rolex', 'omega', 'casio', 'seiko', 'citizen', 'timex',
        'wilson', 'yonex', 'babolat', 'head ', 'spalding',
        // Auto
        'toyota', 'honda', 'ford', 'bmw', 'mercedes', 'mercedes-benz',
        'audi', 'tesla', 'porsche', 'ferrari', 'lamborghini',
        // Misc protected
        'rolex', 'apple inc',
    ];

    public static function init(): void {
        add_filter( 'woocommerce_structured_data_product', [ self::class, 'augment_product_schema' ], 99, 2 );
        add_filter( 'wpseo_schema_graph',                   [ self::class, 'inject_product_into_yoast_graph' ], 11, 2 );
    }

    /**
     * @param array<string, mixed> $markup   The schema array WC built.
     * @param \WC_Product|null     $product  The product being rendered.
     * @return array<string, mixed>
     */
    public static function augment_product_schema( $markup, $product ): array {
        if ( ! is_array( $markup ) ) {
            $markup = [];
        }
        if ( ! ( $product instanceof \WC_Product ) ) {
            return $markup;
        }

        // ---- brand / mpn / category ----
        $markup = self::apply_brand( $markup, $product );
        $markup = self::apply_mpn( $markup, $product );
        $markup = self::apply_category( $markup, $product );

        // ---- itemCondition ----
        // GMC requires this on every product. We default to NewCondition
        // because cloned shops sell new items by default; per-product
        // postmeta `_cmc_condition` lets the operator override to
        // 'UsedCondition' or 'RefurbishedCondition' for second-hand
        // listings without touching code.
        if ( empty( $markup['itemCondition'] ) ) {
            $markup['itemCondition'] = self::resolve_condition( $product );
        }

        // ---- gtin13 ----
        // We do NOT auto-fake a GTIN — GMC rejects fabricated GTINs
        // and bans the account on detection. Only emit when the
        // operator has set a real value via postmeta `_cmc_gtin13`
        // or the filter.
        if ( empty( $markup['gtin13'] ) ) {
            $gtin = (string) get_post_meta( $product->get_id(), '_cmc_gtin13', true );
            $gtin = (string) apply_filters( 'cmc_product_schema_gtin13', $gtin, $product );
            $gtin = preg_replace( '/[^0-9]/', '', $gtin );
            if ( strlen( (string) $gtin ) === 13 ) {
                $markup['gtin13'] = $gtin;
            }
        }

        return $markup;
    }

    /**
     * Hook callback for `wpseo_schema_graph`. Adds a Product node to the
     * Yoast graph on single-product pages so audit tools that scan only
     * Yoast's `<script class="yoast-schema-graph">` see Product data.
     * Mirrors the fields WC + augment_product_schema() emit so the two
     * blocks tell the same story to consumers.
     *
     * @param array $graph   Existing graph nodes (each is an associative array with @type, @id, etc.).
     * @param mixed $context Yoast's Meta_Tags_Context (we only use it loosely).
     * @return array
     */
    public static function inject_product_into_yoast_graph( $graph, $context ): array {
        if ( ! is_array( $graph ) ) {
            return is_array( $graph ) ? $graph : [];
        }
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            return $graph;
        }

        $product = wc_get_product( get_the_ID() );
        if ( ! ( $product instanceof \WC_Product ) ) {
            return $graph;
        }

        // Skip if some other plugin (e.g. Yoast WooCommerce SEO premium)
        // already added a Product node — avoid duplicates that would
        // confuse Google's parser.
        foreach ( $graph as $node ) {
            $type = is_array( $node ) ? ( $node['@type'] ?? '' ) : '';
            if ( $type === 'Product'
                || ( is_array( $type ) && in_array( 'Product', $type, true ) ) ) {
                return $graph;
            }
        }

        $node = self::build_product_node( $product );
        if ( $node ) {
            $graph[] = $node;
        }
        return $graph;
    }

    /**
     * Assemble a Schema.org Product node aligned with WC's structured
     * data, augmented with brand/mpn/category/itemCondition/gtin13 the
     * same way `augment_product_schema()` does for WC's standalone
     * block.
     *
     * @return array<string, mixed>|null
     */
    private static function build_product_node( \WC_Product $product ): ?array {
        $product_id = $product->get_id();
        $permalink  = get_permalink( $product_id );
        if ( ! is_string( $permalink ) || $permalink === '' ) {
            return null;
        }

        $node = [
            '@type'       => 'Product',
            '@id'         => $permalink . '#product',
            'name'        => $product->get_name(),
            'url'         => $permalink,
            'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
            'sku'         => $product->get_sku(),
        ];

        // Image — primary featured image.
        $image_id = (int) $product->get_image_id();
        if ( $image_id > 0 ) {
            $img = wp_get_attachment_image_src( $image_id, 'full' );
            if ( is_array( $img ) && ! empty( $img[0] ) ) {
                $node['image'] = (string) $img[0];
            }
        }

        // Brand / MPN / Category / itemCondition / GTIN — reuse the same
        // resolution logic as the WC-block augmenter so both surfaces
        // tell the same story.
        $tmp = [];
        $tmp = self::apply_brand( $tmp, $product );
        $tmp = self::apply_mpn( $tmp, $product );
        $tmp = self::apply_category( $tmp, $product );
        if ( ! empty( $tmp['brand'] ) )    { $node['brand']    = $tmp['brand']; }
        if ( ! empty( $tmp['mpn'] ) )      { $node['mpn']      = $tmp['mpn']; }
        if ( ! empty( $tmp['category'] ) ) { $node['category'] = $tmp['category']; }

        $node['itemCondition'] = self::resolve_condition( $product );

        $gtin = (string) get_post_meta( $product_id, '_cmc_gtin13', true );
        $gtin = (string) apply_filters( 'cmc_product_schema_gtin13', $gtin, $product );
        $gtin = preg_replace( '/[^0-9]/', '', $gtin );
        if ( strlen( (string) $gtin ) === 13 ) {
            $node['gtin13'] = $gtin;
        }

        // Offer — minimal but GMC-valid.
        $price = $product->get_price();
        if ( $price !== '' && $price !== null ) {
            $availability = $product->is_in_stock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock';
            $offer = [
                '@type'           => 'Offer',
                'price'           => wc_format_decimal( $price, wc_get_price_decimals() ),
                'priceCurrency'   => get_woocommerce_currency(),
                'availability'    => $availability,
                'url'             => $permalink,
                'itemCondition'   => $node['itemCondition'],
                'priceValidUntil' => wp_date( 'Y-12-31' ),
            ];
            $node['offers'] = [ $offer ];
        }

        // Aggregate rating + review count (only when reviews exist).
        if ( $product->get_review_count() > 0 ) {
            $node['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $product->get_average_rating(),
                'reviewCount' => (int) $product->get_review_count(),
                'bestRating'  => '5',
                'worstRating' => '1',
            ];
        }

        return $node;
    }

    private static function apply_brand( array $markup, \WC_Product $product ): array {
        if ( ! empty( $markup['brand'] ) ) {
            return $markup;
        }
        $settings = class_exists( 'CMC_Settings' ) ? CMC_Settings::get() : [];
        $company  = is_array( $settings['company_info'] ?? null ) ? $settings['company_info'] : [];

        // Walk the candidate list in priority order — accept the FIRST
        // candidate that doesn't collide with a protected trademark.
        // Anything containing a known mega-brand token (Nike, Adidas,
        // Apple, Samsung, …) is rejected to avoid the GMC trademark-
        // infringement flag, even when the operator typed it themselves.
        $candidates = [
            (string) get_post_meta( $product->get_id(), '_cmc_brand', true ),
            (string) ( $company['ten_doanh_nghiep'] ?? '' ),
            (string) ( $company['ten_web'] ?? '' ),
            (string) get_option( 'blogname', '' ),
        ];

        $brand = '';
        foreach ( $candidates as $candidate ) {
            $candidate = trim( (string) $candidate );
            if ( $candidate === '' ) { continue; }
            if ( ! self::is_protected_brand( $candidate ) ) {
                $brand = $candidate;
                break;
            }
        }

        // All Settings candidates collided with the blocklist → derive
        // a safe brand from the current domain. The domain is unique
        // (the operator owns it) so it cannot collide with any
        // trademark; ucfirst on the second-level label gives a clean
        // brand-style string ("everbloomfaux.shop" → "Everbloomfaux").
        if ( $brand === '' ) {
            $brand = self::derive_brand_from_domain();
        }

        $brand = (string) apply_filters( 'cmc_product_schema_brand', $brand, $product );
        if ( $brand !== '' ) {
            $markup['brand'] = [
                '@type' => 'Brand',
                'name'  => $brand,
            ];
        }
        return $markup;
    }

    /**
     * True when the candidate string contains (case-insensitive substring)
     * any token from the PROTECTED_BRANDS list — including any extras the
     * `cmc_product_schema_protected_brands` filter adds. We use substring
     * rather than exact match so "Nike Outlet Store" gets caught the same
     * way "Nike" does.
     */
    private static function is_protected_brand( string $candidate ): bool {
        $candidate = strtolower( trim( $candidate ) );
        if ( $candidate === '' ) { return false; }

        $list = (array) apply_filters(
            'cmc_product_schema_protected_brands',
            self::PROTECTED_BRANDS
        );
        foreach ( $list as $brand ) {
            $brand = strtolower( trim( (string) $brand ) );
            if ( $brand === '' ) { continue; }
            if ( strpos( $candidate, $brand ) !== false ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Derive a safe, GMC-friendly brand name from the current site
     * domain. Strips `www.`, drops the public-suffix segment, replaces
     * hyphens with spaces, and Title-Cases the result so a domain like
     * `my-shop-store.com` reads as "My Shop Store" rather than
     * "my-shop-store" — better for human-facing schema output.
     */
    private static function derive_brand_from_domain(): string {
        $host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        $host = preg_replace( '/^www\./i', '', (string) $host );
        if ( $host === '' ) {
            return '';
        }
        $parts = explode( '.', $host );
        if ( count( $parts ) >= 2 ) {
            // Public-suffix-aware enough for the common cases:
            // `.com`, `.shop`, `.store`, `.co.uk`, `.com.vn` all collapse
            // to the leading label below.
            $candidate = (string) $parts[0];
        } else {
            $candidate = $host;
        }
        $candidate = str_replace( [ '-', '_' ], ' ', $candidate );
        $candidate = preg_replace( '/\s+/', ' ', trim( $candidate ) );
        if ( $candidate === '' ) {
            return '';
        }
        // Title-case each word; keeps "abc xyz" → "Abc Xyz".
        $candidate = ucwords( strtolower( $candidate ) );

        // Defensive: if the derived name itself happens to collide with
        // a protected mark (extremely unlikely on a real domain), tack
        // on a neutral suffix so we never emit a bare protected token.
        if ( self::is_protected_brand( $candidate ) ) {
            $candidate .= ' Store';
        }
        return $candidate;
    }

    private static function apply_mpn( array $markup, \WC_Product $product ): array {
        if ( ! empty( $markup['mpn'] ) ) {
            return $markup;
        }
        $sku = (string) $product->get_sku();
        $mpn = (string) apply_filters( 'cmc_product_schema_mpn', $sku, $product );
        if ( $mpn !== '' ) {
            $markup['mpn'] = $mpn;
        }
        return $markup;
    }

    private static function apply_category( array $markup, \WC_Product $product ): array {
        if ( ! empty( $markup['category'] ) ) {
            return $markup;
        }
        $category = self::resolve_primary_category( $product );
        if ( $category === '' ) {
            $settings = class_exists( 'CMC_Settings' ) ? CMC_Settings::get() : [];
            $company  = is_array( $settings['company_info'] ?? null ) ? $settings['company_info'] : [];
            if ( ! empty( $company['nganh_hang'] ) && class_exists( 'CMC_Shortcodes' ) ) {
                $options  = CMC_Shortcodes::nganh_hang_options();
                $category = (string) ( $options[ $company['nganh_hang'] ] ?? '' );
            }
        }
        $category = (string) apply_filters( 'cmc_product_schema_category', $category, $product );
        if ( $category !== '' ) {
            $markup['category'] = $category;
        }
        return $markup;
    }

    /**
     * Return a full schema.org URL for the product's condition. Default
     * NewCondition; postmeta `_cmc_condition` may carry the bare token
     * (`UsedCondition`) or the full URL — both forms are normalised to
     * the canonical https://schema.org/ form.
     */
    private static function resolve_condition( \WC_Product $product ): string {
        $raw = (string) get_post_meta( $product->get_id(), '_cmc_condition', true );
        if ( $raw === '' ) {
            $raw = 'NewCondition';
        }
        // Normalise: accept full URL, schema.org/X, or bare X.
        $raw = trim( $raw );
        if ( strpos( $raw, '://' ) === false ) {
            $raw = 'https://schema.org/' . ltrim( $raw, '/' );
            $raw = preg_replace( '#^https://schema\.org/schema\.org/#', 'https://schema.org/', $raw );
        }
        $raw = (string) apply_filters( 'cmc_product_schema_condition', $raw, $product );
        return $raw;
    }

    private static function resolve_primary_category( \WC_Product $product ): string {
        $product_id = $product->get_id();

        $primary_id = (int) get_post_meta( $product_id, '_yoast_wpseo_primary_product_cat', true );
        if ( $primary_id > 0 ) {
            $term = get_term( $primary_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                return (string) $term->name;
            }
        }

        $terms = get_the_terms( $product_id, 'product_cat' );
        if ( is_array( $terms ) && ! empty( $terms ) ) {
            usort( $terms, static fn( $a, $b ) => (int) $a->term_id <=> (int) $b->term_id );
            return (string) $terms[0]->name;
        }

        return '';
    }
}
