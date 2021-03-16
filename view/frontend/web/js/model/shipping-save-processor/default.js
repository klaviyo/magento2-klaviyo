
define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/select-billing-address'
], function(
    $,
    ko,
    quote,
    resourceUrlManager,
    storage,
    paymentService,
    methodConverter,
    errorProcessor,
    fullScreenLoader,
    selectBillingAddressAction
) {
    'use strict';

    return {
        saveShippingInformation: function() {
            var payload;
            var kl_sms_consent = $('[name="custom_attributes[kl_sms_consent]"]').is(':checked');
            var kl_email_consent = $('[name="custom_attributes[kl_email_consent]"]').is(':checked');

            if(!quote.billingAddress()) {
                selectBillingAddressAction(quote.shippingAddress());
            }

            payload = {
                addressInformation: {
                    shipping_address: quote.shippingAddress(),
                    billing_address: quote.billingAddress(),
                    shipping_method_code: quote.shippingMethod().method_code,
                    shipping_carrier_code: quote.shippingMethod().carrier_code,
                    extension_attributes: {
                        kl_sms_consent: kl_sms_consent,
                        kl_email_consent: kl_email_consent,
                    }
                }
            };

            fullScreenLoader.startLoader();

            return storage.post(
                resourceUrlManager.getUrlForSetShippingInformation(quote),
                JSON.stringify(payload)
            ).done(
                function(response) {
                    quote.setTotals(response.totals);
                    paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                    fullScreenLoader.stopLoader();
                }
            ).fail(
                function(response) {
                    errorProcessor.process(response);
                    fullScreenLoader.stopLoader();
                }
            );
        }
    };
});
