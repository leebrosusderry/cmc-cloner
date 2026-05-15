/* global jQuery, CMCCloner */
(function ($) {
    'use strict';

    var toast;

    $(function () {
        toast = initToasts();
        initColorPicker();
        initProviderRows();
        initShortcodeSwitchLabel();
        initTestApi();
        initSkeletonRandomize();
        initPromptTabs();
        initPromptShowDefault();
        initPagesScreen();
        initBulkGenerate();
        initImageRename();
        initProductsEraser();
        initSkuNormalizer();
        initTitleRewriter();
        initReviewSeeder();
        initRunAll();
        initPodSetupButton();
        initRevertSubcatsButton();
        initCopyButtons();
        initComboboxes();
        initHomepagePrompt();
    });

    function initColorPicker() {
        if ($.fn.wpColorPicker) {
            $('.cmc-color-field').wpColorPicker();
        }
    }

    function initProviderRows() {
        var $provider = $('#ai_provider');
        if (!$provider.length) {
            return;
        }
        function apply() {
            var value = $provider.val();
            $('.cmc-provider-row').removeClass('is-active');
            $('.cmc-provider-' + value).addClass('is-active');
        }
        $provider.on('change', apply);
        apply();
    }

    function initShortcodeSwitchLabel() {
        $('.cmc-switch input[type="checkbox"]').on('change', function () {
            var $text = $(this).closest('.cmc-switch').find('.cmc-switch__text');
            if (!$text.length) {
                return;
            }
            $text.text(this.checked
                ? 'Enabled — CMC Cloner owns the shortcodes.'
                : 'Disabled — legacy plugin still owns the shortcodes.');
        });
    }

    function initTestApi() {
        var $btn = $('#cmc-test-api');
        if (!$btn.length || typeof CMCCloner === 'undefined') {
            return;
        }
        var $result = $('.cmc-test-result');

        $btn.on('click', function () {
            if (!hasKeyForActiveProvider()) {
                warnMissingKey();
                return;
            }
            busy($btn, true, 'Testing…');
            $result.removeClass('is-success is-error').addClass('is-loading').text('Testing…');

            $.post(CMCCloner.ajaxUrl, {
                action: CMCCloner.actions.test,
                nonce: CMCCloner.nonce
            })
                .done(function (res) {
                    if (res && res.success && res.data) {
                        $result.removeClass('is-loading is-error').addClass('is-success')
                            .text('✓ OK via ' + res.data.provider + ' — response: "' + (res.data.output || '') + '"');
                        toast.success('API key works for ' + res.data.provider + '.');
                    } else {
                        var msg = extractError(res, 'Unknown error');
                        $result.removeClass('is-loading is-success').addClass('is-error').text('✗ ' + msg);
                        toast.error('API test failed: ' + msg);
                    }
                })
                .fail(function (xhr) {
                    var msg = xhrError(xhr);
                    $result.removeClass('is-loading is-success').addClass('is-error').text('✗ ' + msg);
                    toast.error('API test failed: ' + msg);
                })
                .always(function () { busy($btn, false); });
        });
    }

    function initSkeletonRandomize() {
        var $btn = $('#cmc-skeleton-randomize');
        if (!$btn.length || typeof CMCCloner === 'undefined') {
            return;
        }
        var $result = $('.cmc-skeleton-randomize-result');
        var $select = $('#skeleton_variant');

        $btn.on('click', function () {
            busy($btn, true, 'Randomizing…');
            $result.removeClass('is-success is-error').addClass('is-loading').text('Randomizing…');

            $.post(CMCCloner.ajaxUrl, {
                action: CMCCloner.actions.skeletonRandomize,
                nonce: CMCCloner.nonce
            })
                .done(function (res) {
                    if (res && res.success && res.data && res.data.number) {
                        var n = parseInt(res.data.number, 10);
                        $select.val('auto');
                        $select.find('option[value="auto"]').text('Auto (currently ' + n + ')');
                        $result.removeClass('is-loading is-error').addClass('is-success').text('✓ Picked skeleton ' + n + '.');
                        toast.success('Skeleton variant set to ' + n + '.');
                    } else {
                        var msg = extractError(res, 'Unknown error');
                        $result.removeClass('is-loading is-success').addClass('is-error').text('✗ ' + msg);
                        toast.error('Re-randomize failed: ' + msg);
                    }
                })
                .fail(function (xhr) {
                    var msg = xhrError(xhr);
                    $result.removeClass('is-loading is-success').addClass('is-error').text('✗ ' + msg);
                    toast.error('Re-randomize failed: ' + msg);
                })
                .always(function () { busy($btn, false); });
        });
    }

    function initPromptTabs() {
        var $nav = $('.cmc-prompts__nav');
        if (!$nav.length) {
            return;
        }
        $nav.on('click', '.cmc-prompts__nav-item', function () {
            var target = $(this).data('target');
            $nav.find('.cmc-prompts__nav-item').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');
            $('.cmc-prompts__panel').removeClass('is-active');
            $('#' + target).addClass('is-active');
            if (history.replaceState) {
                history.replaceState(null, '', '#' + target);
            }
        });

        var hash = (window.location.hash || '').replace(/^#/, '');
        if (hash) {
            var $btn = $nav.find('[data-target="' + hash + '"]');
            if ($btn.length) {
                $btn.trigger('click');
            }
        }
    }

    function initPromptShowDefault() {
        $('.cmc-prompt-show-default').on('click', function () {
            var slug = $(this).data('slug');
            var $details = $('#default-' + slug);
            if ($details.length) {
                $details.attr('open', !$details.attr('open'));
                var el = $details.get(0);
                if (el && el.scrollIntoView) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        });
    }

    /* ----------------------- Pages screen ----------------------- */

    function initPagesScreen() {
        var $wrap = $('.cmc-pages-wrap');
        if (!$wrap.length || typeof CMCCloner === 'undefined') {
            return;
        }

        var $pageSelect     = $wrap.find('#cmc-page-select');
        var $loadBtn        = $wrap.find('.cmc-btn-load');
        var $templateSelect = $wrap.find('#cmc-template-select');
        var $skeletonSelect = $wrap.find('#cmc-skeleton-select');
        var $previewBtn     = $wrap.find('.cmc-btn-preview');
        var $rePreviewBtn   = $wrap.find('.cmc-btn-repreview');
        var $generateBtn    = $wrap.find('.cmc-btn-generate');
        var $regenBtn       = $wrap.find('.cmc-btn-regenerate');
        var $updateBtn      = $wrap.find('.cmc-btn-update');
        var $revertBtn      = $wrap.find('.cmc-btn-revert');

        var state = {
            pageId:       0,
            templateSlug: '',
            skeletonSlug: '',
            styleSeed:    0
        };
        var baseline = { prompt: '', output: '' };
        var dirty    = { prompt: false, output: false };

        $wrap.on('input change', '.cmc-prompt-preview', function () {
            dirty.prompt = ($(this).val() !== baseline.prompt);
        });
        $wrap.on('input change', '.cmc-output', function () {
            dirty.output = ($(this).val() !== baseline.output);
        });

        $(window).on('beforeunload.cmcCloner', function () {
            if (dirty.prompt || dirty.output) {
                return CMCCloner.strings.unsavedChanges;
            }
        });

        function checkUnsaved() {
            if (!dirty.prompt && !dirty.output) { return true; }
            return confirm(CMCCloner.strings.unsavedChanges);
        }

        $pageSelect.on('change', function () {
            var val = parseInt($pageSelect.val(), 10) || 0;
            $loadBtn.prop('disabled', val === 0);
            hideStepsFrom('page');
        });

        function populateSkeletons(templateSlug, preselect) {
            var map = (CMCCloner && CMCCloner.skeletons) || {};
            var list = (templateSlug && map[templateSlug]) || [];
            $skeletonSelect.empty();
            $skeletonSelect.append($('<option>').val('').text('Auto (by style seed)'));
            list.forEach(function (slug) {
                $skeletonSelect.append($('<option>').val(slug).text(slug));
            });
            var chosen = (preselect && list.indexOf(preselect) !== -1) ? preselect : '';
            $skeletonSelect.val(chosen);
            $skeletonSelect.prop('disabled', list.length === 0);
        }

        $loadBtn.on('click', function () {
            var pageId = parseInt($pageSelect.val(), 10) || 0;
            if (!pageId) { return; }
            if (!checkUnsaved()) { return; }

            busy($loadBtn, true, 'Loading…');
            post(CMCCloner.actions.load, { page_id: pageId })
                .done(function (res) {
                    if (!res || !res.success || !res.data || !res.data.page) {
                        toast.error(extractError(res, 'Failed to load page.'));
                        return;
                    }
                    renderPageInfo(res.data.page);
                    state.pageId = res.data.page.id;
                    dirty.prompt = false;
                    dirty.output = false;
                    baseline.prompt = '';
                    baseline.output = '';

                    var opt = $pageSelect.find('option:selected');
                    var existingTpl = opt.data('template') || '';
                    var existingSkel = res.data.page.skeleton || '';
                    $templateSelect.val(existingTpl || '');
                    populateSkeletons(existingTpl || '', existingSkel);
                    $previewBtn.prop('disabled', $templateSelect.val() === '');

                    showStep('page');
                    showStep('template');
                    if (res.data.page.has_backup) {
                        showStep('revert');
                    } else {
                        hideStep('revert');
                    }
                })
                .fail(function (xhr) { toast.error(xhrError(xhr)); })
                .always(function () { busy($loadBtn, false); });
        });

        $templateSelect.on('change', function () {
            $previewBtn.prop('disabled', $templateSelect.val() === '');
            populateSkeletons($templateSelect.val(), '');
            hideStepsFrom('template');
        });

        $skeletonSelect.on('change', function () {
            hideStepsFrom('template');
        });

        $previewBtn.on('click', function () { buildPrompt(); });
        $rePreviewBtn.on('click', function () {
            if (!confirm('Discard edits and re-build the prompt from the template?')) { return; }
            buildPrompt();
        });

        function buildPrompt() {
            var templateSlug = $templateSelect.val();
            if (!state.pageId || !templateSlug) { return; }

            busy($previewBtn, true, 'Building…');
            busy($rePreviewBtn, true);
            post(CMCCloner.actions.preview, {
                page_id:       state.pageId,
                template_slug: templateSlug,
                skeleton_slug: $skeletonSelect.val() || ''
            })
                .done(function (res) {
                    if (!res || !res.success || !res.data) {
                        toast.error(extractError(res, 'Failed to preview prompt.'));
                        return;
                    }
                    state.templateSlug = res.data.template_slug;
                    state.skeletonSlug = res.data.skeleton_slug;
                    state.styleSeed    = parseInt(res.data.style_seed, 10) || 0;

                    if ($skeletonSelect.val() !== res.data.skeleton_slug && $skeletonSelect.find('option[value="' + res.data.skeleton_slug + '"]').length) {
                        $skeletonSelect.val(res.data.skeleton_slug);
                    }

                    $wrap.find('.cmc-meta-template').text(res.data.template_slug);
                    $wrap.find('.cmc-meta-skeleton').text(res.data.skeleton_slug);
                    $wrap.find('.cmc-meta-seed').text(String(state.styleSeed));
                    $wrap.find('.cmc-meta-hascontent').text(res.data.has_content ? 'yes' : 'no (empty page)');
                    $wrap.find('.cmc-prompt-preview').val(res.data.prompt);

                    baseline.prompt = res.data.prompt;
                    dirty.prompt = false;

                    showStep('prompt');
                    hideStepsFrom('prompt');

                    if (!res.data.has_content) {
                        toast.warning('The selected page has no existing content — the AI will have to improvise.');
                    }
                })
                .fail(function (xhr) { toast.error(xhrError(xhr)); })
                .always(function () {
                    busy($previewBtn, false);
                    busy($rePreviewBtn, false);
                });
        }

        $generateBtn.on('click', function () { generate(); });
        $regenBtn.on('click', function () {
            if (!confirm(CMCCloner.strings.confirmReplace)) { return; }
            generate();
        });

        function generate() {
            var prompt = $wrap.find('.cmc-prompt-preview').val();
            if (!prompt || !prompt.trim()) {
                setInlineMsg($generateBtn, 'Prompt is empty.', 'error');
                return;
            }
            if (!hasKeyForActiveProvider()) {
                warnMissingKey();
                return;
            }

            busy($generateBtn, true, 'Generating…', true);
            busy($regenBtn, true);
            setInlineMsg($generateBtn, 'Talking to the AI…', 'loading');

            post(CMCCloner.actions.generate, { prompt: prompt })
                .done(function (res) {
                    if (!res || !res.success || !res.data) {
                        var msg = extractError(res, 'Generation failed.');
                        setInlineMsg($generateBtn, msg, 'error');
                        toast.error('Generation failed: ' + msg);
                        return;
                    }
                    $wrap.find('.cmc-output').val(res.data.content);
                    baseline.output = res.data.content;
                    dirty.output = false;
                    setInlineMsg($generateBtn, '✓ Generated via ' + res.data.provider, 'success');
                    toast.success('Generated via ' + res.data.provider + '. Review and click Update page.');
                    showStep('output');
                })
                .fail(function (xhr) {
                    var msg = xhrError(xhr);
                    setInlineMsg($generateBtn, msg, 'error');
                    toast.error('Generation failed: ' + msg);
                })
                .always(function () {
                    busy($generateBtn, false);
                    busy($regenBtn, false);
                });
        }

        $updateBtn.on('click', function () {
            var content = $wrap.find('.cmc-output').val();
            if (!content || !content.trim()) {
                setInlineMsg($updateBtn, 'Nothing to save.', 'error');
                return;
            }

            busy($updateBtn, true, 'Saving…', true);
            setInlineMsg($updateBtn, 'Updating page…', 'loading');

            post(CMCCloner.actions.update, {
                page_id:       state.pageId,
                content:       content,
                template_slug: state.templateSlug,
                skeleton_slug: state.skeletonSlug,
                style_seed:    state.styleSeed
            })
                .done(function (res) {
                    if (!res || !res.success || !res.data || !res.data.page) {
                        var msg = extractError(res, 'Update failed.');
                        setInlineMsg($updateBtn, msg, 'error');
                        toast.error('Update failed: ' + msg);
                        return;
                    }
                    renderPageInfo(res.data.page);
                    updatePageOption(res.data.page);
                    baseline.output = content;
                    dirty.output = false;
                    setInlineMsg($updateBtn, '✓ Saved. Page is now cloned.', 'success');
                    var html = '✓ Page updated. <a href="' + res.data.page.view_url + '" target="_blank" rel="noopener">View</a> &middot; <a href="' + res.data.page.edit_url + '" target="_blank" rel="noopener">Edit</a>';
                    toast.success(html, { html: true });
                    showStep('revert');
                })
                .fail(function (xhr) {
                    var msg = xhrError(xhr);
                    setInlineMsg($updateBtn, msg, 'error');
                    toast.error('Update failed: ' + msg);
                })
                .always(function () { busy($updateBtn, false); });
        });

        $revertBtn.on('click', function () {
            if (!confirm(CMCCloner.strings.confirmRevert)) { return; }

            busy($revertBtn, true, 'Reverting…');
            setInlineMsg($revertBtn, 'Restoring original content…', 'loading');

            post(CMCCloner.actions.revert, { page_id: state.pageId })
                .done(function (res) {
                    if (!res || !res.success || !res.data || !res.data.page) {
                        var msg = extractError(res, 'Revert failed.');
                        setInlineMsg($revertBtn, msg, 'error');
                        toast.error('Revert failed: ' + msg);
                        return;
                    }
                    renderPageInfo(res.data.page);
                    updatePageOption(res.data.page);
                    baseline.output = '';
                    dirty.output = false;
                    setInlineMsg($revertBtn, '✓ Reverted to original.', 'success');
                    toast.success('Page reverted to original content.');
                    hideStep('revert');
                    hideStep('output');
                })
                .fail(function (xhr) {
                    var msg = xhrError(xhr);
                    setInlineMsg($revertBtn, msg, 'error');
                    toast.error('Revert failed: ' + msg);
                })
                .always(function () { busy($revertBtn, false); });
        });

        function renderPageInfo(page) {
            $wrap.find('.cmc-meta-title').text(page.title || '(no title)');
            $wrap.find('.cmc-meta-status').text(page.status);
            $wrap.find('.cmc-meta-cloned').text(
                page.cloned
                    ? 'yes (' + (page.template || '—') + (page.has_backup ? ', backup available' : '') + ')'
                    : 'no'
            );
            var $genRow = $wrap.find('.cmc-meta-generated-row');
            if (page.cloned && page.generated_at_human) {
                $wrap.find('.cmc-meta-generated').text(page.generated_at_human);
                $genRow.prop('hidden', false);
            } else {
                $genRow.prop('hidden', true);
            }
            $wrap.find('.cmc-meta-view').attr('href', page.view_url);
            $wrap.find('.cmc-meta-edit').attr('href', page.edit_url);
            $wrap.find('.cmc-original-content').val(page.content);
        }

        function updatePageOption(page) {
            var $opt = $pageSelect.find('option[value="' + page.id + '"]');
            if (!$opt.length) { return; }
            $opt.attr('data-cloned', page.cloned ? '1' : '0');
            $opt.attr('data-template', page.template || '');
            var label = page.title || '(no title)';
            if (page.status !== 'publish') { label += ' [' + page.status + ']'; }
            if (page.cloned) { label += ' • cloned' + (page.template ? ' (' + page.template + ')' : ''); }
            $opt.text(label);
        }

        /* ----- step visibility ----- */

        var STEP_ORDER = ['page', 'template', 'prompt', 'output', 'revert'];

        function showStep(name) {
            $wrap.find('.cmc-step-' + name).removeClass('is-hidden');
        }
        function hideStep(name) {
            $wrap.find('.cmc-step-' + name).addClass('is-hidden');
        }
        function hideStepsFrom(name) {
            var i = STEP_ORDER.indexOf(name);
            if (i < 0) { return; }
            for (var j = i + 1; j < STEP_ORDER.length; j++) {
                hideStep(STEP_ORDER[j]);
            }
        }
    }

    /* ----------------------- Bulk generate ----------------------- */

    function initBulkGenerate() {
        var $wrap = $('.cmc-pages-wrap');
        if (!$wrap.length || typeof CMCCloner === 'undefined') { return; }

        var $card = $wrap.find('.cmc-bulk-card');
        if (!$card.length) { return; }

        var $modal      = $wrap.find('.cmc-bulk-modal');
        var $allChk     = $card.find('.cmc-bulk-all-chk');
        var $runBtn     = $card.find('.cmc-bulk-run-btn');
        var $countLabel = $card.find('.cmc-bulk-count');

        var DELAY_MS = (CMCCloner.bulkDelayMs != null) ? CMCCloner.bulkDelayMs : 5000;

        function eligibleRows() {
            return $card.find('tbody tr').filter(function () {
                return !$(this).find('.cmc-bulk-chk').prop('disabled');
            });
        }

        function selectedRows() {
            return $card.find('.cmc-bulk-chk:checked').closest('tr');
        }

        function rowTemplate($tr) {
            return String($tr.find('.cmc-bulk-template').val() || '');
        }

        function refreshControls() {
            var $selected = selectedRows();
            var n         = $selected.length;
            $countLabel.text(n + (n === 1 ? ' page selected' : ' pages selected'));

            var allValid = n > 0;
            $selected.each(function () {
                if (!rowTemplate($(this))) { allValid = false; return false; }
            });
            $runBtn.prop('disabled', !allValid);

            var $eligible = eligibleRows();
            var allChecked = $eligible.length > 0 && $eligible.filter(function () {
                return $(this).find('.cmc-bulk-chk').prop('checked');
            }).length === $eligible.length;
            $allChk.prop('checked', allChecked);
            $allChk.prop('indeterminate', !allChecked && n > 0);
        }

        $card.on('change', '.cmc-bulk-chk', refreshControls);

        $card.on('change', '.cmc-bulk-template', function () {
            var $tr = $(this).closest('tr');
            var hasTpl = !!$(this).val();
            var $chk = $tr.find('.cmc-bulk-chk');
            $chk.prop('disabled', !hasTpl);
            if (!hasTpl) { $chk.prop('checked', false); }
            refreshControls();
        });

        $allChk.on('change', function () {
            var on = $(this).prop('checked');
            eligibleRows().find('.cmc-bulk-chk').prop('checked', on);
            refreshControls();
        });

        $runBtn.on('click', function () {
            if (!hasKeyForActiveProvider()) { warnMissingKey(); return; }
            var $selected = selectedRows();
            if (!$selected.length) { return; }

            var jobs = [];
            $selected.each(function () {
                var $tr = $(this);
                jobs.push({
                    pageId:   parseInt($tr.data('page-id'), 10) || 0,
                    template: rowTemplate($tr),
                    title:    $tr.find('.cmc-bulk-page-title').text(),
                    $tr:      $tr,
                    status:   'queued',
                    error:    '',
                    duration: 0
                });
            });
            runBulk(jobs);
        });

        function runBulk(jobs) {
            var cancelled = false;

            openModal(jobs);

            $modal.find('.cmc-bulk-cancel-btn').off('click').on('click', function () {
                cancelled = true;
                $(this).prop('disabled', true).text('Cancelling…');
            });

            function runQueue(queue) {
                var i = 0;

                function finish() {
                    $modal.find('.cmc-bulk-cancel-btn').prop('disabled', true).text('Cancel');
                    $modal.find('.cmc-bulk-close-btn').prop('disabled', false);

                    var failed = jobs.filter(function (j) { return j.status === 'error'; });
                    var okCount = jobs.filter(function (j) { return j.status === 'ok'; }).length;

                    if (failed.length) {
                        var $retry = $modal.find('.cmc-bulk-retry-btn');
                        $retry.prop('hidden', false).text('Retry ' + failed.length + ' failed')
                            .off('click').on('click', function () {
                                failed.forEach(function (j) {
                                    j.status = 'queued'; j.error = ''; j.duration = 0;
                                    renderJob(j);
                                });
                                $retry.prop('hidden', true);
                                $modal.find('.cmc-bulk-cancel-btn').prop('disabled', false).text('Cancel');
                                $modal.find('.cmc-bulk-close-btn').prop('disabled', true);
                                cancelled = false;
                                updateProgress(jobs);
                                runQueue(failed);
                            });
                    }

                    var summary = 'Bulk generation finished — ' + okCount + '/' + jobs.length + ' succeeded.';
                    if (failed.length === 0 && okCount === jobs.length) {
                        toast.success(summary);
                    } else if (okCount === 0) {
                        toast.error(summary);
                    } else {
                        toast.warning(summary);
                    }
                }

                function step() {
                    if (cancelled) {
                        queue.slice(i).forEach(function (j) {
                            if (j.status === 'queued') {
                                j.status = 'cancelled';
                                renderJob(j);
                            }
                        });
                        updateProgress(jobs);
                        finish();
                        return;
                    }
                    if (i >= queue.length) { finish(); return; }

                    var job = queue[i++];
                    job.status = 'running';
                    renderJob(job);
                    var startedAt = Date.now();

                    post(CMCCloner.actions.bulkOne, {
                        page_id:       job.pageId,
                        template_slug: job.template
                    })
                        .done(function (res) {
                            if (res && res.success && res.data) {
                                job.status = 'ok';
                                job.duration = Math.round((Date.now() - startedAt) / 100) / 10;
                                applySuccessToRow(job, res.data);
                            } else {
                                job.status = 'error';
                                job.error  = extractError(res, 'Unknown error');
                                applyErrorToRow(job);
                            }
                            renderJob(job);
                        })
                        .fail(function (xhr) {
                            job.status = 'error';
                            job.error  = xhrError(xhr);
                            applyErrorToRow(job);
                            renderJob(job);
                        })
                        .always(function () {
                            updateProgress(jobs);
                            if (cancelled || i >= queue.length) {
                                step();
                            } else {
                                setTimeout(step, DELAY_MS);
                            }
                        });
                }
                step();
            }

            runQueue(jobs);
        }

        function openModal(jobs) {
            var $list = $modal.find('.cmc-bulk-modal__list').empty();
            jobs.forEach(function (j) {
                var $li = $(
                    '<li class="cmc-bulk-job">' +
                        '<span class="cmc-bulk-job__status" aria-hidden="true"></span>' +
                        '<span class="cmc-bulk-job__title"></span>' +
                        '<span class="cmc-bulk-job__meta"></span>' +
                    '</li>'
                );
                $li.find('.cmc-bulk-job__title').text(j.title);
                $list.append($li);
                j.$li = $li;
                renderJob(j);
            });
            $modal.find('.cmc-bulk-modal__progress-text').text('0/' + jobs.length);
            $modal.find('.cmc-bulk-modal__progress span').css('width', '0%');
            $modal.find('.cmc-bulk-modal__progress').attr('aria-valuenow', '0');
            $modal.find('.cmc-bulk-close-btn').prop('disabled', true);
            $modal.find('.cmc-bulk-retry-btn').prop('hidden', true);
            $modal.find('.cmc-bulk-cancel-btn').prop('disabled', false).text('Cancel');
            $modal.prop('hidden', false).addClass('is-open');
            updateProgress(jobs);
        }

        $modal.on('click', '.cmc-bulk-close-btn', function () {
            $modal.prop('hidden', true).removeClass('is-open');
        });

        $modal.on('click', '.cmc-bulk-modal__backdrop', function () {
            if (!$modal.find('.cmc-bulk-close-btn').prop('disabled')) {
                $modal.prop('hidden', true).removeClass('is-open');
            }
        });

        function renderJob(j) {
            if (!j.$li) { return; }
            var icons  = { queued: '·', running: '⟳', ok: '✓', error: '✗', cancelled: '–' };
            var labels = { queued: 'Queued', running: 'Generating…', ok: '', error: '', cancelled: 'Cancelled' };
            j.$li.attr('data-status', j.status);
            j.$li.find('.cmc-bulk-job__status').text(icons[j.status] || '·');

            var meta;
            if (j.status === 'ok') {
                meta = 'Saved in ' + j.duration + 's — ' + j.template;
            } else if (j.status === 'error') {
                meta = j.error || 'Error';
            } else if (j.status === 'queued') {
                meta = 'Queued — ' + j.template;
            } else {
                meta = labels[j.status] || '';
            }
            j.$li.find('.cmc-bulk-job__meta').text(meta);
        }

        function updateProgress(jobs) {
            var done = jobs.filter(function (j) {
                return j.status === 'ok' || j.status === 'error' || j.status === 'cancelled';
            }).length;
            var pct = jobs.length ? Math.round((done / jobs.length) * 100) : 0;
            $modal.find('.cmc-bulk-modal__progress-text').text(done + '/' + jobs.length);
            $modal.find('.cmc-bulk-modal__progress span').css('width', pct + '%');
            $modal.find('.cmc-bulk-modal__progress').attr('aria-valuenow', String(pct));
        }

        function applySuccessToRow(job, data) {
            var $tr = job.$tr;
            $tr.addClass('is-just-cloned');
            $tr.find('.cmc-bulk-status-text').text('✓ ' + job.duration + 's').removeClass('is-err').addClass('is-ok');
            $tr.find('.cmc-bulk-chk').prop('checked', false);

            var templateSlug = (data && data.template_slug) ? data.template_slug : job.template;
            var $badges = $tr.find('.cmc-bulk-badge.is-cloned');
            if ($badges.length) {
                $badges.first().text('cloned · ' + templateSlug);
            } else {
                $tr.find('.cmc-bulk-page-title').after(
                    ' <span class="cmc-bulk-badge is-cloned">cloned · ' + escapeHtml(templateSlug) + '</span>'
                );
            }
            $tr.addClass('is-cloned-row');

            // Keep the single-page <select> option in sync so the existing
            // workflow knows this page is now cloned too.
            var $opt = $wrap.find('#cmc-page-select option[value="' + job.pageId + '"]');
            if ($opt.length) {
                $opt.attr('data-cloned', '1').attr('data-template', templateSlug);
            }

            refreshControls();
        }

        function applyErrorToRow(job) {
            var $tr = job.$tr;
            $tr.removeClass('is-just-cloned');
            var short = (job.error || 'Error').replace(/\s+/g, ' ').substring(0, 60);
            $tr.find('.cmc-bulk-status-text').text('✗ ' + short).removeClass('is-ok').addClass('is-err');
        }

        refreshControls();
    }

    /* ----------------------- helpers ----------------------- */

    function post(action, data) {
        var body = $.extend({ action: action, nonce: CMCCloner.nonce }, data || {});
        return $.post(CMCCloner.ajaxUrl, body);
    }

    function busy($btn, on, busyText, withSpinner) {
        if (!$btn || !$btn.length) { return; }
        if (on) {
            if (typeof $btn.data('cmc-orig-html') === 'undefined') {
                $btn.data('cmc-orig-html', $btn.html());
            }
            var spinner = withSpinner ? '<span class="cmc-spinner" aria-hidden="true"></span>' : '';
            if (busyText) {
                $btn.html(spinner + escapeHtml(busyText));
            } else if (withSpinner) {
                $btn.html(spinner + $btn.data('cmc-orig-html'));
            }
            $btn.prop('disabled', true);
        } else {
            var orig = $btn.data('cmc-orig-html');
            if (typeof orig !== 'undefined') { $btn.html(orig); }
            $btn.prop('disabled', false);
        }
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function setInlineMsg($nearBtn, text, state) {
        var $msg = $nearBtn.closest('.cmc-actions').find('.cmc-inline-msg');
        if (!$msg.length) { $msg = $nearBtn.siblings('.cmc-inline-msg'); }
        $msg.removeClass('is-loading is-success is-error').text('');
        if (state) { $msg.addClass('is-' + state); }
        $msg.text(text || '');
    }

    function extractError(res, fallback) {
        if (res && res.data) {
            if (typeof res.data === 'string') { return res.data; }
            if (res.data.message) { return res.data.message; }
            if (res.data.error)   { return res.data.error; }
        }
        return fallback || 'Unknown error';
    }

    function xhrError(xhr) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            return xhr.responseJSON.data.message;
        }
        return 'HTTP ' + (xhr ? xhr.status : '?');
    }

    /* ----------------------- Key pre-flight ----------------------- */

    function hasKeyForActiveProvider() {
        if (typeof CMCCloner === 'undefined' || !CMCCloner.hasApiKey) { return true; }
        var p = CMCCloner.provider || 'openai';
        return !!CMCCloner.hasApiKey[p];
    }

    function warnMissingKey() {
        var label = (CMCCloner && CMCCloner.providerLabel) || 'the current provider';
        var url = (CMCCloner && CMCCloner.settingsUrl) || '#';
        var msg = 'No API key configured for ' + escapeHtml(label) +
                  '. <a href="' + url + '">Open Settings</a> to add one.';
        toast.warning(msg, { html: true, timeout: 8000 });
    }

    function initImageRename() {
        var $card = $('.cmc-image-rename-card');
        if (!$card.length || typeof CMCCloner === 'undefined') { return; }

        var $cat      = $card.find('#cmc-img-rename-cat');
        var $subcats  = $card.find('#cmc-img-rename-subcats');
        var $scanBtn  = $card.find('.cmc-btn-img-scan');
        var $runBtn   = $card.find('.cmc-btn-img-rename');
        var $revertBtn = $card.find('.cmc-btn-img-revert');
        var $msg      = $card.find('.cmc-img-rename-msg');
        var $progress = $card.find('.cmc-img-rename-progress');
        var $progressBar = $progress.find('.cmc-img-rename-progress__bar span');
        var $progressMeta = $progress.find('.cmc-img-rename-progress__meta');
        var $results  = $card.find('.cmc-img-rename-results');

        var scanState = null;

        function setMsg(text, type) {
            $msg.text(text || '').removeClass('is-error is-ok');
            if (type === 'error') { $msg.addClass('is-error'); }
            if (type === 'ok')    { $msg.addClass('is-ok'); }
        }

        function resetUi() {
            $runBtn.prop('hidden', true).prop('disabled', true);
            $revertBtn.prop('hidden', true).prop('disabled', false);
            $progress.prop('hidden', true);
            $progressBar.css('width', '0%');
            $progressMeta.text('');
            $results.empty();
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
            });
        }

        function renderPreviewTable(products) {
            if (!products.length) {
                $results.html('<p class="description">No products in this category.</p>');
                return;
            }
            var $table = $(
                '<table class="widefat cmc-img-rename-table">' +
                    '<thead><tr>' +
                        '<th class="cmc-col-pick"><input type="checkbox" class="cmc-img-pick-all" checked></th>' +
                        '<th>Product</th><th>Slug</th><th>Eligible</th>' +
                        '<th>Already renamed</th><th>Shared</th><th>Missing on disk</th>' +
                        '<th class="cmc-col-action"></th>' +
                    '</tr></thead>' +
                    '<tbody></tbody>' +
                '</table>'
            );
            var $tb = $table.find('tbody');
            products.forEach(function (p) {
                var missing = (p.missing | 0);
                var missingCell = missing > 0
                    ? '<td class="cmc-col-missing is-warn">' + missing + '</td>'
                    : '<td class="cmc-col-missing">0</td>';
                $tb.append(
                    '<tr data-pid="' + p.id + '">' +
                        '<td class="cmc-col-pick"><input type="checkbox" class="cmc-img-pick" value="' + p.id + '" checked></td>' +
                        '<td><strong>' + escapeHtml(p.title) + '</strong> <span class="cmc-row-id">#' + p.id + '</span></td>' +
                        '<td><code>' + escapeHtml(p.slug) + '</code></td>' +
                        '<td class="cmc-col-eligible">' + p.images + '</td>' +
                        '<td>' + p.already + '</td>' +
                        '<td>' + p.shared + '</td>' +
                        missingCell +
                        '<td class="cmc-col-action"><button type="button" class="button button-small cmc-btn-img-rename-row">Rename</button></td>' +
                    '</tr>'
                );
            });
            $results.empty().append($table);
        }

        function selectedProducts() {
            if (!scanState || !scanState.products) { return []; }
            var picked = {};
            $results.find('.cmc-img-pick:checked').each(function () {
                picked[parseInt(this.value, 10)] = true;
            });
            return scanState.products.filter(function (p) { return picked[p.id]; });
        }

        // Master checkbox toggles every row.
        $results.on('change', '.cmc-img-pick-all', function () {
            $results.find('.cmc-img-pick').prop('checked', this.checked);
        });
        // Single-row Rename button: process just that product.
        $results.on('click', '.cmc-btn-img-rename-row', function () {
            if (!scanState) { return; }
            var pid = parseInt($(this).closest('tr').data('pid'), 10);
            if (!pid) { return; }
            var product = (scanState.products || []).filter(function (p) { return p.id === pid; })[0];
            if (!product) { return; }
            runQueue([ product ], CMCCloner.actions.imgRename, 'rename');
        });

        function markRow(pid, result, mode) {
            var $row = $results.find('tr[data-pid="' + pid + '"]');
            if (!$row.length) { return; }
            var $cell = $row.find('td.cmc-col-eligible');
            if (result && result.errors && result.errors.length) {
                $row.addClass('is-error');
                var errHtml = result.errors.map(function (e) {
                    return '<span class="cmc-error-detail">' + escapeHtml(String(e)) + '</span>';
                }).join('');
                $cell.html('<strong>ERROR</strong>' + errHtml);
            } else {
                var label;
                if (mode === 'rename') {
                    label = 'Renamed ' + (result.renamed | 0);
                    if (result.skipped) { label += ', skipped ' + result.skipped; }
                } else {
                    label = 'Reverted ' + (result.reverted | 0);
                    if (result.skipped) { label += ', skipped ' + result.skipped; }
                }
                $row.addClass('is-ok');
                $cell.text(label);
            }
        }

        function runQueue(products, endpoint, mode, opts) {
            if (!products.length) { return; }
            opts = opts || {};
            resetProgress(products.length);
            $scanBtn.prop('disabled', true);
            $runBtn.prop('disabled', true);
            $revertBtn.prop('disabled', true);

            var i = 0;
            var totalRenamed = 0;
            var totalSynced = 0;
            var totalReverted = 0;
            var totalErrors = 0;

            function next() {
                if (i >= products.length) {
                    $scanBtn.prop('disabled', false);
                    var summary;
                    if (mode === 'rename') {
                        var parts = [];
                        if (totalRenamed > 0) { parts.push(totalRenamed + ' renamed'); }
                        if (totalSynced > 0)  { parts.push(totalSynced + ' synced (in-place ALT + URL sweep)'); }
                        if (parts.length === 0) { parts.push('0 changes — nothing to do'); }
                        summary = parts.join(', ') + ' across ' + products.length + ' product(s).';
                    } else {
                        summary = 'Reverted ' + totalReverted + ' image(s) across ' + products.length + ' product(s).';
                    }
                    if (totalErrors) { summary += ' ' + totalErrors + ' error(s).'; }
                    setMsg(summary, totalErrors ? 'error' : 'ok');
                    if (toast) { toast[totalErrors ? 'error' : 'success'](summary); }
                    if (mode === 'rename') {
                        // Keep Rename button visible — V0.9.2 supports
                        // override / re-run any time.
                        $revertBtn.prop('hidden', false).prop('disabled', false);
                    } else {
                        $revertBtn.prop('hidden', true);
                    }
                    // Auto-chain Repair image metadata: after a Rename
                    // pass, srcset URLs and `_wp_attachment_metadata`
                    // for the just-renamed images may need a regen
                    // (Woo POD imports leave them empty, and the rename
                    // touches `meta['file']` paths). Triggering Repair
                    // here saves the user one click on every clone day.
                    // Skipped on revert mode and on hard errors so the
                    // user can inspect failures before piling on
                    // another batch op. The Repair button remains
                    // visible for manual reruns.
                    if (mode === 'rename' && opts.chainRepair && !totalErrors) {
                        setTimeout(function () {
                            if ($repairBtn && $repairBtn.length && !$repairBtn.prop('disabled')) {
                                if (toast) { toast.info('Auto-running "Repair image metadata" for the same category…'); }
                                $repairBtn.trigger('click');
                            }
                        }, 600);
                    }
                    return;
                }

                var pid = products[i].id;
                advanceProgress(i + 1, products.length, products[i].title);

                $.post(CMCCloner.ajaxUrl, {
                    action:     endpoint,
                    nonce:      CMCCloner.nonce,
                    product_id: pid
                })
                .done(function (res) {
                    if (res && res.success) {
                        var data = res.data || {};
                        if (mode === 'rename') {
                            totalRenamed += (data.renamed | 0);
                            totalSynced  += (data.synced  | 0);
                        } else {
                            totalReverted += (data.reverted | 0);
                        }
                        if (data.errors && data.errors.length) { totalErrors += data.errors.length; }
                        markRow(pid, data, mode);
                    } else {
                        totalErrors++;
                        markRow(pid, { errors: [ extractError(res, 'Request failed') ] }, mode);
                    }
                })
                .fail(function () {
                    totalErrors++;
                    markRow(pid, { errors: [ 'Network error' ] }, mode);
                })
                .always(function () {
                    i++;
                    next();
                });
            }

            next();
        }

        function resetProgress(total) {
            $progress.prop('hidden', false);
            $progressBar.css('width', '0%');
            $progressMeta.text('0 / ' + total);
        }
        function advanceProgress(i, total, label) {
            var pct = Math.round((i / total) * 100);
            $progressBar.css('width', pct + '%');
            $progressMeta.text(i + ' / ' + total + (label ? ' — ' + label : ''));
        }

        $scanBtn.on('click', function () {
            var termId = parseInt($cat.val(), 10);
            if (!termId) {
                setMsg(CMCCloner.strings.imgPickCat, 'error');
                return;
            }
            setMsg('Scanning…');
            resetUi();
            $scanBtn.prop('disabled', true);

            $.post(CMCCloner.ajaxUrl, {
                action:          CMCCloner.actions.imgScan,
                nonce:           CMCCloner.nonce,
                term_id:         termId,
                include_subcats: $subcats.is(':checked') ? 1 : 0
            })
            .done(function (res) {
                if (!res || !res.success) {
                    setMsg(extractError(res, 'Scan failed.'), 'error');
                    return;
                }
                scanState = res.data;
                renderPreviewTable(scanState.products || []);
                var t = scanState.totals || {};
                if (!t.products) {
                    setMsg('No products in this category.', 'ok');
                    return;
                }
                var parts = [
                    t.products + ' product(s)',
                    t.images   + ' eligible image(s)',
                    t.already  + ' already renamed',
                    t.shared   + ' shared / skipped'
                ];
                if ((t.missing | 0) > 0) {
                    parts.push(t.missing + ' missing on disk');
                }
                var version = scanState.renamer_version || 'OLD (no version tag)';
                parts.push('renamer=' + version);
                setMsg(parts.join(' · '), (t.missing | 0) > 0 ? 'error' : 'ok');

                // Always show the Rename button after a successful scan, even
                // when every attachment is already renamed. Re-running the
                // batch on a fully-renamed category is now meaningful: the
                // backend (V0.9+) refreshes ALT text + post_title from the
                // current product title — useful when the user has rewritten
                // titles after a previous rename pass.
                $runBtn.prop('hidden', false).prop('disabled', false);
                if (t.already > 0) {
                    $revertBtn.prop('hidden', false);
                }
            })
            .fail(function () { setMsg('Network error during scan.', 'error'); })
            .always(function () { $scanBtn.prop('disabled', false); });
        });

        $runBtn.on('click', function () {
            if (!scanState) { return; }
            // Send only the products the user has CHECKED in the preview
            // table. This lets the user run rename on a single product at
            // a time during testing instead of paying for a full-category
            // pass on every click. The "select all" checkbox in the table
            // header restores the previous one-click batch behavior.
            var products = selectedProducts();
            if (!products.length) {
                setMsg('No products selected. Tick at least one row first.', 'error');
                return;
            }
            // No confirmation popup — clicking the button is the
            // confirmation. Pass `chainRepair: true` so the queue
            // auto-fires "Repair image metadata" on completion.
            runQueue(products, CMCCloner.actions.imgRename, 'rename', { chainRepair: true });
        });

        $revertBtn.on('click', function () {
            if (!scanState) { return; }
            var reverTargets = (scanState.products || []).filter(function (p) { return p.already > 0; });
            if (!reverTargets.length) {
                setMsg('Nothing to revert in this category.', 'error');
                return;
            }
            var msg = CMCCloner.strings.imgConfirmRevert.replace('%d', reverTargets.length);
            if (!window.confirm(msg)) { return; }
            runQueue(reverTargets, CMCCloner.actions.imgRevert, 'revert');
        });

        var $purgeBtn = $card.find('.cmc-btn-img-purge');
        $purgeBtn.on('click', function () {
            $purgeBtn.prop('disabled', true);
            setMsg('Purging caches...');
            $.post(CMCCloner.ajaxUrl, {
                action: CMCCloner.actions.imgPurge,
                nonce:  CMCCloner.nonce
            })
            .done(function (res) {
                if (res && res.success) {
                    var data = res.data || {};
                    setMsg(data.message || 'Caches purged.', 'ok');
                    if (toast) { toast.success('Caches purged. Hard-reload (Ctrl+Shift+R) to see fresh HTML.'); }
                } else {
                    setMsg(extractError(res, 'Purge failed.'), 'error');
                }
            })
            .fail(function () { setMsg('Network error during purge.', 'error'); })
            .always(function () { $purgeBtn.prop('disabled', false); });
        });

        var $repairBtn      = $card.find('.cmc-btn-img-meta-repair');
        var $repairMsg      = $card.find('.cmc-img-meta-repair-msg');
        var $repairProgress = $card.find('.cmc-img-meta-repair-progress');
        var $repairBar      = $repairProgress.find('.cmc-img-meta-repair-progress__bar span');
        var $repairBarRoot  = $repairProgress.find('.cmc-img-meta-repair-progress__bar');
        var $repairMeta     = $repairProgress.find('.cmc-img-meta-repair-progress__meta');
        // Smaller batch: each attachment now triggers a full
        // wp_generate_attachment_metadata() call (cropping every
        // registered intermediate size from disk), which can spike
        // PHP memory + CPU. 30 per AJAX request keeps each call under
        // the typical 30s LSAPI timeout even with 1500x1500 originals.
        var REPAIR_BATCH = 30;
        var repairRunning = false;

        // beforeunload guard — if the user tries to close the tab or
        // navigate away mid-repair, the browser shows the native
        // "Changes you made may not be saved" warning. The DB is in a
        // consistent state at any point because each attachment is
        // written atomically, but the cache-purge step at the end is
        // skipped if interrupted, leaving the storefront serving stale
        // HTML — better to nudge the user to wait it out.
        function repairBeforeUnload(e) {
            if (!repairRunning) { return; }
            e.preventDefault();
            e.returnValue = 'Image metadata repair is still running. Leaving now will skip the final cache purge — old image URLs may keep showing on the storefront.';
            return e.returnValue;
        }
        $(window).on('beforeunload', repairBeforeUnload);

        function setRepairMsg(text, type) {
            $repairMsg.text(text || '').removeClass('is-error is-ok');
            if (type === 'error') { $repairMsg.addClass('is-error'); }
            if (type === 'ok')    { $repairMsg.addClass('is-ok'); }
        }

        function summarizeRepair(totals) {
            var parts = [
                totals.checked + ' product image(s) ready to regenerate'
            ];
            if (totals.skipped) {
                parts.push(totals.skipped + ' skipped (file missing on disk)');
            }
            if (totals.samples && totals.samples.length) {
                var lines = totals.samples.map(function (s) {
                    var reason = s.reason ? ' [' + s.reason + ']' : '';
                    return '#' + s.aid + ': attached="' + s.attached + '"' + reason;
                });
                parts.push('e.g. ' + lines.join('; '));
            }
            return parts.join(' · ');
        }

        function runRepairBatches(apply, onDone) {
            var totals = { checked: 0, mismatched: 0, fixed: 0, incomplete: 0, regenerated: 0, skipped: 0, total: 0, caches_purged: false, samples: [] };
            var offset = 0;

            // Scope: read the same category dropdown the rename / scan
            // tools use. If a term is selected, the backend restricts
            // the brute-force regen to product images of products in
            // that term only (with sub-cats per the checkbox), making
            // the run an order of magnitude faster on stores that ship
            // 5000+ images across many niches.
            var termId  = parseInt($cat.val(), 10) || 0;
            var subcats = $subcats.is(':checked') ? 1 : 0;

            function step() {
                $.ajax({
                    url: CMCCloner.ajaxUrl,
                    type: 'POST',
                    timeout: 120000,
                    data: {
                        action:          CMCCloner.actions.imgMetaRepair,
                        nonce:           CMCCloner.nonce,
                        apply:           apply ? 1 : 0,
                        offset:          offset,
                        limit:           REPAIR_BATCH,
                        term_id:         termId,
                        include_subcats: subcats
                    }
                })
                .done(function (res) {
                    if (!res || !res.success) {
                        setRepairMsg(extractError(res, 'Request failed.'), 'error');
                        $repairProgress.prop('hidden', true);
                        $repairBtn.prop('disabled', false);
                        repairRunning = false;
                        return;
                    }
                    var d = res.data || {};
                    totals.checked     += (d.checked | 0);
                    totals.mismatched  += (d.mismatched | 0);
                    totals.fixed       += (d.fixed | 0);
                    totals.incomplete  += (d.incomplete | 0);
                    totals.regenerated += (d.regenerated | 0);
                    totals.skipped     += (d.skipped | 0);
                    totals.total        = (d.total | 0);
                    if (d.caches_purged) { totals.caches_purged = true; }
                    if (d.samples && d.samples.length) {
                        for (var i = 0; i < d.samples.length && totals.samples.length < 5; i++) {
                            totals.samples.push(d.samples[i]);
                        }
                    }
                    var nextOffset = (d.next_offset | 0);
                    var pct = totals.total
                        ? Math.min(100, Math.round((nextOffset / totals.total) * 100))
                        : 0;
                    // Animate the bar even on the scan pass so the
                    // user sees progress while the backend walks
                    // through every product image.
                    $repairBar.css('width', pct + '%');
                    $repairBarRoot.attr('aria-valuenow', pct);
                    $repairMeta.text(
                        nextOffset + ' / ' + totals.total +
                        (apply
                            ? ' · ' + totals.regenerated + ' regenerated, ' + totals.fixed + ' fallback patched, ' + totals.skipped + ' skipped'
                            : '')
                    );
                    setRepairMsg(
                        (apply ? 'Regenerating product images… ' : 'Counting product images… ') +
                        nextOffset + ' / ' + totals.total + ' (' + pct + '%)' +
                        (apply ? ' · ' + totals.regenerated + ' regenerated, ' + totals.fixed + ' fallback patched, ' + totals.skipped + ' skipped' : '')
                    );
                    if (d.done) {
                        onDone(totals);
                        return;
                    }
                    offset = nextOffset;
                    step();
                })
                .fail(function (xhr, status) {
                    var label = status === 'timeout' ? 'Request timed out' : 'Network error';
                    setRepairMsg(label + ' at offset ' + offset + ' — reduce REPAIR_BATCH and retry.', 'error');
                    $repairProgress.prop('hidden', true);
                    $repairBtn.prop('disabled', false);
                    repairRunning = false;
                });
            }

            step();
        }

        var $diagBtn    = $card.find('.cmc-btn-img-diagnose');
        var $diagInput  = $card.find('#cmc-img-diagnose-needle');
        var $diagOut    = $card.find('.cmc-img-diagnose-out');

        $diagBtn.on('click', function () {
            var needle = $.trim($diagInput.val() || '');
            if (!needle) {
                $diagOut.show().text('Enter an attachment ID, product slug, or filename fragment first.');
                return;
            }
            $diagBtn.prop('disabled', true);
            $diagOut.show().text('Diagnosing…');
            $.ajax({
                url: CMCCloner.ajaxUrl,
                type: 'POST',
                timeout: 30000,
                data: {
                    action: CMCCloner.actions.imgDiagnose,
                    nonce:  CMCCloner.nonce,
                    needle: needle
                }
            })
            .done(function (res) {
                if (!res || !res.success) {
                    $diagOut.text(extractError(res, 'Diagnose failed.'));
                    return;
                }
                $diagOut.text(JSON.stringify(res.data, null, 2));
            })
            .fail(function (xhr, status) {
                var msg;
                if (status === 'timeout') {
                    msg = 'Timed out (server took longer than 30s).';
                } else if (xhr && xhr.status === 0) {
                    msg = 'Network error — no response from server (CORS, offline, or blocked).';
                } else if (xhr && xhr.status) {
                    // Server replied with an HTTP error — surface the real reason.
                    var serverMsg = '';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        serverMsg = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        serverMsg = String(xhr.responseText).substring(0, 300);
                    }
                    msg = 'HTTP ' + xhr.status + (serverMsg ? ': ' + serverMsg : '');
                } else {
                    msg = 'Unknown error.';
                }
                $diagOut.text(msg);
            })
            .always(function () { $diagBtn.prop('disabled', false); });
        });

        $repairBtn.on('click', function () {
            $repairBtn.prop('disabled', true);
            repairRunning = true;
            var termId   = parseInt($cat.val(), 10) || 0;
            var catLabel = termId > 0 ? ($cat.find('option:selected').text().split(' (')[0]) : 'ALL categories';
            // Reveal + reset the progress bar — both scan and apply
            // passes drive it via runRepairBatches above.
            $repairProgress.prop('hidden', false);
            $repairBar.css('width', '0%');
            $repairBarRoot.attr('aria-valuenow', 0);
            $repairMeta.text('Initialising…');
            // Scan → apply runs back-to-back with no confirmation popup.
            // The category dropdown above is the only knob; clicking the
            // button is itself the confirmation.
            setRepairMsg('Counting product images in "' + catLabel + '"…');
            runRepairBatches(false, function (totals) {
                if (!totals.checked) {
                    setRepairMsg(summarizeRepair(totals) + ' — nothing to regenerate in "' + catLabel + '".', 'ok');
                    $repairProgress.prop('hidden', true);
                    $repairBtn.prop('disabled', false);
                    repairRunning = false;
                    return;
                }
                // Apply pass — reset bar to 0 since the scan pass
                // walked the same set and would have shown 100%.
                $repairBar.css('width', '0%');
                $repairBarRoot.attr('aria-valuenow', 0);
                $repairMeta.text('0 / ' + totals.total);
                setRepairMsg('Regenerating ' + totals.checked + ' product image(s) in "' + catLabel + '"…');
                runRepairBatches(true, function (applied) {
                    var totalWrites = (applied.fixed | 0) + (applied.regenerated | 0);
                    var cacheLine = applied.caches_purged
                        ? 'Page/object/image caches purged — hard-refresh the storefront (Ctrl+F5) to see the fix.'
                        : 'Cache purge skipped.';
                    setRepairMsg(
                        'Done in "' + catLabel + '": ' +
                        applied.regenerated + ' fully regenerated, ' +
                        applied.fixed + ' fallback patched (editor failed), ' +
                        applied.skipped + ' skipped (file missing). ' +
                        'Total writes: ' + totalWrites + '. ' +
                        cacheLine,
                        'ok'
                    );
                    // Pin the bar at 100% briefly, then hide.
                    $repairBar.css('width', '100%');
                    $repairBarRoot.attr('aria-valuenow', 100);
                    setTimeout(function () { $repairProgress.prop('hidden', true); }, 1200);
                    $repairBtn.prop('disabled', false);
                    repairRunning = false;
                });
            });
        });
    }

    /* ----------------------- Products eraser (Site Setup 2f) ----------------------- */

    function initProductsEraser() {
        var $card = $('.cmc-products-eraser-card');
        if (!$card.length || typeof CMCCloner === 'undefined') { return; }

        var $scanBtn     = $card.find('.cmc-btn-products-scan');
        var $counts      = $card.find('.cmc-products-eraser-counts');
        var $confirmRow  = $card.find('.cmc-products-eraser-confirm');
        var $confirmIn   = $card.find('#cmc-products-eraser-confirm-input');
        var $deleteBtn   = $card.find('.cmc-btn-products-delete');
        var $msg         = $card.find('.cmc-products-eraser-msg');
        var $progress    = $card.find('.cmc-products-eraser-progress');
        var $bar         = $progress.find('.cmc-products-eraser-progress__bar span');
        var $meta        = $progress.find('.cmc-products-eraser-progress__meta');
        var $results     = $card.find('.cmc-products-eraser-results');

        var startTotal = 0;
        var running    = false;

        var totals = { product: 0, variations: 0, attachments: 0, errors: [] };

        function setMsg(text, type) {
            $msg.text(text || '').removeClass('is-error is-ok');
            if (type === 'error') { $msg.addClass('is-error'); }
            if (type === 'ok')    { $msg.addClass('is-ok'); }
        }

        function renderScan(data) {
            var products    = (data.products | 0);
            var variations  = (data.variations | 0);
            var orphans     = (data.orphan_variations | 0);
            var attachments = (data.attachments | 0);

            // Show the orphan-variation count parenthetically when nonzero
            // so the user understands the second-phase wipe.
            var variationLabel = variations + ' variation(s)';
            if (orphans > 0) {
                variationLabel += ' (' + orphans + ' orphan)';
            }

            $counts.text([
                products    + ' product(s)',
                variationLabel,
                attachments + ' attached image(s)'
            ].join(' · '));

            // Anything deletable → enable the confirm row. Includes orphan
            // variations left over from a prior wipe.
            if (products > 0 || orphans > 0) {
                $confirmRow.prop('hidden', false);
            } else {
                $confirmRow.prop('hidden', true);
                if (variations > 0 || attachments > 0) {
                    // Variations exist but have living parent products
                    // somehow, OR attachments are referenced only by them.
                    // Either way: scan shows there is no orphan work for
                    // the eraser to do.
                    setMsg('Nothing to delete — no products or orphan variations found.', 'ok');
                } else {
                    setMsg('Nothing to delete — catalogue is clean.', 'ok');
                }
            }
        }

        $scanBtn.on('click', function () {
            if (running) { return; }
            setMsg('Scanning…');
            $counts.text('');
            $confirmRow.prop('hidden', true);
            $deleteBtn.prop('disabled', true);
            $confirmIn.val('');
            $progress.prop('hidden', true);
            $bar.css('width', '0%');
            $meta.text('');
            $results.empty();
            $scanBtn.prop('disabled', true);

            post(CMCCloner.actions.productsScan, {})
                .done(function (res) {
                    if (!res || !res.success) {
                        setMsg(extractError(res, 'Scan failed.'), 'error');
                        return;
                    }
                    setMsg('');
                    renderScan(res.data || {});
                })
                .fail(function () { setMsg('Network error during scan.', 'error'); })
                .always(function () { $scanBtn.prop('disabled', false); });
        });

        $confirmIn.on('input', function () {
            $deleteBtn.prop('disabled', $(this).val().trim() !== 'OK');
        });

        $deleteBtn.on('click', function () {
            if (running) { return; }
            if ($confirmIn.val().trim() !== 'OK') { return; }
            if (!window.confirm('Xoá toàn bộ sản phẩm + variations + media gắn trực tiếp? Thao tác KHÔNG thể hoàn tác.')) {
                return;
            }
            startBatchLoop();
        });

        function startBatchLoop() {
            running = true;
            totals = { product: 0, variations: 0, attachments: 0, errors: [] };

            post(CMCCloner.actions.productsScan, {})
                .done(function (res) {
                    if (!res || !res.success) {
                        setMsg(extractError(res, 'Scan failed.'), 'error');
                        running = false;
                        return;
                    }
                    // Total deletable = products + orphan variations.
                    // The eraser drains products first, then sweeps orphan
                    // variations (post_type=product_variation whose parent
                    // is gone OR no longer a product), so the start total
                    // must include both — otherwise an orphan-only run
                    // exits with "Nothing to delete." before any work runs.
                    var prodCount  = (res.data && res.data.products) | 0;
                    var orphanCount = (res.data && res.data.orphan_variations) | 0;
                    startTotal = prodCount + orphanCount;
                    if (startTotal === 0) {
                        setMsg('Nothing to delete.', 'ok');
                        running = false;
                        return;
                    }
                    $scanBtn.prop('disabled', true);
                    $deleteBtn.prop('disabled', true);
                    $confirmIn.prop('disabled', true);
                    $progress.prop('hidden', false);
                    $bar.css('width', '0%');
                    $meta.text('0 / ' + startTotal + ' record(s)');
                    setMsg('Deleting…');
                    runNextBatch();
                })
                .fail(function () {
                    setMsg('Network error before delete.', 'error');
                    running = false;
                });
        }

        function runNextBatch() {
            post(CMCCloner.actions.productsDeleteBatch, {
                confirm:    'OK',
                batch_size: 20
            })
                .done(function (res) {
                    if (!res || !res.success) {
                        setMsg(extractError(res, 'Delete failed.'), 'error');
                        finish(false);
                        return;
                    }
                    var b = (res.data && res.data.batch) || {};
                    totals.product     += (b.product | 0);
                    totals.variations  += (b.variations | 0);
                    totals.attachments += (b.attachments | 0);
                    if (b.errors && b.errors.length) {
                        totals.errors = totals.errors.concat(b.errors);
                    }

                    var remaining = (res.data && res.data.remaining) | 0;
                    var processed = Math.max(0, startTotal - remaining);
                    var pct = startTotal > 0 ? Math.round((processed / startTotal) * 100) : 100;
                    $bar.css('width', pct + '%');
                    $meta.text(processed + ' / ' + startTotal + ' record(s)');

                    if (res.data && res.data.done) {
                        finish(true);
                        return;
                    }
                    if (res.data && res.data.deleted_in_batch === 0) {
                        // Defensive: avoid infinite loop if nothing was consumed.
                        finish(true);
                        return;
                    }
                    runNextBatch();
                })
                .fail(function () {
                    setMsg('Network error during delete.', 'error');
                    finish(false);
                });
        }

        function finish(success) {
            running = false;
            $scanBtn.prop('disabled', false);
            $confirmIn.prop('disabled', false).val('');
            $deleteBtn.prop('disabled', true);
            $confirmRow.prop('hidden', true);

            var summary = 'Xoá xong: '
                + totals.product     + ' product(s), '
                + totals.variations  + ' variation(s), '
                + totals.attachments + ' attachment(s).';
            if (totals.errors.length) {
                summary += ' ' + totals.errors.length + ' error(s).';
            }
            setMsg(summary, totals.errors.length ? 'error' : 'ok');
            if (toast) {
                toast[totals.errors.length ? 'error' : 'success'](summary);
            }
            if (totals.errors.length) {
                var html = totals.errors.slice(0, 20).map(function (e) {
                    return '<li>' + escapeHtml(String(e)) + '</li>';
                }).join('');
                $results.html('<ul class="cmc-products-eraser-errors">' + html + '</ul>');
            }
            $counts.text('');
        }
    }

    /* ----------------------- SKU Normalizer ----------------------- */

    function initSkuNormalizer() {
        var $card = $('.cmc-sku-normalizer-card');
        if (!$card.length || typeof CMCCloner === 'undefined') { return; }

        var $applyBtn  = $card.find('.cmc-btn-sku-apply');
        var $revertBtn = $card.find('.cmc-btn-sku-revert');
        var $counts    = $card.find('.cmc-sku-normalizer-counts');
        var $msg       = $card.find('.cmc-sku-normalizer-msg');
        var $progress  = $card.find('.cmc-sku-normalizer-progress');
        var $bar       = $progress.find('.cmc-sku-normalizer-progress__bar span');
        var $meta      = $progress.find('.cmc-sku-normalizer-progress__meta');
        var $results   = $card.find('.cmc-sku-normalizer-results');

        var running   = false;
        var mode      = null; // 'apply' | 'revert'
        var startTotal = 0;
        var totals    = { items: 0, errors: [] };

        function setMsg(text, type) {
            $msg.text(text || '').removeClass('is-error is-ok');
            if (type === 'error') { $msg.addClass('is-error'); }
            if (type === 'ok')    { $msg.addClass('is-ok'); }
        }

        // Normalize SKUs — single-click run with no confirmation popup
        // and no preview list. The internal scan call still happens to
        // derive the total for the progress bar but its result is
        // consumed only as a count.
        $applyBtn.on('click', function () {
            if (running) { return; }
            mode = 'apply';
            startLoop();
        });

        $revertBtn.on('click', function () {
            if (running) { return; }
            if (!window.confirm('Restore original SKUs for every product that still has a saved original?')) {
                return;
            }
            mode = 'revert';
            startLoop();
        });

        function startLoop() {
            running = true;
            totals  = { items: 0, errors: [] };
            $applyBtn.prop('disabled', true);
            $revertBtn.prop('disabled', true);
            $progress.prop('hidden', false);
            $bar.css('width', '0%');
            $meta.text('');
            $counts.text('');
            $results.empty();

            if (mode === 'apply') {
                setMsg('Counting eligible SKUs…');
                post(CMCCloner.actions.skuScan, {})
                    .done(function (res) {
                        var d = (res && res.data) || {};
                        startTotal = (d.eligible_products | 0) + (d.eligible_variations | 0);
                        var parts = [
                            (d.eligible_products | 0) + ' product(s) with ASIN-style SKU',
                            (d.eligible_variations | 0) + ' variation(s)',
                            'prefix ' + (d.prefix || 'PRD')
                        ];
                        $counts.text(parts.join(' · '));
                        if (startTotal === 0) {
                            finish(true, 'No ASIN-style SKUs found — nothing to normalize.');
                            return;
                        }
                        setMsg('Normalizing…');
                        $meta.text('0 / ' + startTotal);
                        runBatch();
                    })
                    .fail(function () { finish(false, 'Network error before normalize.'); });
                return;
            }

            // revert: we don't know total upfront in one call, so kick off a
            // batch and derive total from the first remaining + processed.
            setMsg('Reverting…');
            startTotal = 0;
            runBatch();
        }

        function runBatch() {
            var action = (mode === 'apply')
                ? CMCCloner.actions.skuApplyBatch
                : CMCCloner.actions.skuRevertBatch;

            var batchSize = (mode === 'apply') ? 25 : 50;

            post(action, { batch_size: batchSize })
                .done(function (res) {
                    if (!res || !res.success) {
                        finish(false, extractError(res, 'Batch failed.'));
                        return;
                    }
                    var d = res.data || {};
                    var items = d.items || [];
                    totals.items += items.length;
                    if (d.errors && d.errors.length) {
                        totals.errors = totals.errors.concat(d.errors);
                    }

                    if (startTotal === 0) {
                        // first revert pass — derive total
                        startTotal = (d.remaining | 0) + items.length;
                        if (startTotal === 0) { startTotal = items.length; }
                    }

                    var remaining = (d.remaining | 0);
                    var processed = Math.max(0, startTotal - remaining);
                    var pct = startTotal > 0 ? Math.round((processed / startTotal) * 100) : 100;
                    $bar.css('width', pct + '%');
                    $meta.text(processed + ' / ' + startTotal);

                    if (d.done) {
                        finish(true);
                        return;
                    }
                    if (d.processed === 0) {
                        finish(true); // defensive: nothing consumed
                        return;
                    }
                    runBatch();
                })
                .fail(function () { finish(false, 'Network error during batch.'); });
        }

        function finish(success, maybeMsg) {
            running = false;
            // Both buttons stay clickable so the user can rerun /
            // revert without reloading the page.
            $applyBtn.prop('disabled', false);
            $revertBtn.prop('disabled', false);

            var label = (mode === 'apply') ? 'normalized' : 'reverted';
            var summary;
            if (maybeMsg && !success) {
                summary = maybeMsg;
            } else if (maybeMsg && success && totals.items === 0) {
                summary = maybeMsg;
            } else {
                summary = totals.items + ' SKU(s) ' + label
                    + (totals.errors.length ? (', ' + totals.errors.length + ' error(s)') : '');
            }
            setMsg(summary, (success && !totals.errors.length) ? 'ok' : 'error');

            if (toast) {
                toast[(success && !totals.errors.length) ? 'success' : 'error'](summary);
            }

            if (totals.errors.length) {
                var html = totals.errors.slice(0, 30).map(function (e) {
                    return '<li>' + escapeHtml(String(e)) + '</li>';
                }).join('');
                $results.html('<ul class="cmc-sku-normalizer-errors">' + html + '</ul>');
            }
        }
    }

    /* ----------------------- AI Title Rewriter ----------------------- */

    function initTitleRewriter() {
        var $card = $('.cmc-title-rewriter-card');
        if (!$card.length || typeof CMCCloner === 'undefined') { return; }
        if (!CMCCloner.actions || !CMCCloner.actions.titleRewriteScan) { return; }

        var $applyBtn  = $card.find('.cmc-btn-title-rewrite');
        var $revertBtn = $card.find('.cmc-btn-title-revert');
        var $counts    = $card.find('.cmc-title-rewriter-counts');
        var $msg       = $card.find('.cmc-title-rewriter-msg');
        var $progress  = $card.find('.cmc-title-rewriter-progress');
        var $bar       = $progress.find('.cmc-title-rewriter-progress__bar span');
        var $barRoot   = $progress.find('.cmc-title-rewriter-progress__bar');
        var $meta      = $progress.find('.cmc-title-rewriter-progress__meta');
        var $results   = $card.find('.cmc-title-rewriter-results');

        var running = false;
        var totals  = { processed: 0, succeeded: 0, failed: 0, skipped: 0 };
        var startTotal = 0;

        function setMsg(text, type) {
            $msg.text(text || '').removeClass('is-error is-ok');
            if (type === 'error') { $msg.addClass('is-error'); }
            if (type === 'ok')    { $msg.addClass('is-ok'); }
        }

        function setBeforeUnloadGuard(on) {
            if (on) {
                $(window).on('beforeunload.cmcTitle', function (e) {
                    e.preventDefault();
                    e.returnValue = 'Title rewrite is still running — leaving now will stop the AI loop mid-batch and burn credits without finishing. Continue?';
                    return e.returnValue;
                });
            } else {
                $(window).off('beforeunload.cmcTitle');
            }
        }

        function appendSamples(samples) {
            if (!samples || !samples.length) { return; }
            samples.forEach(function (s) {
                var html;
                if (s.error) {
                    html =
                        '<div class="cmc-title-rewriter-sample is-error">' +
                        '<div class="cmc-title-rewriter-sample__before">#' + (s.id | 0) + ': ' + escapeHtml(s.before || '(empty)') + '</div>' +
                        '<div class="cmc-title-rewriter-sample__error">⚠ ' + escapeHtml(s.error) + '</div>' +
                        '</div>';
                } else {
                    // desc_status: "rewritten" (AI gave a clean description) or
                    // "placeholder" (AI output failed validation — a safe
                    // generic placeholder was saved). The original supplier
                    // description is NEVER kept anymore.
                    var descTag = '';
                    if (s.desc_status === 'rewritten') {
                        descTag = ' <span class="cmc-title-rewriter-sample__desc">+ description rewritten</span>';
                    } else if (s.desc_status === 'placeholder') {
                        descTag = ' <span class="cmc-title-rewriter-sample__desc cmc-title-rewriter-sample__desc--kept">desc set to placeholder</span>';
                    }
                    html =
                        '<div class="cmc-title-rewriter-sample">' +
                        '<div class="cmc-title-rewriter-sample__before">#' + (s.id | 0) + ': ' + escapeHtml(s.before || '') + '</div>' +
                        '<div class="cmc-title-rewriter-sample__after">→ ' + escapeHtml(s.after || '') + descTag + '</div>' +
                        '</div>';
                }
                $results.append(html);
                // Auto-scroll to the latest sample so the user sees
                // progress without manually scrolling.
                $results.scrollTop($results[0].scrollWidth + $results[0].scrollHeight);
            });
        }

        $applyBtn.on('click', function () {
            if (running) { return; }
            running = true;
            totals  = { processed: 0, succeeded: 0, failed: 0, skipped: 0 };
            $applyBtn.prop('disabled', true);
            $revertBtn.prop('disabled', true);
            $progress.prop('hidden', false);
            $bar.css('width', '0%');
            $barRoot.attr('aria-valuenow', 0);
            $meta.text('Counting eligible products…');
            $counts.text('');
            $results.empty();
            setMsg('Counting eligible products…');
            setBeforeUnloadGuard(true);

            // Scan first to get the denominator for the progress bar.
            $.post(CMCCloner.ajaxUrl, {
                action: CMCCloner.actions.titleRewriteScan,
                nonce:  CMCCloner.nonce
            })
            .done(function (res) {
                if (!res || !res.success) {
                    finish(false, extractError(res, 'Scan failed.'));
                    return;
                }
                var d = res.data || {};
                var total   = (d.total | 0);
                var pending = (d.pending | 0);
                var already = (d.already | 0);
                $counts.text(
                    total + ' products · ' +
                    pending + ' pending · ' +
                    already + ' already rewritten'
                );
                if (pending === 0) {
                    finish(true, 'No pending products to rewrite. Click Revert to start over.');
                    return;
                }
                startTotal = pending;
                $meta.text('0 / ' + startTotal);
                setMsg('Rewriting ' + startTotal + ' product title(s) via AI…');
                runBatch();
            })
            .fail(function () { finish(false, 'Network error during scan.'); });
        });

        function runBatch() {
            $.ajax({
                url: CMCCloner.ajaxUrl,
                type: 'POST',
                timeout: 90000,
                data: {
                    action:     CMCCloner.actions.titleRewriteBatch,
                    nonce:      CMCCloner.nonce,
                    batch_size: 5
                }
            })
            .done(function (res) {
                if (!res || !res.success) {
                    finish(false, extractError(res, 'Batch failed.'));
                    return;
                }
                var d = res.data || {};
                totals.processed += (d.processed | 0);
                totals.succeeded += (d.succeeded | 0);
                totals.failed    += (d.failed    | 0);
                totals.skipped   += (d.skipped   | 0);
                appendSamples(d.samples || []);

                var pct = startTotal > 0
                    ? Math.min(100, Math.round((totals.processed / startTotal) * 100))
                    : 0;
                $bar.css('width', pct + '%');
                $barRoot.attr('aria-valuenow', pct);
                $meta.text(
                    totals.processed + ' / ' + startTotal +
                    ' · ' + totals.succeeded + ' rewritten' +
                    (totals.failed  ? ', ' + totals.failed  + ' failed'  : '') +
                    (totals.skipped ? ', ' + totals.skipped + ' skipped' : '')
                );

                if (d.done || d.processed === 0) {
                    finish(true, null);
                    return;
                }
                runBatch();
            })
            .fail(function (xhr, status) {
                var label = status === 'timeout' ? 'AI request timed out' : 'Network error during batch';
                finish(false, label + ' — partial progress saved. Click Rewrite to continue.');
            });
        }

        $revertBtn.on('click', function () {
            if (running) { return; }
            running = true;
            $applyBtn.prop('disabled', true);
            $revertBtn.prop('disabled', true);
            $progress.prop('hidden', false);
            $bar.css('width', '0%');
            $meta.text('Reverting…');
            $counts.text('');
            $results.empty();
            setMsg('Restoring original product titles…');
            setBeforeUnloadGuard(true);

            var totalReverted = 0;
            var firstRemaining = -1;

            function step() {
                $.post(CMCCloner.ajaxUrl, {
                    action:     CMCCloner.actions.titleRevertBatch,
                    nonce:      CMCCloner.nonce,
                    batch_size: 50
                })
                .done(function (res) {
                    if (!res || !res.success) {
                        finish(false, extractError(res, 'Revert failed.'));
                        return;
                    }
                    var d = res.data || {};
                    totalReverted += (d.reverted | 0);
                    if (firstRemaining < 0) {
                        firstRemaining = (d.remaining | 0) + (d.processed | 0);
                    }
                    var done = (d.remaining | 0);
                    var pct  = firstRemaining > 0
                        ? Math.min(100, Math.round(((firstRemaining - done) / firstRemaining) * 100))
                        : 100;
                    $bar.css('width', pct + '%');
                    $meta.text(totalReverted + ' restored · ' + done + ' remaining');
                    if (d.done || (d.processed | 0) === 0) {
                        finish(true, 'Restored ' + totalReverted + ' original title(s).');
                        return;
                    }
                    step();
                })
                .fail(function () { finish(false, 'Network error during revert.'); });
            }
            step();
        });

        function finish(success, msg) {
            running = false;
            setBeforeUnloadGuard(false);
            $applyBtn.prop('disabled', false);
            $revertBtn.prop('disabled', false);
            // Pin the bar at 100 % when we finished cleanly so the user
            // sees a satisfying "done" state, then hide after a beat.
            if (success) {
                $bar.css('width', '100%');
                $barRoot.attr('aria-valuenow', 100);
                setTimeout(function () { $progress.prop('hidden', true); }, 1200);
            }

            var summary = msg || (
                'Done — ' + totals.succeeded + ' rewritten, ' +
                totals.failed  + ' failed, ' +
                totals.skipped + ' skipped (out of ' + totals.processed + ' processed).'
            );
            setMsg(summary, success && !totals.failed ? 'ok' : 'error');
            if (toast) {
                toast[(success && !totals.failed) ? 'success' : 'error'](summary);
            }
        }
    }

    /* ----------------------- Review Seeder ----------------------- */

    function initReviewSeeder() {
        var $card = $('.cmc-review-seeder-card');
        if (!$card.length || typeof CMCCloner === 'undefined') { return; }

        var $scanBtn   = $card.find('.cmc-btn-review-scan');
        var $counts    = $card.find('.cmc-review-seeder-counts');
        var $filters   = $card.find('.cmc-review-seeder-filters');
        var $filter    = $card.find('.cmc-review-seeder-filter');
        var $hideExist = $card.find('.cmc-review-seeder-hide-existing');
        var $selectAll = $card.find('.cmc-review-seeder-select-all');
        var $list      = $card.find('.cmc-review-seeder-list');
        var $tbody     = $list.find('tbody');
        var $seedBtn   = $card.find('.cmc-btn-review-seed');
        var $polishBtn = $card.find('.cmc-btn-review-polish');
        var $removeBtn = $card.find('.cmc-btn-review-remove');
        var $msg       = $card.find('.cmc-review-seeder-msg');
        var $progress  = $card.find('.cmc-review-seeder-progress');
        var $bar       = $progress.find('.cmc-review-seeder-progress__bar span');
        var $meta      = $progress.find('.cmc-review-seeder-progress__meta');
        var $results   = $card.find('.cmc-review-seeder-results');

        var products    = [];
        var seededTotal = 0;
        var running     = false;

        function setMsg(text, type) {
            $msg.text(text || '').removeClass('is-error is-ok');
            if (type === 'error') { $msg.addClass('is-error'); }
            if (type === 'ok')    { $msg.addClass('is-ok'); }
        }

        function renderList(autoSelectN) {
            var q    = ($filter.val() || '').toString().toLowerCase().trim();
            var hide = $hideExist.is(':checked');
            var rows = products.filter(function (p) {
                if (hide && (p.review_count | 0) > 0) { return false; }
                if (!q) { return true; }
                return (p.title + ' ' + (p.sku || '')).toLowerCase().indexOf(q) !== -1;
            });
            if (!rows.length) {
                $tbody.html('<tr><td colspan="5"><em>No products match.</em></td></tr>');
                updateSeedButton();
                return;
            }
            // Pre-pick a deterministic set of rows when called with
            // autoSelectN > 0 (typically right after a Scan): we draw
            // up to N rows uniformly at random from the filtered list,
            // capped by what's available so a category with < N products
            // still works without errors. The user can untick / tick
            // freely afterwards — the auto-pick is just a starting
            // point so they don't have to click 5 checkboxes by hand.
            var preselected = {};
            if ((autoSelectN | 0) > 0) {
                var pool = rows.slice();
                var pick = Math.min(autoSelectN | 0, pool.length);
                for (var i = 0; i < pick; i++) {
                    var idx = Math.floor(Math.random() * pool.length);
                    var chosen = pool.splice(idx, 1)[0];
                    if (chosen) { preselected[chosen.id | 0] = true; }
                }
            }
            var html = rows.map(function (p) {
                var pid = p.id | 0;
                var ck  = preselected[pid] ? ' checked' : '';
                return '<tr>'
                    + '<td><input type="checkbox" class="cmc-review-seeder-pick" value="' + pid + '"' + ck + '></td>'
                    + '<td>' + escapeHtml(p.title || '(untitled)') + ' <span class="cmc-setup-meta">#' + pid + '</span></td>'
                    + '<td><code>' + escapeHtml(p.sku || '') + '</code></td>'
                    + '<td>' + (p.review_count | 0) + '</td>'
                    + '<td>' + escapeHtml(p.status || '') + '</td>'
                    + '</tr>';
            }).join('');
            $tbody.html(html);
            $selectAll.prop('checked', false);
            updateSeedButton();
        }

        function selectedIds() {
            return $tbody.find('.cmc-review-seeder-pick:checked').toArray().map(function (el) {
                return (el.value | 0);
            });
        }

        function updateSeedButton() {
            $seedBtn.prop('disabled', running || selectedIds().length === 0);
        }

        $scanBtn.on('click', function () {
            if (running) { return; }
            setMsg('Scanning products…');
            $scanBtn.prop('disabled', true);
            $results.empty();
            $progress.prop('hidden', true);
            $bar.css('width', '0%');
            $meta.text('');

            post(CMCCloner.actions.reviewScan, {})
                .done(function (res) {
                    if (!res || !res.success) {
                        setMsg(extractError(res, 'Scan failed.'), 'error');
                        return;
                    }
                    var d = res.data || {};
                    products    = d.products || [];
                    seededTotal = d.seeded_total | 0;
                    $counts.text(
                        (d.total_products | 0) + ' products · '
                        + (d.products_with_reviews | 0) + ' already have reviews · '
                        + seededTotal + ' seeded review(s) on file'
                    );
                    $filters.prop('hidden', false);
                    $list.prop('hidden', false);
                    // Auto-pick up to 5 random products on the first
                    // scan so the user can hit "Seed reviews" without
                    // ticking checkboxes by hand. They can adjust the
                    // selection before clicking — this is a starting
                    // point, not a constraint.
                    renderList(5);
                    $removeBtn.prop('hidden', seededTotal === 0);
                    $polishBtn.prop('hidden', seededTotal === 0);
                    setMsg('');
                })
                .fail(function () { setMsg('Network error during scan.', 'error'); })
                .always(function () { $scanBtn.prop('disabled', false); });
        });

        $filter.on('input', renderList);
        $hideExist.on('change', renderList);
        $tbody.on('change', '.cmc-review-seeder-pick', updateSeedButton);
        $selectAll.on('change', function () {
            $tbody.find('.cmc-review-seeder-pick').prop('checked', $(this).is(':checked'));
            updateSeedButton();
        });

        $seedBtn.on('click', function () {
            if (running) { return; }
            var ids = selectedIds();
            if (!ids.length) { return; }
            var include = !$hideExist.is(':checked');

            // No confirmation popup — clicking is the confirmation.
            running = true;
            $seedBtn.prop('disabled', true);
            $scanBtn.prop('disabled', true);
            setMsg('Seeding ' + ids.length + ' product(s)…');
            $progress.prop('hidden', false);
            $bar.css('width', '0%');
            $meta.text('Starting · 0 / ' + ids.length);

            // The reviewSeed endpoint is single-shot (it processes all
            // selected IDs server-side and returns one summary), so we
            // can't reflect per-item progress. Instead, animate the bar
            // up to 90% during the request to show liveness, and snap
            // it to 100% on done — combined with the striped animation
            // in CSS it's a clear "still working" cue.
            var fakeProgress = 8;
            var interval = setInterval(function () {
                if (!running) { return; }
                if (fakeProgress < 90) {
                    fakeProgress += 2 + Math.random() * 4;
                    if (fakeProgress > 90) { fakeProgress = 90; }
                    $bar.css('width', fakeProgress + '%');
                    $meta.text('Writing reviews… ' + Math.round(fakeProgress) + '% (server is processing ' + ids.length + ' product(s))');
                }
            }, 350);

            post(CMCCloner.actions.reviewSeed, {
                'product_ids[]':    ids,
                include_existing:   include ? 1 : 0
            })
                .done(function (res) {
                    clearInterval(interval);
                    if (!res || !res.success) {
                        $bar.css('width', '0%');
                        finish(false, extractError(res, 'Seed failed.'));
                        return;
                    }
                    var d = res.data || {};
                    $bar.css('width', '100%');
                    $meta.text('Done · ' + (d.seeded | 0) + ' review(s) seeded');
                    var summary = 'Seeded ' + (d.seeded | 0) + ' review(s)'
                        + (d.skipped ? ', skipped ' + d.skipped + ' product(s) that already had reviews' : '')
                        + (d.errors && d.errors.length ? ', ' + d.errors.length + ' error(s)' : '') + '.';
                    finish(true, summary);
                    seededTotal += (d.seeded | 0);
                    $polishBtn.prop('hidden', seededTotal === 0);
                    $removeBtn.prop('hidden', seededTotal === 0);
                    if (d.errors && d.errors.length) {
                        var html = d.errors.slice(0, 20).map(function (e) {
                            return '<li>' + escapeHtml(String(e)) + '</li>';
                        }).join('');
                        $results.html('<ul class="cmc-review-seeder-errors">' + html + '</ul>');
                    }
                    // refresh the list so the review_count column updates
                    $scanBtn.trigger('click');
                })
                .fail(function () {
                    clearInterval(interval);
                    $bar.css('width', '0%');
                    finish(false, 'Network error during seed.');
                });
        });

        $polishBtn.on('click', function () {
            if (running) { return; }
            if (!window.confirm('Rewrite every seeded review with the configured AI provider? This will make each review unique across cloned sites but uses AI credits.')) {
                return;
            }
            running = true;
            $scanBtn.prop('disabled', true);
            $seedBtn.prop('disabled', true);
            $polishBtn.prop('disabled', true);
            $removeBtn.prop('disabled', true);
            setMsg('Fetching queue…');

            post(CMCCloner.actions.reviewAiPolishOne, {})
                .done(function (res) {
                    if (!res || !res.success) {
                        finish(false, extractError(res, 'Queue fetch failed.'));
                        return;
                    }
                    var queue = (res.data && res.data.queue) || [];
                    if (!queue.length) {
                        finish(true, 'No seeded reviews to polish.');
                        return;
                    }
                    $progress.prop('hidden', false);
                    polishNext(queue, 0, { ok: 0, fail: 0, errors: [] });
                })
                .fail(function () { finish(false, 'Network error.'); });
        });

        function polishNext(queue, index, stats) {
            if (index >= queue.length) {
                var summary = 'Polished ' + stats.ok + ' / ' + queue.length + ' review(s)'
                    + (stats.fail ? ', ' + stats.fail + ' failure(s)' : '') + '.';
                finish(true, summary);
                if (stats.errors.length) {
                    var html = stats.errors.slice(0, 20).map(function (e) {
                        return '<li>' + escapeHtml(String(e)) + '</li>';
                    }).join('');
                    $results.html('<ul class="cmc-review-seeder-errors">' + html + '</ul>');
                }
                return;
            }
            var pct = Math.round((index / queue.length) * 100);
            $bar.css('width', pct + '%');
            $meta.text(index + ' / ' + queue.length + ' polished');
            setMsg('Polishing review #' + queue[index] + '…');

            post(CMCCloner.actions.reviewAiPolishOne, { comment_id: queue[index] })
                .done(function (res) {
                    if (res && res.success) {
                        stats.ok++;
                    } else {
                        stats.fail++;
                        stats.errors.push('#' + queue[index] + ': ' + extractError(res, 'failed'));
                    }
                    polishNext(queue, index + 1, stats);
                })
                .fail(function () {
                    stats.fail++;
                    stats.errors.push('#' + queue[index] + ': network error');
                    polishNext(queue, index + 1, stats);
                });
        }

        $removeBtn.on('click', function () {
            if (running) { return; }
            if (!window.confirm('Delete EVERY seeded review on this site? Real customer reviews are untouched. This cannot be undone.')) {
                return;
            }
            running = true;
            $scanBtn.prop('disabled', true);
            $seedBtn.prop('disabled', true);
            $polishBtn.prop('disabled', true);
            $removeBtn.prop('disabled', true);
            setMsg('Removing seeded reviews…');
            $progress.prop('hidden', false);
            $bar.css('width', '50%');

            post(CMCCloner.actions.reviewRemove, {})
                .done(function (res) {
                    if (!res || !res.success) {
                        finish(false, extractError(res, 'Remove failed.'));
                        return;
                    }
                    var d = res.data || {};
                    seededTotal = 0;
                    $bar.css('width', '100%');
                    finish(true, 'Deleted ' + (d.deleted | 0) + ' seeded review(s) across ' + ((d.products || []).length) + ' product(s).');
                    $polishBtn.prop('hidden', true);
                    $removeBtn.prop('hidden', true);
                    $scanBtn.trigger('click');
                })
                .fail(function () { finish(false, 'Network error during remove.'); });
        }); /* $removeBtn.on('click') */

        function finish(success, text) {
            running = false;
            $scanBtn.prop('disabled', false);
            $polishBtn.prop('disabled', false);
            $removeBtn.prop('disabled', false);
            updateSeedButton();
            setMsg(text || '', success ? 'ok' : 'error');
            if (toast) {
                toast[success ? 'success' : 'error'](text || (success ? 'Done.' : 'Failed.'));
            }
            if (success) {
                $bar.css('width', '100%');
            }
        }
    }

    function initCopyButtons() {
        $(document).on('click', '.cmc-copy-btn', function () {
            var $btn = $(this);
            var selector = $btn.data('target');
            var $target = $btn.closest('.cmc-code-wrap').find(selector);
            if (!$target.length) { $target = $(selector); }
            if (!$target.length) { return; }

            var text = $target.val() || $target.text() || '';
            copyText(text)
                .then(function () {
                    toast.success(CMCCloner.strings.copied, { timeout: 1800 });
                })
                .catch(function () {
                    toast.error(CMCCloner.strings.copyFailed);
                });
        });
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            try {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.top = '-1000px';
                document.body.appendChild(ta);
                ta.select();
                var ok = document.execCommand('copy');
                document.body.removeChild(ta);
                ok ? resolve() : reject();
            } catch (e) { reject(e); }
        });
    }

    /* ----------------------- Homepage prompt builder ----------------------- */

    /**
     * "Sinh prompt cho site này" on the Site Setup page. The server has
     * already substituted the Settings values into the template; we just
     * copy it from the hidden <script type="text/template"> source into
     * the visible textarea. A `data-missing` attribute flags any Setting
     * fields that weren't filled so the placeholder stays visible.
     */
    function initHomepagePrompt() {
        $(document).on('click', '.cmc-btn-prompt-generate', function () {
            var $btn    = $(this);
            var $target = $($btn.data('target'));
            var $source = $($btn.data('source'));
            if (!$target.length || !$source.length) { return; }

            // Script-template content is exposed as textContent (browsers
            // do not HTML-decode inside <script> raw-text).
            var filled = $source[0].textContent || '';
            $target.val(filled).trigger('input');

            var missing = String($btn.data('missing') || '').trim();
            var $msg = $btn.closest('.cmc-setup-row').find('.cmc-prompt-generate-msg');
            if (missing) {
                $msg.text('Missing in Settings: ' + missing + '. Tokens left as-is.')
                    .removeClass('is-ok').addClass('is-warn');
            } else {
                $msg.text('Prompt built from current Settings.')
                    .removeClass('is-warn').addClass('is-ok');
                setTimeout(function () { $msg.text(''); }, 2500);
            }
        });
    }

    /* ----------------------- Combobox (grouped + search) ----------------------- */

    function initComboboxes() {
        $('[data-cmc-combobox]').each(function () {
            buildCombobox($(this));
        });

        $(document).on('click.cmcCombobox', function (e) {
            if (!$(e.target).closest('.cmc-combobox').length) {
                $('.cmc-combobox.is-open').each(function () {
                    closeCombobox($(this));
                });
            }
        });
    }

    function buildCombobox($wrap) {
        var $select = $wrap.find('select.cmc-combobox__select').first();
        if (!$select.length) { return; }

        var groups = [];
        $select.children().each(function () {
            if (this.tagName === 'OPTGROUP') {
                var items = [];
                $(this).children('option').each(function () {
                    items.push({ value: this.value, label: $(this).text() });
                });
                groups.push({ label: this.getAttribute('label') || '', items: items });
            } else if (this.tagName === 'OPTION') {
                groups.push({ label: '', items: [{ value: this.value, label: $(this).text() }] });
            }
        });

        var selectedValue = $select.val();
        var selectedLabel = $select.find('option:selected').text() || '';

        var $toggle = $(
            '<button type="button" class="cmc-combobox__toggle regular-text" aria-haspopup="listbox" aria-expanded="false">' +
                '<span class="cmc-combobox__toggle-label"></span>' +
                '<span class="cmc-combobox__caret" aria-hidden="true">▾</span>' +
            '</button>'
        );
        $toggle.find('.cmc-combobox__toggle-label').text(selectedLabel || '— Select an industry —');

        var $panel = $(
            '<div class="cmc-combobox__panel" role="dialog" hidden>' +
                '<input type="text" class="cmc-combobox__search" placeholder="Search industries…" aria-label="Search industries" autocomplete="off" />' +
                '<ul class="cmc-combobox__list" role="listbox"></ul>' +
                '<p class="cmc-combobox__empty" hidden>No match.</p>' +
            '</div>'
        );

        var $list = $panel.find('.cmc-combobox__list');
        var optionNodes = [];
        var groupNodes  = [];
        groups.forEach(function (g) {
            var groupIdx = -1;
            if (g.label) {
                var $gl = $('<li class="cmc-combobox__group-label" role="presentation"></li>').text(g.label);
                $list.append($gl);
                groupIdx = groupNodes.length;
                groupNodes.push({ $el: $gl, anyVisible: false });
            }
            g.items.forEach(function (it) {
                var $opt = $('<li class="cmc-combobox__option" role="option" tabindex="-1"></li>')
                    .attr('data-value', it.value)
                    .text(it.label);
                if (it.value === selectedValue) {
                    $opt.addClass('is-selected').attr('aria-selected', 'true');
                }
                $list.append($opt);
                optionNodes.push({
                    $el: $opt,
                    value: it.value,
                    haystack: (it.label + ' ' + it.value).toLowerCase(),
                    groupIdx: groupIdx
                });
            });
        });

        $select.addClass('screen-reader-text');
        $wrap.append($toggle).append($panel);

        var $search = $panel.find('.cmc-combobox__search');
        var $empty  = $panel.find('.cmc-combobox__empty');

        $toggle.on('click', function (e) {
            e.preventDefault();
            if ($wrap.hasClass('is-open')) {
                closeCombobox($wrap);
            } else {
                openCombobox($wrap, $toggle, $panel, $search, optionNodes, groupNodes, $empty);
            }
        });

        $search.on('input', function () {
            filterOptions($(this).val(), optionNodes, groupNodes, $empty);
        });

        $search.on('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var $first = $list.find('.cmc-combobox__option:visible').first();
                if ($first.length) { $first.focus(); }
            } else if (e.key === 'Escape') {
                closeCombobox($wrap);
                $toggle.focus();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                var $match = $list.find('.cmc-combobox__option:visible').first();
                if ($match.length) { pickOption($match, $wrap, $select, $toggle); }
            }
        });

        $list.on('click', '.cmc-combobox__option', function () {
            pickOption($(this), $wrap, $select, $toggle);
        });

        $list.on('keydown', '.cmc-combobox__option', function (e) {
            var $visible = $list.find('.cmc-combobox__option:visible');
            var idx      = $visible.index(this);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                $visible.eq(Math.min(idx + 1, $visible.length - 1)).focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (idx <= 0) { $search.focus(); }
                else { $visible.eq(idx - 1).focus(); }
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                pickOption($(this), $wrap, $select, $toggle);
            } else if (e.key === 'Escape') {
                closeCombobox($wrap);
                $toggle.focus();
            }
        });
    }

    function openCombobox($wrap, $toggle, $panel, $search, optionNodes, groupNodes, $empty) {
        $wrap.addClass('is-open');
        $toggle.attr('aria-expanded', 'true');
        $panel.prop('hidden', false);
        $search.val('').trigger('focus');
        filterOptions('', optionNodes, groupNodes, $empty);
    }

    function closeCombobox($wrap) {
        $wrap.removeClass('is-open');
        $wrap.find('.cmc-combobox__toggle').attr('aria-expanded', 'false');
        $wrap.find('.cmc-combobox__panel').prop('hidden', true);
    }

    function filterOptions(query, optionNodes, groupNodes, $empty) {
        var q   = String(query || '').trim().toLowerCase();
        var any = false;

        groupNodes.forEach(function (g) { g.anyVisible = false; });

        optionNodes.forEach(function (node) {
            var match = q === '' || node.haystack.indexOf(q) !== -1;
            node.$el.toggle(match);
            if (match) {
                any = true;
                if (node.groupIdx >= 0) {
                    groupNodes[node.groupIdx].anyVisible = true;
                }
            }
        });

        groupNodes.forEach(function (g) { g.$el.toggle(g.anyVisible); });

        $empty.prop('hidden', any);
    }

    function pickOption($opt, $wrap, $select, $toggle) {
        var value = $opt.attr('data-value');
        var label = $opt.text();
        $select.val(value).trigger('change');
        $wrap.find('.cmc-combobox__option.is-selected')
            .removeClass('is-selected')
            .removeAttr('aria-selected');
        $opt.addClass('is-selected').attr('aria-selected', 'true');
        $toggle.find('.cmc-combobox__toggle-label').text(label);
        closeCombobox($wrap);
        $toggle.focus();
    }

    /* ----------------------- Toasts ----------------------- */

    function initToasts() {
        var $host = $('<div class="cmc-toasts" role="region" aria-live="polite" aria-label="Notifications"></div>');
        $('body').append($host);

        function make(type, body, opts) {
            opts = opts || {};
            var timeout = typeof opts.timeout === 'number' ? opts.timeout : (type === 'error' ? 7000 : 4000);
            var html = opts.html ? body : escapeHtml(body);

            var $t = $(
                '<div class="cmc-toast is-' + type + '" role="status">' +
                    '<div class="cmc-toast__body">' + html + '</div>' +
                    '<button type="button" class="cmc-toast__close" aria-label="Dismiss">×</button>' +
                '</div>'
            );
            $host.append($t);
            requestAnimationFrame(function () { $t.addClass('is-visible'); });

            function dismiss() {
                $t.removeClass('is-visible');
                setTimeout(function () { $t.remove(); }, 180);
            }
            $t.find('.cmc-toast__close').on('click', dismiss);
            if (timeout > 0) { setTimeout(dismiss, timeout); }
            return dismiss;
        }

        return {
            info:    function (msg, opts) { return make('info',    msg, opts); },
            success: function (msg, opts) { return make('success', msg, opts); },
            warning: function (msg, opts) { return make('warning', msg, opts); },
            error:   function (msg, opts) { return make('error',   msg, opts); }
        };
    }

    /* ----------------------- Help tooltip ----------------------- */
    /**
     * One shared `<div class="cmc-help__bubble">` rides the body and
     * gets re-positioned + repopulated each time the user hovers a
     * `.cmc-help` trigger. Keeps the DOM clean (no per-trigger bubble)
     * and the positioning logic centralised.
     *
     * Triggers can be activated via mouse hover, keyboard focus
     * (tab to the button), or click. Escape closes the bubble.
     */
    function initHelpTooltips() {
        if ($('.cmc-help').length === 0) { return; }

        var $body   = $('body');
        var $bubble = $('<div class="cmc-help__bubble" role="tooltip" aria-hidden="true"></div>').appendTo($body);
        var hideTimer = null;
        var $current  = null;

        function position($trigger) {
            var off  = $trigger.offset();
            var trH  = $trigger.outerHeight();
            var bW   = $bubble.outerWidth();
            var bH   = $bubble.outerHeight();
            var winW = $(window).width();
            var winS = $(window).scrollLeft();

            var left = off.left + 18;
            if (left + bW + 12 > winS + winW) {
                left = Math.max(winS + 12, winS + winW - bW - 12);
            }
            var top = off.top + trH + 8;
            $bubble.css({ top: top, left: left });
        }

        function show($trigger) {
            clearTimeout(hideTimer);
            if ($current && $current[0] !== $trigger[0]) {
                $current.attr('aria-expanded', 'false');
            }
            $current = $trigger;
            $bubble.html($trigger.attr('data-cmc-help-html') || '');
            $bubble.attr('aria-hidden', 'false').addClass('is-shown');
            position($trigger);
            $trigger.attr('aria-expanded', 'true');
        }

        function hide() {
            $bubble.removeClass('is-shown').attr('aria-hidden', 'true');
            if ($current) {
                $current.attr('aria-expanded', 'false');
                $current = null;
            }
        }

        $body.on('mouseenter focus', '.cmc-help', function () { show($(this)); });
        $body.on('mouseleave',       '.cmc-help', function () {
            hideTimer = setTimeout(hide, 160);
        });
        $body.on('blur', '.cmc-help', function () {
            hideTimer = setTimeout(hide, 80);
        });
        $body.on('mouseenter', '.cmc-help__bubble', function () { clearTimeout(hideTimer); });
        $body.on('mouseleave', '.cmc-help__bubble', function () { hide(); });
        $body.on('click', '.cmc-help', function (e) {
            // Buttons inside <label> would otherwise toggle the
            // associated input — block that and treat click as a
            // mobile-friendly toggle of the bubble itself.
            e.preventDefault();
            e.stopPropagation();
            if ($(this).attr('aria-expanded') === 'true') { hide(); }
            else { show($(this)); }
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) { hide(); }
        });
        $(window).on('scroll resize', function () {
            if ($current) { position($current); }
        });
    }

    $(document).on('ready', initHelpTooltips);

    /* ----------------------- Run All Setup Tasks (orchestrator) ----------------------- */
    /**
     * Sequential orchestrator for the five product-setup tasks:
     *   1. Rename Category Name
     *   2. Rewrite Title & Description
     *   3. SKU Normalize
     *   4. Seed Product Reviews (auto-pick 5 random no-review products)
     *   5. Product Image Rename
     *
     * Reuses existing AJAX endpoints — this is a CLIENT-SIDE state
     * machine, not a new server-side bulk action. That means closing
     * the browser tab halts the loop, but every server-side mutation
     * already happened up to that point (postmeta markers, term
     * renames, etc.), so a re-run skips finished products by default.
     *
     * Error policy: continue-on-error. A step's failures are logged
     * to its timeline row; the orchestrator advances. Only network
     * errors (`.fail()`) halt the active step, never the whole run.
     */
    function initRunAll() {
        var $card = $('.cmc-run-all-card');
        if (!$card.length || typeof CMCCloner === 'undefined') { return; }

        var $startBtn   = $card.find('.cmc-btn-run-all');
        var $cancelBtn  = $card.find('.cmc-btn-run-all-cancel');
        var $summary    = $card.find('.cmc-run-all-summary');
        var $checkboxes = $card.find('.cmc-run-all-step');
        var $selectAll  = $card.find('.cmc-run-all-select-all');
        var $overall    = $card.find('.cmc-run-all-overall');
        var $bar        = $card.find('.cmc-run-all-overall__bar span');
        var $meta       = $card.find('.cmc-run-all-overall__meta');
        var $timeline   = $card.find('.cmc-run-all-timeline');

        var running   = false;
        var cancelled = false;
        var startedAt = 0;

        // Master toggle: clicking "Select All" flips every step checkbox.
        // Each individual checkbox keeps the master in sync via the
        // change handler below (checked when all are on, unchecked when
        // any are off, indeterminate when mixed).
        $selectAll.on('change', function () {
            $checkboxes.prop('checked', $selectAll.is(':checked'));
        });
        $checkboxes.on('change', function () {
            var total   = $checkboxes.length;
            var checked = $checkboxes.filter(':checked').length;
            $selectAll.prop('checked', checked === total);
            // Tri-state visual hint when some-but-not-all are picked.
            $selectAll.prop('indeterminate', checked > 0 && checked < total);
        }).trigger('change');

        function $row(stepId)  { return $timeline.find('li[data-step="' + stepId + '"]'); }
        function $icon(stepId) { return $row(stepId).find('.cmc-run-all-icon'); }
        function $log(stepId)  { return $row(stepId).find('.cmc-run-all-log'); }

        function setStepState(stepId, state, logText) {
            var $r = $row(stepId);
            $r.removeClass('is-pending is-running is-done is-failed is-skipped').addClass('is-' + state);
            var icons = { pending: '⭕', running: '🔄', done: '✅', failed: '❌', skipped: '⏭️' };
            $icon(stepId).text(icons[state] || '⭕');
            if (logText !== undefined) { $log(stepId).text(logText); }
        }
        function setStepLog(stepId, text) { $log(stepId).text(text); }

        function setOverall(stepIdx, totalSteps, currentLabel) {
            var pct = totalSteps > 0 ? Math.round((stepIdx / totalSteps) * 100) : 0;
            $bar.css('width', pct + '%');
            $meta.text('Step ' + Math.min(stepIdx + 1, totalSteps) + ' / ' + totalSteps + ' — ' + (currentLabel || ''));
        }

        function resetUI() {
            $checkboxes.each(function () {
                setStepState($(this).val(), 'pending', '');
            });
            $bar.css('width', '0%');
            $meta.text('');
            $overall.prop('hidden', true);
            $summary.text('').removeClass('is-ok is-error');
        }

        function selectedSteps() {
            // Preserve the timeline order (cat → title → sku → varnorm → review → image → regen → guidheal → subcats → sizeguide)
            var order = ['cat', 'title', 'sku', 'varnorm', 'review', 'image', 'regen', 'guidheal', 'subcats', 'sizeguide'];
            return order.filter(function (id) {
                return $checkboxes.filter('[value="' + id + '"]:checked').length > 0;
            });
        }

        function beforeUnloadHandler(e) {
            if (!running) { return; }
            e.preventDefault();
            e.returnValue = 'Run All is still in progress. Leaving now stops the loop — completed steps stay, but the rest will need to be triggered manually.';
            return e.returnValue;
        }

        function finish(success, summaryText) {
            running = false;
            $startBtn.prop('disabled', false).text('Start All');
            $cancelBtn.prop('hidden', true).prop('disabled', false);
            $checkboxes.prop('disabled', false);
            $selectAll.prop('disabled', false);
            $(window).off('beforeunload.cmcRunAll');
            $bar.css('width', '100%');
            var elapsed = startedAt ? Math.round((Date.now() - startedAt) / 1000) : 0;
            var elapsedTxt = elapsed > 60
                ? Math.floor(elapsed / 60) + 'm ' + (elapsed % 60) + 's'
                : elapsed + 's';
            var msg = (summaryText || '') + ' (took ' + elapsedTxt + ')';
            $summary.text(msg).addClass(success ? 'is-ok' : 'is-error');
            if (toast) {
                toast[success ? 'success' : 'error']('Run All — ' + msg);
            }
        }

        $cancelBtn.on('click', function () {
            if (!running) { return; }
            cancelled = true;
            $cancelBtn.prop('disabled', true).text('Cancelling…');
            $summary.text('Cancelling — finishing current step first…');
        });

        $startBtn.on('click', function () {
            if (running) { return; }
            var steps = selectedSteps();
            if (!steps.length) {
                $summary.text('Pick at least one task above.').addClass('is-error');
                return;
            }

            running   = true;
            cancelled = false;
            startedAt = Date.now();
            $startBtn.prop('disabled', true).text('Running…');
            $cancelBtn.prop('hidden', false).text('Cancel');
            $checkboxes.prop('disabled', true);
            $selectAll.prop('disabled', true);
            $(window).on('beforeunload.cmcRunAll', beforeUnloadHandler);

            resetUI();
            $overall.prop('hidden', false);
            // Mark un-selected steps as skipped up front so the
            // timeline reads correctly from the start.
            $checkboxes.each(function () {
                if (!$(this).is(':checked')) {
                    setStepState($(this).val(), 'skipped', 'skipped (unchecked)');
                }
            });

            runSequential(steps);
        });

        function runSequential(steps) {
            var idx = 0;
            function next() {
                if (cancelled) {
                    finish(false, 'Cancelled at step ' + (idx + 1) + ' / ' + steps.length);
                    return;
                }
                if (idx >= steps.length) {
                    finish(true, 'All ' + steps.length + ' selected steps complete');
                    return;
                }
                var stepId = steps[idx];
                var label  = stepLabel(stepId);
                setOverall(idx, steps.length, label);
                setStepState(stepId, 'running', 'starting…');

                runStep(stepId).then(function (result) {
                    setStepState(stepId, result.success ? 'done' : 'failed', result.summary);
                    idx++;
                    setTimeout(next, 250);
                }).catch(function (err) {
                    setStepState(stepId, 'failed', String(err && err.message ? err.message : err));
                    idx++;
                    setTimeout(next, 250);
                });
            }
            next();
        }

        function stepLabel(stepId) {
            return ({
                cat:       'Rename Category Name',
                title:     'Rewrite Title & Description',
                sku:       'SKU Normalize',
                varnorm:   'Normalize Variation Attributes',
                review:    'Seed Product Reviews',
                image:     'Product Image Rename',
                regen:     'Regenerate Image Thumbnails',
                guidheal:  'Heal Stale Attachment GUIDs',
                subcats:   'Build Sub-Categories',
                sizeguide: 'Sync Size Guide Page'
            })[stepId] || stepId;
        }

        function runStep(stepId) {
            if (stepId === 'cat')       { return runRenameCategory(); }
            if (stepId === 'title')     { return runTitleRewrite(stepId); }
            if (stepId === 'sku')       { return runSkuNormalize(stepId); }
            if (stepId === 'varnorm')   { return runVariationNormalize(stepId); }
            if (stepId === 'review')    { return runSeedReviews(stepId); }
            if (stepId === 'image')     { return runImageRename(stepId); }
            if (stepId === 'regen')     { return runImageRegen(stepId); }
            if (stepId === 'guidheal')  { return runHealGuids(stepId); }
            if (stepId === 'subcats')   { return runBuildSubcats(stepId); }
            if (stepId === 'sizeguide') { return runSizeGuide(stepId); }
            return Promise.resolve({ success: false, summary: 'unknown step' });
        }

        /* ---- Step 1: Rename Category ---- */
        function runRenameCategory() {
            return new Promise(function (resolve) {
                $.post(CMCCloner.ajaxUrl, {
                    action: CMCCloner.actions.runAllRenameCat,
                    nonce:  CMCCloner.nonce
                })
                .done(function (res) {
                    if (res && res.success && res.data) {
                        var d = res.data.data || {};
                        var msg = d.before
                            ? '"' + d.before + '" → "' + d.after + '" (' + (d.deleted | 0) + ' other category(ies) deleted)'
                            : (res.data.message || 'OK');
                        resolve({ success: true, summary: msg });
                    } else {
                        var errData = (res && res.data) || {};
                        var errMsg = errData.message || (res && res.data && res.data.message) || 'unknown';
                        resolve({ success: false, summary: errMsg });
                    }
                })
                .fail(function () { resolve({ success: false, summary: 'Network error' }); });
            });
        }

        /* ---- Step 2: Rewrite Title & Description ---- */
        function runTitleRewrite(stepId) {
            return new Promise(function (resolve) {
                // Scan first to get pending count.
                $.post(CMCCloner.ajaxUrl, {
                    action: CMCCloner.actions.titleRewriteScan,
                    nonce:  CMCCloner.nonce
                })
                .done(function (res) {
                    if (!res || !res.success) { resolve({ success: false, summary: 'scan failed' }); return; }
                    var d = res.data || {};
                    var total = (d.pending | 0);
                    if (total === 0) {
                        resolve({ success: true, summary: 'nothing to rewrite (' + (d.already | 0) + ' already done)' });
                        return;
                    }
                    var totals = { processed: 0, succeeded: 0, failed: 0, skipped: 0 };
                    function loop() {
                        if (cancelled) { resolve({ success: false, summary: 'cancelled mid-step' }); return; }
                        $.ajax({
                            url: CMCCloner.ajaxUrl, type: 'POST', timeout: 90000,
                            data: {
                                action: CMCCloner.actions.titleRewriteBatch,
                                nonce: CMCCloner.nonce, batch_size: 5
                            }
                        })
                        .done(function (br) {
                            if (!br || !br.success) { resolve({ success: false, summary: 'batch failed' }); return; }
                            var bd = br.data || {};
                            totals.processed += (bd.processed | 0);
                            totals.succeeded += (bd.succeeded | 0);
                            totals.failed    += (bd.failed | 0);
                            totals.skipped   += (bd.skipped | 0);
                            setStepLog(stepId, totals.processed + ' / ' + total + ' processed, ' + totals.succeeded + ' OK, ' + totals.failed + ' failed');
                            if (bd.done || (bd.processed | 0) === 0) {
                                resolve({
                                    success: totals.failed === 0,
                                    summary: totals.succeeded + ' / ' + total + ' rewritten' + (totals.failed ? ', ' + totals.failed + ' failed' : '')
                                });
                                return;
                            }
                            loop();
                        })
                        .fail(function () { resolve({ success: false, summary: 'network error mid-batch' }); });
                    }
                    loop();
                })
                .fail(function () { resolve({ success: false, summary: 'scan network error' }); });
            });
        }

        /* ---- Step 3: SKU Normalize ---- */
        function runSkuNormalize(stepId) {
            return new Promise(function (resolve) {
                $.post(CMCCloner.ajaxUrl, {
                    action: CMCCloner.actions.skuScan,
                    nonce:  CMCCloner.nonce
                })
                .done(function (res) {
                    if (!res || !res.success) { resolve({ success: false, summary: 'scan failed' }); return; }
                    var d = res.data || {};
                    var total = (d.eligible_products | 0) + (d.eligible_variations | 0);
                    if (total === 0) {
                        resolve({ success: true, summary: 'no ASIN-style SKUs found' });
                        return;
                    }
                    var totalItems = 0, totalErrors = 0;
                    function loop() {
                        if (cancelled) { resolve({ success: false, summary: 'cancelled mid-step' }); return; }
                        $.post(CMCCloner.ajaxUrl, {
                            action: CMCCloner.actions.skuApplyBatch,
                            nonce:  CMCCloner.nonce,
                            batch_size: 25
                        })
                        .done(function (br) {
                            if (!br || !br.success) { resolve({ success: false, summary: 'batch failed' }); return; }
                            var bd = br.data || {};
                            totalItems  += (bd.items ? bd.items.length : 0);
                            totalErrors += (bd.errors ? bd.errors.length : 0);
                            var remaining = (bd.remaining | 0);
                            setStepLog(stepId, totalItems + ' / ' + total + ' normalized, ' + remaining + ' remaining');
                            if (bd.done || (bd.processed | 0) === 0) {
                                resolve({
                                    success: totalErrors === 0,
                                    summary: totalItems + ' SKU(s) normalized' + (totalErrors ? ', ' + totalErrors + ' error(s)' : '')
                                });
                                return;
                            }
                            loop();
                        })
                        .fail(function () { resolve({ success: false, summary: 'network error mid-batch' }); });
                    }
                    loop();
                })
                .fail(function () { resolve({ success: false, summary: 'scan network error' }); });
            });
        }

        /* ---- Step 3b: Normalize Variation Attributes ----
         * Walks every product-attribute term (pa_color, pa_size,
         * pa_material, pa_capacity, plus generic pa_*) and rewrites
         * messy Amazon imports ("01black" → "Black", "Light Pink 1"
         * merged with "Light Pink", etc.). Paginated so a catalogue
         * with thousands of variations stays inside the LSAPI budget.
         */
        function runVariationNormalize(stepId) {
            var BATCH = 100;
            return new Promise(function (resolve) {
                var action = CMCCloner.actions && CMCCloner.actions.variationNormalizeBatch;
                if (!action) {
                    resolve({ success: false, summary: 'variationNormalizeBatch action missing — reload the page' });
                    return;
                }
                var totals = { processed: 0, renamed: 0, merged: 0, skipped: 0, total: 0, dedupe_deleted: 0 };
                var offset = 0;

                function step() {
                    if (cancelled) { resolve({ success: false, summary: 'cancelled mid-step' }); return; }
                    $.ajax({
                        url:     CMCCloner.ajaxUrl,
                        type:    'POST',
                        timeout: 180000,
                        data: {
                            action:     action,
                            nonce:      CMCCloner.nonce,
                            offset:     offset,
                            batch_size: BATCH
                        }
                    })
                    .done(function (res) {
                        if (!res || !res.success) {
                            resolve({ success: false, summary: 'batch failed at offset ' + offset });
                            return;
                        }
                        var d = res.data || {};
                        totals.processed += (d.processed | 0);
                        totals.renamed   += (d.renamed   | 0);
                        totals.merged    += (d.merged    | 0);
                        totals.skipped   += (d.skipped   | 0);
                        totals.total      = (d.total     | 0);
                        // dedupe_deleted only arrives on the final batch
                        if (d.dedupe_deleted) { totals.dedupe_deleted = (d.dedupe_deleted | 0); }

                        var nextOffset = (d.next_offset | 0);
                        var pct = totals.total ? Math.min(100, Math.round((nextOffset / totals.total) * 100)) : 100;
                        setStepLog(stepId, nextOffset + ' / ' + totals.total + ' (' + pct + '%) — ' + totals.renamed + ' renamed, ' + totals.merged + ' merged');

                        if (d.done) {
                            var summary = totals.renamed + ' renamed, ' + totals.merged + ' merged out of ' + totals.total + ' term(s)';
                            if (totals.dedupe_deleted > 0) {
                                summary += ' · ' + totals.dedupe_deleted + ' duplicate variation(s) cleaned up';
                            }
                            resolve({ success: true, summary: summary });
                            return;
                        }
                        offset = nextOffset;
                        step();
                    })
                    .fail(function (xhr, status) {
                        resolve({ success: false, summary: (status === 'timeout' ? 'timed out' : 'network error') + ' at offset ' + offset });
                    });
                }
                step();
            });
        }

        /* ---- Step 4: Seed Product Reviews (auto-pick 5 random no-review) ---- */
        function runSeedReviews(stepId) {
            return new Promise(function (resolve) {
                $.post(CMCCloner.ajaxUrl, {
                    action: CMCCloner.actions.reviewScan,
                    nonce:  CMCCloner.nonce
                })
                .done(function (res) {
                    if (!res || !res.success) { resolve({ success: false, summary: 'scan failed' }); return; }
                    var d = res.data || {};
                    var products = (d.products || []).filter(function (p) {
                        return (p.review_count | 0) === 0;
                    });
                    if (!products.length) {
                        resolve({ success: true, summary: 'no products without reviews — skipping' });
                        return;
                    }
                    // Random-pick up to 5.
                    var pool = products.slice();
                    var picked = [];
                    var n = Math.min(5, pool.length);
                    for (var i = 0; i < n; i++) {
                        var idx = Math.floor(Math.random() * pool.length);
                        picked.push(pool.splice(idx, 1)[0].id | 0);
                    }
                    setStepLog(stepId, 'seeding ' + picked.length + ' product(s)…');
                    $.ajax({
                        url: CMCCloner.ajaxUrl, type: 'POST', timeout: 60000,
                        data: {
                            action: CMCCloner.actions.reviewSeed,
                            nonce:  CMCCloner.nonce,
                            'product_ids[]':  picked,
                            include_existing: 0
                        }
                    })
                    .done(function (sr) {
                        if (!sr || !sr.success) { resolve({ success: false, summary: 'seed failed' }); return; }
                        var sd = sr.data || {};
                        resolve({
                            success: !((sd.errors || []).length),
                            summary: (sd.seeded | 0) + ' reviews seeded across ' + picked.length + ' product(s)'
                        });
                    })
                    .fail(function () { resolve({ success: false, summary: 'seed network error' }); });
                })
                .fail(function () { resolve({ success: false, summary: 'scan network error' }); });
            });
        }

        /* ---- Step 5: Product Image Rename ---- */
        function runImageRename(stepId) {
            return new Promise(function (resolve) {
                // Strategy: pull EVERY product_cat from the server, scan each
                // one, then dedupe products by ID before renaming. This avoids
                // the stale-dropdown problem after Rename Category Name has
                // run earlier in the Run-All pass, and also catches products
                // that live only in a non-top category.
                pickAllCategories(function (terms) {
                    if (!terms.length) {
                        resolve({ success: false, summary: 'no categories available' });
                        return;
                    }
                    scanAllTerms(terms, stepId, function (products) {
                        if (!products.length) {
                            resolve({ success: true, summary: 'no eligible images in any category' });
                            return;
                        }
                        renameProducts(products, stepId, resolve);
                    });
                });
            });

            // --- helpers ---

            function pickAllCategories(cb) {
                var pickAction = CMCCloner.actions && CMCCloner.actions.runAllPickCat;
                if (!pickAction) {
                    // Old localized actions object (page was loaded before this
                    // upgrade landed) — fall back to the dropdown so we still
                    // run on whatever terms the page knew about.
                    cb(termsFromDropdown());
                    return;
                }
                $.post(CMCCloner.ajaxUrl, { action: pickAction, nonce: CMCCloner.nonce })
                    .done(function (res) {
                        if (res && res.success && res.data && Array.isArray(res.data.terms) && res.data.terms.length) {
                            cb(res.data.terms.map(function (t) { return (t.id | 0); }).filter(Boolean));
                        } else {
                            cb(termsFromDropdown());
                        }
                    })
                    .fail(function () { cb(termsFromDropdown()); });
            }

            function termsFromDropdown() {
                var ids = [];
                $('#cmc-img-rename-cat option').each(function () {
                    var v = parseInt($(this).val(), 10);
                    if (v > 0) { ids.push(v); }
                });
                return ids;
            }

            function scanAllTerms(terms, stepId, cb) {
                var seen = {};
                var products = [];
                var idx = 0;
                function next() {
                    if (cancelled) { cb([]); return; }
                    if (idx >= terms.length) { cb(products); return; }
                    var termId = terms[idx++];
                    setStepLog(stepId, 'scanning category ' + idx + ' / ' + terms.length);
                    $.post(CMCCloner.ajaxUrl, {
                        action:          CMCCloner.actions.imgScan,
                        nonce:           CMCCloner.nonce,
                        term_id:         termId,
                        include_subcats: 1
                    })
                    .done(function (res) {
                        if (res && res.success && res.data && Array.isArray(res.data.products)) {
                            res.data.products.forEach(function (p) {
                                if (!p || seen[p.id]) { return; }
                                if ((p.images | 0) > 0 || (p.already | 0) > 0) {
                                    seen[p.id] = 1;
                                    products.push(p);
                                }
                            });
                        }
                    })
                    .always(next);
                }
                next();
            }

            function renameProducts(products, stepId, resolve) {
                var totalRenamed = 0, totalSynced = 0, totalErrors = 0;
                var i = 0;
                function loop() {
                    if (cancelled) { resolve({ success: false, summary: 'cancelled mid-step' }); return; }
                    if (i >= products.length) {
                        resolve({
                            success: totalErrors === 0,
                            summary: totalRenamed + ' renamed, ' + totalSynced + ' synced across ' + products.length + ' product(s)' + (totalErrors ? ', ' + totalErrors + ' error(s)' : '')
                        });
                        return;
                    }
                    var pid = products[i].id;
                    setStepLog(stepId, (i + 1) + ' / ' + products.length + ' — ' + (products[i].title || '#' + pid));
                    $.post(CMCCloner.ajaxUrl, {
                        action:     CMCCloner.actions.imgRename,
                        nonce:      CMCCloner.nonce,
                        product_id: pid
                    })
                    .done(function (pr) {
                        if (pr && pr.success) {
                            var pd = pr.data || {};
                            totalRenamed += (pd.renamed | 0);
                            totalSynced  += (pd.synced | 0);
                            if (pd.errors && pd.errors.length) { totalErrors += pd.errors.length; }
                        } else {
                            totalErrors++;
                        }
                    })
                    .fail(function () { totalErrors++; })
                    .always(function () { i++; loop(); });
                }
                loop();
            }
        }

        /* ---- Step 6: Regenerate Image Thumbnails ----
         * Mirrors the standalone "Repair image metadata" button — runs a
         * scan pass to learn `total`, then an apply pass that paginates
         * via `next_offset`. Scoped to ALL product categories (term_id=0)
         * so it covers every product image even if Rename Category Name
         * earlier in the run created a new term. */
        function runImageRegen(stepId) {
            var REPAIR_BATCH = 25;
            return new Promise(function (resolve) {
                var action = CMCCloner.actions && CMCCloner.actions.imgMetaRepair;
                if (!action) {
                    resolve({ success: false, summary: 'imgMetaRepair action missing — reload the page' });
                    return;
                }
                runRegenPass(action, false, stepId, function (scanRes) {
                    if (!scanRes.ok) {
                        resolve({ success: false, summary: scanRes.summary });
                        return;
                    }
                    if (!scanRes.totals.checked) {
                        resolve({ success: true, summary: 'nothing to regenerate' });
                        return;
                    }
                    setStepLog(stepId, 'regenerating ' + scanRes.totals.checked + ' image(s) — 0%');
                    runRegenPass(action, true, stepId, function (applyRes) {
                        if (!applyRes.ok) {
                            resolve({ success: false, summary: applyRes.summary });
                            return;
                        }
                        var t = applyRes.totals;
                        var writes = (t.fixed | 0) + (t.regenerated | 0);
                        resolve({
                            success: t.regenerated > 0 || t.fixed > 0 || t.checked === 0,
                            summary: t.regenerated + ' regenerated, ' + t.fixed + ' fallback patched, ' + t.skipped + ' skipped (total writes: ' + writes + ')'
                        });
                    });
                });
            });

            function runRegenPass(action, apply, stepId, cb) {
                var totals = { checked: 0, mismatched: 0, fixed: 0, incomplete: 0, regenerated: 0, skipped: 0, total: 0 };
                var offset = 0;
                function step() {
                    if (cancelled) { cb({ ok: false, summary: 'cancelled mid-step' }); return; }
                    $.ajax({
                        url: CMCCloner.ajaxUrl,
                        type: 'POST',
                        timeout: 120000,
                        data: {
                            action:          action,
                            nonce:           CMCCloner.nonce,
                            apply:           apply ? 1 : 0,
                            offset:          offset,
                            limit:           REPAIR_BATCH,
                            term_id:         0,
                            include_subcats: 1
                        }
                    })
                    .done(function (res) {
                        if (!res || !res.success) {
                            cb({ ok: false, summary: (apply ? 'apply' : 'scan') + ' batch failed at offset ' + offset });
                            return;
                        }
                        var d = res.data || {};
                        totals.checked     += (d.checked | 0);
                        totals.mismatched  += (d.mismatched | 0);
                        totals.fixed       += (d.fixed | 0);
                        totals.incomplete  += (d.incomplete | 0);
                        totals.regenerated += (d.regenerated | 0);
                        totals.skipped     += (d.skipped | 0);
                        totals.total        = (d.total | 0);
                        var nextOffset = (d.next_offset | 0);
                        var pct = totals.total ? Math.min(100, Math.round((nextOffset / totals.total) * 100)) : 0;
                        if (apply) {
                            setStepLog(stepId, nextOffset + ' / ' + totals.total + ' (' + pct + '%) — ' + totals.regenerated + ' regen, ' + totals.fixed + ' patched, ' + totals.skipped + ' skipped');
                        } else {
                            setStepLog(stepId, 'scanning… ' + nextOffset + ' / ' + totals.total + ' (' + pct + '%)');
                        }
                        if (d.done) { cb({ ok: true, totals: totals }); return; }
                        offset = nextOffset;
                        step();
                    })
                    .fail(function (xhr, status) {
                        cb({ ok: false, summary: (status === 'timeout' ? 'timed out' : 'network error') + ' at offset ' + offset });
                    });
                }
                step();
            }
        }

        /* ---- Step 8: Heal Stale Attachment GUIDs ----
         * Walks every attachment in batches and rewrites wp_posts.guid
         * whenever it drifted from the _wp_attached_file-derived URL.
         * Backfill for pre-fix renames where wp_update_post() silently
         * dropped guid updates — without this, FIFU / show-link-image's
         * wp_get_attachment_url filter keeps returning the stale guid
         * (the original Amazon filename) to CTX Feed / GMC. */
        function runHealGuids(stepId) {
            var BATCH = 200;
            return new Promise(function (resolve) {
                var action = CMCCloner.actions && CMCCloner.actions.runAllHealGuids;
                if (!action) {
                    resolve({ success: false, summary: 'runAllHealGuids action missing — reload the page' });
                    return;
                }
                var totals = { checked: 0, updated: 0, total: 0 };
                var offset = 0;
                function step() {
                    if (cancelled) { resolve({ success: false, summary: 'cancelled mid-step' }); return; }
                    $.ajax({
                        url:     CMCCloner.ajaxUrl,
                        type:    'POST',
                        timeout: 120000,
                        data: {
                            action: action,
                            nonce:  CMCCloner.nonce,
                            offset: offset,
                            limit:  BATCH
                        }
                    })
                    .done(function (res) {
                        if (!res || !res.success) {
                            resolve({ success: false, summary: 'batch failed at offset ' + offset });
                            return;
                        }
                        var d = res.data || {};
                        totals.checked += (d.checked | 0);
                        totals.updated += (d.updated | 0);
                        totals.total    = (d.total | 0);
                        var nextOffset  = (d.next_offset | 0);
                        var pct = totals.total ? Math.min(100, Math.round((nextOffset / totals.total) * 100)) : 0;
                        setStepLog(stepId, nextOffset + ' / ' + totals.total + ' (' + pct + '%) — ' + totals.updated + ' guid(s) patched');
                        if (d.done) {
                            resolve({
                                success: true,
                                summary: totals.updated + ' guid(s) patched out of ' + totals.checked + ' attachment(s) checked'
                            });
                            return;
                        }
                        offset = nextOffset;
                        step();
                    })
                    .fail(function (xhr, status) {
                        resolve({ success: false, summary: (status === 'timeout' ? 'timed out' : 'network error') + ' at offset ' + offset });
                    });
                }
                step();
            });
        }

        /* ---- Step 9: Build Sub-Categories ----
         * Single AJAX call. Server handles: AI plan generation (or cache
         * lookup) → wp_insert_term for each sub-cat under the parent →
         * walk products + score titles + multi-cat assign → prune empty
         * sub-cats AI suggested but no products matched. Idempotent. */
        function runBuildSubcats(stepId) {
            return new Promise(function (resolve) {
                var action = CMCCloner.actions && CMCCloner.actions.runAllBuildSubcats;
                if (!action) {
                    resolve({ success: false, summary: 'runAllBuildSubcats action missing — reload the page' });
                    return;
                }
                setStepLog(stepId, 'generating sub-cat plan + distributing products…');
                $.ajax({
                    url:     CMCCloner.ajaxUrl,
                    type:    'POST',
                    timeout: 180000,
                    data: {
                        action: action,
                        nonce:  CMCCloner.nonce
                    }
                })
                .done(function (res) {
                    if (!res || !res.success) {
                        var em = (res && res.data && res.data.message) || 'failed';
                        resolve({ success: false, summary: em });
                        return;
                    }
                    var d = res.data || {};
                    var summary = (d.created | 0) + ' sub-cat(s) created'
                                + ((d.pruned_empty | 0) > 0 ? ' (' + (d.pruned_empty | 0) + ' empty pruned)' : '')
                                + ' · ' + (d.distributed | 0) + ' product(s) distributed'
                                + ((d.unmatched | 0) > 0 ? ' (' + (d.unmatched | 0) + ' unmatched left in parent)' : '');
                    resolve({ success: true, summary: summary });
                })
                .fail(function (xhr, status) {
                    if (status === 'timeout') {
                        resolve({ success: false, summary: 'AI call timed out' });
                        return;
                    }
                    // Surface the server-side error message when present
                    // (HTTP 500 with wp_send_json_error payload) instead
                    // of the generic "network error" — categorisation
                    // errors are almost always AI / JSON parse issues
                    // that the operator needs to see.
                    var msg = 'network error';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        msg = xhr.responseJSON.data.message;
                    } else if (xhr && xhr.status) {
                        msg = 'HTTP ' + xhr.status;
                    }
                    resolve({ success: false, summary: msg });
                });
            });
        }

        /* ---- Step 10: Ensure Size Guide Page ----
         * Single AJAX call. Server handles: industry gate → create empty
         * page if missing → build prompt → AI → save (which triggers the
         * cmc_cloner_page_updated action that auto-attaches the page to
         * the footer menu). Idempotent — already-generated pages are
         * left untouched. */
        function runSizeGuide(stepId) {
            return new Promise(function (resolve) {
                var action = CMCCloner.actions && CMCCloner.actions.runAllSizeGuide;
                if (!action) {
                    resolve({ success: false, summary: 'runAllSizeGuide action missing — reload the page' });
                    return;
                }
                setStepLog(stepId, 'checking industry + syncing page…');
                $.ajax({
                    url:     CMCCloner.ajaxUrl,
                    type:    'POST',
                    timeout: 120000,
                    data: {
                        action: action,
                        nonce:  CMCCloner.nonce
                    }
                })
                .done(function (res) {
                    if (!res || !res.success) {
                        var em = (res && res.data && res.data.message) || 'failed';
                        resolve({ success: false, summary: em });
                        return;
                    }
                    var d = res.data || {};
                    if (d.skipped) {
                        resolve({ success: true, summary: d.message || 'skipped' });
                        return;
                    }
                    resolve({ success: true, summary: d.message || 'done' });
                })
                .fail(function (xhr, status) {
                    resolve({ success: false, summary: status === 'timeout' ? 'AI call timed out' : 'network error' });
                });
            });
        }

    }

    /* ----------------------- Woo POD Setup button (Site Setup → Find products on Amazon) -----------------------
     * Standalone button. Hits the four 237NDMOE2MY4 plugin endpoints in
     * sequence via fetch() with credentials so the user's logged-in
     * session cookie travels along (same-origin, no CORS). Each script
     * returns JSON; we trust HTTP 2xx + parseable JSON as success unless
     * the body has success:false / error / status:"error".
     *
     * Idempotent: succeeds-once flag is keyed by home_url() on the
     * server, so a cloned site (new domain) re-runs automatically. The
     * button still allows manual re-runs (with a confirm prompt) in case
     * the flag is wrong or the user wants to refresh the install. */
    function initPodSetupButton() {
        var $btn = $('.cmc-btn-pod-setup');
        if (!$btn.length || typeof CMCCloner === 'undefined') { return; }

        var $status = $('.cmc-pod-setup-status');

        function setStatus(text, cls) {
            $status.removeClass('is-ok is-error is-running').text(text || '');
            if (cls) { $status.addClass(cls); }
        }

        if (CMCCloner.podSetupDone) {
            setStatus('Already completed on this domain.', 'is-ok');
        }

        $btn.on('click', function () {
            if (CMCCloner.podSetupDone) {
                if (!window.confirm('Woo POD Setup is already marked as done on this domain. Run again anyway?')) {
                    return;
                }
            }

            $btn.prop('disabled', true);
            setStatus('Starting…', 'is-running');

            var base = window.location.origin + '/wp-content/plugins/237NDMOE2MY4/';
            var steps = [
                { url: base + 'setup.php',                                          label: 'setup.php',            timeoutMs: 60000  },
                { url: base + 'setup.php?action=skip_check_wp_config',              label: 'skip_check_wp_config', timeoutMs: 60000  },
                { url: base + 'init_file_ini_config.php?action=create_ini_config',  label: 'create_ini_config',    timeoutMs: 60000  },
                { url: base + 'update.php',                                         label: 'update.php',           timeoutMs: 180000 }
            ];

            var i = 0;
            function next() {
                if (i >= steps.length) {
                    // All four succeeded — persist done flag on server.
                    $.post(CMCCloner.ajaxUrl, {
                        action: CMCCloner.actions.runAllPodMarkDone,
                        nonce:  CMCCloner.nonce
                    }).always(function () {
                        CMCCloner.podSetupDone = true;
                        setStatus('Done. 4 / 4 endpoints OK — marked done for this domain.', 'is-ok');
                        $btn.prop('disabled', false);
                    });
                    return;
                }
                var s = steps[i];
                setStatus((i + 1) + ' / 4 — ' + s.label + '…', 'is-running');
                fetchWithTimeout(s.url, s.timeoutMs).then(function (result) {
                    if (!result.ok) {
                        setStatus('Sub-step ' + (i + 1) + '/4 (' + s.label + ') failed: ' + result.reason, 'is-error');
                        $btn.prop('disabled', false);
                        return;
                    }
                    i++;
                    next();
                });
            }
            next();
        });

        function fetchWithTimeout(url, timeoutMs) {
            return new Promise(function (resolve) {
                var ctrl  = (typeof AbortController !== 'undefined') ? new AbortController() : null;
                var timer = setTimeout(function () {
                    if (ctrl) { ctrl.abort(); }
                    resolve({ ok: false, reason: 'timed out after ' + Math.round(timeoutMs / 1000) + 's' });
                }, timeoutMs);
                fetch(url, {
                    method:      'GET',
                    credentials: 'include',
                    cache:       'no-store',
                    signal:      ctrl ? ctrl.signal : undefined
                })
                .then(function (res) {
                    clearTimeout(timer);
                    if (!res.ok) { resolve({ ok: false, reason: 'HTTP ' + res.status }); return; }
                    return res.text().then(function (text) {
                        var body = (text || '').trim();
                        if (body === '') { resolve({ ok: true }); return; }
                        try {
                            var json = JSON.parse(body);
                            if (json && json.success === false)  { resolve({ ok: false, reason: (json.message || json.error || 'success=false') }); return; }
                            if (json && json.error)              { resolve({ ok: false, reason: String(json.error) });                              return; }
                            if (json && json.status === 'error') { resolve({ ok: false, reason: (json.message || 'status=error') });                return; }
                            resolve({ ok: true });
                        } catch (e) {
                            var lower = body.toLowerCase();
                            if (lower.indexOf('fatal error') !== -1 || lower.indexOf('parse error') !== -1) {
                                resolve({ ok: false, reason: 'PHP error in response' });
                                return;
                            }
                            resolve({ ok: true });
                        }
                    });
                })
                .catch(function (err) {
                    clearTimeout(timer);
                    var msg = (err && err.name === 'AbortError') ? 'aborted' : String(err && err.message ? err.message : err);
                    resolve({ ok: false, reason: msg });
                });
            });
        }
    }

    /* ----------------------- Revert Sub-Categories button (Site Setup → Run All footer) -----------------------
     * Standalone button that undoes the "Build Sub-Categories" Run-All
     * step: restores each product's pre-distribution `product_cat`
     * list from postmeta backup, deletes plugin-created sub-cat terms.
     *
     * Confirm prompt is intentionally noisy — re-running the build step
     * after a revert means another AI call, so users should pause before
     * confirming. */
    function initRevertSubcatsButton() {
        var $btn = $('.cmc-btn-revert-subcats');
        if (!$btn.length || typeof CMCCloner === 'undefined') { return; }

        var $status = $('.cmc-revert-subcats-status');
        function setStatus(text, cls) {
            $status.removeClass('is-ok is-error is-running').text(text || '');
            if (cls) { $status.addClass(cls); }
        }

        $btn.on('click', function () {
            var action = CMCCloner.actions && CMCCloner.actions.revertSubcats;
            if (!action) { setStatus('revertSubcats action missing — reload the page', 'is-error'); return; }
            if (!window.confirm('Revert all sub-categories?\n\nThis restores every reassigned product to its pre-distribution product_cat list and deletes plugin-created sub-cat terms. The cached AI plan will also be cleared, so re-running "Build Sub-Categories" will call AI again.')) {
                return;
            }
            $btn.prop('disabled', true);
            setStatus('Reverting…', 'is-running');
            $.ajax({
                url:     CMCCloner.ajaxUrl,
                type:    'POST',
                timeout: 120000,
                data: {
                    action: action,
                    nonce:  CMCCloner.nonce
                }
            })
            .done(function (res) {
                if (!res || !res.success) {
                    setStatus('Revert failed: ' + ((res && res.data && res.data.message) || 'unknown'), 'is-error');
                    $btn.prop('disabled', false);
                    return;
                }
                var d = res.data || {};
                setStatus(
                    'Restored ' + (d.restored | 0) + ' product(s); deleted ' + (d.deleted | 0) + ' auto sub-cat(s).',
                    'is-ok'
                );
                $btn.prop('disabled', false);
            })
            .fail(function (xhr, status) {
                setStatus(status === 'timeout' ? 'Timed out — try again' : 'Network error', 'is-error');
                $btn.prop('disabled', false);
            });
        });
    }

})(jQuery);
