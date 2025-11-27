!function(){if(!window.klaviyo){window._klOnsite=window._klOnsite||[];try{window.klaviyo=new Proxy({},{get:function(n,i){return"push"===i?function(){var n;(n=window._klOnsite).push.apply(n,arguments)}:function(){for(var n=arguments.length,o=new Array(n),w=0;w<n;w++)o[w]=arguments[w];var t="function"==typeof o[o.length-1]?o.pop():void 0,e=new Promise((function(n){window._klOnsite.push([i].concat(o,[function(i){t&&t(i),n(i)}]))}));return e}}})}catch(n){window.klaviyo=window.klaviyo||[],window.klaviyo.push=function(){var n;(n=window._klOnsite).push.apply(n,arguments)}}}}();

define([
  'uiComponent',
  'mage/url',
  'jquery',
  'domReady!'
], function (Component, url, $) {
  'use strict';

  return Component.extend({
    initialize: function () {
      this._super();
      // Read customerData at initialization time rather than module definition time
      // to ensure it's populated after Magento's customer-data module has loaded
      this._klaviyoCustomerData = window.customerData || null;
      this._email = undefined;
      this._listenerBound = false;

      // Wrap in try-catch to prevent errors from breaking checkout rendering
      try {
        this.handleCheckout();
      } catch (e) {
        console.error('Klaviyo_Reclaim - Error during checkout initialization:', e);
      }

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
      return false;
    },
    isKlaviyoActive: function () {
      return !!(window.klaviyo && typeof window.klaviyo.identify === 'function');
    },
    bindEmailListener: function () {
      // Prevent binding multiple times
      if (this._listenerBound) {
        return;
      }
      this._listenerBound = true;

      var self = this;
      console.log('Klaviyo_Reclaim - Binding to #customer-email');

      // Use 'on' with event delegation instead of deprecated 'delegate'
      // Bind to document.body as fallback if #maincontent doesn't exist yet
      var $container = jQuery('#maincontent');
      if ($container.length === 0) {
        $container = jQuery(document.body);
      }

      $container.on('change', '#customer-email', function () {
        try {
          if (!self.isKlaviyoActive()) {
            return;
          }

          self._email = jQuery(this).val();

          // Handle isIdentified() which returns a Promise
          if (window.klaviyo && typeof window.klaviyo.isIdentified === 'function') {
            var identifiedResult = window.klaviyo.isIdentified();

            // Check if it's a Promise (has .then method)
            if (identifiedResult && typeof identifiedResult.then === 'function') {
              identifiedResult.then(function (identified) {
                if (self._email && !identified) {
                  window.klaviyo.identify({
                    '$email': self._email
                  });
                }
              }).catch(function (err) {
                console.error('Klaviyo_Reclaim - Error checking identification:', err);
              });
            } else {
              // Fallback for older Klaviyo versions where isIdentified returns boolean
              if (self._email && !identifiedResult) {
                window.klaviyo.identify({
                  '$email': self._email
                });
              }
            }
          }

          self.postUserEmail(self._email);
        } catch (e) {
          console.error('Klaviyo_Reclaim - Error in email change handler:', e);
        }
      });
    },
    postUserEmail: function (customer_email) {
      if (!customer_email) {
        return;
      }

      $.ajax({
        url: url.build('reclaim/checkout/email'),
        method: 'POST',
        data: {
          'email': customer_email
        },
        success: function () {
          console.log('Klaviyo_Reclaim - Quote updated with customer email: ' + customer_email);
        },
        error: function (xhr, status, error) {
          console.error('Klaviyo_Reclaim - Failed to update quote:', error);
        }
      });
    }
  });
});
