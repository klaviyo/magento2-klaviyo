define([
    'jquery',
    'uiRegistry',
    'mage/utils/wrapper'
], function ($, registry, wrapper) {
    'use strict';

    /**
     * Compatibility magento < 2.2.2. PayloadExtender wasn't implemented.
     *
     * @param  {Object} target
     * @return {Object}
     */
    return function (target) {

        target.post = wrapper.wrap(
            target.post,
            function (o, url) {
                var data;

                if (url.indexOf('/shipping-information') !== -1) {
                    data = JSON.parse(arguments[2]);

                    if (!data.addressInformation) {
                        data.addressInformation = {};
                    }

                    if (!data.addressInformation.extension_attributes) {
                        data.addressInformation.extension_attributes = {};
                    }

                    if (data.addressInformation.extension_attributes.kl_sms_consent === undefined) {
                        data.addressInformation.extension_attributes.kl_sms_consent =
                            $('[name="custom_attributes[kl_sms_consent]"]').is(':checked');
                    }

                    if (data.addressInformation.extension_attributes.kl_email_consent === undefined) {
                        data.addressInformation.extension_attributes.kl_email_consent =
                            $('[name="custom_attributes[kl_email_consent]"]').is(':checked');
                    }
                    arguments[2] = JSON.stringify(data);
                }

                return o.apply(
                    target,
                    Array.prototype.slice.call(arguments, 1)
                );
            }
        );

        return target;
    };
});
