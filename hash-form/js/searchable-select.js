/**
 * Searchable dropdowns for the plugin's admin screens.
 *
 * Applies to selects with more options than the threshold (filter
 * hashform_searchable_select_threshold, default 10) or marked data-hf-searchable.
 * The <select> is never replaced; choosing sets its value and fires one change.
 * Skipped: multiple/size>1, select2, data-hf-native, touch-first devices.
 */
(function () {
    'use strict';

    var config = window.hashformSearchableSelect || {};
    var text = config.i18n || {};
    var popup = null;
    var uid = 0;
    var threshold = parseInt(config.threshold, 10);

    if (isNaN(threshold) || threshold < 0) {
        threshold = 10;
    }

    function t(key, fallback) {
        return text[key] || fallback;
    }

    // Touch-first devices keep the native picker.
    if (window.matchMedia && window.matchMedia('(hover: none) and (pointer: coarse)').matches) {
        return;
    }

    function eligible(select) {
        return select
                && select.tagName === 'SELECT'
                && !select.multiple
                && !(select.size > 1)
                && !select.disabled
                && !select.classList.contains('select2-hidden-accessible')
                && !select.closest('[data-hf-native]')
                && !select.closest('#screen-meta')
                // Skip hidden selects standing in for another control (chosen, segmented buttons).
                && 'true' !== select.getAttribute('aria-hidden')
                && null !== select.offsetParent
                && needsSearch(select);
    }

    function needsSearch(select) {
        if (select.closest('[data-hf-searchable]')) {
            return true;
        }

        return select.options.length > threshold;
    }

    // Case and accents do not matter: "curacao" finds "Curaçao".
    function fold(value) {
        return String(value || '')
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .toLowerCase();
    }

    /* ------------------------------------------------------------------
     * The list
     * --------------------------------------------------------------- */

    function build(select) {
        var id = 'hf-ss-' + (++uid);
        var wrap = document.createElement('div');
        wrap.className = 'hf-ss';
        wrap.setAttribute('data-hf-ss', '');

        var search = document.createElement('input');
        search.type = 'search';
        search.className = 'hf-ss-search';
        search.placeholder = t('search', 'Search');
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-expanded', 'true');
        search.setAttribute('aria-controls', id);
        search.setAttribute('aria-autocomplete', 'list');
        search.setAttribute('autocomplete', 'off');
        search.setAttribute('spellcheck', 'false');

        var label = labelFor(select);

        if (label) {
            search.setAttribute('aria-label', label);
        }

        var list = document.createElement('ul');
        list.className = 'hf-ss-list';
        list.id = id;
        list.setAttribute('role', 'listbox');

        var empty = document.createElement('p');
        empty.className = 'hf-ss-empty';
        empty.hidden = true;
        empty.textContent = t('noResults', 'No matches');

        var status = document.createElement('p');
        status.className = 'hf-ss-status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');

        wrap.appendChild(search);
        wrap.appendChild(list);
        wrap.appendChild(empty);
        wrap.appendChild(status);

        var items = [];

        Array.prototype.forEach.call(select.children, function (node) {
            if (node.tagName === 'OPTGROUP') {
                var group = document.createElement('li');
                group.className = 'hf-ss-group';
                group.setAttribute('role', 'presentation');
                group.textContent = node.label;
                list.appendChild(group);

                var members = [];

                Array.prototype.forEach.call(node.children, function (option) {
                    var item = addOption(option, node.disabled);

                    if (item) {
                        item.group = group;
                        members.push(item);
                    }
                });

                group.members = members;
            } else if (node.tagName === 'OPTION') {
                addOption(node, false);
            }
        });

        function addOption(option, groupDisabled) {
            if (option.hidden) {
                return null;
            }

            var li = document.createElement('li');
            li.className = 'hf-ss-option';
            li.id = id + '-' + items.length;
            li.setAttribute('role', 'option');
            li.textContent = option.textContent;

            var disabled = option.disabled || groupDisabled;

            if (disabled) {
                li.setAttribute('aria-disabled', 'true');
            }

            var selected = option.selected;
            li.setAttribute('aria-selected', selected ? 'true' : 'false');

            list.appendChild(li);

            var item = {
                el: li,
                option: option,
                disabled: disabled,
                haystack: fold(option.textContent + ' ' + option.value)
            };

            items.push(item);

            return item;
        }

        return {wrap: wrap, search: search, list: list, empty: empty, status: status, items: items};
    }

    function labelFor(select) {
        if (select.getAttribute('aria-label')) {
            return select.getAttribute('aria-label');
        }

        if (select.id) {
            var label = document.querySelector('label[for="' + CSS.escape(select.id) + '"]');

            if (label) {
                return label.textContent.trim();
            }
        }

        var wrapping = select.closest('label');

        return wrapping ? wrapping.textContent.trim() : '';
    }

    /* ------------------------------------------------------------------
     * Open, filter, choose, close
     * --------------------------------------------------------------- */

    function open(select) {
        if (popup && popup.select === select) {
            return;
        }

        close(false);

        var ui = build(select);
        document.body.appendChild(ui.wrap);

        popup = {select: select, ui: ui, visible: ui.items.slice(), active: -1};

        place();
        filter('');

        // Start on the current choice, so Enter keeps it and arrows move from it.
        var current = popup.visible.findIndex(function (item) {
            return item.option.selected && !item.disabled;
        });

        setActive(current >= 0 ? current : firstEnabled(0, 1));

        ui.search.addEventListener('input', function () {
            filter(ui.search.value);
            setActive(firstEnabled(0, 1));
        });

        ui.search.addEventListener('keydown', onSearchKey);

        ui.list.addEventListener('mousedown', function (event) {
            // Keep focus in the search box while a choice is being clicked.
            event.preventDefault();
        });

        ui.list.addEventListener('click', function (event) {
            var li = event.target.closest('.hf-ss-option');

            if (!li) {
                return;
            }

            var index = popup.visible.findIndex(function (item) {
                return item.el === li;
            });

            if (index >= 0) {
                choose(popup.visible[index]);
            }
        });

        ui.search.focus();
    }

    function filter(term) {
        var needle = fold(term).trim();
        var ui = popup.ui;

        popup.visible = [];

        ui.items.forEach(function (item) {
            var show = !needle || item.haystack.indexOf(needle) !== -1;
            item.el.hidden = !show;

            if (show) {
                popup.visible.push(item);
            }
        });

        // A group heading shows while any of its options do.
        Array.prototype.forEach.call(ui.list.querySelectorAll('.hf-ss-group'), function (group) {
            group.hidden = !(group.members || []).some(function (item) {
                return !item.el.hidden;
            });
        });

        ui.empty.hidden = popup.visible.length > 0;

        if (needle) {
            var count = popup.visible.length;
            ui.status.textContent = 1 === count
                    ? t('oneResult', '1 result')
                    : t('results', '%d results').replace('%d', count);
        } else {
            ui.status.textContent = '';
        }
    }

    function firstEnabled(from, step) {
        var visible = popup.visible;

        for (var i = from; i >= 0 && i < visible.length; i += step) {
            if (!visible[i].disabled) {
                return i;
            }
        }

        return -1;
    }

    function setActive(index) {
        var ui = popup.ui;

        popup.visible.forEach(function (item) {
            item.el.classList.remove('is-active');
        });

        popup.active = index;

        if (index < 0 || !popup.visible[index]) {
            ui.search.removeAttribute('aria-activedescendant');
            return;
        }

        var el = popup.visible[index].el;
        el.classList.add('is-active');
        ui.search.setAttribute('aria-activedescendant', el.id);

        // Keep the active option in view inside the list.
        var list = ui.list;

        if (el.offsetTop < list.scrollTop) {
            list.scrollTop = el.offsetTop;
        } else if (el.offsetTop + el.offsetHeight > list.scrollTop + list.clientHeight) {
            list.scrollTop = el.offsetTop + el.offsetHeight - list.clientHeight;
        }
    }

    function onSearchKey(event) {
        var key = event.key;

        if ('ArrowDown' === key || 'ArrowUp' === key) {
            event.preventDefault();

            var step = 'ArrowDown' === key ? 1 : -1;
            var start = popup.active < 0 ? (step > 0 ? 0 : popup.visible.length - 1) : popup.active + step;
            var next = firstEnabled(start, step);

            if (next >= 0) {
                setActive(next);
            }
        } else if ('Home' === key && !popup.ui.search.value) {
            event.preventDefault();
            setActive(firstEnabled(0, 1));
        } else if ('End' === key && !popup.ui.search.value) {
            event.preventDefault();
            setActive(firstEnabled(popup.visible.length - 1, -1));
        } else if ('Enter' === key) {
            event.preventDefault();

            if (popup.active >= 0) {
                choose(popup.visible[popup.active]);
            }
        } else if ('Escape' === key) {
            event.preventDefault();
            event.stopPropagation();
            close(true);
        } else if ('Tab' === key) {
            close(true);
        }
    }

    function choose(item) {
        if (!item || item.disabled) {
            return;
        }

        var select = popup.select;
        var changed = !item.option.selected;

        close(true);

        if (!changed) {
            return;
        }

        item.option.selected = true;

        // Native events, so jQuery and addEventListener handlers alike hear one input and one change.
        select.dispatchEvent(new Event('input', {bubbles: true}));
        select.dispatchEvent(new Event('change', {bubbles: true}));
    }

    function close(refocus) {
        if (!popup) {
            return;
        }

        var select = popup.select;
        popup.ui.wrap.remove();
        popup = null;

        if (refocus && document.contains(select)) {
            select.focus({preventScroll: true});
        }
    }

    /* ------------------------------------------------------------------
     * Placement
     * --------------------------------------------------------------- */

    function place() {
        if (!popup) {
            return;
        }

        var rect = popup.select.getBoundingClientRect();

        // The select scrolled away or was hidden: the list goes with it.
        if (!rect.width || rect.bottom < 0 || rect.top > window.innerHeight) {
            close(false);
            return;
        }

        var wrap = popup.ui.wrap;
        var width = Math.max(rect.width, 240);
        var left = Math.min(rect.left, window.innerWidth - width - 8);

        wrap.style.width = width + 'px';
        wrap.style.left = Math.max(8, left) + 'px';

        // Below the select, or above it when there is more room there.
        var below = window.innerHeight - rect.bottom - 8;
        var above = rect.top - 8;
        var height = wrap.offsetHeight;

        if (height > below && above > below) {
            wrap.style.top = Math.max(8, rect.top - height - 4) + 'px';
            wrap.classList.add('is-above');
        } else {
            wrap.style.top = (rect.bottom + 4) + 'px';
            wrap.classList.remove('is-above');
        }
    }

    /* ------------------------------------------------------------------
     * Wiring - delegated, so selects added later are covered too
     * --------------------------------------------------------------- */

    document.addEventListener('mousedown', function (event) {
        if (popup && !popup.ui.wrap.contains(event.target) && event.target !== popup.select) {
            close(false);
        }

        var select = event.target;

        if (0 !== event.button || !eligible(select)) {
            return;
        }

        // Instead of the browser's own list.
        event.preventDefault();
        select.focus({preventScroll: true});

        if (popup && popup.select === select) {
            close(true);
        } else {
            open(select);
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        var select = event.target;

        if (!eligible(select) || popup) {
            return;
        }

        var opens = ' ' === event.key
                || 'Enter' === event.key
                || 'F4' === event.key
                || (event.altKey && ('ArrowDown' === event.key || 'ArrowUp' === event.key));

        // Arrows and typed letters still change the value the native way.
        if (opens) {
            event.preventDefault();
            open(select);
        }
    }, true);

    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, true);
})();
