var hashFormBuilder = hashFormBuilder || {};

(function ($) {

    'use strict';

    /* -----------------------------------------------------------------------
     * Module state
     * -------------------------------------------------------------------- */

    const $editorFieldsWrap = $('#hf-editor-fields');
    const buildForm = document.getElementById('hf-fields-form');
    const currentFormId = $('#hf-form-id').val();

    // Counter behind the temporary ids given to fields while they load.
    let autoId = 0;

    /* -----------------------------------------------------------------------
     * WYSIWYG editor
     * -------------------------------------------------------------------- */

    const wysiwyg = {
        init(editor, {setupCallback, height, addFocusEvents} = {}) {
            if (isTinyMceActive()) {
                setTimeout(resetTinyMce, 0);
            } else {
                initQuickTagsButtons();
            }

            setUpTinyMceVisualButtonListener();
            setUpTinyMceHtmlButtonListener();

            function initQuickTagsButtons() {
                if ('function' !== typeof window.quicktags || typeof window.QTags.instances[editor.id] !== 'undefined') {
                    return;
                }

                const id = editor.id;
                window.quicktags({
                    name: 'qt_' + id,
                    id: id,
                    canvas: editor,
                    settings: {id},
                    toolbar: document.getElementById('qt_' + id + '_toolbar'),
                    theButtons: {}
                });
            }

            function initRichText() {
                // Clone the settings WordPress prepared for the first editor on the page.
                const key = Object.keys(tinyMCEPreInit.mceInit)[0];
                const orgSettings = tinyMCEPreInit.mceInit[key];

                const settings = Object.assign({}, orgSettings, {
                    selector: '#' + editor.id,
                    body_class: orgSettings.body_class.replace(key, editor.id)
                });

                settings.setup = mceEditor => {
                    if (addFocusEvents) {
                        // Forward the first focus of each visit to the textarea, then wait
                        // for a focusout before arming the listener again.
                        const focusInCallback = () => {
                            $(mceEditor.targetElm).trigger('focusin');
                            mceEditor.off('focusin', '**');
                        };

                        mceEditor.on('focusin', focusInCallback);
                        mceEditor.on('focusout', () => mceEditor.on('focusin', focusInCallback));
                    }
                    if (setupCallback) {
                        setupCallback(mceEditor);
                    }
                };

                if (height) {
                    settings.height = height;
                }

                tinymce.init(settings);
            }

            function resetTinyMce() {
                tinymce.EditorManager.execCommand('mceRemoveEditor', true, editor.id);
                initRichText();
            }

            function isTinyMceActive() {
                const wrapper = document.getElementById('wp-' + editor.id + '-wrap');
                return null !== wrapper && wrapper.classList.contains('tmce-active');
            }

            function setUpTinyMceVisualButtonListener() {
                $(document).on('click', '#' + editor.id + '-html', function () {
                    editor.style.visibility = 'visible';
                    initQuickTagsButtons();
                });
            }

            function setUpTinyMceHtmlButtonListener() {
                $('#' + editor.id + '-tmce').on('click', handleTinyMceHtmlButtonClick);
            }

            function handleTinyMceHtmlButtonClick() {
                if (isTinyMceActive()) {
                    resetTinyMce();
                } else {
                    initRichText();
                }

                const wrap = document.getElementById('wp-' + editor.id + '-wrap');
                wrap.classList.add('tmce-active');
                wrap.classList.remove('html-active');
            }
        }
    };

    /* -----------------------------------------------------------------------
     * Shared helpers
     * -------------------------------------------------------------------- */

    function editorWrap() {
        return document.getElementById('hf-editor-wrap');
    }

    /**
     * Mark whether the form has any fields yet.
     *
     * The class lives on the canvas, and the "drag a field in" hint lives in
     * the sidebar, so the two cannot see each other in CSS. Mirroring it onto
     * the body lets the hint retire once there is something to look at, and
     * come back if the last field is deleted.
     */
    function setHasFields(hasFields) {
        const wrap = editorWrap();

        // The settings and style screens load this script without a canvas.
        if (!wrap) {
            return;
        }

        wrap.classList.toggle('hf-editor-has-fields', hasFields);
        document.body.classList.toggle('hf-form-has-fields', hasFields);
    }

    /**
     * The move and delete controls for a columns row.
     *
     * A row is built in the browser, so it never passed through the PHP that
     * stamps these onto a field: there was no way to move a row or to get rid
     * of one once it had been added.
     */
    function columnRowActions() {
        const move = hashFormBuilder.tag('a', {
            className: 'hf-editor-move-action',
            child: hashFormBuilder.tag('span', {className: 'mdi mdi-cursor-move'})
        });
        move.href = '#';
        move.title = columnRowText('move', 'Move Row');
        move.setAttribute('aria-label', move.title);

        const remove = hashFormBuilder.tag('a', {
            className: 'hf-editor-row-delete',
            child: hashFormBuilder.tag('span', {className: 'mdi mdi-trash-can-outline'})
        });
        remove.href = '#';
        remove.title = columnRowText('delete', 'Delete Row');
        remove.setAttribute('aria-label', remove.title);

        return hashFormBuilder.div({
            className: 'hf-editor-action-buttons hf-editor-row-actions',
            children: [move, remove]
        });
    }

    function columnRowText(key, fallback) {
        return ('undefined' !== typeof hashform_builder_js && hashform_builder_js[key]) ? hashform_builder_js[key] : fallback;
    }

    function emptyColumnLabel() {
        return 'undefined' === typeof hashform_builder_js ? 'Drop a field here' : hashform_builder_js.drop_field_here;
    }

    // Colour inputs arrive with freshly rendered field markup, so re-scan once the
    // markup has settled.
    function initColorPickers() {
        setTimeout(function () {
            $(document).find('.hf-color-picker').wpColorPicker();
        }, 1000);
    }

    // Where a field should be stored, based on the section it now sits in.
    // getFormIdForFieldPlacement also re-parents stray end dividers, so the calls
    // have to stay in this order.
    function getFieldPlacement(currentItem) {
        const section = hashFormBuilder.getSectionForFieldPlacement(currentItem);
        return {
            formId: hashFormBuilder.getFormIdForFieldPlacement(section),
            sectionId: hashFormBuilder.getSectionIdForFieldPlacement(section)
        };
    }

    function insertFieldRequest(formId, fieldType, onSuccess) {
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'hashform_insert_field',
                form_id: formId,
                field_type: fieldType,
                backend_nonce: hashform_backend_js.nonce,
            },
            success: onSuccess,
            error: hashFormBuilder.handleInsertFieldError
        });
    }

    // Walks the items backwards looking for the first one the cursor has passed;
    // the cursor lands after that item once it is past its midpoint.
    function indexAtMousePosition($items, position, startOf, sizeOf) {
        for (let index = $items.length - 1; index >= 0; --index) {
            const $item = $($items.get(index));
            const start = startOf($item);
            if (position > start) {
                return position > start + (sizeOf($item) / 2) ? index + 1 : index;
            }
        }
        return 0;
    }

    hashFormBuilder = {

        /* -------------------------------------------------------------------
         * Bootstrap
         * ---------------------------------------------------------------- */

        init: function () {
            // The canvas is rendered with the class already set, so the body
            // has to be told once on load as well as on every change.
            setHasFields(!!editorWrap() && editorWrap().classList.contains('hf-editor-has-fields'));
            hashFormBuilder.initBuild();
        },

        initBuild: function () {
            $('ul.hf-fields-list, .hf-fields-list li').disableSelection();

            hashFormBuilder.normalizeColumnRows();
            // Forms saved before the row was written out only learn it here, so
            // opening and saving is enough to bring them up to date.
            hashFormBuilder.syncColumnGroups();
            hashFormBuilder.setupSortable('ul.hf-editor-sorting');
            document.querySelectorAll('.hf-fields-list > li').forEach(hashFormBuilder.makeDraggable);

            $editorFieldsWrap.on('click', 'li.hf-editor-field-box.ui-state-default', hashFormBuilder.clickField);
            $editorFieldsWrap.on('click', '.hf-editor-delete-action', hashFormBuilder.clickDeleteField);
            $editorFieldsWrap.on('click', '.hf-editor-duplicate-action', hashFormBuilder.clickDuplicateField);
            $editorFieldsWrap.on('mousedown', 'input, textarea, select', hashFormBuilder.stopFieldFocus);
            $editorFieldsWrap.on('click', 'input[type=radio], input[type=checkbox]', hashFormBuilder.stopFieldFocus);

            $('#hf-add-fields-panel').on('click', '.hf-field-box', hashFormBuilder.hintToDragField);
            $('#hf-add-fields-panel').on('click', '.hf-add-columns', hashFormBuilder.addColumnsClick);
            $editorFieldsWrap.on('click', '.hf-editor-row-delete', hashFormBuilder.clickDeleteColumnRow);

            hashFormBuilder.renumberMultiSteps();
            hashFormBuilder.resetToFirstStep();
        },

        /* -------------------------------------------------------------------
         * DOM builders
         * ---------------------------------------------------------------- */

        div: function (args) {
            return hashFormBuilder.tag('div', args);
        },

        tag: function (type, args = {}) {
            const output = document.createElement(type);
            if ('string' === typeof args) {
                output.textContent = args;
                return output;
            }

            const {id, className, children, child, text, data} = args;

            if (id) {
                output.id = id;
            }
            if (className) {
                output.className = className;
            }
            if (children) {
                children.forEach(child => output.appendChild(child));
            } else if (child) {
                output.appendChild(child);
            } else if (text) {
                output.textContent = text;
            }
            if (data) {
                Object.keys(data).forEach(dataKey => output.setAttribute('data-' + dataKey, data[dataKey]));
            }
            return output;
        },

        // Every field lives inside its own row (ul) so that fields can be grouped.
        wrapFieldLi: function (field) {
            const wrapper = hashFormBuilder.div();
            if ('string' === typeof field) {
                wrapper.innerHTML = field;
            } else {
                wrapper.appendChild(field);
            }

            let result = $();
            Array.from(wrapper.children).forEach(li => {
                result = result.add(
                    $('<li>')
                        .addClass('hf-editor-field-box')
                        .html($('<ul>').addClass('hf-editor-grid-container hf-editor-sorting').append(li))
                );
            });
            return result;
        },

        wrapFieldLiInPlace: function (li) {
            const ul = hashFormBuilder.tag('ul', {
                className: 'hf-editor-grid-container hf-editor-sorting'
            });
            const wrapper = hashFormBuilder.tag('li', {
                className: 'hf-editor-field-box',
                child: ul
            });

            li.replaceWith(wrapper);
            ul.appendChild(li);

            hashFormBuilder.makeDroppable(ul);
            hashFormBuilder.makeDraggable(wrapper, '.hf-editor-move-action');
        },

        msgAsObject: function (msg) {
            const element = hashFormBuilder.div();
            element.innerHTML = msg;
            return $(element.innerHTML);
        },

        /* -------------------------------------------------------------------
         * Drag and drop: setup
         * ---------------------------------------------------------------- */

        setupSortable: function (sortableSelector) {
            document.querySelectorAll(sortableSelector).forEach(list => {
                hashFormBuilder.makeDroppable(list);
                Array.from(list.children).forEach(child => {
                    // A column is placed by the row it belongs to, so it is a
                    // drop target only, never something to drag around itself.
                    if (child.classList.contains('hf-editor-column')) {
                        return;
                    }
                    hashFormBuilder.makeDraggable(child, '.hf-editor-move-action');
                });
            });
        },

        // A saved form comes back with its columns, but without the markers the
        // editor stamps on when the row is first built, so restore them here.
        normalizeColumnRows: function () {
            document.querySelectorAll('#hf-editor-fields ul.hf-editor-sorting').forEach(row => {
                const columns = Array.from(row.children).filter(
                    child => child.classList.contains('hf-editor-column')
                );
                if (!columns.length) {
                    return;
                }

                row.classList.add('hf-editor-columns-row');
                row.setAttribute('data-columns', columns.length);

                const wrapper = row.closest('li.hf-editor-field-box');

                if (wrapper && !wrapper.querySelector(':scope > .hf-editor-row-actions')) {
                    wrapper.classList.add('hf-editor-columns-wrapper');
                    wrapper.insertBefore(columnRowActions(), wrapper.firstChild);
                    hashFormBuilder.makeDraggable(wrapper, '.hf-editor-move-action');
                }

                columns.forEach(column => {
                    const list = column.querySelector('ul.hf-editor-column-fields');
                    if (list && !list.hasAttribute('data-empty-label')) {
                        list.setAttribute('data-empty-label', emptyColumnLabel());
                    }
                });
            });
        },

        makeDroppable: function (list) {
            $(list).droppable({
                accept: '.hf-field-box, .hf-editor-field-box',
                deactivate: hashFormBuilder.handleFieldDrop,
                over: hashFormBuilder.onDragOverDroppable,
                out: hashFormBuilder.onDraggableLeavesDroppable,
                tolerance: 'pointer'
            });
        },

        makeDraggable: function (draggable, handle) {
            const settings = {
                helper: function (event) {
                    const dragged = event.delegateTarget;

                    if (dragged.classList.contains('hf-editor-field-box') && !dragged.classList.contains('hf-editor-form-field')) {
                        return hashFormBuilder.div({
                            className: 'hf-editor-field-box ui-sortable-helper',
                            child: hashFormBuilder.tag('span', 'Field Group')
                        });
                    }

                    // A brand new field is dragged out of the "add field" panel.
                    const isNewField = dragged.classList.contains('hf-field-box');
                    if (isNewField) {
                        const copyTarget = dragged.cloneNode(true);
                        copyTarget.classList.add('ui-sortable-helper');
                        dragged.classList.add('hf-added-field');
                        return copyTarget;
                    }

                    /*
                     * An existing field drags a ghost of itself, at the width
                     * it occupies.
                     *
                     * This used to drag a copy of the field's button from the
                     * add-field panel: a small tile, so a full-width field
                     * appeared to shrink the moment it was picked up, and
                     * nothing about the drag showed how much room it needed —
                     * which is exactly what you are judging when moving a
                     * field between columns.
                     */
                    if (dragged.hasAttribute('data-type')) {
                        const ghost = dragged.cloneNode(true);

                        // Ids have to go: a clone carrying them puts a second
                        // element with every one of the field's ids into the
                        // document for as long as the drag lasts.
                        ghost.removeAttribute('id');
                        ghost.querySelectorAll('[id]').forEach(function (node) {
                            node.removeAttribute('id');
                        });

                        ghost.classList.add('hf-editor-form-field', 'ui-sortable-helper', 'hf-drag-ghost');
                        ghost.style.width = dragged.offsetWidth + 'px';

                        return ghost;
                    }

                    return hashFormBuilder.div({className: 'hf-field-box'});
                },
                revert: 'invalid',
                delay: 10,
                start: function (event, ui) {
                    document.body.classList.add('hf-dragging');
                    ui.helper.addClass('hf-sortable-helper');

                    event.target.classList.add('hf-drag-fade');

                    hashFormBuilder.unselectFieldGroups();
                    hashFormBuilder.deleteEmptyDividerWrappers();
                    hashFormBuilder.maybeRemoveGroupHoverTarget();
                },
                stop: function () {
                    document.body.classList.remove('hf-dragging');

                    /*
                     * The drop target keeps hf-dropabble otherwise: it is only
                     * ever cleared by leaving one droppable for another, so
                     * after a drop the class stayed on the list that received
                     * the field and on every list above it.
                     */
                    document.querySelectorAll('.hf-dropabble').forEach(function (el) {
                        el.classList.remove('hf-dropabble');
                    });

                    const fade = document.querySelector('.hf-drag-fade');
                    if (fade) {
                        fade.classList.remove('hf-drag-fade');
                    }
                },
                drag: function (event, ui) {
                    const dragged = event.target;
                    const droppable = hashFormBuilder.getDroppableTarget();

                    let placeholder = document.getElementById('hf-placeholder');

                    if (!hashFormBuilder.allowDrop(dragged, droppable)) {
                        if (placeholder) {
                            placeholder.remove();
                        }
                        return;
                    }

                    if (!placeholder) {
                        placeholder = hashFormBuilder.tag('li', {
                            id: 'hf-placeholder',
                            className: 'sortable-placeholder'
                        });
                    }

                    // The top level list, sections and columns stack fields
                    // vertically; everything else lays them out side by side.
                    if ('hf-editor-fields' === droppable.id || droppable.classList.contains('start_divider') || hashFormBuilder.isColumnList(droppable)) {
                        placeholder.style.left = 0;
                        hashFormBuilder.handleDragOverYAxis({droppable, y: event.clientY, placeholder});
                        return;
                    }

                    placeholder.style.top = '';
                    hashFormBuilder.handleDragOverFieldGroup({droppable, x: event.clientX, placeholder});
                },
                cursor: 'grabbing',
                refreshPositions: true,
                cursorAt: {
                    top: 0,
                    left: 90 // The width of draggable button is 180. 90 should center the draggable on the cursor.
                }
            };
            if ('string' === typeof handle) {
                settings.handle = handle;
            }
            $(draggable).draggable(settings);
        },

        /* -------------------------------------------------------------------
         * Drag and drop: placeholder positioning
         * ---------------------------------------------------------------- */

        getDroppableTarget: function () {
            let droppable = document.getElementById('hf-editor-fields');
            while (droppable.querySelector('.hf-dropabble')) {
                droppable = droppable.querySelector('.hf-dropabble');
            }
            if ('hf-editor-fields' === droppable.id && !droppable.classList.contains('hf-dropabble')) {
                droppable = false;
            }
            return droppable;
        },

        handleDragOverYAxis: function ({droppable, y, placeholder}) {
            const $list = $(droppable);
            let top;

            const $children = $list.children().not('.hf-editor-field-type-end_divider');
            if (0 === $children.length) {
                $list.prepend(placeholder);
                top = 0;
            } else {
                const insertAtIndex = hashFormBuilder.determineIndexBasedOffOfMousePositionInList($list, y);
                if (insertAtIndex === $children.length) {
                    const $lastChild = $($children.get(insertAtIndex - 1));
                    top = $lastChild.offset().top + $lastChild.outerHeight();
                    $list.append(placeholder);

                    // Make sure nothing gets inserted after the end divider.
                    const $endDivider = $list.children('.hf-editor-field-type-end_divider');
                    if ($endDivider.length) {
                        $list.append($endDivider);
                    }
                } else {
                    top = $($children.get(insertAtIndex)).offset().top;
                    $($children.get(insertAtIndex)).before(placeholder);
                }
            }
            top -= $list.offset().top;
            placeholder.style.top = top + 'px';
        },

        handleDragOverFieldGroup: function ({droppable, x, placeholder}) {
            const $row = $(droppable);
            const $children = hashFormBuilder.getFieldsInRow($row);
            if (!$children.length) {
                return;
            }
            let left;
            const insertAtIndex = hashFormBuilder.determineIndexBasedOffOfMousePositionInRow($row, x);

            if (insertAtIndex === $children.length) {
                const $lastChild = $($children.get(insertAtIndex - 1));
                left = $lastChild.offset().left + $lastChild.outerWidth();
                $row.append(placeholder);
            } else {
                left = $($children.get(insertAtIndex)).offset().left;
                $($children.get(insertAtIndex)).before(placeholder);

                // Offset the placeholder slightly so it appears between two fields.
                // Offset by 8 in between rows, but only 4 for the first item in a group.
                left -= 0 === insertAtIndex ? 4 : 8;
            }
            left -= $row.offset().left;
            placeholder.style.left = left + 'px';
        },

        determineIndexBasedOffOfMousePositionInRow: function ($row, x) {
            return indexAtMousePosition(
                hashFormBuilder.getFieldsInRow($row),
                x,
                $input => $input.offset().left,
                $input => $input.outerWidth()
            );
        },

        determineIndexBasedOffOfMousePositionInList: function ($list, y) {
            return indexAtMousePosition(
                $list.children().not('.hf-editor-field-type-end_divider'),
                y,
                $item => $item.offset().top,
                $item => $item.outerHeight()
            );
        },

        onDragOverDroppable: function (event, ui) {
            const droppable = event.target;
            const draggable = ui.draggable[0];
            if (!hashFormBuilder.allowDrop(draggable, droppable)) {
                droppable.classList.remove('hf-dropabble');
                $(droppable).parents('ul.hf-editor-sorting').addClass('hf-dropabble');
                return;
            }
            document.querySelectorAll('.hf-dropabble').forEach(el => el.classList.remove('hf-dropabble'));
            droppable.classList.add('hf-dropabble');
            $(droppable).parents('ul.hf-editor-sorting').addClass('hf-dropabble');
        },

        onDraggableLeavesDroppable: function (event) {
            event.target.classList.remove('hf-dropabble');
        },

        /* -------------------------------------------------------------------
         * Drag and drop: what may be dropped where
         * ---------------------------------------------------------------- */

        allowDrop: function (draggable, droppable) {
            if (false === droppable) {
                return false;
            }

            if (droppable.closest('.hf-sortable-helper')) {
                return false;
            }

            if ('hf-editor-fields' === droppable.id) {
                return true;
            }

            if (hashFormBuilder.isColumnList(droppable)) {
                // A column stacks its fields, so the per-row cap does not apply.
                return hashFormBuilder.allowNewFieldDrop(draggable, droppable);
            }

            if (hashFormBuilder.isColumnsRow(droppable)) {
                // Fields belong in the columns, not loose in the row itself.
                return false;
            }

            if (!droppable.classList.contains('start_divider')) {
                const $fieldsInRow = hashFormBuilder.getFieldsInRow($(droppable));
                if (!hashFormBuilder.groupCanFitAnotherField($fieldsInRow, $(draggable))) {
                    // Field group is full and cannot accept another field.
                    return false;
                }
            }

            const isNewField = draggable.classList.contains('hf-added-field');
            if (isNewField) {
                return hashFormBuilder.allowNewFieldDrop(draggable, droppable);
            }
            return hashFormBuilder.allowMoveField(draggable, droppable);
        },

        groupCanFitAnotherField: function (fieldsInRow, $field) {
            if (fieldsInRow.length < 6) {
                return true;
            }
            if (fieldsInRow.length > 6) {
                return false;
            }
            // allow 6 if we're not changing field groups.
            const fieldId = $field.attr('data-fid');
            return 1 === $(fieldsInRow).filter('[data-fid="' + fieldId + '"]').length;
        },

        allowNewFieldDrop: function (draggable, droppable) {
            const classes = draggable.classList;
            const newPageBreakField = classes.contains('hashform_break');
            const newHiddenField = classes.contains('hashform_hidden');
            const newSectionField = classes.contains('hashform_divider');
            const newEmbedField = classes.contains('hashform_form');

            const newFieldWillBeAddedToAGroup = !('hf-editor-fields' === droppable.id || droppable.classList.contains('start_divider'));
            if (newFieldWillBeAddedToAGroup) {
                if (hashFormBuilder.groupIncludesBreakOrHidden(droppable)) {
                    return false;
                }
                return !newHiddenField && !newPageBreakField;
            }

            const fieldTypeIsAlwaysAllowed = !newPageBreakField && !newHiddenField && !newSectionField && !newEmbedField;
            if (fieldTypeIsAlwaysAllowed) {
                return true;
            }

            const newFieldWillBeAddedToASection = droppable.classList.contains('start_divider') || null !== droppable.closest('.start_divider');
            if (newFieldWillBeAddedToASection) {
                return !newEmbedField && !newSectionField;
            }

            return true;
        },

        allowMoveField: function (draggable, droppable) {
            if (draggable.classList.contains('hf-editor-field-box') && !draggable.classList.contains('hf-editor-form-field')) {
                return hashFormBuilder.allowMoveFieldGroup(draggable, droppable);
            }

            const isPageBreak = draggable.classList.contains('hf-editor-field-type-break');
            if (isPageBreak) {
                return false;
            }

            if (droppable.classList.contains('start_divider')) {
                return hashFormBuilder.allowMoveFieldToSection(draggable);
            }

            const isHiddenField = draggable.classList.contains('hf-editor-field-type-hidden');
            if (isHiddenField) {
                return false;
            }
            return hashFormBuilder.allowMoveFieldToGroup(draggable, droppable);
        },

        allowMoveFieldGroup: function (fieldGroup, droppable) {
            // Allow a field group with no section inside of a section.
            return droppable.classList.contains('start_divider') && null === fieldGroup.querySelector('.start_divider');
        },

        allowMoveFieldToSection: function (draggable) {
            const draggableIncludeEmbedForm = draggable.classList.contains('hf-editor-field-type-form') || draggable.querySelector('.hf-editor-field-type-form');
            if (draggableIncludeEmbedForm) {
                // Do not allow an embedded form inside of a section.
                return false;
            }

            const draggableIncludesSection = draggable.classList.contains('hf-editor-field-type-divider') || draggable.querySelector('.hf-editor-field-type-divider');
            if (draggableIncludesSection) {
                // Do not allow a section inside of a section.
                return false;
            }

            return true;
        },

        allowMoveFieldToGroup: function (draggable, group) {
            if (hashFormBuilder.groupIncludesBreakOrHidden(group)) {
                // Never allow any field beside a page break or a hidden field.
                return false;
            }

            const isFieldGroup = $(draggable).children('ul.hf-editor-sorting').not('.start_divider').length > 0;
            if (isFieldGroup) {
                // Do not allow a field group directly inside of a field group unless it's in a section.
                return false;
            }

            const draggableIncludesASection = draggable.classList.contains('hf-editor-field-type-divider') || draggable.querySelector('.hf-editor-field-type-divider');
            const draggableIsEmbedField = draggable.classList.contains('hf-editor-field-type-form');
            const groupIsInASection = null !== group.closest('.start_divider');
            if (groupIsInASection && (draggableIncludesASection || draggableIsEmbedField)) {
                // Do not allow a section or an embed field inside of a section.
                return false;
            }

            return true;
        },

        groupIncludesBreakOrHidden: function (group) {
            return null !== group.querySelector('.hf-editor-field-type-multi_step, .hf-editor-field-type-hidden');
        },

        /* -------------------------------------------------------------------
         * Drag and drop: handling the drop
         * ---------------------------------------------------------------- */

        handleFieldDrop: function (_, ui) {
            const draggable = ui.draggable[0];
            const placeholder = document.getElementById('hf-placeholder');

            if (!placeholder) {
                ui.helper.remove();
                hashFormBuilder.syncAfterDragAndDrop();
                return;
            }
            const $previousFieldContainer = ui.helper.parent();
            const previousSection = ui.helper.get(0).closest('ul.start_divider');
            const newSection = placeholder.closest('ul.hf-editor-sorting');

            if (draggable.classList.contains('hf-added-field')) {
                hashFormBuilder.insertNewFieldByDragging(draggable.id);
            } else {
                hashFormBuilder.moveFieldThatAlreadyExists(draggable, placeholder);
            }

            const previousSectionId = previousSection ? parseInt(previousSection.closest('.hf-editor-field-type-divider').getAttribute('data-fid')) : 0;
            const newSectionId = newSection.classList.contains('start_divider') ? parseInt(newSection.closest('.hf-editor-field-type-divider').getAttribute('data-fid')) : 0;

            placeholder.remove();
            ui.helper.remove();

            const $previousContainerFields = $previousFieldContainer.length ? hashFormBuilder.getFieldsInRow($previousFieldContainer) : [];
            hashFormBuilder.maybeUpdatePreviousFieldContainerAfterDrop($previousFieldContainer, $previousContainerFields);
            hashFormBuilder.maybeUpdateDraggableClassAfterDrop(draggable, $previousContainerFields, $previousFieldContainer);

            if (previousSectionId !== newSectionId) {
                hashFormBuilder.updateFieldAfterMovingBetweenSections($(draggable), previousSection);
            }
            hashFormBuilder.syncAfterDragAndDrop();
        },

        moveFieldThatAlreadyExists: function (draggable, placeholder) {
            placeholder.parentNode.insertBefore(draggable, placeholder);
        },

        syncAfterDragAndDrop: function () {
            hashFormBuilder.fixUnwrappedListItems();
            hashFormBuilder.toggleSectionHolder();
            hashFormBuilder.maybeFixEndDividers();
            hashFormBuilder.maybeDeleteEmptyFieldGroups();
            hashFormBuilder.updateFieldOrder();

            document.dispatchEvent(new Event('hashform_sync_after_drag_and_drop', {bubbles: false}));
            hashFormBuilder.maybeFixRangeSlider();
            hashFormBuilder.notifyFieldMarkupChanged();
            initColorPickers();
        },

        maybeUpdatePreviousFieldContainerAfterDrop: function ($previousFieldContainer, $previousContainerFields) {
            if (!$previousFieldContainer.length) {
                return;
            }

            if ($previousContainerFields.length) {
                hashFormBuilder.syncLayoutClasses($previousContainerFields.first());
            } else {
                hashFormBuilder.maybeDeleteAnEmptyFieldGroup($previousFieldContainer.get(0));
            }
        },

        maybeUpdateDraggableClassAfterDrop: function (draggable, $previousContainerFields, $previousFieldContainer) {
            // A field leaving a column still carries that column's width, so it
            // needs a resync even when it lands alone in a row of its own.
            const cameOutOfAColumn = hashFormBuilder.isColumnList($previousFieldContainer.get(0));

            if (cameOutOfAColumn || 0 !== $previousContainerFields.length || 1 !== hashFormBuilder.getFieldsInRow($(draggable.parentNode)).length) {
                hashFormBuilder.syncLayoutClasses($(draggable));
            }
        },

        maybeDeleteAnEmptyFieldGroup: function (previousFieldContainer) {
            // An emptied column keeps its place: the columns and the row holding
            // them are a layout the user asked for, not a wrapper built around a
            // field. Its closest field box is the whole columns row, so deleting
            // it here would take the other columns and their fields with it.
            if (hashFormBuilder.isColumnList(previousFieldContainer)) {
                return;
            }

            const closestFieldBox = previousFieldContainer.closest('li.hf-editor-field-box');
            if (!closestFieldBox || closestFieldBox.classList.contains('hf-editor-field-type-divider')) {
                return;
            }

            // Only ever drop a box that has nothing left in it.
            if (closestFieldBox.querySelector('li.hf-editor-form-field:not(.hf-editor-field-type-end_divider)')) {
                return;
            }

            closestFieldBox.remove();
        },

        /* -------------------------------------------------------------------
         * Editor tree housekeeping
         * ---------------------------------------------------------------- */

        // Any field sitting straight in a list, rather than in a row, gets wrapped.
        fixUnwrappedListItems: function () {
            document.querySelectorAll('ul#hf-editor-fields, ul.start_divider').forEach(list => {
                list.childNodes.forEach(child => {
                    if ('undefined' === typeof child.classList) {
                        return;
                    }
                    if (child.classList.contains('hf-editor-field-type-end_divider')) {
                        // Never wrap end divider in place.
                        return;
                    }
                    if (child.classList.contains('hf-editor-form-field')) {
                        hashFormBuilder.wrapFieldLiInPlace(child);
                    }
                });
            });
        },

        deleteEmptyDividerWrappers: function () {
            document.querySelectorAll('ul.start_divider').forEach(divider => {
                Array.from(divider.children).forEach(child => {
                    const isEmpty = 0 === child.children.length;
                    const holdsOnlyAnEmptyRow = 1 === child.children.length &&
                        'ul' === child.firstElementChild.nodeName.toLowerCase() &&
                        0 === child.firstElementChild.children.length;

                    if (isEmpty || holdsOnlyAnEmptyRow) {
                        child.remove();
                    }
                });
            });
        },

        maybeFixEndDividers: function () {
            document.querySelectorAll('.hf-editor-field-type-end_divider').forEach(
                endDivider => endDivider.parentNode.appendChild(endDivider)
            );
        },

        maybeDeleteEmptyFieldGroups: function () {
            document.querySelectorAll('li.form_field_box:not(.hf-editor-form-field)').forEach(
                fieldGroup => !fieldGroup.children.length && fieldGroup.remove()
            );
        },

        getFieldsInRow: function ($row) {
            let $fields = $();
            const row = $row.get(0);
            if (!row.children) {
                return $fields;
            }

            Array.from(row.children).forEach(child => {
                if ('none' === child.style.display) {
                    return;
                }
                const classes = child.classList;
                if (!classes.contains('hf-editor-form-field') || classes.contains('hf-editor-field-type-end_divider') || classes.contains('hf-sortable-helper')) {
                    return;
                }
                $fields = $fields.add(child);
            });
            return $fields;
        },

        updateFieldOrder: function () {
            // Every path that reorders fields ends up here, so this is the one
            // place column membership needs re-deriving.
            hashFormBuilder.syncColumnGroups();

            $('#hf-editor-fields').each(function () {
                const fields = $('li.hf-editor-field-box', this);
                for (let i = 0; i < fields.length; i++) {
                    const fieldId = fields[i].getAttribute('data-fid');
                    const field = $('input[name="field_options[field_order_' + fieldId + ']"]');
                    const newOrder = i + 1;

                    // Loose compare: the input value is a string.
                    if (field.val() != newOrder) {
                        field.val(newOrder);
                        hashFormBuilder.moveFieldSettings(document.getElementById('hf-fields-settings-' + fieldId));
                    }
                }
            });
        },

        /* -------------------------------------------------------------------
         * Sections
         * ---------------------------------------------------------------- */

        toggleSectionHolder: function () {
            document.querySelectorAll('.start_divider').forEach(
                divider => hashFormBuilder.toggleOneSectionHolder($(divider))
            );
        },

        // Show the "no fields yet" holder only while the section is empty.
        toggleOneSectionHolder: function ($section) {
            if (!$section.length) {
                return;
            }

            const sectionHasFields = $section.find('ul.hf-editor-sorting').toArray().some(
                row => 0 !== hashFormBuilder.getFieldsInRow($(row)).length
            );

            const noSectionFields = $section.parent().children('.hashform_no_section_fields').get(0);
            noSectionFields.classList.toggle('hashform_block', !sectionHasFields);
        },

        getSectionForFieldPlacement: function (currentItem) {
            let section = '';
            if (typeof currentItem !== 'undefined' && !currentItem.hasClass('hf-editor-field-type-divider')) {
                section = currentItem.closest('.hf-editor-field-type-divider');
            }
            return section;
        },

        getFormIdForFieldPlacement: function (section) {
            let formId = '';
            if (typeof section[0] !== 'undefined') {
                const sDivide = section.children('.start_divider');
                sDivide.children('.hf-editor-field-type-end_divider').appendTo(sDivide);
                if (typeof section.attr('data-formid') !== 'undefined') {
                    const fieldId = section.attr('data-fid');
                    formId = $('input[name="field_options[form_select_' + fieldId + ']"]').val();
                }
            }
            if (typeof formId === 'undefined' || formId === '') {
                formId = currentFormId;
            }
            return formId;
        },

        getSectionIdForFieldPlacement: function (section) {
            if (typeof section[0] !== 'undefined') {
                return section.attr('id').replace('hf-editor-field-id-', '');
            }
            return 0;
        },

        updateFieldAfterMovingBetweenSections: function (currentItem, previousSection) {
            if (!currentItem.hasClass('hf-editor-form-field')) {
                // A whole group moved, so update each field it holds.
                hashFormBuilder.getFieldsInRow($(currentItem.get(0).firstChild)).each(function () {
                    hashFormBuilder.updateFieldAfterMovingBetweenSections($(this), previousSection);
                });
                return;
            }

            const fieldId = currentItem.attr('id').replace('hf-editor-field-id-', '');
            const {formId, sectionId} = getFieldPlacement(currentItem);
            const previousFormId = previousSection ? hashFormBuilder.getFormIdForFieldPlacement($(previousSection.parentNode)) : 0;

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'hashform_update_field_after_move', /**Check This */
                    form_id: formId,
                    field: fieldId,
                    section_id: sectionId,
                    previous_form_id: previousFormId,
                    backend_nonce: hashform_backend_js.nonce
                },
                success: function () {
                    hashFormBuilder.toggleSectionHolder();
                }
            });
        },

        /* -------------------------------------------------------------------
         * Adding fields
         * ---------------------------------------------------------------- */

        // Fields are placed by dragging, so a plain click has nothing to do but
        // point at the hint that says so.
        hintToDragField: function (event) {
            /*jshint validthis:true */
            event.preventDefault();

            hashFormBuilder.flashHint(document.getElementById('hf-drag-hint'), 'hf-hint-pulse');
            hashFormBuilder.flashHint(this, 'hf-hint-nudge');
        },

        hintTimers: new WeakMap(),

        // Runs the hint from the top on every click, and leaves no styling
        // behind once it has played.
        flashHint: function (element, className) {
            if (null === element) {
                return;
            }

            clearTimeout(hashFormBuilder.hintTimers.get(element));
            element.classList.remove(className);
            // Reading offsetWidth restarts an animation still part way through.
            void element.offsetWidth;
            element.classList.add(className);

            hashFormBuilder.hintTimers.set(element, setTimeout(
                () => element.classList.remove(className),
                1200
            ));
        },

        insertNewFieldByDragging: function (fieldType) {
            const placeholder = document.getElementById('hf-placeholder');
            const loadingID = fieldType.replace('|', '-') + '_' + hashFormBuilder.getAutoId();
            const loading = hashFormBuilder.tag('li', {
                id: loadingID,
                className: 'hf-wait hf-field-loading'
            });
            const $placeholder = $(loading);
            const {formId} = getFieldPlacement($(placeholder));

            // Swap the drop placeholder for a loading item while the field renders.
            placeholder.parentNode.insertBefore(loading, placeholder);
            placeholder.remove();
            hashFormBuilder.syncLayoutClasses($placeholder);

            insertFieldRequest(formId, fieldType, function (msg) {
                setHasFields(true);

                const $siblings = $placeholder.siblings('li.hf-editor-form-field').not('.hf-editor-field-type-end_divider');
                // A column already provides the list, so the field goes in
                // bare; wrapping it would nest a row inside the column.
                const inColumn = hashFormBuilder.isColumnList($placeholder.get(0).parentNode);
                const needsRowWrapper = !$siblings.length && !inColumn;
                let replaceWith;

                if (needsRowWrapper) {
                    replaceWith = hashFormBuilder.wrapFieldLi(msg);
                } else {
                    replaceWith = hashFormBuilder.msgAsObject(msg);
                    // Inside a column the grandparent is the column itself,
                    // which must not become draggable by the field's handle.
                    const rowWrapper = $placeholder.get(0).parentNode.parentNode;
                    if (!inColumn && !rowWrapper.classList.contains('ui-draggable')) {
                        hashFormBuilder.makeDraggable(rowWrapper, '.hf-editor-move-action');
                    }
                }

                $placeholder.replaceWith(replaceWith);
                hashFormBuilder.updateFieldOrder();
                hashFormBuilder.afterAddField(msg, false);
                // Dropping a new field goes through here rather than through
                // syncAfterDragAndDrop, so without this a range slider added
                // from the palette was never wired up and stayed inert until
                // the page was reloaded.
                hashFormBuilder.maybeFixRangeSlider();
                hashFormBuilder.notifyFieldMarkupChanged();
                if ($siblings.length) {
                    hashFormBuilder.syncLayoutClasses($siblings.first());
                }
                hashFormBuilder.toggleSectionHolder();

                if (needsRowWrapper) {
                    hashFormBuilder.makeDroppable(replaceWith.get(0).querySelector('ul.hf-editor-sorting'));
                    hashFormBuilder.makeDraggable(replaceWith.get(0).querySelector('li.hf-editor-form-field'), '.hf-editor-move-action');
                } else {
                    hashFormBuilder.makeDraggable(replaceWith.get(0), '.hf-editor-move-action');
                }
            });
        },

        afterAddField: function (msg, addFocus) {
            const match = /id="(\S+)"/.exec(msg);
            const field = document.getElementById(match[1]);
            // Reassigned below, where the original code assigns instead of compares.
            let type = field.getAttribute('data-type');
            const section = '#' + match[1] + '.hf-editor-field-type-divider ul.hf-editor-sorting.start_divider';
            const $thisSection = $(section);
            let toggled = false;

            hashFormBuilder.setupSortable(section);
            if ($thisSection.length) {
                $thisSection.parent('.hf-editor-field-box').children('.hashform_no_section_fields').addClass('hashform_block');
            } else {
                const $parentSection = $(field).closest('ul.hf-editor-sorting.start_divider');
                if ($parentSection.length) {
                    hashFormBuilder.toggleOneSectionHolder($parentSection);
                    toggled = true;
                }
            }

            $(field).addClass('hf-newly-added');
            setTimeout(() => field.classList.remove('hf-newly-added'), 1000);

            if (addFocus) {
                hashFormBuilder.scrollNewFieldIntoView(field);
                if (toggled === false) {
                    hashFormBuilder.toggleOneSectionHolder($thisSection);
                }
            }

            hashFormBuilder.deselectFields();

            const addedEvent = new Event('hashform_added_field', {bubbles: false});
            addedEvent.hfField = field;
            addedEvent.hfSection = section;
            addedEvent.hfType = type;
            addedEvent.hfToggles = toggled;
            document.dispatchEvent(addedEvent);
            if (type === 'multi_step') {
                hashFormBuilder.resetToFirstStep();
                hashFormBuilder.renumberMultiSteps();
            }
        },

        scrollNewFieldIntoView: function (field) {
            const bounding = field.getBoundingClientRect();
            const inView = bounding.top >= 0 &&
                bounding.left >= 0 &&
                bounding.right <= (window.innerWidth || document.documentElement.clientWidth) &&
                bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight);

            if (inView) {
                return;
            }

            const container = document.getElementById('hf-form-panel');
            container.scroll({
                top: container.scrollHeight,
                left: 0,
                behavior: 'smooth'
            });
        },

        handleInsertFieldError: function (jqXHR, _, errorThrown) {
            hashFormBuilder.maybeShowInsertFieldError(errorThrown, jqXHR);
        },

        maybeShowInsertFieldError: function (errorThrown, jqXHR) {
            if (!hashFormBuilder.jqXHRAborted(jqXHR)) {
                hashFormBuilder.infoModal(errorThrown + '. Please try again.');
            }
        },

        jqXHRAborted: function (jqXHR) {
            return jqXHR.status === 0 || jqXHR.readyState === 0;
        },

        getAutoId: function () {
            return ++autoId;
        },

        /* -------------------------------------------------------------------
         * Column rows
         *
         * A column row is a field row whose children are columns rather than
         * fields. Each column holds its own vertical list, so fields dropped
         * into one stack under each other.
         *
         * What persists is per field: column_group says which column it sits
         * in, grid_id carries that column's width. The front end regroups
         * consecutive fields sharing a column_group into one grid cell.
         * ---------------------------------------------------------------- */

        addColumnsClick: function () {
            /*jshint validthis:true */
            const columns = parseInt($(this).attr('data-columns'), 10);

            if (!hashFormBuilder.columnCountIsValid(columns)) {
                return false;
            }

            hashFormBuilder.addColumnRow(columns);
            return false;
        },

        // Only divisors of 12 keep every column the same width.
        columnCountIsValid: function (columns) {
            return -1 !== [2, 3, 4, 6].indexOf(columns);
        },

        columnWidthClass: function (columns) {
            return hashFormBuilder.getLayoutClassForSize(12 / columns);
        },

        // Lowercase alphanumerics only, so the key survives sanitize_key() on
        // the way into the database.
        newColumnGroupKey: function (index) {
            return 'cg' + Date.now().toString(36) + index + hashFormBuilder.getAutoId();
        },

        addColumnRow: function (columns) {
            const widthClass = hashFormBuilder.columnWidthClass(columns);
            const row = hashFormBuilder.tag('ul', {
                className: 'hf-editor-grid-container hf-editor-sorting hf-editor-columns-row'
            });
            row.setAttribute('data-columns', columns);

            for (let i = 0; i < columns; i++) {
                row.appendChild(hashFormBuilder.buildColumn(widthClass, hashFormBuilder.newColumnGroupKey(i)));
            }

            const wrapper = hashFormBuilder.tag('li', {
                className: 'hf-editor-field-box hf-editor-columns-wrapper',
                children: [columnRowActions(), row]
            });

            setHasFields(true);
            $editorFieldsWrap.append(wrapper);

            hashFormBuilder.makeDroppable(row);
            row.querySelectorAll('ul.hf-editor-column-fields').forEach(hashFormBuilder.makeDroppable);
            hashFormBuilder.makeDraggable(wrapper, '.hf-editor-move-action');
            hashFormBuilder.scrollNewFieldIntoView(wrapper);
        },

        // The column carries hf-editor-form-field so the row's drop machinery
        // treats it as an occupant and positions the drop marker against it.
        buildColumn: function (widthClass, group) {
            const list = hashFormBuilder.tag('ul', {
                className: 'hf-editor-column-fields hf-editor-sorting'
            });
            list.setAttribute('data-empty-label', emptyColumnLabel());

            const column = hashFormBuilder.tag('li', {
                className: 'hf-editor-form-field hf-editor-column ' + widthClass,
                children: [list]
            });
            column.setAttribute('data-column-group', group);
            return column;
        },

        isColumnList: function (el) {
            return !!el && !!el.classList && el.classList.contains('hf-editor-column-fields');
        },

        isColumnsRow: function (el) {
            return !!el && !!el.classList && el.classList.contains('hf-editor-columns-row');
        },

        columnsInRow: function (row) {
            return Array.from(row.children).filter(child => child.classList.contains('hf-editor-column'));
        },

        columnWidth: function (column) {
            return (column.className.match(/hf-grid-\d+/) || [''])[0];
        },

        // A column row written out as group:width pairs. Every field of the row
        // carries the whole thing, so a column nobody used still comes back.
        rowSpec: function (columns) {
            return columns.map(
                column => column.getAttribute('data-column-group') + ':' + (hashFormBuilder.columnWidth(column) || 'hf-grid-12')
            ).join(',');
        },

        // Write a field's column membership, width and row to the inputs that
        // get saved with the form.
        setFieldColumn: function (fieldEl, group, widthClass, spec) {
            const fieldId = fieldEl.dataset.fid;
            if ('undefined' === typeof fieldId) {
                return;
            }

            hashFormBuilder.moveFieldSettings(document.getElementById('hf-fields-settings-' + fieldId));

            hashFormBuilder.setFieldInput('hf-column-group-' + fieldId, group);
            hashFormBuilder.setFieldInput('hf-column-row-' + fieldId, spec);

            if (!widthClass) {
                return;
            }

            const gridInput = document.getElementById('hf-grid-class-' + fieldId);
            if (gridInput) {
                gridInput.value = widthClass;
                hashFormBuilder.changeFieldClass(fieldEl, widthClass);
            }
        },

        setFieldInput: function (inputId, value) {
            const input = document.getElementById(inputId);
            if (input && input.value !== value) {
                input.value = value;
            }
        },

        // Re-derive every field's column membership from where it now sits.
        syncColumnGroups: function () {
            document.querySelectorAll('ul.hf-editor-columns-row').forEach(row => {
                const columns = hashFormBuilder.columnsInRow(row);
                const spec = hashFormBuilder.rowSpec(columns);

                columns.forEach(column => {
                    const group = column.getAttribute('data-column-group');
                    const widthClass = hashFormBuilder.columnWidth(column);
                    const list = column.querySelector('ul.hf-editor-column-fields');
                    if (null === list) {
                        return;
                    }

                    Array.from(list.children).forEach(child => {
                        if (child.classList.contains('hf-editor-form-field')) {
                            hashFormBuilder.setFieldColumn(child, group, widthClass, spec);
                        }
                    });
                });
            });

            // A field dragged back out of a column must stop claiming one.
            document.querySelectorAll('#hf-editor-fields li.hf-editor-form-field[data-fid]').forEach(field => {
                if (field.closest('ul.hf-editor-column-fields')) {
                    return;
                }
                hashFormBuilder.setFieldInput('hf-column-group-' + field.dataset.fid, '');
                hashFormBuilder.setFieldInput('hf-column-row-' + field.dataset.fid, '');
            });
        },

        /* -------------------------------------------------------------------
         * Duplicating fields
         * ---------------------------------------------------------------- */

        clickDuplicateField: function () {
            /*jshint validthis:true */
            const $trigger = $(this);

            if ($trigger.hasClass('hf-duplicating')) {
                return false;
            }
            $trigger.addClass('hf-duplicating');

            hashFormBuilder.duplicateField($trigger.attr('data-duplicatefield'), function () {
                $trigger.removeClass('hf-duplicating');
            });

            return false;
        },

        duplicateField: function (fieldId, onDone) {
            const $source = $('#hf-editor-field-id-' + fieldId);

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'hashform_duplicate_field',
                    field_id: fieldId,
                    backend_nonce: hashform_backend_js.nonce
                },
                success: function (msg) {
                    onDone();

                    // The handler prints nothing when it refuses the copy.
                    if (!msg || -1 === msg.indexOf('hf-editor-field-id-')) {
                        hashFormBuilder.infoModal('This field could not be duplicated.');
                        return;
                    }

                    hashFormBuilder.insertDuplicatedField($source, msg);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    onDone();
                    hashFormBuilder.handleInsertFieldError(jqXHR, textStatus, errorThrown);
                }
            });
        },

        // Mirrors how a dragged field is placed: a field that owns its row gets
        // a row of its own, one sharing a row gets dropped in beside its source.
        insertDuplicatedField: function ($source, msg) {
            setHasFields(true);

            const $siblings = $source.siblings('li.hf-editor-form-field').not('.hf-editor-field-type-end_divider');
            let inserted;

            if (!$siblings.length) {
                inserted = hashFormBuilder.wrapFieldLi(msg);
                $source.closest('li.hf-editor-field-box').after(inserted);
                inserted.each(function () {
                    hashFormBuilder.makeDroppable(this.querySelector('ul.hf-editor-sorting'));
                    hashFormBuilder.makeDraggable(this.querySelector('.hf-editor-form-field'), '.hf-editor-move-action');
                });
            } else {
                inserted = hashFormBuilder.msgAsObject(msg);
                $source.after(inserted);
                hashFormBuilder.makeDraggable(inserted.get(0), '.hf-editor-move-action');
                hashFormBuilder.syncLayoutClasses($source);
            }

            hashFormBuilder.updateFieldOrder();
            hashFormBuilder.afterAddField(msg, true);
            hashFormBuilder.maybeFixRangeSlider();
            hashFormBuilder.notifyFieldMarkupChanged();
            initColorPickers();
        },

        /* -------------------------------------------------------------------
         * Deleting fields
         * ---------------------------------------------------------------- */

        /**
         * Delete a whole columns row, and whatever is standing in it.
         *
         * The fields inside have rows of their own in the database, so they are
         * deleted there before the row goes from the canvas; dropping the
         * markup alone would leave them behind as orphans.
         */
        clickDeleteColumnRow: function (event) {
            /*jshint validthis:true */
            event.preventDefault();

            const wrapper = this.closest('li.hf-editor-field-box');

            if (!wrapper) {
                return false;
            }

            const fields = wrapper.querySelectorAll('li.hf-editor-form-field[data-fid]');
            const question = fields.length
                ? columnRowText('delete_row_with_fields', 'Delete this row and the fields in it?')
                : columnRowText('delete_row', 'Delete this row?');

            if (!confirm(question)) {
                return false;
            }

            fields.forEach(function (field) {
                const id = field.getAttribute('data-fid');
                $('#hf-fields-settings-' + id).remove();
                hashFormBuilder.deleteFieldRequest(id);
            });

            $(wrapper).fadeOut('fast', function () {
                wrapper.remove();

                if (!document.querySelectorAll('#hf-editor-fields li').length) {
                    setHasFields(false);
                }

                hashFormBuilder.syncAfterDragAndDrop();
            });

            return false;
        },

        // Just the server side of a delete, for callers doing their own tidying.
        deleteFieldRequest: function (fieldId) {
            return jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'hashform_delete_field',
                    field_id: fieldId,
                    backend_nonce: hashform_backend_js.nonce
                }
            });
        },

        clickDeleteField: function () {
            if (confirm("Are you sure?")) {
                hashFormBuilder.deleteFields($(this).attr('data-deletefield'));
            }
            return false;
        },

        // Deleting a section takes every field inside it along.
        deleteFields: function (fieldId) {
            const field = $('#hf-editor-field-id-' + fieldId);

            hashFormBuilder.deleteField(fieldId);
            if (field.hasClass('hf-editor-field-type-divider')) {
                field.find('li.hf-editor-field-box').each(function () {
                    hashFormBuilder.deleteField(this.getAttribute('data-fid'));
                });
            }
            hashFormBuilder.toggleSectionHolder();
        },

        deleteField: function (fieldId) {
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'hashform_delete_field',
                    field_id: fieldId,
                    backend_nonce: hashform_backend_js.nonce
                },
                success: function () {
                    const $thisField = $('#hf-editor-field-id-' + fieldId);
                    const settings = $('#hf-fields-settings-' + fieldId);

                    // Remove settings from sidebar.
                    if (settings.is(':visible')) {
                        document.getElementById('hf-add-fields-tab').click();
                    }
                    settings.remove();

                    $thisField.fadeOut('fast', function () {
                        const $section = $thisField.closest('.start_divider');
                        const type = $thisField.data('type');
                        const $adjacentFields = $thisField.siblings('li.hf-editor-form-field');
                        const $list = $thisField.closest('ul.hf-editor-sorting');
                        const inColumn = $list.length && hashFormBuilder.isColumnList($list.get(0));
                        let $liWrapper;

                        if (!$adjacentFields.length) {
                            if ($thisField.is('.hf-editor-field-type-end_divider')) {
                                $adjacentFields.length = $thisField.closest('li.hf-editor-form-field').siblings();
                            } else if (!inColumn) {
                                /*
                                 * An emptied column keeps its place, ready for
                                 * the next field. Its wrapper is the column
                                 * itself, so removing it here deleted the
                                 * column out from under the person who had
                                 * only asked to delete a field. Dragging the
                                 * last field out already worked this way; only
                                 * deleting it did not.
                                 */
                                $liWrapper = $list.parent();
                            }
                        }

                        $thisField.remove();
                        if ($('#hf-editor-fields li').length === 0) {
                            setHasFields(false);
                        } else if ($section.length) {
                            hashFormBuilder.toggleOneSectionHolder($section);
                        }
                        if ($adjacentFields.length) {
                            hashFormBuilder.syncLayoutClasses($adjacentFields.first());
                        } else if ($liWrapper) {
                            $liWrapper.remove();
                        }

                        if (type === 'multi_step') {
                            hashFormBuilder.renumberMultiSteps();
                        }
                    });
                }
            });
        },

        /* -------------------------------------------------------------------
         * Selecting a field and showing its settings
         * ---------------------------------------------------------------- */

        clickField: function (e) {
            /*jshint validthis:true */
            const currentClass = e.target.classList;

            if (currentClass.contains('hf-collapse-page') || currentClass.contains('hf-sub-label') || e.target.closest('.dropdown') !== null) {
                return;
            }

            if (this.closest('.start_divider') !== null) {
                e.stopPropagation();
            }

            if (this.classList.contains('hf-editor-field-type-divider')) {
                const originalList = e.originalEvent.target.closest('ul.hf-editor-sorting');
                if (null !== originalList) {
                    // prevent section click if clicking a field group within a section.
                    if (originalList.classList.contains('hf-editor-field-type-divider') || originalList.parentNode.parentNode.classList.contains('start_divider')) {
                        return;
                    }
                }
            }

            hashFormBuilder.clickAction(this);
        },

        clickAction: function (obj) {
            const $thisobj = $(obj);
            if (obj.className.indexOf('selected') !== -1) {
                return;
            }
            if (obj.className.indexOf('hf-editor-field-type-end_divider') !== -1 && $thisobj.closest('.hf-editor-field-type-divider').hasClass('no_repeat_section')) {
                return;
            }
            hashFormBuilder.deselectFields();
            $thisobj.addClass('selected');
            hashFormBuilder.showFieldOptions(obj);
        },

        showFieldOptions: function (obj) {
            const fieldId = obj.getAttribute('data-fid');

            document.querySelectorAll('.hf-fields-settings:not(.hf-hidden)').forEach(
                fieldSettings => fieldSettings.classList.add('hf-hidden')
            );

            const singleField = document.getElementById('hf-fields-settings-' + fieldId);
            hashFormBuilder.moveFieldSettings(singleField);

            singleField.classList.remove('hf-hidden');
            document.getElementById('hf-options-tab').click();

            const editor = singleField.querySelector('.wp-editor-area');
            if (editor) {
                wysiwyg.init(editor, {setupCallback: hashFormBuilder.setupTinyMceEventHandlers});
            }
        },

        deselectFields: function (preventFieldGroups) {
            $('li.ui-state-default.selected').removeClass('selected');
            if (!preventFieldGroups) {
                hashFormBuilder.unselectFieldGroups();
            }
        },

        unselectFieldGroups: function (event) {
            if ('undefined' !== typeof event) {
                const target = event.originalEvent.target;
                // Clicks inside the editor, or on the group layout controls, keep the selection.
                const keepsSelection = null !== target.closest('#hf-editor-fields, .hf-merge-fields-into-row') ||
                    target.classList.contains('hf-custom-field-group-layout') ||
                    target.classList.contains('hf-cancel-custom-field-group-layout');

                if (keepsSelection) {
                    return;
                }
            }
            $('.hf-selected-field-group').removeClass('hf-selected-field-group');
            $(document).off('click', hashFormBuilder.unselectFieldGroups);
        },

        maybeRemoveGroupHoverTarget: function () {
            const controls = document.getElementById('hashform_field_group_controls');
            if (null !== controls) {
                controls.style.display = 'none';
            }

            const previousHoverTarget = document.querySelector('.hf-field-group-hover-target');
            if (null === previousHoverTarget) {
                return false;
            }

            $('#wpbody-content').off('mousemove', hashFormBuilder.maybeRemoveHoverTargetOnMouseMove);
            previousHoverTarget.classList.remove('hf-field-group-hover-target');
            return previousHoverTarget;
        },

        maybeRemoveHoverTargetOnMouseMove: function (event) {
            const elementFromPoint = document.elementFromPoint(event.clientX, event.clientY);
            if (null !== elementFromPoint && null !== elementFromPoint.closest('#hf-editor-fields')) {
                return;
            }
            hashFormBuilder.maybeRemoveGroupHoverTarget();
        },

        stopFieldFocus: function (e) {
            e.preventDefault();
        },

        // Settings panels live in the editor markup, so move them into the settings form.
        moveFieldSettings: function (singleField) {
            if (singleField === null) {
                return;
            }
            const classes = singleField.parentElement.classList;
            if (classes.contains('hf-editor-field-box') || classes.contains('divider_section_only')) {
                buildForm.insertBefore(singleField, document.getElementById('hf-end-form-marker'));
            }
        },

        infoModal: function (msg) {
            const $info = hashFormAdmin.initModal('#hashform_info_modal', '400px');
            if ($info === false) {
                return false;
            }
            $('.hf-info-msg').html(msg);
            $info.dialog('open');
            return false;
        },

        /* -------------------------------------------------------------------
         * Grid layout classes
         * ---------------------------------------------------------------- */

        // How wide the odd field out is, and how wide the rest are, for a row of
        // `size` fields laid out with one large field on the left or the right.
        // Keyed by number so a stringy size falls through to the default, as the
        // original switch statements did.
        largeGridForSize: new Map([[2, 9], [3, 6], [4, 6], [5, 4], [6, 7]]),
        smallGridForSize: new Map([[2, 3], [3, 3], [4, 2], [5, 2], [6, 1]]),

        getLargeClassForSize: function (size) {
            return hashFormBuilder.getLayoutClassForSize(hashFormBuilder.largeGridForSize.get(size) || 12);
        },

        getSmallClassForSize: function (size) {
            return hashFormBuilder.getLayoutClassForSize(hashFormBuilder.smallGridForSize.get(size) || 12);
        },

        getLayoutClassForSize: function (size) {
            return 'hf-grid-' + size;
        },

        getEvenClassForSize: function (size, index) {
            if (-1 !== [2, 3, 4, 6].indexOf(size)) {
                return hashFormBuilder.getLayoutClassForSize(12 / size);
            }
            // A row of 5 cannot divide evenly, so widen the first field.
            if (5 === size && 'undefined' !== typeof index) {
                return 0 === index ? 'hf-grid-4' : 'hf-grid-2';
            }
            return 'hf-grid-12';
        },

        getClassForBlock: function (size, type, index) {
            if ('even' === type) {
                return hashFormBuilder.getEvenClassForSize(size, index);
            }
            if ('middle' === type) {
                if (3 === size) {
                    return 1 === index ? 'hf-grid-6' : 'hf-grid-3';
                }
                if (5 === size) {
                    return 2 === index ? 'hf-grid-4' : 'hf-grid-2';
                }
            }
            if ('left' === type) {
                return 0 === index ? hashFormBuilder.getLargeClassForSize(size) : hashFormBuilder.getSmallClassForSize(size);
            }
            if ('right' === type) {
                return index === size - 1 ? hashFormBuilder.getLargeClassForSize(size) : hashFormBuilder.getSmallClassForSize(size);
            }
            return 'hf-grid-12';
        },

        // `type` is either a named layout, 'clear', or an array of explicit widths.
        syncLayoutClasses: function ($item, type = 'even') {
            // Fields stacked in a column are full width inside it; their saved
            // grid_id belongs to the column and is set by syncColumnGroups.
            if (hashFormBuilder.isColumnList($item.parent().get(0))) {
                return;
            }

            // A columns row lays out columns, not fields. Their widths come from
            // the column the user picked, and a column carries no data-fid, so
            // resizing here would stack a second grid class onto each one.
            if (hashFormBuilder.isColumnsRow($item.parent().get(0))) {
                return;
            }

            const $fields = $item.parent()
                .children('li.hf-editor-form-field, li.hf-field-loading')
                .not('.hf-editor-field-type-end_divider');
            const size = $fields.length;

            let classToAdd;
            if ('even' === type && 5 !== size) {
                classToAdd = hashFormBuilder.getEvenClassForSize(size);
            } else if ('clear' === type) {
                classToAdd = '';
            } else if (-1 !== ['left', 'right', 'middle', 'even'].indexOf(type)) {
                classToAdd = index => hashFormBuilder.getClassForBlock(size, type, index);
            } else {
                classToAdd = index => hashFormBuilder.getLayoutClassForSize(type[index]);
            }

            $fields.each(hashFormBuilder.getSyncLayoutClass(classToAdd));
        },

        getSyncLayoutClass: function (classToAdd) {
            return function (itemIndex) {
                const currentClassToAdd = 'function' === typeof classToAdd ? classToAdd(itemIndex) : classToAdd;

                const fieldId = this.dataset.fid;
                if ('undefined' === typeof fieldId) {
                    // we are syncing the drag/drop placeholder before the actual field has loaded.
                    // this will get called again afterward and the input will exist then.
                    this.classList.add(currentClassToAdd);
                    return;
                }

                hashFormBuilder.moveFieldSettings(document.getElementById('hf-fields-settings-' + fieldId));
                const gridClassInput = document.getElementById('hf-grid-class-' + fieldId);

                if (null === gridClassInput) {
                    // not every field type has a layout class input.
                    return;
                }

                gridClassInput.value = currentClassToAdd;
                hashFormBuilder.changeFieldClass(document.getElementById('hf-editor-field-id-' + fieldId), currentClassToAdd);
            };
        },

        changeFieldClass: function (field, setting) {
            const classes = field.className.split(' ').filter(value => value.indexOf('hf-grid-') !== 0);
            classes.push(setting);
            field.className = classes.join(' ');
        },

        /* -------------------------------------------------------------------
         * TinyMCE change tracking
         * ---------------------------------------------------------------- */

        setupTinyMceEventHandlers: function (editor) {
            editor.on('Change', () => hashFormBuilder.handleTinyMceChange(editor));
        },

        handleTinyMceChange: function (editor) {
            if (!hashFormBuilder.isTinyMceActive() || tinyMCE.activeEditor.isHidden()) {
                return;
            }

            editor.targetElm.value = editor.getContent();
            $(editor.targetElm).trigger('change');
        },

        isTinyMceActive: function () {
            const activeSettings = document.querySelector('.hf-fields-settings:not(.hf-hidden)');
            if (!activeSettings) {
                return false;
            }

            const wrapper = activeSettings.querySelector('.wp-editor-wrap');
            return null !== wrapper && wrapper.classList.contains('tmce-active');
        },

        /* -------------------------------------------------------------------
         * Range slider fields
         * ---------------------------------------------------------------- */

        /**
         * Announces that a field's markup on the canvas has been created or
         * replaced.
         *
         * Field types that need JavaScript applied to their preview — a
         * select2 dropdown, a picker, anything the server cannot render on
         * its own — listen for this instead of each one needing its own call
         * added here. Add-on field types have no other way in.
         *
         * @param {number|string|null} fieldId The field affected, if known.
         */
        notifyFieldMarkupChanged: function (fieldId) {
            document.dispatchEvent(new CustomEvent('hashform_field_markup_changed', {
                detail: {fieldId: typeof fieldId === 'undefined' ? null : fieldId}
            }));
        },

        /**
         * Wires up any slider the server has rendered but jQuery UI has not
         * seen yet.
         *
         * Runs immediately: every caller invokes it once the new markup is
         * already in the document, so the second-long timer this used to sit
         * behind only left a freshly added slider dead to the touch for that
         * second.
         *
         * Sliders that are already wired are skipped. Moving a field
         * relocates its node rather than rebuilding it, so its slider comes
         * through the move intact and re-initialising would discard the
         * handle's state for nothing.
         */
        maybeFixRangeSlider: function () {
            $(document).find('.hf-range-input-selector').each(function () {
                const newSlider = $(this);
                const $track = newSlider.prev('.hf-range-slider');

                if (!$track.length || $track.hasClass('ui-slider')) {
                    return;
                }

                $track.slider({
                    value: newSlider.val(),
                    min: parseFloat(newSlider.attr('min')),
                    max: parseFloat(newSlider.attr('max')),
                    step: parseFloat(newSlider.attr('step')),
                    range: 'min',
                    slide: function (e, ui) {
                        $(this).next().val(ui.value);
                    }
                });
            });
        },

        /* -------------------------------------------------------------------
         * Multi step fields
         * ---------------------------------------------------------------- */

        renumberMultiSteps: function () {
            $('.hf-step-num').each(function (index) {
                this.textContent = index + 1;
            });
        },

        resetToFirstStep: function () {
            if ($('.hf-step-item').length > 1) {
                $('.hf-step-item#hf-first-step').trigger('click');
            }
        },

        /* -------------------------------------------------------------------
         * Field option helpers (also used by backend.js)
         * ---------------------------------------------------------------- */

        resetOptionTextDetails: function () {
            $('.hf-fields-settings ul input[type="text"][name^="field_options[options_"]').filter('[data-value-on-load]').removeAttr('data-value-on-load');
            $('input[type="hidden"][name^=optionmap]').remove();
        },

        addBlankSelectOption: function (field, placeholder) {
            const opt = document.createElement('option');
            const firstChild = field.firstChild;

            opt.value = '';
            opt.innerHTML = placeholder;

            if (firstChild !== null) {
                field.insertBefore(opt, firstChild);
                field.selectedIndex = 0;
            } else {
                field.appendChild(opt);
            }
        },

        getImageLabel: function (label, showLabelWithImage, imageUrl) {
            let fullLabel = '<div class="hf-field-is-image">';
            fullLabel += '<span class="hf-field-is-checked mdi-check-circle"></span>';
            if (imageUrl) {
                fullLabel += '<img src="' + imageUrl + '" alt="' + label + '" />';
            }
            fullLabel += '</div>';
            fullLabel += '<div class="hf-field-is-label">' + label + '</div>';

            const imageLabelClass = showLabelWithImage ? ' hf-field-is-has-label' : '';

            return '<div class="hf-field-is-container' + imageLabelClass + '">' + fullLabel + '</div>';
        },

        getImageUrlFromInput: function (optVal) {
            const wrapper = $(optVal).closest('li').find('.hf-is-image-preview');
            if (!wrapper.length) {
                return '';
            }

            const img = wrapper.find('img');
            if (!img.length) {
                return '';
            }

            return img.attr('src');
        },

        getChecked: function (id) {
            const field = $('.' + id);
            if (field.length === 0) {
                return false;
            }

            const checkbox = field.siblings('.hf-choice-input');
            return checkbox.length && checkbox.prop('checked');
        },

        removeWPUnload: function () {
            window.onbeforeunload = null;
            const w = $(window);
            w.off('beforeunload.widgets');
            w.off('beforeunload.edit-post');
        }
    };

    $(function () {
        hashFormBuilder.init();
    });

})(jQuery);
