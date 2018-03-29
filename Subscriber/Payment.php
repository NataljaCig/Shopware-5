<?php

namespace Icepay\Subscriber;

use Enlight\Event\SubscriberInterface;


class Payment implements SubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_InitiatePaymentClass_AddClass' => 'onAddPaymentClass'
        ];
    }


    /**
     * This method registers shopware's generic payment method handler
     * and the ICEPAY payment method handler
     *
     * @param Enlight_Event_EventArgs $args
     *
     * @return array
     */
    public function onAddPaymentClass(\Enlight_Event_EventArgs $args)
    {
        $dirs = $args->getReturn();
        $dirs['icepay'] = 'Icepay\Components\IcepayPayment\IcepayPaymentMethod';
        return $dirs;
    }
}
