var config = {
    map: {
        '*': {
            KlaviyoCustomerData: 'Klaviyo_Reclaim/js/customer',
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'Klaviyo_Reclaim/js/mixin/shipping-payload-extender-mixin': true
            },
        }
    }
};
