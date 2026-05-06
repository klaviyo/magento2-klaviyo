define(['jquery'], function ($) {
    'use strict';

    var PREFIX = 'klaviyo_reclaim_consent_at_checkout_mobile_consent_';

    // Centralized per-channel content: helper texts and field defaults
    var CONTENT = {
        sms: {
            channelsNote: 'Text message must be set up in Klaviyo. US merchants must comply with TCPA requirements for SMS marketing.',
            labelDefault: 'Subscribe for SMS updates*',
            consentDefault: '*By checking this box and entering your phone number above, you consent to receive marketing text messages (e.g. promos, cart reminders) from [company name] at the number provided, including messages sent by autodialer. Consent is not a condition of purchase. Msg &amp; data rates may apply. Msg frequency varies. Unsubscribe at any time by replying STOP or clicking the unsubscribe link (where available). Privacy Policy [link] &amp; Terms of Service [link].',
            consentNote: 'You must include disclosure language for TCPA compliance for SMS marketing.'
        },
        whatsapp: {
            channelsNote: 'WhatsApp must be set up in Klaviyo. Subscribers will receive messages via WhatsApp.',
            labelDefault: 'Subscribe for WhatsApp updates',
            consentDefault: 'By checking this box, you consent to receive marketing messages via WhatsApp from [company name]. You can unsubscribe at any time by using the unsubscribe link in any message.',
            consentNote: 'Include disclosure language for WhatsApp consent compliance.'
        },
        both: {
            channelsNote: 'Both SMS and WhatsApp must be set up in Klaviyo. US merchants must comply with TCPA requirements for SMS.',
            labelDefault: 'Subscribe for SMS and WhatsApp updates*',
            consentDefault: '*By checking this box and entering your phone number above, you consent to receive marketing text messages and WhatsApp messages from [company name]. Msg &amp; data rates may apply for SMS. Unsubscribe at any time by replying STOP (SMS) or using the unsubscribe link in WhatsApp. Privacy Policy [link] &amp; Terms of Service [link].',
            consentNote: 'You must include disclosure language for TCPA compliance (SMS) and WhatsApp consent compliance.'
        }
    };

    // Dirty flags track whether merchant has manually edited each field
    var dirty = { label: false, consent: false };

    // Track the last value we auto-populated so we can detect merchant overrides
    var lastAutoPopulated = { label: null, consent: null };

    function resolveKey(values) {
        var hasSms = values.indexOf('sms') !== -1;
        var hasWa = values.indexOf('whatsapp') !== -1;
        if (hasSms && hasWa) { return 'both'; }
        if (hasWa) { return 'whatsapp'; }
        return 'sms';
    }

    function handleChange() {
        var values = $('#' + PREFIX + 'channels').val() || [];
        var key = resolveKey(values);
        var cfg = CONTENT[key];

        // Always update helper text (comment spans) regardless of dirty state
        $('#row_' + PREFIX + 'channels .note span').html(cfg.channelsNote);
        $('#row_' + PREFIX + 'consent_text .note span').html(cfg.consentNote);

        // Auto-populate label_text if not dirty or value matches previous auto-populated default
        var $label = $('#' + PREFIX + 'label_text');
        if (!dirty.label) {
            var lv = $label.val();
            if (lv === '' || lv === lastAutoPopulated.label) {
                $label.val(cfg.labelDefault);
                lastAutoPopulated.label = cfg.labelDefault;
            }
        }

        // Auto-populate consent_text if not dirty or value matches previous auto-populated default
        var $consent = $('#' + PREFIX + 'consent_text');
        if (!dirty.consent) {
            var cv = $consent.val();
            if (cv === '' || cv === lastAutoPopulated.consent) {
                $consent.val(cfg.consentDefault);
                lastAutoPopulated.consent = cfg.consentDefault;
            }
        }
    }

    return {
        init: function () {
            var $channels = $('#' + PREFIX + 'channels');
            if (!$channels.length) {
                return;
            }

            // Mark dirty when merchant manually edits a field
            $('#' + PREFIX + 'label_text').on('input', function () {
                dirty.label = true;
            });
            $('#' + PREFIX + 'consent_text').on('input', function () {
                dirty.consent = true;
            });

            $channels.on('change', handleChange);
        }
    };
});
