var config = {
    map: {
        '*': {
            KlaviyoCustomerData: 'Klaviyo_Reclaim/js/customer',
        }
    },
    config: {
        mixins: {
            // Compatibility with Magento < 2.2.2
            'mage/storage': {
                'Klaviyo_Reclaim/js/mixin/storage-mixin': true
            },
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'Klaviyo_Reclaim/js/mixin/shipping-payload-extender-mixin': true
            },
        }
    }
};
