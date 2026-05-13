<?php
/**
 * View: CMC Cloner → Pages
 *
 * Provided by CMC_Pages_Controller::render_page():
 *   @var array  $pages     List from CMC_Page_Reader::list_pages()
 *   @var array  $templates List from CMC_Template_Registry::all()
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap cmc-wrap cmc-pages-wrap">

    <h1 class="cmc-title">
        <span>CMC Cloner &mdash; Pages</span>
    </h1>
    <p class="description">Pick a page, choose a template, review the prompt, generate GMC-compliant content with AI, then update the page in place.</p>

    <?php if ( empty( $pages ) ) : ?>
        <div class="notice notice-warning cmc-notice">
            <p>No pages found. Create at least one WordPress page before using CMC Cloner.</p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $pages ) ) : ?>
    <div class="cmc-card cmc-bulk-card">
        <div class="cmc-card__header">
            <h2>Bulk generate</h2>
            <p class="description">
                Pick multiple pages and generate + save them all in one go. Templates are auto-matched by page slug; override any row's dropdown if the guess is wrong. Pages without a confident match are disabled until you pick a template.
            </p>
        </div>
        <div class="cmc-card__body">
            <div class="cmc-bulk-bar">
                <span class="cmc-bulk-count">0 pages selected</span>
                <button type="button" class="button button-primary cmc-bulk-run-btn" disabled>Generate selected</button>
            </div>

            <div class="cmc-bulk-table-wrap">
                <table class="cmc-bulk-table widefat">
                    <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" class="cmc-bulk-all-chk" aria-label="Select all matched pages">
                            </th>
                            <th>Page</th>
                            <th>Template</th>
                            <th class="cmc-bulk-status-col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $pages as $p ) :
                            $auto     = (string) ( $p['auto_template'] ?? '' );
                            $has_auto = $auto !== '';
                        ?>
                            <tr
                                data-page-id="<?php echo (int) $p['id']; ?>"
                                data-auto-template="<?php echo esc_attr( $auto ); ?>"
                                class="<?php echo $p['cloned'] ? 'is-cloned-row' : ''; ?>"
                            >
                                <th class="check-column">
                                    <input
                                        type="checkbox"
                                        class="cmc-bulk-chk"
                                        <?php echo $has_auto ? '' : 'disabled'; ?>
                                        aria-label="Select <?php echo esc_attr( $p['title'] ); ?>"
                                    >
                                </th>
                                <td class="cmc-bulk-page-cell">
                                    <strong class="cmc-bulk-page-title"><?php echo esc_html( $p['title'] ); ?></strong>
                                    <?php if ( $p['status'] !== 'publish' ) : ?>
                                        <span class="cmc-bulk-badge">[<?php echo esc_html( $p['status'] ); ?>]</span>
                                    <?php endif; ?>
                                    <?php if ( $p['cloned'] ) : ?>
                                        <span class="cmc-bulk-badge is-cloned">cloned<?php echo $p['template'] !== '' ? ' · ' . esc_html( $p['template'] ) : ''; ?></span>
                                    <?php endif; ?>
                                    <code class="cmc-bulk-slug"><?php echo esc_html( $p['slug'] !== '' ? $p['slug'] : '(no slug)' ); ?></code>
                                </td>
                                <td>
                                    <select class="cmc-bulk-template">
                                        <option value="">— Pick template —</option>
                                        <?php foreach ( $templates as $t ) :
                                            $slug  = (string) ( $t['slug'] ?? '' );
                                            $label = (string) ( $t['label'] ?? $slug );
                                        ?>
                                            <option
                                                value="<?php echo esc_attr( $slug ); ?>"
                                                <?php selected( $auto, $slug ); ?>
                                            >
                                                <?php echo esc_html( $label ); ?>
                                                <?php echo $slug === $auto ? '— auto' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="cmc-bulk-status">
                                    <span class="cmc-bulk-status-text">—</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="cmc-bulk-modal" hidden role="dialog" aria-modal="true" aria-labelledby="cmc-bulk-modal-title">
        <div class="cmc-bulk-modal__backdrop"></div>
        <div class="cmc-bulk-modal__panel">
            <header class="cmc-bulk-modal__header">
                <h2 id="cmc-bulk-modal-title">Bulk generation</h2>
                <span class="cmc-bulk-modal__progress-text">0/0</span>
            </header>
            <div class="cmc-bulk-modal__progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <span style="width:0%"></span>
            </div>
            <ul class="cmc-bulk-modal__list"></ul>
            <footer class="cmc-bulk-modal__footer">
                <button type="button" class="button cmc-bulk-cancel-btn">Cancel</button>
                <button type="button" class="button button-primary cmc-bulk-retry-btn" hidden>Retry failed</button>
                <button type="button" class="button cmc-bulk-close-btn" disabled>Close</button>
            </footer>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $pages ) ) : ?>
    <h2 class="cmc-section-divider">— or work on one page at a time —</h2>
    <?php endif; ?>

    <div class="cmc-card">
        <div class="cmc-card__header">
            <h2>1. Pick a page</h2>
            <p class="description">Only WordPress pages (post_type=page) are listed. Start typing to search.</p>
        </div>
        <div class="cmc-card__body">
            <div class="cmc-field-row">
                <label for="cmc-page-select" class="cmc-field-row__label">Page</label>
                <select id="cmc-page-select" class="regular-text cmc-select">
                    <option value="">&mdash; Select a page &mdash;</option>
                    <?php foreach ( $pages as $p ) : ?>
                        <option
                            value="<?php echo esc_attr( (string) $p['id'] ); ?>"
                            data-cloned="<?php echo $p['cloned'] ? '1' : '0'; ?>"
                            data-template="<?php echo esc_attr( (string) $p['template'] ); ?>"
                            data-status="<?php echo esc_attr( (string) $p['status'] ); ?>"
                        >
                            <?php echo esc_html( $p['title'] ); ?>
                            <?php if ( $p['status'] !== 'publish' ) : ?>
                                [<?php echo esc_html( $p['status'] ); ?>]
                            <?php endif; ?>
                            <?php if ( $p['cloned'] ) : ?>
                                &bull; cloned<?php echo $p['template'] !== '' ? ' (' . esc_html( $p['template'] ) . ')' : ''; ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button cmc-btn-load" disabled>Load page</button>
            </div>
        </div>
    </div>

    <div class="cmc-card cmc-step cmc-step-page is-hidden">
        <div class="cmc-card__header">
            <h2>2. Page info</h2>
            <p class="description">Current state of the selected page and its raw content.</p>
        </div>
        <div class="cmc-card__body">
            <ul class="cmc-page-meta">
                <li><strong>Title:</strong> <span class="cmc-meta-title">&mdash;</span></li>
                <li><strong>Status:</strong> <span class="cmc-meta-status">&mdash;</span></li>
                <li><strong>Cloned:</strong> <span class="cmc-meta-cloned">&mdash;</span></li>
                <li class="cmc-meta-generated-row" hidden>
                    <strong>Last generated:</strong> <span class="cmc-meta-generated">&mdash;</span>
                </li>
                <li>
                    <strong>Links:</strong>
                    <a href="#" class="cmc-meta-view" target="_blank" rel="noopener">View</a>
                    &nbsp;|&nbsp;
                    <a href="#" class="cmc-meta-edit" target="_blank" rel="noopener">Edit</a>
                </li>
            </ul>
            <details class="cmc-original">
                <summary>Original page content (raw shortcodes / HTML)</summary>
                <textarea class="cmc-original-content cmc-code" readonly rows="12"></textarea>
            </details>
        </div>
    </div>

    <div class="cmc-card cmc-step cmc-step-template is-hidden">
        <div class="cmc-card__header">
            <h2>3. Pick a template</h2>
            <p class="description">Choose which GMC-compliant page template to generate. If the page is already cloned, its current template is preselected.</p>
        </div>
        <div class="cmc-card__body">
            <div class="cmc-field-row">
                <label for="cmc-template-select" class="cmc-field-row__label">Template</label>
                <select id="cmc-template-select" class="regular-text cmc-select">
                    <option value="">&mdash; Select a template &mdash;</option>
                    <?php foreach ( $templates as $t ) : ?>
                        <option value="<?php echo esc_attr( (string) $t['slug'] ); ?>">
                            <?php echo esc_html( (string) $t['label'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cmc-field-row">
                <label for="cmc-skeleton-select" class="cmc-field-row__label">Skeleton</label>
                <select id="cmc-skeleton-select" class="regular-text cmc-select" disabled>
                    <option value="">Auto (by style seed)</option>
                </select>
                <button type="button" class="button button-primary cmc-btn-preview" disabled>Preview prompt &rarr;</button>
                <span class="description cmc-skeleton-hint">Pick a specific skeleton to override the auto-picked one for this page.</span>
            </div>
        </div>
    </div>

    <div class="cmc-card cmc-step cmc-step-prompt is-hidden">
        <div class="cmc-card__header">
            <h2>4. Review &amp; edit prompt</h2>
            <p class="description">This prompt will be sent to the AI. You can edit it before generating.</p>
        </div>
        <div class="cmc-card__body">
            <ul class="cmc-prompt-meta">
                <li><strong>Template:</strong> <code class="cmc-meta-template">&mdash;</code></li>
                <li><strong>Skeleton:</strong> <code class="cmc-meta-skeleton">&mdash;</code></li>
                <li><strong>Style seed:</strong> <code class="cmc-meta-seed">&mdash;</code></li>
                <li><strong>Page has content:</strong> <span class="cmc-meta-hascontent">&mdash;</span></li>
            </ul>
            <div class="cmc-code-wrap">
                <textarea class="cmc-prompt-preview cmc-code" rows="22" spellcheck="false"></textarea>
                <button type="button" class="button button-small cmc-copy-btn" data-target=".cmc-prompt-preview">Copy</button>
            </div>
            <div class="cmc-actions">
                <button type="button" class="button button-primary cmc-btn-generate">Generate content</button>
                <button type="button" class="button cmc-btn-repreview">Re-build prompt</button>
                <span class="cmc-inline-msg"></span>
            </div>
        </div>
    </div>

    <div class="cmc-card cmc-step cmc-step-output is-hidden">
        <div class="cmc-card__header">
            <h2>5. Review &amp; update</h2>
            <p class="description">The AI output, in Flatsome shortcode / HTML form. Edit if needed, then click Update page.</p>
        </div>
        <div class="cmc-card__body">
            <div class="cmc-code-wrap">
                <textarea class="cmc-output cmc-code" rows="26" spellcheck="false"></textarea>
                <button type="button" class="button button-small cmc-copy-btn" data-target=".cmc-output">Copy</button>
            </div>
            <div class="cmc-actions">
                <button type="button" class="button button-primary button-hero cmc-btn-update">Update page</button>
                <button type="button" class="button cmc-btn-regenerate">Regenerate</button>
                <span class="cmc-inline-msg"></span>
            </div>
        </div>
    </div>

    <div class="cmc-card cmc-step cmc-step-revert is-hidden">
        <div class="cmc-card__header">
            <h2>Revert</h2>
            <p class="description">Restore the original page content from the backup. The backup will be deleted after reverting.</p>
        </div>
        <div class="cmc-card__body">
            <button type="button" class="button button-link-delete cmc-btn-revert">Revert to original</button>
            <span class="cmc-inline-msg"></span>
        </div>
    </div>

</div>
