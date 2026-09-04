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

            /*
             * A second save while the first is in flight would create a second
             * template, because a new one still carries an id of 0 until the
             * answer comes back.
             */
            if ($button.prop('disabled')) {
                return;
            }

            $button.prop('disabled', true).addClass('hf-button-loader');

            /*
             * The name field is printed in the header bar, which sits outside
             * this form; it is tied here with the form attribute, and FormData
             * collects form-associated controls wherever they are.
             */
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

                    // A template that did not exist before this save does, now,
                    // and under an id this form has never seen. Reload onto it
                    // rather than saving a second copy next time.
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                        return;
                    }

                    notify(response.data && response.data.message ? response.data.message : hf_st_obj.saved, 'success');
                },
                error: function () {
                    // Said out loud rather than logged: the request never
                    // reached the server, and silence reads as a save.
                    notify(hf_st_obj.save_failed, 'warning');
                },
                complete: function () {
                    $button.prop('disabled', false).removeClass('hf-button-loader');
                }
            });
        });
    });
})(jQuery);
