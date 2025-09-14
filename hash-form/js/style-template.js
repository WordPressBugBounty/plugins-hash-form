(function ($) {
    "use strict";
    $(document).ready(function ($) {
        $('form#post').on('submit', function (e) {
            e.preventDefault();
            const $formBtn = $(this).find('#publishing-action button');
            $formBtn.addClass('hf-button-loader');

            var postId = $('#post_ID').val();
            var formData = $(this).serializeArray();
            var formData = new FormData(this);
            formData.append('action', 'hashform_save_style_template');

            $.ajax({
                url: hf_st_obj.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        $('.hf-alert').addClass('hf-alert-success');
                        $('.hf-alert span').html(response.data.message);
                        $('.hf-alert').addClass('hf-alert-active');
                        $('.hf-footer .button').removeClass('hf-button-loader');
                        clearTimeout();

                        setTimeout(function () {
                            if ($('.hf-alert').hasClass('hf-alert-active')) {
                                $('.hf-alert').removeClass('hf-alert-active');
                                $('.hf-alert').removeClass('hf-alert-success hf-alert-warning hf-alert-neutral');
                            }
                        }, 3500);
                        window.history.pushState(null, '', hf_st_obj.admin_url + '?post=' + postId + '&action=edit');
                    } else {
                        console.log('Failed to save.');
                    }
                }
            });
        });

    });
})(jQuery);
