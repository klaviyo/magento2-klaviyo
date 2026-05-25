define(['jquery'], function ($) {
    'use strict';

    var PREFIX = 'klaviyo_reclaim_consent_at_checkout_mobile_consent_';

    // Per-channel copy. Helper-text strings (channelsNote, consentNote)
    // are verbatim from Figma design 12257:10184. Field defaults
    // (labelDefault, consentDefault) are verbatim from product spec.
    var CONTENT = {
        sms: {
            channelsNote: 'Text message must be set up to collect consent.<br><a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/360039190611-On-Demand-Training-Getting-Started-with-Klaviyo-SMS">Set up text message</a>',
            labelDefault: 'Check this box to receive promotional marketing texts (Exclusive text messaging-only deals, offers, and coupons)',
            consentDefault: 'By checking this box and entering your phone number, you consent to receive informational (e.g., order updates) and/or marketing texts (e.g., cart reminders) from [company name] including texts sent by autodialer. Consent is not a condition of purchase. Msg & data rates may apply. Msg frequency varies. Unsubscribe at any time by replying STOP or clicking the unsubscribe link (where available). Privacy Policy [link] & Terms [link].',
            consentNote: 'Add disclosure language to adhere to compliance laws when collecting consent for text messaging. Update your Terms of Service and Privacy Policy for mobile messaging. <a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/360035055312-About-US-SMS-Compliance-Laws">Learn about text message compliance</a>'
        },
        whatsapp: {
            channelsNote: 'WhatsApp must be set up to collect consent.<br><a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/whatsapp">Set up WhatsApp</a>',
            labelDefault: 'Check this box to receive promotional marketing messages (Exclusive WhatsApp-only deals, offers, and coupons)',
            consentDefault: 'By checking this box and entering your phone number, you consent to receive informational (e.g., order updates) and/or marketing messages (e.g., cart reminders) from [company name] including messages sent by autodialer. Consent is not a condition of purchase. Unsubscribe at any time by replying STOP.',
            consentNote: 'As a best practice, add disclosure language when collecting consent for WhatsApp. Update your Terms of Service and Privacy Policy for mobile messaging.'
        },
        both: {
            channelsNote: 'Text message and WhatsApp must be set up to collect consent.<br><a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/360039190611-On-Demand-Training-Getting-Started-with-Klaviyo-SMS">Set up text message</a><br><a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/whatsapp">Set up WhatsApp</a>',
            labelDefault: 'Check this box to receive promotional marketing messages (WhatsApp and text messaging-only deals, offers, and coupons)',
            consentDefault: 'By checking this box and entering your phone number, you consent to receive informational (e.g., order updates) and/or marketing texts and/or messages (e.g., cart reminders) from [company name] including messages sent by autodialer. Consent is not a condition of purchase. Msg & data rates may apply. Msg frequency varies. Unsubscribe at any time by replying STOP. Privacy Policy & Terms. Privacy Policy [link] & Terms [link].',
            consentNote: 'Add disclosure language to adhere to compliance laws when collecting consent for text messaging and WhatsApp. Update your Terms of Service and Privacy Policy for mobile messaging. <a target="_blank" href="https://help.klaviyo.com/hc/en-us/articles/360035055312-About-US-SMS-Compliance-Laws">Learn about text message compliance</a>'
        }
    };

    function resolveKey(values) {
        var hasSms = values.indexOf('sms') !== -1;
        var hasWa = values.indexOf('whatsapp') !== -1;
        if (hasSms && hasWa) { return 'both'; }
        if (hasWa) { return 'whatsapp'; }
        return 'sms';
    }

    function handleChange() {
        var values = $('#' + PREFIX + 'channels').val() || [];
        var cfg = CONTENT[resolveKey(values)];

        $('#row_' + PREFIX + 'channels .note span').html(cfg.channelsNote);
        $('#row_' + PREFIX + 'consent_text .note span').html(cfg.consentNote);

        $('#' + PREFIX + 'label_text').val(cfg.labelDefault);
        $('#' + PREFIX + 'consent_text').val(cfg.consentDefault);
    }

    return {
        init: function () {
            var $channels = $('#' + PREFIX + 'channels');
            if (!$channels.length) {
                return;
            }
            $channels.on('change', handleChange);
        }
    };
});
