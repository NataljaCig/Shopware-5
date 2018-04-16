
Ext.define('Shopware.apps.IcepayPaymentMethodSync', {
    extend: 'Enlight.app.SubApplication',
    
    name: 'Shopware.apps.IcepayPaymentMethodSync',
    
    loadPath: '{url action=load}',
    
    controllers: [
        'Synchronize'
    ],
    
    launch: function() {
        var me = this;
        return me.getController('Synchronize').synchronize();
    }
    
});
