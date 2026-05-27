define(['jquery'], function ($) {
    'use strict';

    var PREFIX = 'klaviyo_reclaim_consent_at_checkout_mobile_consent_';

    // Per-channel copy. Helper-text strings (channelsNote, consentNote)
    // are verbatim from Figma design 12257:10184. Field defaults
    // (labelDefault, consentDefault) are verbatim from product spec.
    var CONTENT = {
        sms: {
            channelsNote: 'Text message must be set up to collect consent.<br><a target="_blank" href="https://www.klaviyo.com/settings/sms">Set up text message</a>',
            labelDefault: 'Check this box to receive promotional marketing texts (Exclusive text messaging-only deals, offers, and coupons)',
            consentDefault: 'By checking this box and entering your phone number, you consent to receive informational (e.g., order updates) and/or marketing texts (e.g., cart reminders) from [company name] including texts sent by autodialer. Consent is not a condition of purchase. Msg & data rates may apply. Msg frequency varies. Unsubscribe at any time by replying STOP or clicking the unsubscribe link (where available). Privacy Policy [link] & Terms [link].',
            consentNote: 'Add disclosure language to adhere to compliance laws when collecting consent for text messaging. Update your Terms of Service and Privacy Policy for mobile messaging. <a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/360035055312-About-US-SMS-Compliance-Laws">Learn about text message compliance</a>'
        },
        whatsapp: {
            channelsNote: 'WhatsApp must be set up to collect consent.<br><a target="_blank" href="https://www.klaviyo.com/settings/whatsapp">Set up WhatsApp</a>',
            labelDefault: 'Check this box to receive promotional marketing messages (Exclusive WhatsApp-only deals, offers, and coupons)',
            consentDefault: 'By checking this box and entering your phone number, you consent to receive informational (e.g., order updates) and/or marketing messages (e.g., cart reminders) from [company name] including messages sent by autodialer. Consent is not a condition of purchase. Unsubscribe at any time by replying STOP.',
            consentNote: 'As a best practice, add disclosure language when collecting consent for WhatsApp. Update your Terms of Service and Privacy Policy for mobile messaging.'
        },
        both: {
            channelsNote: 'Text message and WhatsApp must be set up to collect consent.<br><a target="_blank" href="https://www.klaviyo.com/settings/sms">Set up text message</a><br><a target="_blank" href="https://www.klaviyo.com/settings/whatsapp">Set up WhatsApp</a>',
            labelDefault: 'Check this box to receive promotional marketing messages (WhatsApp and text messaging-only deals, offers, and coupons)',
            consentDefault: 'By checking this box and entering your phone number, you consent to receive informational (e.g., order updates) and/or marketing texts and/or messages (e.g., cart reminders) from [company name] including messages sent by autodialer. Consent is not a condition of purchase. Msg & data rates may apply. Msg frequency varies. Unsubscribe at any time by replying STOP. Privacy Policy & Terms. Privacy Policy [link] & Terms [link].',
            consentNote: 'Add disclosure language to adhere to compliance laws when collecting consent for text messaging and WhatsApp. Update your Terms of Service and Privacy Policy for mobile messaging. <a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/360035055312-About-US-SMS-Compliance-Laws">Learn about text message compliance</a>'
        }
    };

    // Returns the CONTENT entry for the current selection, or null if the
    // multiselect is empty. An empty selection has no meaningful default —
    // callers should leave existing helper text and field values untouched.
    function currentConfig() {
        var values = $('#' + PREFIX + 'channels').val() || [];
        var hasSms = values.indexOf('sms') !== -1;
        var hasWa = values.indexOf('whatsapp') !== -1;
        if (hasSms && hasWa) { return CONTENT.both; }
        if (hasWa) { return CONTENT.whatsapp; }
        if (hasSms) { return CONTENT.sms; }
        return null;
    }

    function applyHelperText(cfg) {
        $('#row_' + PREFIX + 'channels .note span').html(cfg.channelsNote);
        $('#row_' + PREFIX + 'consent_text .note span').html(cfg.consentNote);
    }

    function handleChange() {
        var cfg = currentConfig();
        if (cfg === null) {
            return;
        }
        applyHelperText(cfg);
        // Field values are only overwritten on merchant interaction, not on load.
        $('#' + PREFIX + 'label_text').val(cfg.labelDefault);
        $('#' + PREFIX + 'consent_text').val(cfg.consentDefault);
    }

    return {
        init: function () {
            var $channels = $('#' + PREFIX + 'channels');
            if (!$channels.length) {
                return;
            }
            var cfg = currentConfig();
            if (cfg !== null) {
                applyHelperText(cfg);
            }
            $channels.on('change', handleChange);
        }
    };
});
