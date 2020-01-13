define([
  'uiComponent',
  'jquery',
  'jquery/ui',
  'domReady!'
], function (Component) {
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
      return !!(window._learnq && window._learnq.identify);
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
        if (!window._learnq.identify().email) {
          window._learnq.push(['identify', {
            '$email': self._email
          }]);
        }
        self.postUserEmail(self._email);
      });
    },
    postUserEmail: function (customer_email) {
      var path = window.location.pathname;
      if (path.slice(-1) == '/') {
        path = path.slice(0, -1);
      }

      var url = window.location.protocol + '//' + window.location.host + path.substring(0, path.lastIndexOf("/"));

      jQuery.ajax({
        url: url + '/reclaim/checkout/email',
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
