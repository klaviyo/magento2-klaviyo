define([
    'jquery',
    'underscore',
], function ($,_, registry) {
    'use strict';

    return function (container) {
        var kl_mobile_consent = $('[name="custom_attributes[kl_mobile_consent]"]').is(':checked');
        var kl_email_consent = $('[name="custom_attributes[kl_email_consent]"]').is(':checked');

        container.extension_attributes = _.extend(
            container.extension_attributes || {},
            {
                kl_mobile_consent: kl_mobile_consent,
                kl_email_consent: kl_email_consent
            }
        );
    };
});
