<?php
defined('ABSPATH') || die();
/*
 * Free vs Pro, one topic at a time.
 *
 * Counts must match what the plugins ship: 27 free field types + 23 Pro (50), 79 templates, 20 gateways, 73 modules, 18 pre-built styles.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.

$hf_yes = function ($text = '') { return array($text, 'yes'); };
$hf_no = function ($text = '') { return array($text, 'no'); };
$hf_plain = function ($text) { return array($text, 'plain'); };

$hf_site = HashFormUpgrade::PRO_URL;
$hf_docs = HashFormUpgrade::PRO_DOCS_URL;

// Each row: feature, description (or a list of names), free cell, pro cell, optional link.
$hf_topics = array(
    'payments' => array(
        'title' => esc_html__('Payments', 'hash-form'),
        'lede' => esc_html__('Turn any form into a paid one. Add a Payment field, price it with products and calculations, and the total is worked out on the server.', 'hash-form'),
        'free' => esc_html__('Forms collect information only.', 'hash-form'),
        'pro' => esc_html__('20 gateways, subscriptions, coupons and a Payments screen for every transaction.', 'hash-form'),
        'links' => array(
            array(esc_html__('See payment forms', 'hash-form'), $hf_site . 'features/payments/'),
            array(esc_html__('All 20 gateways', 'hash-form'), $hf_site . 'integrations/payments/'),
        ),
        'rows' => array(
            array(esc_html__('Take payments on a form', 'hash-form'), '', $hf_no(), $hf_yes()),
            array(esc_html__('Payment gateways', 'hash-form'), array('Stripe', 'PayPal', 'Mollie', 'Paystack', 'Razorpay', 'Authorize.Net', '2Checkout', 'Square', 'Flutterwave', 'Cashfree', 'Coinbase', 'Braintree', 'Paddle', 'Lemon Squeezy', 'PayU', 'Instamojo', 'Mercado Pago', 'PayFast', 'GoCardless', 'Adyen'), $hf_no(), $hf_plain(esc_html__('20', 'hash-form')), $hf_site . 'integrations/payments/'),
            array(esc_html__('Subscriptions', 'hash-form'), esc_html__('Recurring payments with a trial and a cycle count.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Discount codes', 'hash-form'), esc_html__('Priced on the server, so the total cannot be edited in the browser.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Emails held until payment', 'hash-form'), esc_html__('An abandoned checkout sends no confirmation.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Payments and coupons screens', 'hash-form'), esc_html__('Every transaction in one list, with a detail view.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('WooCommerce products on a form', 'hash-form'), esc_html__('Read products and prices from your shop.', 'hash-form'), $hf_no(), $hf_yes()),
        ),
    ),
    'ai' => array(
        'title' => esc_html__('AI form builder', 'hash-form'),
        'lede' => esc_html__('Describe the form you want in a sentence and it is built on the canvas, with fields, labels, options and layout ready to edit.', 'hash-form'),
        'free' => esc_html__('Every form is built by hand.', 'hash-form'),
        'pro' => esc_html__('Forms generated from a description, and formulas written for you.', 'hash-form'),
        'links' => array(
            array(esc_html__('See the AI form builder', 'hash-form'), $hf_site . 'features/ai-form-builder/'),
        ),
        'rows' => array(
            array(esc_html__('AI form generation', 'hash-form'), esc_html__('Describe the form you want and have it built.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Your choice of model, with your own API key', 'hash-form'), array('ChatGPT', 'Claude', 'Gemini', 'DeepSeek', 'Mistral'), $hf_no(), $hf_plain(esc_html__('5', 'hash-form')), $hf_site . 'integrations/ai/'),
            array(esc_html__('Formula assistant', 'hash-form'), esc_html__('Writes and checks a calculation formula from a plain description, with no API key.', 'hash-form'), $hf_no(), $hf_yes()),
        ),
    ),
    'templates' => array(
        'title' => esc_html__('Templates and styles', 'hash-form'),
        'lede' => esc_html__('Start from a finished form and a finished look instead of a blank canvas. Both open as ordinary forms and styles you can change however you like.', 'hash-form'),
        'free' => esc_html__('Full styling controls and your own saved style templates.', 'hash-form'),
        'pro' => esc_html__('79 form templates, 18 pre-built styles, and popup forms.', 'hash-form'),
        'links' => array(
            array(esc_html__('Try the 79 templates', 'hash-form'), $hf_site . 'form-templates/'),
            array(esc_html__('See the 18 styles', 'hash-form'), $hf_site . 'form-styles/'),
        ),
        'rows' => array(
            array(esc_html__('Ready-made templates', 'hash-form'), array(esc_html__('Simple (5)', 'hash-form'), esc_html__('Contact (8)', 'hash-form'), esc_html__('Marketing (6)', 'hash-form'), esc_html__('Feedback (8)', 'hash-form'), esc_html__('Business (9)', 'hash-form'), esc_html__('Education (3)', 'hash-form'), esc_html__('Healthcare (4)', 'hash-form'), esc_html__('Real estate (3)', 'hash-form'), esc_html__('Events (3)', 'hash-form'), esc_html__('Hospitality (3)', 'hash-form'), esc_html__('Nonprofit (2)', 'hash-form'), esc_html__('Workplace (4)', 'hash-form'), esc_html__('Payments (6)', 'hash-form'), esc_html__('Multi-step (4)', 'hash-form'), esc_html__('Layouts (3)', 'hash-form'), esc_html__('Advanced (8)', 'hash-form')), $hf_no(), $hf_plain(esc_html__('79', 'hash-form')), $hf_site . 'form-templates/'),
            array(esc_html__('Pre-built styles', 'hash-form'), array(esc_html__('Minimal', 'hash-form'), esc_html__('Soft Rounded', 'hash-form'), esc_html__('Corporate', 'hash-form'), esc_html__('Compact', 'hash-form'), esc_html__('Underline', 'hash-form'), esc_html__('Outline', 'hash-form'), esc_html__('Ocean', 'hash-form'), esc_html__('Terracotta', 'hash-form'), esc_html__('Pastel Pop', 'hash-form'), esc_html__('Bold', 'hash-form'), esc_html__('Dark Mode', 'hash-form'), esc_html__('Neon Night', 'hash-form'), esc_html__('Luxe', 'hash-form'), esc_html__('High Contrast', 'hash-form'), esc_html__('Sage', 'hash-form'), esc_html__('Notebook', 'hash-form'), esc_html__('Sunset', 'hash-form'), esc_html__('Chat Bubble', 'hash-form')), $hf_no(), $hf_plain(esc_html__('18', 'hash-form')), $hf_site . 'form-styles/'),
            array(esc_html__('Popup and flying button', 'hash-form'), esc_html__('Show a form over the page instead of in it.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Full styling controls', 'hash-form'), esc_html__('Typography, colour and spacing on every part of the form.', 'hash-form'), $hf_yes(), $hf_yes()),
            array(esc_html__('Style templates', 'hash-form'), esc_html__('Save a look once and reuse it across forms.', 'hash-form'), $hf_yes(), $hf_yes()),
            array(esc_html__('Shortcode, block and Elementor widget', 'hash-form'), '', $hf_yes(), $hf_yes()),
        ),
    ),
    'fields' => array(
        'title' => esc_html__('Fields and form logic', 'hash-form'),
        'lede' => esc_html__('The free plugin has the fields most forms need, with rows, columns and conditional logic. Pro adds the ones longer and smarter forms need.', 'hash-form'),
        'free' => esc_html__('27 field types, rows and columns, conditional logic.', 'hash-form'),
        'pro' => esc_html__('50 field types, with steps, repeating groups, live totals and signatures.', 'hash-form'),
        'links' => array(
            array(esc_html__('See all 50 field types', 'hash-form'), $hf_site . 'features/field-types/'),
            array(esc_html__('Multi-step forms', 'hash-form'), $hf_site . 'features/multi-step-forms/'),
            array(esc_html__('Calculations', 'hash-form'), $hf_site . 'features/calculations/'),
        ),
        'rows' => array(
            array(esc_html__('Field types', 'hash-form'), esc_html__('Everything in the free plugin, plus the 23 below.', 'hash-form'), $hf_plain(esc_html__('27', 'hash-form')), $hf_plain(esc_html__('50', 'hash-form'))),
            array(esc_html__('Pro field types', 'hash-form'), array(esc_html__('Checkbox Grid', 'hash-form'), esc_html__('Matrix Input', 'hash-form'), esc_html__('Sortable List', 'hash-form'), esc_html__('Rating Scale', 'hash-form'), esc_html__('Yes/No', 'hash-form'), esc_html__('Toggle', 'hash-form'), esc_html__('Advanced Select', 'hash-form'), esc_html__('Chained Select', 'hash-form'), esc_html__('Signature', 'hash-form'), esc_html__('Date & Time', 'hash-form'), esc_html__('Calculation', 'hash-form'), esc_html__('Repeater Field', 'hash-form'), esc_html__('Multi Step', 'hash-form'), esc_html__('Color Picker', 'hash-form'), esc_html__('Coupon', 'hash-form'), esc_html__('Shortcode', 'hash-form'), esc_html__('Button', 'hash-form'), esc_html__('GDPR Agreement', 'hash-form'), esc_html__('Payment', 'hash-form'), esc_html__('Product', 'hash-form'), esc_html__('hCaptcha', 'hash-form'), esc_html__('Turnstile', 'hash-form'), esc_html__('Math Captcha', 'hash-form')), $hf_no(), $hf_plain(esc_html__('23', 'hash-form')), $hf_site . 'features/field-types/'),
            array(esc_html__('Multi-step forms', 'hash-form'), esc_html__('Break a long form into steps with a progress indicator.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Repeating field groups', 'hash-form'), esc_html__('Let a visitor add another guest, item or line.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Calculations', 'hash-form'), esc_html__('Total the form up as it is filled in.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Signatures', 'hash-form'), esc_html__('Drawn on screen and stored with the entry.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('GDPR consent field', 'hash-form'), esc_html__('A consent tick with your own wording, recorded against the entry.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Forms and entries', 'hash-form'), esc_html__('No cap in either version.', 'hash-form'), $hf_yes(esc_html__('Unlimited', 'hash-form')), $hf_yes(esc_html__('Unlimited', 'hash-form'))),
            array(esc_html__('Rows and columns', 'hash-form'), esc_html__('Arrange fields on a real canvas rather than one per line.', 'hash-form'), $hf_yes(), $hf_yes()),
            array(esc_html__('Conditional logic', 'hash-form'), esc_html__('Show and hide fields based on what has been answered.', 'hash-form'), $hf_yes(), $hf_yes()),
        ),
    ),
    'integrations' => array(
        'title' => esc_html__('Integrations', 'hash-form'),
        'lede' => esc_html__('Send each submission on to the tools you already use. Every integration below is a module you switch on, so a site loads only what it uses.', 'hash-form'),
        'free' => esc_html__('Submissions are stored on your site and emailed.', 'hash-form'),
        'pro' => esc_html__('73 modules across email marketing, CRM, productivity, storage, mail delivery, SMS, payments and AI.', 'hash-form'),
        'links' => array(
            array(esc_html__('Browse all integrations', 'hash-form'), $hf_site . 'integrations/'),
        ),
        'rows' => array(
            array(esc_html__('Modules', 'hash-form'), esc_html__('Switched on one at a time from the Modules screen. The count includes the payment gateways and AI models listed under their own topics.', 'hash-form'), $hf_no(), $hf_plain(esc_html__('73', 'hash-form'))),
            array(esc_html__('Email marketing', 'hash-form'), array('Mailchimp', 'ActiveCampaign', 'Brevo (Sendinblue)', 'Campaign Monitor', 'Constant Contact', 'ConvertKit', 'GetResponse', 'HubSpot', 'MailerLite', 'Mailjet', 'Moosend', 'Salesflare', 'Sendfox', 'Automizy', 'Airtable'), $hf_no(), $hf_plain('15'), $hf_site . 'integrations/email-marketing/'),
            array(esc_html__('CRM', 'hash-form'), array('Zoho CRM', 'Pipedrive', 'Freshsales'), $hf_no(), $hf_plain('3'), $hf_site . 'integrations/crm/'),
            array(esc_html__('Productivity', 'hash-form'), array('Slack', 'Discord', 'Microsoft Teams', 'Mattermost', 'Telegram', 'WhatsApp', 'Trello', 'Google Sheets', 'Google Calendar', 'Webhook', 'CSV Export'), $hf_no(), $hf_plain('11'), $hf_site . 'integrations/productivity/'),
            array(esc_html__('File storage', 'hash-form'), array('Google Drive', 'Dropbox', 'Image Resize'), $hf_no(), $hf_plain('3'), $hf_site . 'integrations/file-storage/'),
            array(esc_html__('Email delivery', 'hash-form'), array('SendGrid', 'Mailgun', 'Postmark', 'Amazon SES'), $hf_no(), $hf_plain('4'), $hf_site . 'integrations/email-delivery/'),
            array(esc_html__('SMS and documents', 'hash-form'), array('Twilio SMS', 'ClickSend SMS', 'PDF Submission'), $hf_no(), $hf_plain('3'), $hf_site . 'integrations/'),
            array(esc_html__('Spam protection', 'hash-form'), array('reCAPTCHA', 'hCaptcha', 'Turnstile', 'Math Captcha', 'Honeypot', 'Akismet'), $hf_plain('1'), $hf_plain('6'), $hf_site . 'integrations/spam-protection/'),
            array(esc_html__('Form metrics', 'hash-form'), array('Form Analytics', 'Google Analytics'), $hf_no(), $hf_plain('2'), $hf_site . 'integrations/form-metrics/'),
        ),
    ),
    'entries' => array(
        'title' => esc_html__('Entries and notifications', 'hash-form'),
        'lede' => esc_html__('Storing, searching and emailing entries is all in the free plugin. Pro adds ways to get entries out, and to control when a form accepts them.', 'hash-form'),
        'free' => esc_html__('Entries, notifications, autoresponder, SMTP and privacy tools.', 'hash-form'),
        'pro' => esc_html__('CSV and PDF, front end entry lists, schedules, limits and post creation.', 'hash-form'),
        'links' => array(
            array(esc_html__('See entries and notifications', 'hash-form'), $hf_site . 'features/entries-and-notifications/'),
        ),
        'rows' => array(
            array(esc_html__('Export entries to CSV', 'hash-form'), '', $hf_no(), $hf_yes()),
            array(esc_html__('PDF of a submission', 'hash-form'), '', $hf_no(), $hf_yes()),
            array(esc_html__('Entries list on the front end', 'hash-form'), esc_html__('Show a form\'s entries on a page to the users allowed to see them.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Schedule, entry limits and login required', 'hash-form'), esc_html__('Open and close a form on a date, or cap how many it takes.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Create a post from a submission', 'hash-form'), esc_html__('A post, a page or a custom post type entry.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Entries stored on your site', 'hash-form'), esc_html__('Every submission kept and searchable.', 'hash-form'), $hf_yes(), $hf_yes()),
            array(esc_html__('Email notifications and autoresponder', 'hash-form'), '', $hf_yes(), $hf_yes()),
            array(esc_html__('SMTP', 'hash-form'), esc_html__('Send through a real mail server rather than the host.', 'hash-form'), $hf_yes(), $hf_yes()),
            array(esc_html__('Duplicate, import and export forms', 'hash-form'), esc_html__('Copy a form, or move one between sites.', 'hash-form'), $hf_yes(), $hf_yes()),
            array(esc_html__('Entry notes and starring', 'hash-form'), esc_html__('Mark and annotate a submission as you work through them.', 'hash-form'), $hf_yes(), $hf_yes()),
            array(esc_html__('Privacy export and erase', 'hash-form'), esc_html__('Answers the data export and erasure requests WordPress handles.', 'hash-form'), $hf_yes(), $hf_yes()),
        ),
    ),
    'spam' => array(
        'title' => esc_html__('Spam protection', 'hash-form'),
        'lede' => esc_html__('Keep bots out without making people prove they are human more than they have to.', 'hash-form'),
        'free' => esc_html__('Google reCAPTCHA v2 and v3.', 'hash-form'),
        'pro' => esc_html__('Three more captchas, a honeypot and Akismet checking.', 'hash-form'),
        'links' => array(
            array(esc_html__('See spam protection', 'hash-form'), $hf_site . 'features/spam-protection/'),
        ),
        'rows' => array(
            array(esc_html__('hCaptcha, Turnstile and math captcha', 'hash-form'), esc_html__('Alternatives to reCAPTCHA, one of them with no outside service.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Honeypot', 'hash-form'), esc_html__('A hidden trap field that bots fill in and people never see.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Akismet', 'hash-form'), esc_html__('Check submissions against the Akismet spam database.', 'hash-form'), $hf_no(), $hf_yes()),
            array(esc_html__('Google reCAPTCHA', 'hash-form'), '', $hf_yes(), $hf_yes()),
        ),
    ),
    'analytics' => array(
        'title' => esc_html__('Form analytics', 'hash-form'),
        'lede' => esc_html__('See how many people saw a form, how many finished it, and which field made the rest give up.', 'hash-form'),
        'free' => esc_html__('You see the entries a form received.', 'hash-form'),
        'pro' => esc_html__('Views, completions, conversion rate and per-field drop-off.', 'hash-form'),
        'links' => array(
            array(esc_html__('See form analytics', 'hash-form'), $hf_site . 'features/form-analytics/'),
        ),
        'rows' => array(
            array(esc_html__('Form analytics', 'hash-form'), array(esc_html__('Impressions', 'hash-form'), esc_html__('Completions', 'hash-form'), esc_html__('Abandonment', 'hash-form'), esc_html__('Conversion rate', 'hash-form'), esc_html__('Per-field drop-off', 'hash-form')), $hf_no(), $hf_yes(), $hf_site . 'features/form-analytics/'),
            array(esc_html__('Google Analytics events', 'hash-form'), esc_html__('Form views and submissions reported to your analytics.', 'hash-form'), $hf_no(), $hf_yes()),
        ),
    ),
);

$hf_stats = array(
    array('50', esc_html__('field types', 'hash-form')),
    array('79', esc_html__('form templates', 'hash-form')),
    array('20', esc_html__('payment gateways', 'hash-form')),
    array('73', esc_html__('modules', 'hash-form')),
    array('18', esc_html__('pre-built styles', 'hash-form')),
);

$hf_cell = function ($value, $column) {
    list($text, $state) = $value;
    $mark = '';

    if ('yes' === $state) {
        $mark = '<span class="hf-cmp-mark" aria-hidden="true">&#10003;</span><span class="screen-reader-text">' . esc_html__('Included', 'hash-form') . '</span>';
    } else if ('no' === $state) {
        $mark = '<span class="hf-cmp-mark" aria-hidden="true">&#8211;</span><span class="screen-reader-text">' . esc_html__('Not included', 'hash-form') . '</span>';
    }

    return '<td class="' . esc_attr('hf-cmp-' . $column . ' hf-cmp-' . $state) . '">' . $mark . '<span>' . esc_html($text) . '</span></td>';
};
?>

<div class="hf-content hf-upgrade-screen">
    <div class="hf-upgrade-hero">
        <div class="hf-upgrade-hero-text">
            <h2><?php esc_html_e('Everything in Hash Form, and then the things you grow into', 'hash-form'); ?></h2>
            <p><?php esc_html_e('The free plugin is a complete form builder. Pro is for taking payments, generating forms with AI, and sending submissions on to the tools you already use.', 'hash-form'); ?></p>
            <div class="hf-upgrade-actions">
                <a class="button button-primary hf-upgrade-button" href="<?php echo esc_url($hf_site); ?>" target="_blank" rel="noopener"><?php esc_html_e('Get Hash Form Pro', 'hash-form'); ?></a>
                <a class="button hf-upgrade-button" href="<?php echo esc_url($hf_site . 'pricing/'); ?>" target="_blank" rel="noopener"><?php esc_html_e('View pricing', 'hash-form'); ?></a>
            </div>
        </div>
        <ul class="hf-up-stats">
            <?php foreach ($hf_stats as $hf_stat) { ?>
                <li><strong><?php echo esc_html($hf_stat[0]); ?></strong><span><?php echo esc_html($hf_stat[1]); ?></span></li>
            <?php } ?>
        </ul>
    </div>

    <div class="hf-up-explorer" data-hf-upgrade>
        <div class="hf-up-nav" role="tablist" aria-orientation="vertical" aria-label="<?php esc_attr_e('What Pro adds', 'hash-form'); ?>">
            <span class="hf-up-nav-title" aria-hidden="true"><?php esc_html_e('What Pro adds', 'hash-form'); ?></span>
            <?php
            foreach ($hf_topics as $hf_key => $hf_topic) {
                $hf_added = 0;

                foreach ($hf_topic['rows'] as $hf_row) {
                    $hf_added += $hf_row[2] !== $hf_row[3] ? 1 : 0;
                }
                ?>
                <button type="button" role="tab" class="hf-up-tab" id="hf-up-tab-<?php echo esc_attr($hf_key); ?>" data-topic="<?php echo esc_attr($hf_key); ?>" aria-controls="hf-up-panel-<?php echo esc_attr($hf_key); ?>">
                    <span class="hf-up-tab-label"><?php echo esc_html($hf_topic['title']); ?></span>
                    <span class="hf-up-tab-count" aria-hidden="true">+<?php echo esc_html($hf_added); ?></span>
                </button>
            <?php } ?>
            <button type="button" role="tab" class="hf-up-tab hf-up-tab-all" data-topic="all">
                <span class="hf-up-tab-label"><?php esc_html_e('Full comparison', 'hash-form'); ?></span>
            </button>
        </div>

        <div class="hf-up-body">
            <div class="hf-up-tools">
                <label class="hf-up-search-wrap">
                    <span class="screen-reader-text"><?php esc_html_e('Search features', 'hash-form'); ?></span>
                    <input type="search" class="hf-up-search" placeholder="<?php esc_attr_e('Search features', 'hash-form'); ?>"/>
                </label>
                <label class="hf-up-only">
                    <input type="checkbox" class="hf-up-only-pro"/>
                    <?php esc_html_e('Only show what Pro adds', 'hash-form'); ?>
                </label>
            </div>

            <?php foreach ($hf_topics as $hf_key => $hf_topic) { ?>
                <section class="hf-up-panel" id="hf-up-panel-<?php echo esc_attr($hf_key); ?>" role="tabpanel" data-topic="<?php echo esc_attr($hf_key); ?>" aria-labelledby="hf-up-tab-<?php echo esc_attr($hf_key); ?>">
                    <div class="hf-up-panel-head">
                        <h3><?php echo esc_html($hf_topic['title']); ?></h3>
                        <p class="hf-up-lede"><?php echo esc_html($hf_topic['lede']); ?></p>
                        <div class="hf-up-versus">
                            <div class="hf-up-vs hf-up-vs-free">
                                <span class="hf-up-vs-label"><?php esc_html_e('Free', 'hash-form'); ?></span>
                                <?php echo esc_html($hf_topic['free']); ?>
                            </div>
                            <div class="hf-up-vs hf-up-vs-pro">
                                <span class="hf-up-vs-label"><?php esc_html_e('Pro', 'hash-form'); ?></span>
                                <?php echo esc_html($hf_topic['pro']); ?>
                            </div>
                        </div>
                        <div class="hf-up-links">
                            <?php foreach ($hf_topic['links'] as $hf_index => $hf_link) { ?>
                                <a class="button <?php echo 0 === $hf_index ? 'button-primary' : ''; ?> hf-upgrade-button" href="<?php echo esc_url($hf_link[1]); ?>" target="_blank" rel="noopener"><?php echo esc_html($hf_link[0]); ?></a>
                            <?php } ?>
                        </div>
                        <span class="hf-up-col" aria-hidden="true"><?php esc_html_e('Free', 'hash-form'); ?></span>
                        <span class="hf-up-col hf-up-col-pro" aria-hidden="true"><?php esc_html_e('Pro', 'hash-form'); ?></span>
                    </div>

                    <table class="hf-cmp">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Feature', 'hash-form'); ?></th>
                                <th scope="col"><?php esc_html_e('Free', 'hash-form'); ?></th>
                                <th scope="col" class="hf-cmp-head-pro"><?php esc_html_e('Pro', 'hash-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hf_topic['rows'] as $hf_row) { ?>
                                <tr class="<?php echo $hf_row[2] !== $hf_row[3] ? 'hf-cmp-row-pro' : 'hf-cmp-row-shared'; ?>">
                                    <td class="hf-cmp-feature">
                                        <?php if (empty($hf_row[4])) { ?>
                                            <span class="hf-cmp-name"><?php echo esc_html($hf_row[0]); ?></span>
                                        <?php } else { ?>
                                            <a class="hf-cmp-name" href="<?php echo esc_url($hf_row[4]); ?>" target="_blank" rel="noopener"><?php echo esc_html($hf_row[0]); ?></a>
                                        <?php } ?>
                                        <?php if (is_array($hf_row[1])) { ?>
                                            <span class="hf-cmp-chips">
                                                <?php foreach ($hf_row[1] as $hf_name) { ?>
                                                    <span><?php echo esc_html($hf_name); ?></span>
                                                <?php } ?>
                                            </span>
                                        <?php } else if ($hf_row[1]) { ?>
                                            <span class="hf-cmp-desc"><?php echo esc_html($hf_row[1]); ?></span>
                                        <?php } ?>
                                    </td>
                                    <?php
                                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from escaped parts.
                                    echo $hf_cell($hf_row[2], 'free');
                                    echo $hf_cell($hf_row[3], 'pro');
                                    // phpcs:enable
                                    ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </section>
            <?php } ?>

            <p class="hf-up-empty" hidden><?php esc_html_e('No feature matches that.', 'hash-form'); ?></p>
        </div>
    </div>

    <div class="hf-upgrade-foot">
        <div>
            <h3><?php esc_html_e('Every plan includes every Pro feature', 'hash-form'); ?></h3>
            <p><?php esc_html_e('Choose 1 site, 5 sites or unlimited sites, paid yearly or as a lifetime license. Pro extends this plugin, so your forms, entries and styles stay exactly as they are.', 'hash-form'); ?></p>
        </div>
        <div class="hf-upgrade-actions">
            <a class="button button-primary hf-upgrade-button" href="<?php echo esc_url($hf_site); ?>" target="_blank" rel="noopener"><?php esc_html_e('Get Hash Form Pro', 'hash-form'); ?></a>
            <a class="button hf-upgrade-button" href="<?php echo esc_url($hf_site . 'pricing/'); ?>" target="_blank" rel="noopener"><?php esc_html_e('View pricing', 'hash-form'); ?></a>
            <a class="hf-up-doc-link" href="<?php echo esc_url($hf_docs); ?>" target="_blank" rel="noopener"><?php esc_html_e('Pro documentation', 'hash-form'); ?></a>
        </div>
        <p class="hf-upgrade-have">
            <?php esc_html_e('Already have Hash Form Pro?', 'hash-form'); ?>
            <a href="<?php echo esc_url($hf_docs . '#Installation'); ?>" target="_blank" rel="noopener"><?php esc_html_e('Install it', 'hash-form'); ?></a>
            <?php esc_html_e('and', 'hash-form'); ?>
            <a href="<?php echo esc_url($hf_docs . '#License'); ?>" target="_blank" rel="noopener"><?php esc_html_e('activate your license', 'hash-form'); ?></a>.
        </p>
    </div>
</div>
