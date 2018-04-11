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

        $config = $this->container->get('config');
        $config->offsetSet('SuccessUrl','http://...');


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
