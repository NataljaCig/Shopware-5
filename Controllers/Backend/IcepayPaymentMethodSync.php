<?php

use Icepay\Models\PaymentMethod;

class Shopware_Controllers_Backend_IcepayPaymentMethodSync extends Shopware_Controllers_Backend_Application
{
    protected $model = PaymentMethod::class;
    protected $alias = 'sync';

    public function preDispatch()
    {
        /** @var \Shopware\Components\Plugin $plugin */
        $plugin = $this->get('kernel')->getPlugins()['Icepay'];

        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views/');
    }

    public function syncAction()
    {
        $pluginConfig = $this->get('shopware.plugin.config_reader')->getByPluginName('Icepay');
        $userId = $pluginConfig['merchantId'];
        $applicationKey = $pluginConfig['secretKey'];

        $service = $this->container->get('icepay.payment_method_setup_service');
        $result = $service->synchronize();
    }
}
