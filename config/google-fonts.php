<?php
/**
 * Curated list of popular Google Fonts.
 *
 * Returned as `[ 'Font Name' => 'Font Name' ]` so the value matches the
 * key (Flatsome's typography control stores the literal font name as a
 * string, no slug translation needed). Grouped via `// ====` divider
 * comments — the Settings page combobox renders these as `<optgroup>`
 * dividers the same way the industry list does.
 *
 * Curation criteria: top fonts on Google Fonts by usage in 2024–2026,
 * filtered to the ones that read well in body text + headings on an
 * e-commerce storefront (no display-only / handwritten that would break
 * paragraph readability when picked as the body font).
 *
 * Extend at runtime via the `cmc_google_fonts_options` filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    // ==== Sans-serif (most common for storefronts) ====
    'Inter',
    'Roboto',
    'Open Sans',
    'Lato',
    'Montserrat',
    'Poppins',
    'Nunito',
    'Nunito Sans',
    'Source Sans 3',
    'Source Sans Pro',
    'Work Sans',
    'Mulish',
    'Rubik',
    'DM Sans',
    'Manrope',
    'Karla',
    'Fira Sans',
    'Hind',
    'Quicksand',
    'Heebo',
    'Barlow',
    'PT Sans',
    'Oxygen',
    'Cabin',
    'Ubuntu',
    'Raleway',
    'Titillium Web',
    'Noto Sans',
    'IBM Plex Sans',
    'Public Sans',
    'Plus Jakarta Sans',
    'Be Vietnam Pro',
    'Outfit',
    'Albert Sans',
    'Onest',
    'Geist',

    // ==== Serif (editorial / luxury / fashion) ====
    'Playfair Display',
    'Merriweather',
    'Lora',
    'PT Serif',
    'Roboto Serif',
    'Source Serif 4',
    'Source Serif Pro',
    'Cormorant Garamond',
    'EB Garamond',
    'Crimson Text',
    'Crimson Pro',
    'Libre Baskerville',
    'Libre Caslon Text',
    'Libre Caslon Display',
    'Bitter',
    'Cardo',
    'Spectral',
    'DM Serif Display',
    'DM Serif Text',
    'Noto Serif',
    'IBM Plex Serif',
    'Frank Ruhl Libre',
    'Vollkorn',

    // ==== Display / Modern (logos, hero headings) ====
    'Oswald',
    'Bebas Neue',
    'Anton',
    'Archivo Black',
    'Abril Fatface',
    'Alfa Slab One',
    'Russo One',
    'Teko',
    'Yanone Kaffeesatz',
    'Big Shoulders Display',
    'Big Shoulders Text',

    // ==== Monospace (techy / dev brands) ====
    'JetBrains Mono',
    'Fira Code',
    'IBM Plex Mono',
    'Roboto Mono',
    'Source Code Pro',
    'Space Mono',

    // ==== Vietnamese-friendly (extra coverage) ====
    'Be Vietnam',
    'Sarabun',
    'Maven Pro',
];
