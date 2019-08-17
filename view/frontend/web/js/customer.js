define([
    'underscore',
    'Magento_Customer/js/customer-data'
], function (_, customerData) {
    'use strict';
    
    window._learnq = window._learnq || [];
    var customer = customerData.get('customer')();

    if(_.has(customer, 'email') && customer.email) {
        _learnq.push(['identify', {
            $email: customer.email,
            $first_name: _.has(customer, 'firstname') ? customer.firstname : '',
            $last_name:  _.has(customer, 'lastname') ? customer.lastname : ''
        }]);
    }
});
