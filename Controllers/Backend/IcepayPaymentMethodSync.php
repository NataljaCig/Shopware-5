<?php

use Icepay\Models\PaymentMethod;

class Shopware_Controllers_Backend_IcepayPaymentMethodSync extends Shopware_Controllers_Backend_ExtJs
{
//    protected $model = PaymentMethod::class;
//    protected $alias = 'sync';

    public function preDispatch()
    {
        /** @var \Shopware\Components\Plugin $plugin */
        $plugin = $this->get('kernel')->getPlugins()['Icepay'];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views/');

        parent::preDispatch();
    }


    public function syncAction()
    {
        try {
            $pluginConfig = $this->get('shopware.plugin.config_reader')->getByPluginName('Icepay');
            $service = $this->container->get('icepay.payment_method_setup_service');
            $result = $service->synchronize();
        } catch (\Exception $e) {
            $this->view->assign([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
