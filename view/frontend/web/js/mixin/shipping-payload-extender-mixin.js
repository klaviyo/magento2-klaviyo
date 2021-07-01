define([
    'mage/utils/wrapper',
    'Klaviyo_Reclaim/js/model/shipping-payload/assigner'
], function (wrapper, assignData) {
    'use strict';

    /**
     * This file works on Magento >= 2.2.2 only.
     *
     * @param  {Object} target
     * @return {Object}
     */
    return function (target) {
        return wrapper.wrap(target, function (parentFunction, payload) {
            parentFunction(payload);

            assignData(payload.addressInformation);

            return payload;
        });
    };
});
