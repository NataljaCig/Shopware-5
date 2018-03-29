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

class Icepay extends Plugin
{
    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');

//        $database = new Database(
//            $this->container->get('models')
//        );
//
//        $database->install();

//        $options = [
//            'name' => 'example_payment_invoice',
//            'description' => 'Example payment method invoice',
//            'action' => 'PaymentExample',
//            'active' => 0,
//            'position' => 0,
//            'additionalDescription' =>
//                '<img src="http://your-image-url"/>'
//                . '<div id="payment_desc">'
//                . '  Pay save and secured by invoice with our example payment provider.'
//                . '</div>'
//        ];
//        $installer->createOrUpdate($context->getPlugin(), $options);

//        $options = [
//            'name' => 'example_payment_cc',
//            'description' => 'Example payment method credit card',
//            'action' => 'PaymentExample',
//            'active' => 0,
//            'position' => 0,
//            'additionalDescription' =>
//                '<img src="http://your-image-url"/>'
//                . '<div id="payment_desc">'
//                . '  Pay save and secured by credit card with our example payment provider.'
//                . '</div>'
//        ];
//
//        $installer->createOrUpdate($context->getPlugin(), $options);

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
