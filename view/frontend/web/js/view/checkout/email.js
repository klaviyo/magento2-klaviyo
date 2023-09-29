!function(){if(!window.klaviyo){window._klOnsite=window._klOnsite||[];try{window.klaviyo=new Proxy({},{get:function(n,i){return"push"===i?function(){var n;(n=window._klOnsite).push.apply(n,arguments)}:function(){for(var n=arguments.length,o=new Array(n),w=0;w<n;w++)o[w]=arguments[w];var t="function"==typeof o[o.length-1]?o.pop():void 0,e=new Promise((function(n){window._klOnsite.push([i].concat(o,[function(i){t&&t(i),n(i)}]))}));return e}}})}catch(n){window.klaviyo=window.klaviyo||[],window.klaviyo.push=function(){var n;(n=window._klOnsite).push.apply(n,arguments)}}}}();

define([
  'uiComponent',
  'mage/url',
  'jquery',
  'domReady!'
], function (Component, url, $) {
  'use strict';

  // initialize the customerData prior to returning the component
  var _klaviyoCustomerData = window.customerData;

  return Component.extend({
    initialize: function () {
      this._super();
      this._klaviyoCustomerData = _klaviyoCustomerData;
      this._email;
      this.handleCheckout();
      return this;
    },
    handleCheckout: function () {
      if (this.isUserLoggedIn() && this._email) {
        this.postUserEmail(this._email);
      } else {
        this.bindEmailListener();
      }
    },
    isUserLoggedIn: function () {
      this._email = this._klaviyoCustomerData ? this._klaviyoCustomerData.email : undefined;
      if (this._email) {
        return true;
      }
    },
    isKlaviyoActive: function() {
      return !!(window.klaviyo && window.klaviyo.identify);
    },
    bindEmailListener: function () {
      // jquery overrides this, so let's create an instance of the parent
      var self = this;
      console.log('Klaviyo_Reclaim - Binding to #customer-email');
      jQuery('#maincontent').delegate('#customer-email', 'change', function (event) {
        if (!self.isKlaviyoActive()) {
          return;
        }

        self._email = jQuery(this).val();
        if (!window.klaviyo.isIdentified()) {
          window.klaviyo.push(['identify', {
            '$email': self._email
          }]);
        }
        self.postUserEmail(self._email);
      });
    },
    postUserEmail: function (customer_email) {
      $.ajax({
        url: url.build('reclaim/checkout/email'),
        method: 'POST',
        data: {
          'email': customer_email
        },
        success: function (data) {
          console.log('Klaviyo_Reclaim - Quote updated with customer email: ' + customer_email);
        }
      });
    }
  });
});
