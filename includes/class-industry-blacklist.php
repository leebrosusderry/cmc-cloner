<?php
/**
 * CMC Cloner — Cross-domain phrase blacklist.
 *
 * Single source of truth for "industry-coded abstract phrases" that LLMs
 * default to but which leak the wrong industry onto a GMC-compliant
 * page. Each rule maps a phrase pattern to the list of industry keywords
 * under which the phrase IS allowed; if `{{nganh_hang}}` doesn't contain
 * any of those keywords, the phrase counts as a violation.
 *
 * Example: "your space" is fine on a Home Decor site but a clear leak
 * on a Fashion site — so the rule whitelists home/decor/room/interior/
 * etc. and rejects everywhere else.
 *
 * Why a data file instead of a hard-coded family map:
 *   - The plugin runs across 800+ niches (config/nganh-hang-options.php).
 *     Hard-coding 9 families would leave the long tail without coverage.
 *   - This list captures only the FAILURE patterns we've observed in
 *     real GMC reviews + the well-known LLM defaults. New patterns get
 *     appended here as they surface from new GMC feedback.
 *
 * Two consumers:
 *   1. `CMC_Content_Validator::validate()` — server-side check after
 *      generation for About Us / Contact Us.
 *   2. `CMC_Prompt_Builder` — injects the list into prompts so the AI
 *      knows up-front what NOT to write.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Industry_Blacklist {

    /**
     * Phrase → list of industry-keyword tokens that, if found in the
     * configured `{{nganh_hang}}` value, whitelist the phrase. All
     * comparisons are case-insensitive.
     *
     * Append new rules here as new leak patterns surface from GMC
     * reviews. Keep entries scoped to ABSTRACT context-nouns (the
     * "wrap-around" framing of a sentence) — not concrete product
     * nouns, which are already handled by per-page OFFERINGS POLICY
     * blocks.
     *
     * @return array<string, list<string>>
     */
    public static function rules(): array {
        return [
            // ----- Home / Decor / Furniture / Interior context -----
            'your space'                      => [ 'home', 'decor', 'furniture', 'interior', 'room', 'kitchen', 'living', 'office', 'workspace', 'desk', 'garden', 'patio' ],
            'your environment'                => [ 'home', 'decor', 'furniture', 'interior', 'room', 'kitchen', 'living', 'office', 'workspace' ],
            'your home'                       => [ 'home', 'decor', 'furniture', 'interior', 'room', 'kitchen', 'living', 'garden', 'patio', 'pet', 'baby' ],
            'your room'                       => [ 'home', 'decor', 'furniture', 'interior', 'room', 'bedroom', 'kitchen', 'living' ],
            'your interior'                   => [ 'home', 'decor', 'furniture', 'interior' ],
            'enhance your space'              => [ 'home', 'decor', 'furniture', 'interior', 'room' ],
            'elevate your space'              => [ 'home', 'decor', 'furniture', 'interior', 'room' ],
            'beauty of your space'            => [ 'home', 'decor', 'furniture', 'interior', 'room' ],
            'joy to your environment'         => [ 'home', 'decor', 'furniture', 'interior', 'room' ],
            'functionality to your environment'=> [ 'home', 'decor', 'furniture', 'interior', 'room' ],
            'enhancing spaces'                => [ 'home', 'decor', 'furniture', 'interior', 'room' ],

            // ----- Fashion / Apparel / Clothing context -----
            'your wardrobe'                   => [ 'fashion', 'apparel', 'clothing', 'wear', 'shoe', 'jewelry', 'accessory', 'streetwear', 'menswear', 'womenswear' ],
            'your outfit'                     => [ 'fashion', 'apparel', 'clothing', 'wear', 'shoe' ],
            'your style'                      => [ 'fashion', 'apparel', 'clothing', 'wear', 'shoe', 'jewelry', 'accessory', 'beauty', 'hair' ],
            'your styling'                    => [ 'fashion', 'apparel', 'clothing', 'wear', 'shoe' ],
            'daily styling'                   => [ 'fashion', 'apparel', 'clothing', 'wear', 'shoe' ],
            'fit and wearability'             => [ 'fashion', 'apparel', 'clothing', 'wear', 'shoe' ],

            // ----- Beauty / Skincare / Wellness context -----
            'your routine'                    => [ 'beauty', 'skin', 'skincare', 'cosmetic', 'makeup', 'wellness', 'health', 'hair', 'self-care' ],
            'your skin'                       => [ 'beauty', 'skin', 'skincare', 'cosmetic', 'wellness' ],
            'your regimen'                    => [ 'beauty', 'skin', 'skincare', 'cosmetic', 'wellness', 'health' ],
            'your hair'                       => [ 'beauty', 'hair', 'cosmetic', 'wig', 'salon' ],

            // ----- Kitchen / Food / Cookware context -----
            'your kitchen'                    => [ 'home', 'kitchen', 'cookware', 'cook', 'food', 'beverage', 'pantry', 'dining' ],
            'your pantry'                     => [ 'home', 'kitchen', 'cookware', 'food', 'beverage', 'pantry' ],
            'your meal'                       => [ 'food', 'beverage', 'kitchen', 'cookware', 'meal', 'dining' ],

            // ----- Fitness / Sports / Outdoor context -----
            'your workout'                    => [ 'fitness', 'sport', 'gym', 'athletic', 'training', 'workout', 'exercise', 'yoga', 'pilates' ],
            'your gear'                       => [ 'fitness', 'sport', 'gym', 'athletic', 'training', 'outdoor', 'camping', 'hiking', 'cycling', 'tech', 'gadget' ],
            'your training'                   => [ 'fitness', 'sport', 'gym', 'athletic', 'training', 'workout' ],

            // ----- Office / Workspace context -----
            'your workspace'                  => [ 'office', 'desk', 'work', 'stationery', 'organizer', 'workspace' ],
            'your desk'                       => [ 'office', 'desk', 'work', 'stationery', 'organizer', 'workspace' ],

            // ----- Pet / Baby / Kids context -----
            'your pet'                        => [ 'pet', 'dog', 'cat', 'animal', 'aquarium', 'bird' ],
            'your baby'                       => [ 'baby', 'kid', 'child', 'toddler', 'infant', 'maternity', 'newborn', 'nursery' ],
            'your child'                      => [ 'baby', 'kid', 'child', 'toddler', 'infant', 'school', 'toy', 'maternity' ],

            // ----- Garden / Outdoor context -----
            'your garden'                     => [ 'garden', 'plant', 'outdoor', 'patio', 'lawn', 'yard', 'flower' ],
        ];
    }

    /**
     * Run the blacklist against a piece of text given the configured
     * industry. Returns the list of violations (empty = clean).
     *
     * @return list<array{phrase:string, allowed_keywords:list<string>}>
     */
    public static function violations( string $text, string $industry ): array {
        $text     = strtolower( $text );
        $industry = strtolower( $industry );
        $hits     = [];

        foreach ( self::rules() as $phrase => $allowed_keywords ) {
            // Cheap substring match — these phrases are short enough that
            // a word-boundary regex is overkill (and would miss compound
            // forms like "your-space" anyway).
            if ( strpos( $text, $phrase ) === false ) {
                continue;
            }
            // Phrase appears. Allow it only if the industry contains any
            // of the whitelist keywords.
            $allowed = false;
            foreach ( $allowed_keywords as $kw ) {
                if ( $kw !== '' && strpos( $industry, $kw ) !== false ) {
                    $allowed = true;
                    break;
                }
            }
            if ( ! $allowed ) {
                $hits[] = [
                    'phrase'           => $phrase,
                    'allowed_keywords' => $allowed_keywords,
                ];
            }
        }

        return $hits;
    }

    /**
     * Format the rule list as a human-readable bullet block to inject
     * into a prompt — gives the AI an up-front "do not say these things"
     * list so we catch leaks at generation time, not only at validation.
     */
    public static function as_prompt_block(): string {
        $lines = [];
        foreach ( self::rules() as $phrase => $allowed_keywords ) {
            $lines[] = sprintf(
                '- "%s" — only allowed if {{nganh_hang}} contains: %s',
                $phrase,
                implode( ', ', $allowed_keywords )
            );
        }
        return implode( "\n", $lines );
    }
}
