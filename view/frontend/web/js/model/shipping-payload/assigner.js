define([
    'jquery',
    'underscore',
], function ($,_, registry) {
    'use strict';

    return function (container) {
        var kl_sms_phone_number = $('[name="custom_attributes[kl_sms_phone_number]"]').val();
        var kl_sms_consent = $('[name="custom_attributes[kl_sms_consent]"]').is(':checked');
        var kl_email_consent = $('[name="custom_attributes[kl_email_consent]"]').is(':checked');

        container.extension_attributes = _.extend(
            container.extension_attributes || {},
            {
                kl_sms_phone_number: kl_sms_phone_number,
                kl_sms_consent: kl_sms_consent,
                kl_email_consent: kl_email_consent
            }
        );
    };
});
