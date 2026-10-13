(function ($) {
    'use strict';

    $(function () {
        let alertTimer;

        function notify(message, tone) {
            const $alert = $('.hf-alert');

            if (!$alert.length) {
                return;
            }

            $alert
                .removeClass('hf-alert-success hf-alert-warning hf-alert-neutral')
                .addClass('hf-alert-active hf-alert-' + tone);
            // text, not html: this is a message, never markup.
            $alert.find('span').text(message);

            clearTimeout(alertTimer);
            alertTimer = setTimeout(function () {
                $alert.removeClass('hf-alert-active hf-alert-success hf-alert-warning hf-alert-neutral');
            }, 3500);
        }

        // Style templates are saved over AJAX instead of the normal post save,
        // so the page (and the live preview) stays put.
        $('form#post').on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('.hf-style-save');

            // Ignore a second save while the first is in flight: a new template has id 0 until it returns.
            if ($button.prop('disabled')) {
                return;
            }

            $button.prop('disabled', true).addClass('hf-button-loader');

            // The name field sits in the header bar outside this form, tied to it by the form attribute.
            const formData = new FormData(this);
            formData.append('action', 'hashform_save_style_template');

            $.ajax({
                url: hf_st_obj.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (!response || !response.success) {
                        notify(hf_st_obj.save_failed, 'warning');
                        return;
                    }

                    // A newly created template has a new id; reload onto it so the next save does not duplicate it.
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                        return;
                    }

                    notify(response.data && response.data.message ? response.data.message : hf_st_obj.saved, 'success');
                },
                error: function () {
                    notify(hf_st_obj.save_failed, 'warning');
                },
                complete: function () {
                    $button.prop('disabled', false).removeClass('hf-button-loader');
                }
            });
        });
    });
})(jQuery);
