Ext.define('Shopware.apps.IcepayPaymentMethodSync.controller.Synchronize', {
    extend: 'Enlight.app.Controller',

    // init: function () {
    //     var me = this;
    //     Shopware.app.Application.on('synchronize-payment-methods', me.onSynchronizePaymentMethods);
    // },

    synchronize: function () {
        var me = this,
            action = me.subApplication.action;

        Ext.Ajax.request({
            url: '{url controller=IcepayPaymentMethodSync action=sync}',
            success: function(response) {
                var responseObj = Ext.JSON.decode(response.responseText),
                    message;
                if (responseObj.success) {
                    message = 'success';
                } else {
                    message = responseObj.message;
                }
        
                Shopware.Notification.createGrowlMessage(
                    'Payment Method Synchronization ',
                    message
                );
        
                me.subApplication.handleSubAppDestroy(null);
            }
        });
    }

});
