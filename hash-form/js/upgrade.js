/* Upgrade to Pro: topic tabs, search and the "only what Pro adds" filter. */
(function () {
    'use strict';

    var root = document.querySelector('[data-hf-upgrade]');

    if (!root) {
        return;
    }

    var tabs = Array.prototype.slice.call(root.querySelectorAll('.hf-up-tab'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('.hf-up-panel'));
    var search = root.querySelector('.hf-up-search');
    var onlyPro = root.querySelector('.hf-up-only-pro');
    var empty = root.querySelector('.hf-up-empty');
    var current = '';

    function filter() {
        var query = search.value.trim().toLowerCase();
        var any = false;

        panels.forEach(function (panel) {
            var visible = 0;

            Array.prototype.forEach.call(panel.querySelectorAll('tbody tr'), function (row) {
                var show = (!onlyPro.checked || row.classList.contains('hf-cmp-row-pro'))
                        && (!query || row.textContent.toLowerCase().indexOf(query) !== -1);

                row.hidden = !show;
                visible += show ? 1 : 0;
            });

            panel.hidden = !(visible && ('all' === current || panel.getAttribute('data-topic') === current));
            any = any || !panel.hidden;
        });

        empty.hidden = any;
    }

    function show(topic, remember) {
        current = topic;

        tabs.forEach(function (tab) {
            var selected = tab.getAttribute('data-topic') === topic;

            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
            tab.classList.toggle('is-active', selected);
        });

        root.classList.toggle('hf-up-all', 'all' === topic);
        filter();

        // Lets a link elsewhere open the screen on one topic.
        if (remember && window.history && history.replaceState) {
            history.replaceState(null, '', '#' + topic);
        }
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () {
            // The search and the filter are only offered across every topic.
            if ('all' !== tab.getAttribute('data-topic')) {
                search.value = '';
                onlyPro.checked = false;
            }

            show(tab.getAttribute('data-topic'), true);
        });

        tab.addEventListener('keydown', function (event) {
            var step = 'ArrowDown' === event.key || 'ArrowRight' === event.key ? 1
                    : ('ArrowUp' === event.key || 'ArrowLeft' === event.key ? -1 : 0);

            if (!step) {
                return;
            }

            event.preventDefault();

            var next = tabs[(index + step + tabs.length) % tabs.length];

            next.focus();
            next.click();
        });
    });

    search.addEventListener('input', filter);

    onlyPro.addEventListener('change', filter);

    var wanted = window.location.hash.replace('#', '');
    var known = tabs.some(function (tab) {
        return tab.getAttribute('data-topic') === wanted;
    });

    root.classList.add('hf-up-js');
    show(known ? wanted : tabs[0].getAttribute('data-topic'), false);
})();
