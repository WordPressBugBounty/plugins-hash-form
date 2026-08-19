(function ($) {
    'use strict';

    $(function () {
        let alertTimer;

        // Style templates are saved over AJAX instead of the normal post save,
        // so the page (and the live preview) stays put.
        $('form#post').on('submit', function (e) {
            e.preventDefault();

            const $formBtn = $(this).find('#publishing-action button');
            $formBtn.addClass('hf-button-loader');

            const postId = $('#post_ID').val();
            const formData = new FormData(this);
            formData.append('action', 'hashform_save_style_template');

            $.ajax({
                url: hf_st_obj.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (!response.success) {
                        console.log('Failed to save.');
                        return;
                    }

                    const $alert = $('.hf-alert');
                    $alert.addClass('hf-alert-success hf-alert-active');
                    $alert.find('span').html(response.data.message);

                    clearTimeout(alertTimer);
                    alertTimer = setTimeout(function () {
                        $alert.removeClass('hf-alert-active hf-alert-success hf-alert-warning hf-alert-neutral');
                    }, 3500);

                    // A freshly created template starts on post-new.php; point
                    // the URL at the edit screen so a reload doesn't create
                    // another one.
                    window.history.pushState(null, '', hf_st_obj.admin_url + '?post=' + postId + '&action=edit');
                },
                complete: function () {
                    $formBtn.removeClass('hf-button-loader');
                }
            });
        });
    });
})(jQuery);
