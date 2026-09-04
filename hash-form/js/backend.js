var hashFormAdmin = hashFormAdmin || {};

(function ($) {
    'use strict';

    let $buildForm = $('#hf-fields-form'),
        $formMeta = $('#hf-meta-form'),
        $formSettings = $('#hf-settings-form'),
        $styleSettings = $('#hf-style-form'),
        copyHelper = false,
        isCheckedField = false;

    // The hidden <li> option template ships with this placeholder key.
    const OPT_TEMPLATE_KEY = '000';

    // Sub-inputs of an option row that are not the option label itself.
    const SKIPPED_OPT_PARTS = ['[000]', '[value]', '[image_id]', '[price]'];

    /* -----------------------------------------------------------------------
     * Shared helpers
     * -------------------------------------------------------------------- */

    // Every settings control lives inside the panel of the field it edits.
    function fieldIdOf(control) {
        return $(control).closest('.hf-fields-settings').data('fid');
    }

    // The live preview of the field whose settings panel `control` belongs to.
    function editorFieldOf(control) {
        return $('#hf-editor-field-id-' + fieldIdOf(control));
    }

    // Modifier classes are written as `prefix + value`, and only one variant of
    // a group may be present at a time, so the whole group is cleared first.
    const MODIFIER_VARIANTS = {
        'hf-label-position-': ['top', 'left', 'right', 'hide'],
        'hf-label-alignment-': ['left', 'right', 'center'],
        'hf-text-alignment-': ['left', 'right', 'center'],
        'hf-field-alignment-': ['left', 'right', 'center'],
        'hf-options-layout-': ['inline', '1', '2', '3', '4', '5', '6'],
        'hf-submit-btn-align-': ['left', 'right', 'center', 'stretch'],
        'hf-form-': ['no-style', 'default-style', 'custom-style']
    };

    function swapModifierClass($el, prefix, value) {
        const group = MODIFIER_VARIANTS[prefix].map((variant) => prefix + variant).join(' ');
        return $el.removeClass(group).addClass(prefix + value);
    }

    // Open the WP media library on images only and hand the picked attachment back.
    function chooseImage(onSelect) {
        const fileFrame = wp.media({
            multiple: false,
            library: {
                type: ['image']
            }
        });

        fileFrame.on('select', function () {
            onSelect(fileFrame.state().get('selection').first().toJSON());
        });
        fileFrame.open();
    }

    /* -----------------------------------------------------------------------
     * Bootstrap
     * -------------------------------------------------------------------- */

    hashFormAdmin = {
        init: function () {
            if ($formSettings.length > 0) {
                this.initFormSettings();
            } else if ($styleSettings.length > 0) {
                this.initStyleSettings();
            } else if ($buildForm.length > 0) {
                $('.hashform-ajax-udpate-button').on('click', hashFormAdmin.submitBuild);
            } else {
                this.initOtherSettings();
            }

            hashFormAdmin.liveChanges();
            hashFormAdmin.setupFieldOptionSorting($('.hf-option-list'));
            hashFormAdmin.initBulkOptionsOverlay();
            hashFormAdmin.initNewFormModal();

            $(document).find('.hf-color-picker').wpColorPicker();

            $(document).on('click', '#hf-fields-tabs a', hashFormAdmin.clickNewTab);
            $(document).on('input', '.hf-search-fields-input', hashFormAdmin.searchContent);
            $(document).on('click', '.hf-field-group-toggle', hashFormAdmin.toggleFieldGroup);
            $(document).on('click', '.hf-format-preset', hashFormAdmin.applyFormatPreset);
            hashFormAdmin.restoreFieldGroups();
            $(document).on('click', '.hf-settings-tab a', hashFormAdmin.clickNewTabSettings);

            /* Image */
            // Replace opens the same media frame; it carries its own class so
            // addImage's "hide the chooser" step does not hide it as well.
            $(document).on('click', '.hf-image-preview .hf-choose-image, .hf-image-preview .hf-replace-image', hashFormAdmin.addImage);
            $(document).on('click', '.hf-image-preview .hf-remove-image', hashFormAdmin.removeImage);
            $(document).on('change', 'select[name^="field_options[image_size"]', hashFormAdmin.liveChangeImageSize);

            /* Add field attr to form in Settings page */
            $(document).on('click', '.hf-add-field-attr-to-form li:not(.hf-attr-search, .hf-attr-empty)', hashFormAdmin.addFieldAttrToForm);
            $(document).on('input', '.hf-attr-search input', hashFormAdmin.filterFieldAttrOptions);
            $(document).on('mouseenter focusin', '.hf-attr-field', hashFormAdmin.placeFieldAttrList);
            $(document).on('keydown', '.hf-attr-field', hashFormAdmin.keyFieldAttrPicker);
            hashFormAdmin.initFieldAttrPickers();

            /* Open the embed popup. Closing it — button, backdrop or Escape —
               is handled with the add-form dialog in initNewFormModal. */
            $(document).on('click', '.hf-embed-button', () => {
                $('#hf-shortcode-form-modal').addClass('hf-open').attr('aria-hidden', 'false');
                $('body').addClass('hf-modal-open');
            });

            $('.hf-add-more-condition').on('click', hashFormAdmin.addConditionRepeaterBlock);
            $(document).on('click', '.hf-condition-remove', hashFormAdmin.removeConditionRepeaterBlock);
            $('.hf-condition-list').each(function () {
                hashFormAdmin.refreshConditionList($(this));
            });

            $(document).on('change', '.hf-fields-type-time .default-value-field', hashFormAdmin.addTimeDefaultValue);
            $(document).on('change', '.hf-fields-type-time .min-value-field, .hf-fields-type-time .max-value-field, .hf-fields-type-time .hf-default-value-field', hashFormAdmin.validateTimeValue);

            $('.hf-fields-type-date .hf-default-value-field').datepicker({
                changeMonth: true
            });

            // Newly built date fields need their picker attached after the fact.
            document.addEventListener('hashform_added_field', (e) => {
                if (e.hfType === 'date') {
                    $(document).find('.hf-fields-type-date .hf-default-value-field').datepicker({
                        changeMonth: true
                    });
                }

                /*
                 * So do the option lists of a choice field. Sorting is set up
                 * once, over the lists that exist when the builder loads, so a
                 * checkbox or radio added afterwards had no sortable at all and
                 * its options would not drag until the page was reloaded.
                 *
                 * Found by id rather than inside the field: updateFieldOrder()
                 * has already run by now and moves the settings panel out of the
                 * field's own li, so searching the li finds nothing.
                 */
                const hfFieldId = e.hfField ? e.hfField.getAttribute('data-fid') : '';
                const $options = hfFieldId
                    ? $('#hf-fields-settings-' + hfFieldId).find('.hf-option-list')
                    : $();

                if ($options.length) {
                    hashFormAdmin.setupFieldOptionSorting($options);
                }

                // Must Match Field lists the other fields, so a new one has to
                // appear in the lists the other fields already carry.
                hashFormAdmin.refreshMatchFieldOptions();
            }, false);
        },

        /* -------------------------------------------------------------------
         * Tabs and search
         * ---------------------------------------------------------------- */

        clickNewTab: function () {
            const $link = $(this);
            const href = $link.attr('href');
            if (typeof href === 'undefined') {
                return false;
            }

            $link.closest('li').addClass('hf-active-tab').siblings('li').removeClass('hf-active-tab');
            $link.closest('.hf-fields-container').find('.ht-fields-panel').hide();
            $(href).show();
            return false;
        },

        clickNewTabSettings: function () {
            const $link = $(this);
            const id = this.getAttribute('href');

            if (typeof id === 'undefined') {
                return false;
            }

            $link.closest('li').addClass('hf-active').siblings('li').removeClass('hf-active');
            $(id).removeClass('hf-hidden').siblings().addClass('hf-hidden');
            return false;
        },

        searchContent: function () {
            const searchText = $(this).val().toLowerCase().trim();
            const toSearch = $(this).attr('data-tosearch');
            let matches = 0;

            $('.' + toSearch).each(function () {
                const $item = $(this);
                // The id is the field type, which rarely reads like the label
                // people actually search for — 'select' for Dropdown, say — so
                // the visible name is matched too.
                const name = $item.attr('data-field-name') || '';
                const hit = !searchText
                    || $item.attr('id').indexOf(searchText) > -1
                    || name.indexOf(searchText) > -1;

                if (searchText) {
                    $item.toggle(hit);
                } else {
                    // Clearing the inline display rather than calling show():
                    // inside a collapsed group show() forces a display value
                    // onto the item, and the group's own CSS should be what
                    // decides whether it is on screen.
                    $item.css('display', '');
                }

                // The match is recorded on the element because the group
                // counts below cannot ask :visible — see the note there.
                $item.toggleClass('hf-search-hit', hit);

                if (hit) {
                    matches++;
                }
            });

            const $section = $('.hf-fields-section');

            if (!$section.length) {
                return;
            }

            // Searching opens every group so a match is never hidden behind a
            // collapsed heading; clearing the box puts them back as they were.
            $section.toggleClass('hf-searching', searchText !== '');

            $section.find('.hf-field-group').each(function () {
                const $group = $(this);

                // Counted from the match class, never from :visible. The line
                // above drops .hf-searching, which re-collapses every closed
                // group, so :visible reported zero for all of them and each
                // one was then hidden outright — clearing the search box
                // emptied the palette and only a reload brought it back.
                const shown = $group.find('.hf-field-box.hf-search-hit').length;

                $group.prop('hidden', shown === 0);

                // While filtering, the badge counts what is actually under the
                // heading rather than the size of the group.
                const $count = $group.find('.hf-field-group-count');

                if ($count.length) {
                    if (typeof $count.data('total') === 'undefined') {
                        $count.data('total', $count.text());
                    }

                    $count.text(searchText ? shown : $count.data('total'));
                }
            });

            $('.hf-fields-empty').prop('hidden', matches !== 0);
        },

        /**
         * Drops a ready-made regex into the Format box, or empties it.
         *
         * change is fired explicitly because the value is set in code, and
         * the builder's unsaved-changes tracking listens for it.
         */
        applyFormatPreset: function (e) {
            e.preventDefault();

            $(this)
                .closest('.hf-form-row')
                .find('.hf-format-input')
                .val($(this).attr('data-format') || '')
                .trigger('change');
        },

        toggleFieldGroup: function () {
            const $group = $(this).closest('.hf-field-group');
            const open = !$group.hasClass('hf-open');

            $group.toggleClass('hf-open', open);
            $(this).attr('aria-expanded', open ? 'true' : 'false');

            // Remembered so the palette opens where it was left.
            try {
                const key = 'hashform.builder.groups';
                const state = JSON.parse(window.localStorage.getItem(key) || '{}');
                state[$group.data('group')] = open;
                window.localStorage.setItem(key, JSON.stringify(state));
            } catch (e) {
                /* Private browsing, or the quota is full; not worth reporting. */
            }
        },

        restoreFieldGroups: function () {
            let state;

            try {
                state = JSON.parse(window.localStorage.getItem('hashform.builder.groups') || '{}');
            } catch (e) {
                return;
            }

            $('.hf-field-group').each(function () {
                const $group = $(this);
                const saved = state[$group.data('group')];

                if (typeof saved === 'boolean') {
                    $group.toggleClass('hf-open', saved);
                    $group.find('.hf-field-group-toggle').attr('aria-expanded', saved ? 'true' : 'false');
                }
            });
        },

        /* -------------------------------------------------------------------
         * Image pickers
         * ---------------------------------------------------------------- */

        // The canvas preview for an image field, found by the class it shares
        // with the hidden input's id.
        canvasImagePreview: function (imagePreview) {
            return $('.' + imagePreview.find('input.hf-image-id').attr('id'));
        },

        // Where each size of a chosen attachment lives, as the media library
        // reports it. Sizes it has no copy of fall back to the original.
        attachmentSizeUrls: function (attachment) {
            const urls = {};

            ['thumbnail', 'medium', 'large'].forEach(function (size) {
                const known = attachment.sizes && attachment.sizes[size];
                urls[size] = known
                    ? { url: known.url, width: known.width, height: known.height }
                    : { url: attachment.url, width: attachment.width, height: attachment.height };
            });

            urls.full = { url: attachment.url, width: attachment.width, height: attachment.height };

            return urls;
        },

        // Point a canvas image at one size of itself. srcset wins over src, so
        // it has to go, and the dimensions travel with the file.
        showImageSize: function (image, size) {
            if (!image.length || !size || !size.url) {
                return;
            }

            image.removeAttr('srcset').removeAttr('sizes').attr('src', size.url);

            if (size.width) {
                image.attr('width', size.width);
            }

            if (size.height) {
                image.attr('height', size.height);
            }
        },

        addImage: function (e) {
            e.preventDefault();
            const imagePreview = $(this).closest('.hf-image-preview');

            chooseImage((attachment) => {
                imagePreview.find('img').attr('src', attachment.url);
                imagePreview.find('input.hf-image-id').val(attachment.id);
                imagePreview.find('.hf-image-preview-wrap').removeClass('hf-hidden');
                imagePreview.find('.hf-choose-image').addClass('hf-hidden');

                const settings = imagePreview.closest('.hf-fields-settings');
                const size = settings.find('select[name^="field_options[image_size"]').val() || 'full';
                const urls = hashFormAdmin.attachmentSizeUrls(attachment);

                const frontImagePreview = hashFormAdmin.canvasImagePreview(imagePreview);
                frontImagePreview.attr('data-sizes', JSON.stringify(urls));

                /*
                 * The canvas image is replaced, not appended to: choosing a
                 * second image left the first one sitting above it. It is also
                 * shown at the size the field is set to rather than always at
                 * full size, which is what the page will do.
                 */
                let canvasImage = frontImagePreview.find('img');

                if (!canvasImage.length) {
                    canvasImage = $('<img class="hf-image-field" alt="" />').appendTo(frontImagePreview);
                }

                hashFormAdmin.showImageSize(canvasImage, urls[size] || urls.full);
                frontImagePreview.find('.hf-no-image-field').addClass('hf-hidden');
            });
        },

        liveChangeImageSize: function () {
            const settings = $(this).closest('.hf-fields-settings');
            const frontImagePreview = $('#hf-editor-field-container-' + settings.data('fid')).find('.hf-image-preview-front');
            const urls = frontImagePreview.attr('data-sizes');

            if (!urls) {
                return;
            }

            hashFormAdmin.showImageSize(frontImagePreview.find('img'), JSON.parse(urls)[this.value]);
        },

        removeImage: function (e) {
            e.preventDefault();
            const imagePreview = $(this).closest('.hf-image-preview');

            // Removed rather than blanked: src="" makes the browser request
            // the current page and try to render it as the image.
            imagePreview.find('img').removeAttr('src');
            imagePreview.find('.hf-image-preview-wrap').addClass('hf-hidden');
            imagePreview.find('.hf-choose-image').removeClass('hf-hidden');
            imagePreview.find('input.hf-image-id').val('');

            const frontImagePreview = $('.' + imagePreview.find('input.hf-image-id').attr('id'));
            frontImagePreview.find('.hf-no-image-field').removeClass('hf-hidden');
            frontImagePreview.find('img').remove();
        },

        addImageToOption: function (e) {
            e.preventDefault();
            const $imagePreview = $(e.target.closest('li'));

            chooseImage((attachment) => {
                $imagePreview.find('.hf-is-image-holder').html('<img src="' + attachment.url + '"/>');
                $imagePreview.find('.hf-is-image-preview-box').addClass('hf-image-added');
                $imagePreview.find('input.hf-image-id').val(attachment.id).trigger('change');
                hashFormAdmin.resetDisplayedOpts(fieldIdOf($imagePreview));
            });
        },

        removeImageFromOption: function (e) {
            e.preventDefault();
            e.stopPropagation();

            const previewWrapper = $(this).closest('li');
            previewWrapper.find('.hf-is-image-holder').html('');
            previewWrapper.find('.hf-is-image-preview-box').removeClass('hf-image-added');
            previewWrapper.find('input.hf-image-id').val('').trigger('change');
            hashFormAdmin.resetDisplayedOpts(fieldIdOf(previewWrapper));
        },

        /* -------------------------------------------------------------------
         * Saving
         * ---------------------------------------------------------------- */

        // Shared tail of every save button: POST, un-busy the button and show
        // the returned notice. Callers must run preFormSave() first, since it
        // can flush pending inline edits into the form before serialization.
        postFormSave: function (button, data) {
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: data
            }).done(function (msg) {
                hashFormAdmin.afterFormSave(button);
                hashFormAdmin.showUpdatedInfo(msg);
            }).fail(function () {
                // There was no failure path at all: a save that died left the
                // button spinning for good and said nothing, which is
                // indistinguishable from a save that is merely slow.
                hashFormAdmin.afterFormSave(button);
                hashFormAdmin.showUpdatedInfo(
                    '<span class="mdi mdi-alert-circle"></span>' + hashFormAdmin.saveErrorText(),
                    true
                );
            });
        },

        saveErrorText: function () {
            return (typeof hashform_backend_js !== 'undefined' && hashform_backend_js.generic_error)
                || 'Something went wrong. Please reload the page and try again.';
        },

        showUpdatedInfo: function (msg, isError) {
            const panel = document.getElementById('hf-form-panel');

            if (!panel) {
                return;
            }

            const notice = document.createElement('div');
            notice.setAttribute('class', isError ? 'hf-updated-info hf-updated-error' : 'hf-updated-info');
            notice.setAttribute('role', isError ? 'alert' : 'status');
            notice.innerHTML = msg;
            panel.insertBefore(notice, panel.firstChild);
        },

        submitBuild: function (e) {
            e.preventDefault();
            hashFormAdmin.preFormSave(this);
            hashFormAdmin.postFormSave(this, {
                action: 'hashform_update_form',
                hashform_fields: JSON.stringify($buildForm.serializeArray()),
                hashform_settings: JSON.stringify($formMeta.serializeArray()),
                backend_nonce: hashform_backend_js.nonce
            });
        },

        submitSettingsBuild: function (e) {
            e.preventDefault();
            hashFormAdmin.preFormSave(this);

            const fields = JSON.stringify($formSettings.serializeArray());
            $('#hashform_compact_fields').val(fields);

            hashFormAdmin.postFormSave(this, {
                action: 'hashform_save_form_settings',
                hashform_compact_fields: fields
            });
        },

        submitStylesBuild: function (e) {
            e.preventDefault();
            hashFormAdmin.preFormSave(this);

            const fields = JSON.stringify($styleSettings.serializeArray());
            $('#hashform_compact_fields').val(fields);

            hashFormAdmin.postFormSave(this, {
                action: 'hashform_save_form_style',
                hashform_compact_fields: fields,
                backend_nonce: hashform_backend_js.nonce
            });
        },

        preFormSave: function (b) {
            hashFormBuilder.removeWPUnload();
            if ($('form.inplace_form').length) {
                $('.inplace_save, .postbox').trigger('click');
            }

            if (b.classList.contains('hashform-ajax-udpate-button')) {
                b.classList.add('hashform-updating');
            } else {
                b.classList.add('hashform_loading_button');
            }
            b.setAttribute('aria-busy', 'true');
        },

        afterFormSave: function (button) {
            button.classList.remove('hashform-updating');
            button.classList.remove('hashform_loading_button');
            hashFormBuilder.resetOptionTextDetails();
            button.setAttribute('aria-busy', 'false');

            setTimeout(function () {
                $('.hf-updated-info').fadeOut('slow', function () {
                    this.parentNode.removeChild(this);
                });
            }, 5000);
        },

        /* -------------------------------------------------------------------
         * Page setup: form settings / styles / global settings
         * ---------------------------------------------------------------- */

        initFormSettings: function () {
            $('.hashform-ajax-udpate-button').on('click', hashFormAdmin.submitSettingsBuild);

            $('.hf-multiple-rows').on('click', '.hf-add-email', function () {
                $(this).closest('.hf-multiple-rows').find('.hf-multiple-email').append('<div class="hf-email-row"><input type="email" name="email_to[]" autocomplete="off" value=""/><span class="mdi mdi-trash-can-outline hf-delete-email-row"></span></div>');
            });

            $(document).on('click', '.hf-multiple-rows .hf-delete-email-row', function () {
                $(this).closest('.hf-email-row').remove();
            });
        },

        initStyleSettings: function () {
            $('.hashform-ajax-udpate-button').on('click', hashFormAdmin.submitStylesBuild);

            hashFormAdmin.loadStylePreview();

            $('#hf-form-style-template').on('change', function (e) {
                e.preventDefault();
                const templateID = $(this).val();
                const style = templateID ? $(document).find('option[value="' + templateID + '"]').attr('data-style') : '';
                $(hashFormAdmin.stylePreviewDoc()).find('style.hf-style-content').text(style);
            });

            $('#hf-form-style-select').on('change', function (e) {
                e.preventDefault();
                const styleClass = $(this).find(':selected').val();
                swapModifierClass($(hashFormAdmin.stylePreviewDoc()).find('form.hashform-form'), 'hf-form-', styleClass);
            });

            // The visible choice is a set of cards; the select behind them is
            // what gets serialised and what the data-condition rules watch, so
            // picking a card feeds it and lets its change handler run.
            $('.hf-style-mode-input').on('change', function () {
                const value = $(this).val();
                const $card = $(this).closest('.hf-style-mode');

                $('.hf-style-mode').removeClass('hf-selected');
                $card.addClass('hf-selected');
                $('#hf-form-style-select').val(value).trigger('change');

                // The canvas header names the style in force, where the
                // builder's counts fields.
                $('#hf-style-mode-label').text($card.find('.hf-style-mode-name').text());
            });
        },

        stylePreviewDoc: function () {
            const iframe = $('#hf-form-preview-iframe')[0];
            return iframe.contentDocument || iframe.contentWindow.document;
        },

        loadStylePreview: function () {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hashform_form_preview',
                    form_id: $('.hf-form-preview').attr('data-form'),
                    backend_nonce: hashform_backend_js.nonce
                },
                success: function (response) {
                    if (!response.success) {
                        return;
                    }

                    $('#hf-form-preview-iframe').remove();
                    const newIframe = $('<iframe>', {
                        id: 'hf-form-preview-iframe'
                    }).appendTo('.hf-form-preview')[0];

                    setTimeout(function () {
                        const doc = newIframe.contentDocument || newIframe.contentWindow.document;
                        doc.open();
                        doc.write(response.data);
                        doc.close();
                        // The preview is for looks only, so swallow all interaction.
                        doc.addEventListener('click', (e) => e.preventDefault(), true);
                        doc.addEventListener('mousedown', (e) => e.preventDefault(), true);
                        doc.addEventListener('mouseup', (e) => e.preventDefault(), true);
                    }, 0);

                    $('.hf-form-wrap').removeClass('hf-content-loading');
                }
            });
        },

        invalidEmailText: function () {
            return (typeof hashform_backend_js !== 'undefined' && hashform_backend_js.invalid_email)
                || 'Enter a valid email address.';
        },

        initOtherSettings: function () {
            $(document).on('click', '#hf-test-email-button', function (e) {
                e.preventDefault();

                const testEmailButton = $(this);
                const testEmail = $(document).find('#hf-test-email').val();

                const $notice = testEmailButton.closest('.hf-settings-row').find('.hf-test-email-notice');

                $notice.html('');
                if (!hashFormAdmin.isEmail(testEmail)) {
                    // Was appended to .hf-grid-3, which this row has never
                    // contained, so an invalid address reported nothing at all.
                    $notice.html('<div class="hf-error">' + hashFormAdmin.invalidEmailText() + '</div>');
                    return;
                }

                testEmailButton.addClass('hf-loading-button');

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'hashform_test_email_template',
                        email_template: $('#hf-settings-email-template').val(),
                        test_email: testEmail,
                        backend_nonce: hashform_backend_js.nonce
                    },
                    success: function (res) {
                        testEmailButton.removeClass('hf-loading-button');
                        const response = JSON.parse(res);
                        const noticeClass = response.success ? 'hf-success' : 'hf-error';
                        testEmailButton.closest('.hf-settings-row').find('.hf-test-email-notice')
                            .html('<div class="' + noticeClass + '">' + response.message + '</div>');
                    }
                });
            });
        },

        /* -------------------------------------------------------------------
         * Live preview bindings
         * ---------------------------------------------------------------- */

        liveChanges: function () {
            $('#hf-meta-panel').on('input', '[data-changeme]', hashFormAdmin.liveChangesInput);
            $('#hf-meta-panel').on('change', 'select[name="submit_btn_alignment"]', hashFormAdmin.liveChangeButtonPosition);

            // 'input change', not 'input, change': jQuery splits event names on
            // whitespace, so the comma made this listen for an event called
            // "input," that nothing ever fires. Typing changed nothing on the
            // canvas until the control lost focus.
            $buildForm.on('input change', '[data-changeme]', hashFormAdmin.liveChangesInput);

            /*
             * Must Match Field lists the other fields by their label, and that
             * list is drawn once when the builder loads. Renaming a field left
             * every one of those dropdowns naming it by its old label until the
             * form was saved and the page came back.
             */
            $buildForm.on('input', 'input[name^="field_options[name_"]', hashFormAdmin.syncFieldLabelInLists);

            $buildForm.on('click', 'input.hf-form-field-required', hashFormAdmin.markRequired);

            $buildForm.on('click', '.hf-add-option', hashFormAdmin.addFieldOption);
            $buildForm.on('input', '.hf-single-option input[type="text"]', hashFormAdmin.resetOptOnChange);
            $buildForm.on('mousedown', '.hf-single-option input[type=radio]', hashFormAdmin.maybeUncheckRadio);
            $buildForm.on('click', '.hf-single-option .hf-choice-input', hashFormAdmin.resetOptOnChange);
            $buildForm.on('change', '.hf-image-id', hashFormAdmin.resetOptOnChange);

            $buildForm.on('click', '.hf-single-option a[data-removeid]', hashFormAdmin.deleteFieldOption);

            $buildForm.on('click', '.hf-is-image-preview-box', hashFormAdmin.addImageToOption);
            $buildForm.on('click', '.hf-is-remove-image', hashFormAdmin.removeImageFromOption);

            $buildForm.on('input', '[data-changeheight]', hashFormAdmin.liveChangeHeight);
            $buildForm.on('input', '[data-changerows]', hashFormAdmin.liveChangeRows);
            $buildForm.on('input', '[data-changestars]', hashFormAdmin.liveChangeStars);

            $buildForm.on('change', 'select[name^="field_options[label_position"]', hashFormAdmin.liveChangeLabelPosition);
            $buildForm.on('change', 'select[name^="field_options[label_alignment"]', hashFormAdmin.liveChangeLabelAlignment);

            $buildForm.on('change', 'select[name^="field_options[options_layout"]', hashFormAdmin.liveChangeOptionsLayout);
            $buildForm.on('change', 'select[name^="field_options[heading_type"]', hashFormAdmin.liveChangeHeadingType);
            $buildForm.on('change', 'select[name^="field_options[text_alignment"]', hashFormAdmin.liveChangeTextAlignment);
            $buildForm.on('change', 'select.hf-select-image-type', hashFormAdmin.liveChangeSelectImageType);

            $buildForm.on('change', '[data-changebordertype]', hashFormAdmin.liveChangeBorderType);
            $buildForm.on('input', '[data-changeborderwidth]', hashFormAdmin.liveChangeBorderWidth);
            $buildForm.on('input', '[data-changeseparatorspacing]', hashFormAdmin.liveChangeSeparatorSpacing);

            $buildForm.on('input', 'input[name^="field_options[field_max_width"]', hashFormAdmin.liveChangeFieldMaxWidth);
            $buildForm.on('change', 'select[name^="field_options[field_max_width_unit"]', hashFormAdmin.liveChangeFieldMaxWidth);

            $buildForm.on('input', 'input[name^="field_options[image_max_width"]', hashFormAdmin.liveChangeImageMaxWidth);
            $buildForm.on('change', 'select[name^="field_options[image_max_width_unit"]', hashFormAdmin.liveChangeImageMaxWidth);

            $buildForm.on('change click', '[data-disablefield]', hashFormAdmin.liveChangeAddressFields);

            $buildForm.on('change click', 'input[name^="field_options[auto_width"]', hashFormAdmin.liveChangeAutoWidth);

            $buildForm.on('change', 'select[name^="field_options[field_alignment"]', hashFormAdmin.liveChangeFieldAlignment);

            $buildForm.on('change', '[data-row-show-hide]', hashFormAdmin.liveChangeHideShowRow);
            $buildForm.on('input', '[data-label-show-hide]', hashFormAdmin.liveChangeHideShowLabel);
            $buildForm.on('change', '[data-label-show-hide-checkbox]', hashFormAdmin.liveChangeHideShowLabelCheckbox);
        },

        /* -------------------------------------------------------------------
         * Live preview: generic [data-changeme] mirroring
         * ---------------------------------------------------------------- */

        liveChangesInput: function () {
            const newValue = this.value,
                changes = document.getElementById(this.getAttribute('data-changeme')),
                att = this.getAttribute('data-changeatt'),
                fieldAttrType = this.getAttribute('type'),
                parentField = $(changes).closest('.hf-editor-form-field');

            if (att === 'value' && fieldAttrType === 'email') {
                hashFormAdmin.showInlineError(this, newValue && !hashFormAdmin.isEmail(newValue), 'Invalid Email Value');
            }

            if (att === 'value' && parentField.attr('data-type') === 'url') {
                hashFormAdmin.showInlineError(this, newValue && !hashFormAdmin.isUrl(newValue), 'Invalid Website/URL Value. Please add full URL value');
            }

            if (parentField.attr('data-type') === 'range_slider') {
                // Wait for the mirrored min/max/step attributes to land first.
                setTimeout(function () {
                    hashFormAdmin.rebuildRangeSlider(parentField.find('.hf-range-input-selector'));
                }, 100);
            }

            if (changes === null) {
                return;
            }

            if (att !== null) {
                hashFormAdmin.applyChangedAtt(changes, att, newValue, this);
            } else if (changes.id.indexOf('setup-message') === 0) {
                if (newValue !== '') {
                    changes.innerHTML = '<input type="text" value="" disabled />';
                }
            } else if (changes.classList.contains('hf-custom-html-field')) {
                /*
                 * The HTML field draws what it holds, so the canvas shows what
                 * the page will. Scrubbed on the way in: the server drops
                 * script, style and every event attribute when this is saved,
                 * and a preview that ran them would behave unlike the thing it
                 * is previewing.
                 */
                hashFormAdmin.renderHtmlFieldPreview(changes, newValue);
            } else {
                // A paragraph field is rendered with wpautop, so the canvas
                // has to break the text up the same way or the preview shows
                // one run of words where the page will show paragraphs.
                changes.innerHTML = changes.classList.contains('hf-paragraph-field')
                    ? hashFormAdmin.autoParagraphs(newValue)
                    : newValue;

                if ('TEXTAREA' === changes.nodeName && changes.classList.contains('wp-editor-area')) {
                    $(changes).trigger('change');
                }

                if (changes.classList.contains('hf-form-label') && 'break' === changes.nextElementSibling.getAttribute('data-type')) {
                    changes.nextElementSibling.querySelector('.hf-editor-submit-button').textContent = newValue;
                }
            }
        },

        // The essentials of wpautop: a blank line starts a paragraph, a single
        // newline is a break. Kept to that, because this only has to agree with
        // what PHP will render, not reimplement it.
        autoParagraphs: function (text) {
            return String(text)
                .replace(/\r\n?/g, '\n')
                .split(/\n{2,}/)
                .filter(function (block) {
                    return block.trim() !== '';
                })
                .map(function (block) {
                    return '<p>' + block.trim().replace(/\n/g, '<br />') + '</p>';
                })
                .join('');
        },

        applyChangedAtt: function (changes, att, newValue, source) {
            if (changes.tagName === 'SELECT' && att === 'placeholder') {
                const option = changes.options[0];
                if (option.value === '') {
                    option.innerHTML = newValue;
                } else {
                    // Create a placeholder option if there are no blank values.
                    hashFormBuilder.addBlankSelectOption(changes, newValue);
                }
            } else if (att === 'class') {
                hashFormBuilder.changeFieldClass(changes, source);
            } else if ('TEXTAREA' === changes.nodeName && att === 'value') {
                changes.innerHTML = newValue;
            } else {
                changes.setAttribute(att, newValue);
            }
        },

        // Replace the inline validation message under a settings input.
        showInlineError: function (input, hasError, message) {
            const holder = $(input).closest('div');
            holder.find('.hf-error').remove();
            if (hasError) {
                holder.append('<p class="hf-error">' + message + '</p>');
            }
        },

        rebuildRangeSlider: function (newSlider) {
            const min = parseFloat(newSlider.attr('min'));
            const max = parseFloat(newSlider.attr('max'));
            const step = parseFloat(newSlider.attr('step'));

            let value = newSlider.val();
            value = value < min ? min : value;
            value = value > max ? max : value;
            value = value - (value % step);

            newSlider.prev('.hf-range-slider').slider({
                value: value,
                min: min,
                max: max,
                step: step,
                range: 'min',
                slide: function (e, ui) {
                    $(this).next().val(ui.value).trigger('change');
                }
            });
        },

        /* -------------------------------------------------------------------
         * Live preview: classes, sizes and layout
         * ---------------------------------------------------------------- */

        liveChangeButtonPosition: function (e) {
            swapModifierClass($('.hf-editor-submit-button-wrap'), 'hf-submit-btn-align-', e.target.value);
        },

        liveChangeLabelPosition: function (e) {
            swapModifierClass(editorFieldOf(this), 'hf-label-position-', e.target.value);
        },

        liveChangeLabelAlignment: function (e) {
            swapModifierClass(editorFieldOf(this), 'hf-label-alignment-', e.target.value);
        },

        liveChangeOptionsLayout: function (e) {
            swapModifierClass(editorFieldOf(this), 'hf-options-layout-', e.target.value);
        },

        liveChangeTextAlignment: function (e) {
            swapModifierClass(editorFieldOf(this), 'hf-text-alignment-', e.target.value);
        },

        liveChangeFieldAlignment: function (e) {
            swapModifierClass(editorFieldOf(this), 'hf-field-alignment-', e.target.value);
        },

        liveChangeHeadingType: function (e) {
            const tag = e.target.value;
            const current = document.getElementById('hf-field-' + fieldIdOf(this));

            if (!current || !/^h[1-6]$/.test(tag)) {
                return;
            }

            /*
             * Carry the attributes over. This used to write a bare
             * <tag id="...">, so changing the level dropped the field's own
             * class and every rule hanging off it until the page was reloaded.
             */
            const replacement = document.createElement(tag);

            Array.prototype.forEach.call(current.attributes, function (attr) {
                replacement.setAttribute(attr.name, attr.value);
            });

            replacement.innerHTML = current.innerHTML;
            current.replaceWith(replacement);
        },

        liveChangeSelectImageType: function () {
            const option = $(this).val();
            const id = $(this).attr('data-is-id');
            const settingsInputs = $('#hf-field-options-' + id).find('.hf-choice-input');
            const previewInputs = $('#hf-editor-field-container-' + id).find('input');

            settingsInputs.prop('checked', false);
            previewInputs.prop('checked', false);
            settingsInputs.attr('type', option);
            previewInputs.attr('type', option);
        },

        liveChangeHeight: function () {
            const changes = document.getElementById(this.getAttribute('data-changeheight'));

            if (changes === null) {
                return;
            }

            // Empty falls back to 50px, the way the field renders it. This
            // cleared the height instead, so the canvas showed nothing while
            // the page would show a 50px gap.
            const height = this.value === '' || isNaN(parseInt(this.value, 10)) ? 50 : Math.max(0, parseInt(this.value, 10));

            $(changes).css('height', height + 'px');

            if (changes.hasAttribute('data-label') && hashform_backend_js.spacer_label) {
                changes.setAttribute('data-label', hashform_backend_js.spacer_label.replace('%d', height));
            }
        },

        liveChangeRows: function () {
            const changes = document.getElementById(this.getAttribute('data-changerows'));
            if (changes !== null) {
                $(changes).attr('rows', this.value);
            }
        },

        liveChangeStars: function () {
            const newValue = this.value,
                changes = document.getElementById(this.getAttribute('data-changestars'));

            if (changes === null) {
                return;
            }

            let stars = '';
            for (let i = 0; i < newValue; i++) {
                stars = stars + '<label class="hf-star-rating"><input type="radio"><span class="mdi mdi-star-outline"></span></label>';
            }
            $(changes).html(stars);
        },

        liveChangeBorderType: function () {
            $('#' + this.getAttribute('data-changebordertype')).css('border-bottom-style', this.value);
        },

        liveChangeBorderWidth: function () {
            // Empty falls back to 2px, the way the field renders it. This wrote
            // 'px' on its own, which the browser drops, so the canvas kept
            // showing the previous width while the page would show 2.
            const width = this.value === '' || isNaN(parseInt(this.value, 10)) ? 2 : parseInt(this.value, 10);
            $('#' + this.getAttribute('data-changeborderwidth')).css('border-bottom-width', width + 'px');
        },

        liveChangeSeparatorSpacing: function () {
            const spacing = this.value === '' || isNaN(parseInt(this.value, 10)) ? '' : parseInt(this.value, 10) + 'px';
            $('#' + this.getAttribute('data-changeseparatorspacing')).css({
                'margin-top': spacing,
                'margin-bottom': spacing
            });
        },

        // Mirror a `<name>` / `<name>_unit` settings pair onto a CSS custom
        // property, clearing it when no positive width is set.
        liveChangeMaxWidth: function (control, name, cssVar) {
            const settings = $(control).closest('.hf-fields-settings');
            const container = $('#hf-editor-field-container-' + settings.data('fid'));
            const width = settings.find('input[name^="field_options[' + name + '"]').val();
            const unit = settings.find('select[name^="field_options[' + name + '_unit"]').val();

            if (parseInt(width) > 0) {
                container.css(cssVar, parseInt(width) + unit);
            } else {
                container.prop('style').removeProperty(cssVar);
            }
        },

        liveChangeFieldMaxWidth: function () {
            hashFormAdmin.liveChangeMaxWidth(this, 'field_max_width', '--hf-width');
        },

        liveChangeImageMaxWidth: function () {
            hashFormAdmin.liveChangeMaxWidth(this, 'image_max_width', '--hf-image-width');
        },

        liveChangeAddressFields: function () {
            $(document).find('#' + $(this).attr('data-disablefield')).toggleClass('hf-hidden', $(this).is(':checked'));
        },

        liveChangeAutoWidth: function () {
            editorFieldOf(this).toggleClass('hf-auto-width', $(this).is(':checked'));
        },

        liveChangeHideShowRow: function () {
            const parentRow = $(this).closest('.hf-form-container');
            parentRow.find('.hf-row-show-hide').addClass('hf-hidden');

            $.each($(this).val().split('_'), function (index, value) {
                parentRow.find('.hf-row-show-hide.hf-sub-field-' + value).removeClass('hf-hidden');
            });
        },

        // The label is hidden when its text is empty or the "hide" box is ticked.
        // Each entry point reads its own control directly and the sibling from
        // the settings panel, so the two handlers keep separate lookups.
        setFieldLabelHidden: function (parentFieldSetting, hidden) {
            $('#hf-editor-field-id-' + parentFieldSetting.data('fid'))
                .find('label.hf-label-show-hide')
                .toggleClass('hf-hidden', hidden);
        },

        liveChangeHideShowLabel: function () {
            const parentFieldSetting = $(this).closest('.hf-fields-settings');
            hashFormAdmin.setFieldLabelHidden(
                parentFieldSetting,
                !$(this).val() || parentFieldSetting.find('[data-label-show-hide-checkbox]').is(':checked')
            );
        },

        liveChangeHideShowLabelCheckbox: function () {
            const parentFieldSetting = $(this).closest('.hf-fields-settings');
            hashFormAdmin.setFieldLabelHidden(
                parentFieldSetting,
                $(this).is(':checked') || !parentFieldSetting.find('[data-label-show-hide]').val()
            );
        },

        /* -------------------------------------------------------------------
         * Required toggle
         * ---------------------------------------------------------------- */

        markRequired: function () {
            const fieldId = this.id.replace('hf-', '').replace('req-field-', ''),
                checked = this.checked,
                label = $('#hf-editor-field-required-' + fieldId);

            hashFormAdmin.toggleValidationBox(checked, '.hf-required-detail-' + fieldId);

            if (checked) {
                const $reqBox = $('input[name="field_options[required_indicator_' + fieldId + ']"]');
                if ($reqBox.val() === '') {
                    $reqBox.val('*');
                }
            }
            label.toggleClass('hf-hidden', !checked);
        },

        toggleValidationBox: function (hasValue, messageClass) {
            const $msg = $(messageClass);
            $msg.toggleClass('hf-hidden', !hasValue);
            $msg.closest('.hf-form-container').find('.hf-validation-header').toggleClass('hf-hidden', !hasValue);
        },

        /* -------------------------------------------------------------------
         * Field options: add / remove / reorder
         * ---------------------------------------------------------------- */

        //Add new option or "Other" option to radio/checkbox/dropdown
        addFieldOption: function () {
            /*jshint validthis:true */
            const fieldId = fieldIdOf(this),
                optType = $(this).data('opttype'),
                lastKey = hashFormAdmin.getHighestOptKey(fieldId);

            const optKey = lastKey !== OPT_TEMPLATE_KEY ? lastKey + 1 : 0;

            if (optType !== 'other') {
                const template = $('#hf-field-options-' + fieldId + ' .hf-option-template').prop('outerHTML');
                $('#hf-field-options-' + fieldId).append(hashFormAdmin.renderOptionTemplate(template, optKey));
                hashFormAdmin.resetDisplayedOpts(fieldId);
                return;
            }

            //Update hidden field
            document.getElementById('other_input_' + fieldId).value = 1;

            //Hide "Add Other" option now if this is radio field
            const ftype = $(this).data('ftype');
            if (ftype === 'radio' || ftype === 'select') {
                $(this).fadeOut('slow');
            }

            $.post(ajaxurl, {
                action: 'hf-add-field_option',
                field_id: fieldId,
                opt_key: optKey,
                opt_type: optType,
                backend_nonce: hashform_backend_js.nonce
            }, function (msg) {
                $('#hf-field-options-' + fieldId).append(msg);
                hashFormAdmin.resetDisplayedOpts(fieldId);
            });
        },

        // The hidden <li> template carries the placeholder key '000' throughout
        // its ids, names and attributes; swap every spelling for the real key.
        renderOptionTemplate: function (template, optKey) {
            const oldKey = OPT_TEMPLATE_KEY;
            return template
                .replace(new RegExp('optkey="' + oldKey + '"', 'g'), 'optkey="' + optKey + '"')
                .replace(new RegExp('-' + oldKey + '_', 'g'), '-' + optKey + '_')
                .replace(new RegExp('-' + oldKey + '"', 'g'), '-' + optKey + '"')
                .replace(new RegExp('\\[' + oldKey + '\\]', 'g'), '[' + optKey + ']')
                .replace('hf-hidden hf-option-template', '');
        },

        deleteFieldOption: function () {
            const parentLi = this.closest('li'),
                parentUl = parentLi.parentNode,
                fieldId = this.getAttribute('data-fid');

            $(parentLi).fadeOut('slow', function () {
                $(parentLi).remove();

                if ($(parentUl).find('.hashform_other_option').length < 1) {
                    const otherInput = document.getElementById('other_input_' + fieldId);
                    if (otherInput !== null) {
                        otherInput.value = 0;
                    }
                    $('#other_button_' + fieldId).fadeIn('slow');
                }
                hashFormAdmin.resetDisplayedOpts(fieldId);
            });
        },

        resetOptOnChange: function () {
            const check = $(this);
            const field = hashFormAdmin.getFieldKeyFromOpt(this);
            if (!field) {
                return;
            }

            hashFormAdmin.resetSingleOpt(field.fieldId, field.fieldKey, check.closest('li'));

            setTimeout(function () {
                check.next('input').trigger('change');
            }, 100);
        },

        // Radios cannot normally be unticked; let a click on the checked one clear it.
        maybeUncheckRadio: function () {
            const $self = $(this);

            if (!$self.is(':checked')) {
                $self.closest('li').siblings().find('.hf-choice-input').prop('checked', false);
                return;
            }

            const unbind = function () {
                $self.off('mouseup', up);
            };

            const up = function () {
                setTimeout(function () {
                    $self.prop('checked', false);
                }, 0);
                unbind();
            };

            $self.on('mouseup', up);
            $self.one('mouseout', unbind);
        },

        /**
         * Draw an HTML field's content on the canvas.
         *
         * Mirrors HashFormHelper::sanitize_html_field_content(): script and
         * style go, then anything that could run. An empty field falls back to
         * the same note the server prints, so the block never collapses to
         * nothing while it is being written.
         */
        renderHtmlFieldPreview: function (target, html) {
            const holder = document.createElement('div');
            holder.innerHTML = html || '';

            holder.querySelectorAll('script, style, iframe, object, embed, link, meta').forEach(
                el => el.remove()
            );

            holder.querySelectorAll('*').forEach(function (el) {
                Array.from(el.attributes).forEach(function (attr) {
                    const name = attr.name.toLowerCase();
                    const value = (attr.value || '').replace(/\s+/g, '').toLowerCase();

                    if (name.indexOf('on') === 0 || value.indexOf('javascript:') === 0) {
                        el.removeAttribute(attr.name);
                    }
                });
            });

            if ('' === holder.textContent.trim() && !holder.querySelector('img, hr, br, input, svg')) {
                const note = target.getAttribute('data-empty-text') || '';
                target.innerHTML = '';
                const empty = document.createElement('div');
                empty.className = 'hf-custom-html-preview';
                empty.textContent = note;
                target.appendChild(empty);

                return;
            }

            target.innerHTML = holder.innerHTML;
        },

        /**
         * Keep the lists that name other fields in step with a renamed one.
         *
         * Only Must Match Field today; it takes the field id from the input's
         * own name, so any list keyed the same way is picked up by adding its
         * selector here.
         */
        syncFieldLabelInLists: function () {
            const match = /field_options\[name_(\d+)\]/.exec(this.name || '');

            if (!match) {
                return;
            }

            const fieldId = match[1];
            const label = this.value;

            $('select[name^="field_options[match_field_"]')
                .find('option[value="' + fieldId + '"]')
                .text(label);
        },

        /**
         * Rebuild the Must Match dropdowns from the server.
         *
         * Which fields may be offered depends on rules that live in PHP - same
         * type, or one side with no format constraint - so the list is asked
         * for rather than worked out again here, where the two would drift.
         * Called when a field is added or removed; a rename is handled by
         * syncFieldLabelInLists() without a request.
         */
        refreshMatchFieldOptions: function () {
            const $selects = $('.hf-match-field-select');

            if (!$selects.length) {
                return;
            }

            const formId = $('#hf-form-id').val() || $('input[name="id"]').val();

            if (!formId) {
                return;
            }

            $.post(ajaxurl, {
                action: 'hashform_match_field_options',
                form_id: formId,
                nonce: hashform_backend_js.nonce
            }, function (response) {
                if (!response || !response.success) {
                    return;
                }

                $selects.each(function () {
                    const $select = $(this);
                    const fieldId = String($select.data('fid'));
                    const options = response.data[fieldId];

                    if (!options) {
                        return;
                    }

                    // Kept across the rebuild: the chosen field is still the
                    // chosen field unless it has just been deleted.
                    const chosen = $select.val();
                    const $none = $select.find('option[value=""]').first();
                    const noneText = $none.length ? $none.text() : '';

                    $select.empty().append($('<option>', {value: '', text: noneText}));

                    $.each(options, function (id, label) {
                        $select.append($('<option>', {value: id, text: label}));
                    });

                    if (chosen && $select.find('option[value="' + chosen + '"]').length) {
                        $select.val(chosen);
                    }
                });
            });
        },

        setupFieldOptionSorting: function (sort) {
            $(sort).sortable({
                items: 'li',
                axis: 'y',
                opacity: 0.65,
                forcePlaceholderSize: false,
                handle: '.hf-drag',
                helper: function (e, li) {
                    if (li.find('input[type="radio"]:checked, input[type="checkbox"]:checked').length > 0) {
                        isCheckedField = true;
                    }
                    copyHelper = li.clone().insertAfter(li);
                    return li.clone();
                },
                stop: function (e, ui) {
                    copyHelper && copyHelper.remove();

                    const fieldId = ui.item.attr('id').replace('hf-option-list-', '').replace('-' + ui.item.data('optkey'), '');
                    hashFormAdmin.resetDisplayedOpts(fieldId);

                    // Sorting clones the row, which drops the checked state.
                    if (isCheckedField) {
                        ui.item.find('input[type="radio"], input[type="checkbox"]').prop('checked', true);
                        ui.item.find('input[type="radio"]').trigger('click');
                        isCheckedField = false;
                    }
                }
            });
        },

        /* -------------------------------------------------------------------
         * Field options: reading the settings panel
         * ---------------------------------------------------------------- */

        getFieldKeyFromOpt: function (object) {
            const allOpts = $(object).closest('.hf-option-list');
            if (!allOpts.length) {
                return false;
            }

            return {
                fieldId: allOpts.attr('id').replace('hf-field-options-', ''),
                fieldKey: allOpts.data('key')
            };
        },

        usingSeparateValues: function (fieldId) {
            const field = document.getElementById('separate_value_' + fieldId);
            return field === null ? false : field.checked;
        },

        getMultipleOpts: function (fieldId) {
            const opts = [],
                hasImageOptions = document.getElementsByName('field_options[select_option_type_' + fieldId + ']').length > 0,
                optVals = $('input[name^="field_options[options_' + fieldId + ']"]'),
                separateValues = hashFormAdmin.usingSeparateValues(fieldId);

            for (let i = 0; i < optVals.length; i++) {
                const name = optVals[i].name;
                if (SKIPPED_OPT_PARTS.some((part) => name.indexOf(part) > 0)) {
                    continue;
                }

                let saved = optVals[i].value;
                let label = saved;
                const key = name.replace('field_options[options_' + fieldId + '][', '').replace('[label]', '').replace(']', '');

                if (separateValues) {
                    saved = $('input[name="' + name.replace('[label]', '[value]') + '"]').val();
                }

                const checked = hashFormBuilder.getChecked(optVals[i].getAttribute('class'));

                if (hasImageOptions) {
                    const imageUrl = hashFormBuilder.getImageUrlFromInput(optVals[i]);
                    label = hashFormBuilder.getImageLabel(label, false, imageUrl);
                }

                opts.push({
                    saved: saved,
                    label: label,
                    checked: checked,
                    key: key
                });
            }
            return opts;
        },

        getFieldOptions: function (fieldId) {
            const list = document.getElementById('hf-field-options-' + fieldId);

            // Not every field keeps its options here: a repeater's columns
            // live in hf-field-options-repeater-<id>. Asking for a list that
            // is not there used to throw.
            if (!list) {
                return [];
            }

            const listItems = list.querySelectorAll('.hashform_single_option'),
                options = [];

            for (let index = 0; index < listItems.length; index++) {
                const li = listItems[index];
                if (li.classList.contains('hf-hidden')) {
                    continue;
                }
                options.push(li.querySelector('.field_' + fieldId + '_option').value);
            }
            return options;
        },

        getHighestOptKey: function (fieldId) {
            const opts = $('#hf-field-options-' + fieldId + ' li');
            let optKey = 0,
                lastKey = 0;

            for (let i = 0; i < opts.length; i++) {
                optKey = opts[i].getAttribute('data-optkey');
                if (opts.length === 1) {
                    return optKey;
                }
                if (optKey !== OPT_TEMPLATE_KEY) {
                    optKey = parseInt(optKey.replace('other_', ''), 10);
                }

                if (!isNaN(lastKey) && (optKey > lastKey || lastKey === OPT_TEMPLATE_KEY)) {
                    lastKey = optKey;
                }
            }
            return lastKey;
        },

        /* -------------------------------------------------------------------
         * Field options: repainting the live preview
         * ---------------------------------------------------------------- */

        resetSingleOpt: function (fieldId, fieldKey, thisOpt) {
            const optKey = thisOpt.data('optkey'),
                separateValues = hashFormAdmin.usingSeparateValues(fieldId),
                single = $('label[for="field_' + fieldKey + '-' + optKey + '"]'),
                baseName = 'field_options[options_' + fieldId + '][' + optKey + ']';

            let label = $('input[name="' + baseName + '[label]"]');

            if (single.length < 1) {
                hashFormAdmin.resetDisplayedOpts(fieldId);

                // Set the default value.
                const defaultVal = thisOpt.find('input[name^="default_value_"]');
                if (defaultVal.is(':checked') && label.length > 0) {
                    $('select[name^="item_meta[' + fieldId + ']"]').val(label.val());
                }
                return;
            }

            const previewInput = single.children('input');
            let saved;

            if (label.length < 1) {
                // Check for other label.
                label = $('input[name="' + baseName + '"]');
                saved = label.val();
            } else if (separateValues) {
                saved = $('input[name="' + baseName + '[value]"]').val();
            } else {
                saved = label.val();
            }

            if (label.length < 1) {
                return;
            }

            // Set the displayed value.
            const text = single[0].childNodes;
            text[text.length - 1].nodeValue = ' ' + label.val();
            previewInput.closest('.hf-choice').find('.hf-field-is-label').text(saved);

            // Set saved value.
            previewInput.val(saved);

            // Set the default value.
            previewInput.prop('checked', thisOpt.find('input[name^="default_value_"]').is(':checked'));
        },

        resetDisplayedOpts: function (fieldId) {
            const input = $('[name^="item_meta[' + fieldId + ']"]');

            if (input.length < 1) {
                return;
            }

            if (input.is('select')) {
                const selectedValDefault = input.val();
                const placeholder = document.getElementById('hf-placeholder-' + fieldId);

                const atts = {sourceID: fieldId};
                if (placeholder !== null && placeholder.value !== '') {
                    atts.placeholder = placeholder.value;
                }
                hashFormAdmin.fillDropdownOpts(input[0], atts);

                // Re-query: fillDropdownOpts has just rewritten the options.
                const refreshed = $('[name^="item_meta[' + fieldId + ']"]');

                /*
                 * Whether the option that was selected is still among them.
                 * This asked the element whether it contained the value, and
                 * Node.contains() takes a node, not a string, so deleting an
                 * option threw and left the rest of this undone.
                 */
                const stillOffered = refreshed.length > 0 && $.makeArray(refreshed[0].options || []).some(
                    option => option.value === selectedValDefault
                );

                if (stillOffered) {
                    refreshed.val(selectedValDefault);
                }

                // Anything attached to this select is now looking at options
                // that no longer exist and has to be told to re-read them.
                if (typeof hashFormBuilder !== 'undefined' && hashFormBuilder.notifyFieldMarkupChanged) {
                    hashFormBuilder.notifyFieldMarkupChanged(fieldId);
                }
            } else {
                const opts = hashFormAdmin.getMultipleOpts(fieldId);
                const type = input.attr('type');
                const fieldInfo = hashFormAdmin.getFieldKeyFromOpt($('#hf-option-list-' + fieldId + '-000'));
                const container = $('#hf-editor-field-container-' + fieldId + ' .hf-choice-container');

                container.html('');
                for (let i = 0; i < opts.length; i++) {
                    container.append(hashFormAdmin.addRadioCheckboxOpt(type, opts[i], fieldId, fieldInfo.fieldKey));
                }
            }

            hashFormAdmin.adjustConditionalLogicOptionOrders(fieldId);
        },

        fillDropdownOpts: function (field, atts) {
            if (field === null) {
                return;
            }

            const showOther = atts.other;
            const placeholder = atts.placeholder;

            hashFormAdmin.removeDropdownOpts(field);

            const opts = hashFormAdmin.getMultipleOpts(atts.sourceID);
            let hasPlaceholder = (typeof placeholder !== 'undefined');

            for (let i = 0; i < opts.length; i++) {
                let label = opts[i].label;
                const isOther = opts[i].key.indexOf('other') !== -1;

                if (hasPlaceholder && label !== '') {
                    hashFormBuilder.addBlankSelectOption(field, placeholder);
                } else if (hasPlaceholder) {
                    label = placeholder;
                }
                hasPlaceholder = false;

                if (!isOther || showOther) {
                    const opt = document.createElement('option');
                    opt.value = opts[i].saved;
                    opt.innerHTML = label;
                    field.appendChild(opt);
                }
            }
        },

        removeDropdownOpts: function (field) {
            if (typeof field.options === 'undefined') {
                return;
            }

            for (let i = field.options.length - 1; i >= 0; i--) {
                field.remove(i);
            }
        },

        addRadioCheckboxOpt: function (type, opt, fieldId, fieldKey) {
            const id = 'hf-field-' + fieldKey + '-' + opt.key;

            return '<div class="hf-choice hf-' + type + '" id="hf-' + type + '-' + fieldId + '-' + opt.key + '"><label for="' + id +
                '"><input type="' + type +
                '" name="item_meta[' + fieldId + ']' + (type === 'checkbox' ? '[]' : '') +
                '" value="' + opt.saved + '" id="' + id + '"' + (opt.checked ? ' checked="checked"' : '') + '> ' + opt.label + '</label>' +
                '</div>';
        },

        adjustConditionalLogicOptionOrders: function (fieldId) {
            const rows = document.getElementById('hf-wrap').querySelectorAll('.hashform_logic_row'),
                fieldOptions = hashFormAdmin.getFieldOptions(fieldId);

            for (let rowIndex = 0; rowIndex < rows.length; rowIndex++) {
                const row = rows[rowIndex];

                // Loose on purpose: the select value is a string, fieldId a number.
                if (row.querySelector('.hashform_logic_field_opts').value != fieldId) {
                    continue;
                }

                const logicId = row.id.split('_')[2];
                const valueSelect = row.querySelector('select[name="field_options[hide_opt_' + logicId + '][]"]');

                // Walk backwards and prepend, so the options end up in source order.
                for (let optionIndex = fieldOptions.length - 1; optionIndex >= 0; optionIndex--) {
                    const expectedOption = fieldOptions[optionIndex];
                    let optionMatch = valueSelect.querySelector('option[value="' + expectedOption + '"]');

                    if (optionMatch === null) {
                        optionMatch = document.createElement('option');
                        optionMatch.setAttribute('value', expectedOption);
                        optionMatch.textContent = expectedOption;
                    }

                    valueSelect.prepend(optionMatch);
                }

                // Keep the blank option first.
                const blank = valueSelect.querySelector('option[value=""]');
                if (blank !== null) {
                    valueSelect.prepend(blank);
                }
            }
        },

        /* -------------------------------------------------------------------
         * Modals: bulk options and new form
         * ---------------------------------------------------------------- */

        initModal: function (id, width) {
            const $info = $(id);
            if (!$info.length) {
                return false;
            }
            if (typeof width === 'undefined') {
                width = '550px';
            }

            $info.dialog({
                dialogClass: 'hf-dialog',
                modal: true,
                autoOpen: false,
                closeOnEscape: true,
                width: width,
                resizable: false,
                draggable: false,
                open: function () {
                    $('.ui-dialog-titlebar').addClass('hf-hidden').removeClass('ui-helper-clearfix');
                    $('#wpwrap').addClass('hashform_overlay');
                    $('.hf-dialog').removeClass('ui-widget ui-widget-content ui-corner-all');
                    $info.removeClass('ui-dialog-content ui-widget-content');
                    hashFormAdmin.bindClickForDialogClose($info);
                },
                close: function () {
                    $('#wpwrap').removeClass('hashform_overlay');
                    $('.spinner').css('visibility', 'hidden');

                    this.removeAttribute('data-option-type');
                    const optionType = document.getElementById('bulk-option-type');
                    if (optionType) {
                        optionType.value = '';
                    }
                }
            });
            return $info;
        },

        bindClickForDialogClose: function ($modal) {
            const closeModal = function () {
                $modal.dialog('close');
            };
            $('.ui-widget-overlay').on('click', closeModal);
            // Tag-agnostic: the close control is a <button> now, so that it can
            // be reached by keyboard, and Cancel carries the same class.
            $modal.on('click', '.dismiss', closeModal);
        },

        initBulkOptionsOverlay: function () {
            const $info = hashFormAdmin.initModal('#hf-bulk-edit-modal', '700px');
            if ($info === false) {
                return;
            }

            $('.hf-insert-preset').on('click', function (event) {
                const opts = JSON.parse(this.getAttribute('data-opts'));
                event.preventDefault();
                document.getElementById('hf-bulk-options').value = opts.join('\n');
                return false;
            });

            $buildForm.on('click', 'a.hf-bulk-edit-link', function (event) {
                event.preventDefault();

                const fieldId = $(this).closest('[data-fid]').data('fid'),
                    separate = hashFormAdmin.usingSeparateValues(fieldId),
                    optList = document.getElementById('hf-field-options-' + fieldId);

                if (!optList) {
                    return;
                }

                const opts = optList.getElementsByTagName('li');
                document.getElementById('bulk-field-id').value = fieldId;

                let content = '';
                for (let i = 0; i < opts.length; i++) {
                    const key = opts[i].getAttribute('data-optkey');
                    if (key !== OPT_TEMPLATE_KEY) {
                        const label = document.getElementsByName('field_options[options_' + fieldId + '][' + key + '][label]')[0];
                        if (typeof label !== 'undefined') {
                            content += label.value;
                            if (separate) {
                                content += '|' + document.getElementsByName('field_options[options_' + fieldId + '][' + key + '][value]')[0].value;
                            }
                            content += '\r\n';
                        }
                    }

                    // Kept inside the loop: an empty list must not clear the textarea.
                    if (i >= opts.length - 1) {
                        document.getElementById('hf-bulk-options').value = content;
                    }
                }
                $info.dialog('open');
                return false;
            });

            $('#hf-update-bulk-options').on('click', function () {
                const fieldId = document.getElementById('bulk-field-id').value;
                if (document.getElementById('bulk-option-type').value) {
                    return;
                }

                this.classList.add('hf-loading-button');

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'hashform_import_options',
                        field_id: fieldId,
                        opts: document.getElementById('hf-bulk-options').value,
                        separate: hashFormAdmin.usingSeparateValues(fieldId),
                        backend_nonce: hashform_backend_js.nonce
                    },
                    success: function (html) {
                        document.getElementById('hf-field-options-' + fieldId).innerHTML = html;
                        hashFormAdmin.resetDisplayedOpts(fieldId);
                        if (typeof $info !== 'undefined') {
                            $info.dialog('close');
                            document.getElementById('hf-update-bulk-options').classList.remove('hf-loading-button');
                        }
                    }
                });
            });
        },

        initNewFormModal: function () {
            const MODALS = '#hf-add-form-modal, #hf-shortcode-form-modal';

            function showError(message) {
                $('#hf-add-template').find('.hf-form-row').addClass('has-error');
                $('#hf-add-template').find('.hf-modal-error').text(message).addClass('is-visible');
            }

            function clearError() {
                $('#hf-add-template').find('.hf-form-row').removeClass('has-error');
                $('#hf-add-template').find('.hf-modal-error').text('').removeClass('is-visible');
            }

            function closeModals() {
                $(MODALS).removeClass('hf-open').attr('aria-hidden', 'true');
                $('body').removeClass('hf-modal-open');
            }

            $(document).on('click', '.hf-trigger-modal', (e) => {
                e.preventDefault();
                clearError();
                $('#hf-add-form-modal').addClass('hf-open').attr('aria-hidden', 'false');
                $('body').addClass('hf-modal-open');

                // Naming the form is the only decision in this dialog, so put
                // the caret in the field rather than making them click it.
                window.setTimeout(() => $('#hf-form-name').trigger('focus'), 50);
            });

            $(document).on('click', '.hashform-close-form-modal', (e) => {
                e.preventDefault();
                closeModals();
            });

            // A click on the dimmed area, but not one that bubbled up out of
            // the panel sitting on top of it.
            $(document).on('click', MODALS, function (e) {
                if (e.target === this) {
                    closeModals();
                }
            });

            $(document).on('keydown', (e) => {
                var $open = $(MODALS).filter('.hf-open');

                if (!$open.length) {
                    return;
                }

                if (e.key === 'Escape') {
                    closeModals();
                    return;
                }

                // Focus trap. Without it Tab walks out of the dialog and into
                // the page behind, which is still there and still clickable.
                if (e.key !== 'Tab') {
                    return;
                }

                var $focusable = $open
                        .find('a[href], button, input, select, textarea, [tabindex]')
                        .filter(':visible')
                        .filter(function () {
                            return !this.disabled && $(this).attr('tabindex') !== '-1';
                        });

                if (!$focusable.length) {
                    return;
                }

                var first = $focusable.get(0);
                var last = $focusable.get($focusable.length - 1);

                // Wrap at whichever end the caret is about to leave from.
                if (e.shiftKey && (document.activeElement === first || !$open.get(0).contains(document.activeElement))) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            });

            $(document).on('input', '#hf-form-name', clearError);

            $(document).on('submit', '#hf-add-template', function (event) {
                event.preventDefault();

                const $template = $(this);
                const $button = $template.find('button[type="submit"]');
                const $name = $template.find('input[name=template_name]');
                const name = ($name.val() || '').trim();

                if ($button.hasClass('hashform-updating')) {
                    return;
                }

                if (!name) {
                    showError(hashform_backend_js.form_name_required);
                    $name.trigger('focus');
                    return;
                }

                $button.addClass('hashform-updating');
                clearError();

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        action: 'hashform_create_form',
                        name: name,
                        backend_nonce: hashform_backend_js.nonce
                    }
                }).done(function (response) {
                    let res = response;

                    if (typeof res === 'string') {
                        try {
                            res = JSON.parse(res);
                        } catch (err) {
                            res = null;
                        }
                    }

                    if (res && res.redirect) {
                        window.location = res.redirect;
                        return;
                    }

                    // Leaving the button spinning forever is worse than
                    // saying nothing happened.
                    $button.removeClass('hashform-updating');
                    showError((res && res.error) || hashform_backend_js.generic_error);
                }).fail(function () {
                    $button.removeClass('hashform-updating');
                    showError(hashform_backend_js.generic_error);
                });
            });
        },

        /* -------------------------------------------------------------------
         * Conditional logic repeater
         * ---------------------------------------------------------------- */

        /*
         * The rules are numbered in the markup rather than by CSS counter so
         * the number is readable to anything that only sees the DOM, and so
         * it survives a row being deleted from the middle.
         */
        refreshConditionList: function ($list) {
            const $rows = $list.find('.hf-condition-row');

            $rows.each(function (index) {
                $(this).find('.hf-condition-index').text(index + 1);
            });

            $list.toggleClass('hf-condition-list-empty', $rows.length === 0);
        },

        addConditionRepeaterBlock: async function (e) {
            e.preventDefault();

            const $button = $(this);
            const $list = $button.closest('.hf-form-row').find('.hf-condition-list');

            $button.prop('disabled', true);

            await $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'hashform_add_more_condition_block',
                    form_id: $('#form_id').val(),
                    backend_nonce: hashform_backend_js.nonce
                },
                success: function (msg) {
                    const $row = $(msg);
                    $list.find('.hf-condition-rows').append($row);
                    hashFormAdmin.refreshConditionList($list);

                    // Land on the field being shown or hidden: the action
                    // beside it defaults sensibly, that never does.
                    $row.find('.hf-condition-cell-target select').trigger('focus');
                }
            });

            $button.prop('disabled', false);
        },

        removeConditionRepeaterBlock: function () {
            const $list = $(this).closest('.hf-condition-list');
            $(this).closest('.hf-condition-row').remove();
            hashFormAdmin.refreshConditionList($list);
        },

        /* -------------------------------------------------------------------
         * Settings page: field attribute inserter
         * ---------------------------------------------------------------- */

        /* -------------------------------------------------------------------
         * Field-tag picker
         *
         * The list is opened by hover, which leaves it unreachable from the
         * keyboard, and on a form with a couple of dozen fields it is a long
         * scroll with nothing to narrow it down. The markup is shared by the
         * email settings, the Pro integration panels and the calculation
         * field, so this upgrades whatever is on the page rather than each
         * template carrying its own copy.
         * ---------------------------------------------------------------- */

        // Below this many fields the list is short enough to read at a glance.
        SEARCH_FROM: 8,

        initFieldAttrPickers: function () {
            $('.hf-attr-field').each(function () {
                const $field = $(this);
                const $trigger = $field.find('.hf-attr-field-tags').first();
                const $list = $field.find('.hf-add-field-attr-to-form').first();

                if (!$trigger.length || !$list.length || $field.data('hfAttrReady')) {
                    return;
                }

                $field.data('hfAttrReady', true);

                // The trigger is a <div> in every template. Rather than edit
                // a dozen of them, it is given the button semantics here.
                $trigger.attr({ role: 'button', tabindex: 0, 'aria-haspopup': 'listbox' });

                const $options = $list.children('li');

                $options.attr({ role: 'option', tabindex: -1 });

                if (!$options.length) {
                    $list.append($('<li class="hf-attr-empty"></li>').text(hashform_backend_js.no_tag_fields));
                    return;
                }

                $list.append($('<li class="hf-attr-empty hf-hidden"></li>').text(hashform_backend_js.no_tag_matches));

                if ($options.length >= hashFormAdmin.SEARCH_FROM) {
                    $('<li class="hf-attr-search"><input type="search" autocomplete="off" /></li>')
                        .prependTo($list)
                        .find('input')
                        .attr('placeholder', hashform_backend_js.search_fields);
                }
            });
        },

        keyFieldAttrPicker: function (e) {
            const $field = $(this);

            if ('Escape' === e.key) {
                $field.find('.hf-attr-field-tags').trigger('blur');
                $field.find('.hf-attr-search input').val('').trigger('input');
                return;
            }

            // Enter and Space on the trigger open the list, which hover does
            // for a pointer; on a row they choose it.
            if ('Enter' !== e.key && ' ' !== e.key) {
                return;
            }

            const $target = $(e.target);

            if ($target.is('.hf-add-field-attr-to-form li')) {
                e.preventDefault();
                $target.trigger('click');
                return;
            }

            if ($target.is('.hf-attr-field-tags')) {
                e.preventDefault();
                $field.find('.hf-add-field-attr-to-form li').not('.hf-attr-search, .hf-attr-empty, .hf-hidden').first().trigger('focus');
            }
        },

        filterFieldAttrOptions: function () {
            const term = $(this).val().toLowerCase().trim();
            const $list = $(this).closest('.hf-add-field-attr-to-form');
            let matches = 0;

            $list.children('li').not('.hf-attr-search, .hf-attr-empty').each(function () {
                const hit = '' === term || $(this).text().toLowerCase().indexOf(term) >= 0;
                $(this).toggleClass('hf-hidden', !hit);
                matches += hit ? 1 : 0;
            });

            $list.children('.hf-attr-empty').toggleClass('hf-hidden', 0 !== matches);
        },

        /*
         * The trigger for a field near the bottom of the panel would open a
         * list that ran past the window, with its last rows unreachable.
         * Measured on open, since the panel scrolls under it.
         */
        placeFieldAttrList: function () {
            const $field = $(this);
            const $list = $field.find('.hf-add-field-attr-to-form').first();

            if (!$list.length) {
                return;
            }

            const box = $field[0].getBoundingClientRect();
            const bounds = hashFormAdmin.clippingBounds($field[0]);

            $field.toggleClass('hf-attr-up', window.innerHeight - box.bottom < Math.min($list.prop('scrollHeight'), 320) + 16);
            $field.toggleClass('hf-attr-left', box.right - $list.outerWidth() < bounds.left);
        },

        /*
         * The nearest ancestor that would cut the list off. The builder's
         * field editor sidebar scrolls, so a list wider than the distance
         * from the trigger to its left edge disappears into the overflow.
         */
        clippingBounds: function (el) {
            for (let node = el.parentElement; node && node !== document.body; node = node.parentElement) {
                if ('visible' !== getComputedStyle(node).overflowX) {
                    return node.getBoundingClientRect();
                }
            }

            return { left: 0, right: document.documentElement.clientWidth };
        },

        addFieldAttrToForm: function () {
            const fieldId = $(this).attr('data-value');
            const $row = $(this).closest('.hf-form-row');
            const inputChange = $row.find('input').not('.hf-attr-search input');
            const textAreaChange = $row.find('textarea');

            // Note: .val(x) writes one value to every match, seeded from the
            // first one. Preserved from the original.
            if (fieldId && inputChange.length > 0) {
                inputChange.val(inputChange.val() + ' ' + fieldId);
            }

            if (fieldId && textAreaChange.length > 0) {
                textAreaChange.val(textAreaChange.val() + ' ' + fieldId);
            }

            // Hover alone would keep the list open over the field just
            // written to, so it is dismissed and the field takes focus.
            const $list = $(this).closest('.hf-add-field-attr-to-form');
            $list.find('.hf-attr-search input').val('').trigger('input');
            $list.closest('.hf-attr-field').find('.hf-attr-field-tags').trigger('blur');
            (textAreaChange.length ? textAreaChange : inputChange).first().trigger('focus');
        },

        /* -------------------------------------------------------------------
         * Time field
         * ---------------------------------------------------------------- */

        // Settings store 24h HH:MM; anything else is reset rather than guessed at.
        clampTimeValue: function (input) {
            if (input.val() && !input.val().match(/^(2[0-3]|[01][0-9]):[0-5][0-9]$/)) {
                input.val('00:00');
            }
        },

        addTimeDefaultValue: function () {
            const that = $(this);
            hashFormAdmin.clampTimeValue(that);

            const fieldId = fieldIdOf(this);
            const [hourString, minute] = that.val().split(':');
            const hour = +hourString % 24;

            $('#hf-editor-field-container-' + fieldId + ' .hf-timepicker')
                .val(minute && (hour % 12 || 12) + ':' + minute + (hour < 12 ? 'am' : 'pm'));
        },

        validateTimeValue: function () {
            const that = $(this);
            hashFormAdmin.clampTimeValue(that);
            that.trigger('input');
        },

        /* -------------------------------------------------------------------
         * Validators
         * ---------------------------------------------------------------- */

        isEmail: function (email) {
            const regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        },

        isUrl: function (url) {
            const regex = /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;
            return regex.test(url);
        }
    };

    $(function () {
        hashFormAdmin.init();
    });
})(jQuery);


/**
 * NOTE: this shadows Node.prototype.contains for <select> elements. Kept as-is;
 * resetDisplayedOpts() depends on this value-lookup behaviour.
 */
HTMLSelectElement.prototype.contains = function (value) {
    for (var i = 0, l = this.options.length; i < l; i++) {
        if (this.options[i].value == value) {
            return true;
        }
    }
    return false;
};


/**
 * Entry workflow: starring, private notes and resending a notification.
 * Kept separate from hashFormAdmin so it stays self contained.
 */
(function ($) {
    'use strict';

    function vars() {
        return (typeof hashform_backend_js === 'undefined') ? {} : hashform_backend_js;
    }

    function post(action, data) {
        return $.post(ajaxurl, $.extend({
            action: action,
            nonce: vars().entry_nonce
        }, data));
    }

    /*
     * The export lives inside the settings form in the markup, because HTML
     * will not nest one form in another. Rather than submit the settings form,
     * the fields are copied into a form of the export's own, attached to the
     * document just long enough to post, so the settings on screen are left
     * exactly as they were.
     */
    $(document).on('click', '#hashform_export', function (e) {
        e.preventDefault();

        const $trigger = $(this);
        const $form = $('<form>', { method: 'post', action: window.location.href })
                .css('display', 'none');

        $trigger.closest('.hf-imex-export, .hf-box, div')
                .find('input[type="hidden"]')
                .each(function () {
                    $form.append($('<input>', { type: 'hidden', name: this.name, value: this.value }));
                });

        $form.appendTo('body').trigger('submit');
        $form.remove();
    });

    $(document).on('click', '.hf-entry-star', function (e) {
        e.preventDefault();

        var $button = $(this);

        if ($button.hasClass('hf-star-busy')) {
            return;
        }

        // Flip straight away; the request only confirms it.
        var starred = $button.attr('data-starred') === '1' ? 0 : 1;

        $button.addClass('hf-star-busy')
                .toggleClass('hf-starred', starred === 1)
                .attr({'data-starred': starred, 'aria-pressed': starred ? 'true' : 'false'})
                .find('.dashicons')
                .toggleClass('dashicons-star-filled', starred === 1)
                .toggleClass('dashicons-star-empty', starred === 0);

        // Put it back the way it was.
        var revert = function () {
            var reverted = starred ? 0 : 1;
            $button.toggleClass('hf-starred', reverted === 1)
                    .attr({'data-starred': reverted, 'aria-pressed': reverted ? 'true' : 'false'})
                    .find('.dashicons')
                    .toggleClass('dashicons-star-filled', reverted === 1)
                    .toggleClass('dashicons-star-empty', reverted === 0);
        };

        post('hashform_toggle_star', {
            entry_id: $button.attr('data-entry'),
            starred: starred
        }).done(function (response) {
            /*
             * A refusal from the server still arrives as a successful request:
             * wp_send_json_error() answers 200, so it lands here rather than in
             * fail(). Only the transport was being checked, which left a star
             * the server had declined — a missing entry, a capability check, a
             * stale nonce — sitting there looking saved.
             */
            if (!response || !response.success) {
                revert();
            }
        }).fail(revert).always(function () {
            $button.removeClass('hf-star-busy');
        });
    });

    $(document).on('click', '.hf-entry-note-save', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $wrap = $button.closest('.hf-entry-notes');
        var $status = $wrap.find('.hf-entry-note-status');

        $button.prop('disabled', true);

        post('hashform_save_entry_note', {
            entry_id: $wrap.attr('data-entry'),
            note: $wrap.find('textarea').val()
        }).done(function (response) {
            // The failure message used to be written into the same element
            // without this class, so a note that had not saved was reported in
            // the colour reserved for one that had.
            var saved = !!(response && response.success);
            $status.toggleClass('hf-entry-status-error', !saved).text(saved ? vars().note_saved : vars().note_error);
        }).fail(function () {
            $status.addClass('hf-entry-status-error').text(vars().note_error);
        }).always(function () {
            $button.prop('disabled', false);
            // Each save clears the previous countdown. Without this a second
            // save shortly after the first inherited the first one's timer and
            // had its message wiped almost immediately.
            window.clearTimeout($status.data('hf-clear-timer'));
            $status.data('hf-clear-timer', window.setTimeout(function () {
                $status.text('').removeClass('hf-entry-status-error');
            }, 4000));
        });
    });

    $(document).on('click', '.hf-entry-resend', function (e) {
        e.preventDefault();

        if (!window.confirm(vars().resend_confirm)) {
            return;
        }

        var $button = $(this);
        var $status = $button.closest('.hf-entry-resend-wrap').find('.hf-entry-resend-status');

        $button.prop('disabled', true);
        $status.text('');

        post('hashform_resend_notification', {
            entry_id: $button.attr('data-entry')
        }).done(function (response) {
            var message = response && response.data && response.data.message;
            $status.text(message || vars().generic_error);
            $status.toggleClass('hf-entry-status-error', !(response && response.success));
        }).fail(function () {
            $status.text(vars().generic_error).addClass('hf-entry-status-error');
        }).always(function () {
            $button.prop('disabled', false);
        });
    });
})(jQuery);




/**
 * Click-to-copy chips, used by the shortcode column on the Forms list.
 */
(function ($) {
    'use strict';

    var RESET_AFTER = 2000;

    /**
     * navigator.clipboard only exists in a secure context, which rules it out
     * on plain-http admin installs, so fall back to a throwaway textarea.
     * Returns a promise either way.
     */
    function copyText(text) {
        if (window.isSecureContext && window.navigator && navigator.clipboard) {
            return navigator.clipboard.writeText(text);
        }

        var $helper = $('<textarea readonly></textarea>')
                .val(text)
                .css({ position: 'fixed', top: '-9999px', opacity: 0 })
                .appendTo('body'),
            copied = false;

        $helper[0].select();

        try {
            copied = document.execCommand('copy');
        } catch (err) {
            copied = false;
        }

        $helper.remove();

        return copied ? $.Deferred().resolve().promise() : $.Deferred().reject().promise();
    }

    // Read-only embed fields: selecting on focus means the value can still be
    // taken by hand if the browser refuses clipboard access.
    $(document).on('focus', '.hf-embed-input', function () {
        this.select();
    });

    $(document).on('click', '[data-hf-clipboard]', function (e) {
        e.preventDefault();

        var $chip = $(this);

        copyText($chip.attr('data-hf-clipboard')).then(function () {
            $chip.addClass('is-copied');

            // Restart the timer on a repeat click rather than letting the
            // first one clear the state early.
            clearTimeout($chip.data('hfCopyTimer'));
            $chip.data('hfCopyTimer', setTimeout(function () {
                $chip.removeClass('is-copied');
            }, RESET_AFTER));
        }, function () {
            // Copying was refused or unsupported. Select the text so the
            // shortcode can still be copied by hand.
            window.getSelection().selectAllChildren($chip.find('code')[0] || $chip[0]);
        });
    });
})(jQuery);
