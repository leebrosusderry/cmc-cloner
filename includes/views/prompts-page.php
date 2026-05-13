<?php
/**
 * Prompts admin page — vertical tabs, one panel per template.
 *
 * @var array $templates  slug => definition map from CMC_Template_Registry::all().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$saved_slug = isset( $_GET['saved'] ) ? sanitize_key( (string) $_GET['saved'] ) : '';
$reset_slug = isset( $_GET['reset'] ) ? sanitize_key( (string) $_GET['reset'] ) : '';

$active_slug = '';
foreach ( [ $saved_slug, $reset_slug ] as $candidate ) {
    if ( $candidate !== '' && isset( $templates[ $candidate ] ) ) {
        $active_slug = $candidate;
        break;
    }
}
if ( $active_slug === '' ) {
    $active_slug = (string) array_key_first( $templates );
}

$available_vars = [
    '{{ten_web}}'          => 'Site name from Settings',
    '{{ten_doanh_nghiep}}' => 'Business name from Settings',
    '{{email_web}}'        => 'Email from Settings',
    '{{so_dien_thoai}}'    => 'Phone from Settings',
    '{{dia_chi}}'          => 'Address from Settings',
    '{{nganh_hang}}'       => 'Industry label from Settings',
    '{{primary_color}}'    => 'Primary color hex from Settings',
    '{{gio_lam_viec}}'     => 'Business hours from Settings (Service Commitments)',
    '{{timezone}}'         => 'Timezone abbreviation auto-extracted from gio_lam_viec parenthetical (e.g. "MST" from "...8:00 AM – 5:00 PM (MST)..."). Empty when not configured.',
    '{{response_time}}'    => 'Response time from Settings',
    '{{return_window}}'    => 'Return window from Settings (e.g. "30 days", "14 days", "100 nights"). Used by Return & Refund Policy + FAQ.',
    '{{SKELETON}}'         => 'Flatsome layout skeleton (injected by Variation Engine)',
    '{{PAGE_HTML}}'        => 'Raw post_content of the source page',
];
?>
<div class="wrap cmc-wrap cmc-prompts-wrap">
    <h1 class="cmc-title">
        <span class="cmc-brand-dot" style="background:<?php echo esc_attr( CMC_Settings::get()['primary_color'] ); ?>"></span>
        CMC Cloner &mdash; Prompts
    </h1>

    <?php if ( $saved_slug !== '' && isset( $templates[ $saved_slug ] ) ) : ?>
        <div class="notice notice-success is-dismissible cmc-notice">
            <p>Prompt for <strong><?php echo esc_html( $templates[ $saved_slug ]['label'] ); ?></strong> saved.</p>
        </div>
    <?php endif; ?>
    <?php if ( $reset_slug !== '' && isset( $templates[ $reset_slug ] ) ) : ?>
        <div class="notice notice-success is-dismissible cmc-notice">
            <p>Prompt for <strong><?php echo esc_html( $templates[ $reset_slug ]['label'] ); ?></strong> reset to default.</p>
        </div>
    <?php endif; ?>

    <div class="cmc-card">
        <div class="cmc-card__header">
            <h2>Available variables</h2>
            <p class="description">Copy any of these tokens into your prompt &mdash; they are replaced at generation time.</p>
        </div>
        <div class="cmc-card__body">
            <ul class="cmc-vars">
                <?php foreach ( $available_vars as $token => $desc ) : ?>
                    <li><code><?php echo esc_html( $token ); ?></code> <span><?php echo esc_html( $desc ); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="cmc-prompts">
        <nav class="cmc-prompts__nav" role="tablist" aria-label="Template prompts">
            <?php foreach ( $templates as $slug => $tpl ) :
                $is_active    = $slug === $active_slug;
                $is_override  = CMC_Prompts::is_overridden( $slug );
            ?>
                <button type="button"
                        role="tab"
                        class="cmc-prompts__nav-item <?php echo $is_active ? 'is-active' : ''; ?>"
                        data-target="tab-<?php echo esc_attr( $slug ); ?>"
                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                        id="navtab-<?php echo esc_attr( $slug ); ?>">
                    <span class="cmc-prompts__nav-label"><?php echo esc_html( $tpl['label'] ); ?></span>
                    <?php if ( $is_override ) : ?>
                        <span class="cmc-badge cmc-badge--custom" title="This prompt has been customized">custom</span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="cmc-prompts__panels">
            <?php foreach ( $templates as $slug => $tpl ) :
                $is_active    = $slug === $active_slug;
                $is_override  = CMC_Prompts::is_overridden( $slug );
                $current      = CMC_Prompts::effective( $slug );
                $default      = (string) ( $tpl['default_prompt'] ?? '' );
                $panel_id     = 'tab-' . $slug;
            ?>
                <section class="cmc-prompts__panel <?php echo $is_active ? 'is-active' : ''; ?>"
                         role="tabpanel"
                         id="<?php echo esc_attr( $panel_id ); ?>"
                         aria-labelledby="navtab-<?php echo esc_attr( $slug ); ?>">
                    <header class="cmc-prompts__panel-header">
                        <h2><?php echo esc_html( $tpl['label'] ); ?></h2>
                        <p class="description"><?php echo esc_html( $tpl['description'] ?? '' ); ?></p>
                        <?php if ( $is_override ) : ?>
                            <p class="cmc-status cmc-status--custom">Using a custom prompt. Reset to restore the built-in default.</p>
                        <?php else : ?>
                            <p class="cmc-status">Using the built-in default prompt. Any edits you save become a custom override.</p>
                        <?php endif; ?>
                    </header>

                    <form method="post" action="" class="cmc-prompt-form">
                        <?php wp_nonce_field( CMC_Prompts::NONCE_ACTION ); ?>
                        <input type="hidden" name="template_slug" value="<?php echo esc_attr( $slug ); ?>" />

                        <textarea name="prompt_content"
                                  class="cmc-prompt-textarea"
                                  rows="22"
                                  spellcheck="false"
                                  aria-label="Prompt content for <?php echo esc_attr( $tpl['label'] ); ?>"><?php echo esc_textarea( $current ); ?></textarea>

                        <div class="cmc-prompt-actions">
                            <button type="submit" name="cmc_prompt_save" value="1" class="button button-primary">Save Prompt</button>
                            <?php if ( $is_override ) : ?>
                                <button type="submit"
                                        name="cmc_prompt_reset"
                                        value="1"
                                        class="button button-secondary"
                                        onclick="return confirm('Reset this prompt to the built-in default? Your custom text will be lost.');">
                                    Reset to Default
                                </button>
                            <?php endif; ?>
                            <button type="button"
                                    class="button button-link cmc-prompt-show-default"
                                    data-slug="<?php echo esc_attr( $slug ); ?>">
                                View built-in default
                            </button>
                        </div>

                        <details class="cmc-prompt-default" id="default-<?php echo esc_attr( $slug ); ?>">
                            <summary>Built-in default (read-only)</summary>
                            <pre><?php echo esc_html( $default ); ?></pre>
                        </details>
                    </form>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>
