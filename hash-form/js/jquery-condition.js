/**
 * Show/hide elements based on the state of other form controls.
 *
 * `data-condition-toggle="id1,id2+id3"`: commas are OR, `+` is AND. Controls with
 * `data-condition="toggle"` re-run the scan on change. `data-condition-val` lists
 * the select values that match, or excludes one with a `!` prefix.
 */
(function ($) {
    'use strict';

    $.fn.conditionToggle = function (settings) {
        const _settings = $.extend({
            callback: function () {
                toggleScan();
            }
        }, settings);

        return this.each(function () {
            $(this).on('change', _settings.callback);
        });
    };

    function toggleScan() {
        $('[data-condition-toggle]').each(function () {
            $(this).toggle(checkValue($(this)));
        });
    }

    // Comma-separated conditions: visible if ANY of them matches.
    function checkValue($el) {
        const conditions = $el.data('condition-toggle').split(',');
        const pick = $el.data('condition-val');

        return conditions.some(function (condition) {
            if (condition.indexOf('+') < 0) {
                return isToggle(condition, pick);
            }
            // `+`-combined controls must ALL match.
            return condition.split('+').every(function (id) {
                return isToggle(id, pick);
            });
        });
    }

    // Whether the control with this id currently matches.
    function isToggle(id, pick) {
        const element = $('#' + id);
        if ('select' === element.prop('tagName').toLowerCase()) {
            return checkSelect(element, pick);
        }
        return element.is(':checked');
    }

    function checkSelect(element, pick) {
        if (0 === pick.length) {
            return false;
        }

        pick = pick.toString();
        const selected = element.find(':selected').val();

        // `!value` excludes a single value; otherwise pick is a list of
        // accepted values.
        if (pick.indexOf('!') < 0) {
            return pick.split(',').indexOf(selected) >= 0;
        }
        return '!' + selected !== pick;
    }

    $(function () {
        toggleScan();
        $('[data-condition="toggle"]').conditionToggle();
    });
})(jQuery);
