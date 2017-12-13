define([
    'uiComponent',
    'jquery',
    'jquery/ui'
], function(Component) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.bindEmailListener();
            return this;
        },

        bindEmailListener: function() {
            console.log('Klaviyo_Reclaim - Binding to #customer-email');
            jQuery('#maincontent').delegate('#customer-email', 'change', function(event) {
                var customer_email = jQuery(this).val();

                var path = window.location.pathname;
                if (path.slice(-1) == '/' ) {
                    path = path.slice(0, -1);
                }

                var url = window.location.protocol + '//' + window.location.host + path.substring(0,path.lastIndexOf("/"));

                jQuery.ajax({
                    url: url +  '/reclaim/checkout/email',
                    method: 'POST',
                    data: {
                        'email' : customer_email
                    },
                    success: function(data) {
                        console.log('Klaviyo_Reclaim - Quote updated with customer email: ' + customer_email);
                    }
                });
            });
        }
    });
});
