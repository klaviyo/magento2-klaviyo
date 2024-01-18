!function(){if(!window.klaviyo){window._klOnsite=window._klOnsite||[];try{window.klaviyo=new Proxy({},{get:function(n,i){return"push"===i?function(){var n;(n=window._klOnsite).push.apply(n,arguments)}:function(){for(var n=arguments.length,o=new Array(n),w=0;w<n;w++)o[w]=arguments[w];var t="function"==typeof o[o.length-1]?o.pop():void 0,e=new Promise((function(n){window._klOnsite.push([i].concat(o,[function(i){t&&t(i),n(i)}]))}));return e}}})}catch(n){window.klaviyo=window.klaviyo||[],window.klaviyo.push=function(){var n;(n=window._klOnsite).push.apply(n,arguments)}}}}();

define([
    'underscore',
    'Magento_Customer/js/customer-data',
    'domReady!'
], function (_, customerData) {
    'use strict';

    var klaviyo = window.klaviyo || [];

    customerData.getInitCustomerData().done(function () {
        var customer = customerData.get('customer')();

        klaviyo.isIdentified().then((identified)=> {
            if(_.has(customer, 'email') && customer.email && !identified) {
                klaviyo.identify({
                    '$email': customer.email,
                    '$first_name': _.has(customer, 'firstname') ? customer.firstname : '',
                    '$last_name':  _.has(customer, 'lastname') ? customer.lastname : ''
                });
            }
        });

    });

});
