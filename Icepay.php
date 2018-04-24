<?php

namespace Icepay;

require_once(__DIR__ . '/restapi/src/Icepay/API/Autoloader.php');


use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;
use Icepay\Bootstrap\Database;
use Doctrine\ORM\Tools\SchemaTool;
use Icepay\Models\PaymentMethod;
use Icepay\Models\RawData;
use Shopware\Components\Plugin\ConfigWriter;

class Icepay extends Plugin
{


    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        parent::install($context);
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $plugin = $context->getPlugin();

        //TODO: find a better way to set configuration from values
        $shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        $shop = $shopRepository->find($shopRepository->getActiveDefault()->getId());


        $router = Shopware()->Container()->get('router');

        $postbackUrl = $router->assemble(['module' => 'frontend', 'controller' => 'icepay', 'action' => 'postback', 'forceSecure' => true]);
        $returnUrl = $router->assemble(['module' => 'frontend', 'controller' => 'icepay', 'action' => 'return', 'forceSecure' => true]);

        /** @var ConfigWriter $configWriter */
        $configWriter = $this->container->get('shopware.plugin.config_writer');
        $configWriter->saveConfigElement($plugin, 'postbackUrl', $postbackUrl, $shop);
        $configWriter->saveConfigElement($plugin, 'successUrl', $returnUrl, $shop);
        $configWriter->saveConfigElement($plugin, 'errorUrl', $returnUrl, $shop);

        // $configForms = $plugin->getConfigForms();


        $schemaTool = new SchemaTool($this->container->get('models'));

        $tables =array(
            $this->container->get('models')->getClassMetadata(PaymentMethod::class),
            $this->container->get('models')->getClassMetadata(RawData::class),
        );
        $schemaTool->updateSchema($tables, true);
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }
}
