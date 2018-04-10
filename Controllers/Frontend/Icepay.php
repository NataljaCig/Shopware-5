<?php

use Icepay\Components\IcepayPayment\PaymentResponse;
use Icepay\Components\IcepayPayment\IcepayPaymentService;
use Shopware\Components\Random;
use Shopware\Models\Order\Status;


class Shopware_Controllers_Frontend_Icepay extends Shopware_Controllers_Frontend_Payment
{
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN = 17;
    const PAYMENTSTATUSCANCELED = 35;


    /**
     * @var sAdmin
     */
    protected $admin;

    /**
     * ICEPAY Web Service
     */
    protected $webserviceObject;

    /**
     * ICEPAY Payment Object instance
     */
    protected $paymentObject;

    /**
     * Shopware_Components_Config $config
     */
    protected $config;

    /**
     * init payment controller
     */
    public function init()
    {


        $this->admin = Shopware()->Modules()->Admin();
//        $this->session = Shopware()->Session();
    }


    public function preDispatch()
    {
        $shop = $this->get('shop');
        if (!$shop) {
            $shop = $this->get('models')->getRepository(Shopware\Models\Shop\Shop::class)->getActiveDefault();
        }

        $this->config = $this->get('shopware.plugin.config_reader')->getByPluginName('Icepay', $shop);

        /** @var \Shopware\Components\Plugin $plugin */
        $plugin = $this->get('kernel')->getPlugins()['Icepay'];
        $this->get('template')->addTemplateDir($plugin->getPath() . '/Resources/views/');

    }

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['postback'];
    }


    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */
    public function indexAction()
    {
//        /**
//         * Check if one of the payment methods is selected. Else return to default controller.
//         */
        if (substr($this->getPaymentShortName(), 0, strlen('icepay_')) === 'icepay_') {
            return $this->redirect(['action' => 'direct', 'forceSecure' => true]);
        } else {
            return $this->redirect(['controller' => 'checkout']);
        }
    }


    /**
     * Direct action method.
     *
     * Collects the payment information and transmits it to ICEPAY.
     */
    public function directAction()
    {

        $merchantId = $this->config['merchantId'];
        $secretKey = $this->config['secretKey'];


        $name =  substr($this->getPaymentShortName(), 7);
        $user = $this->getUser();
        $basket = $this->getBasket();

        if (!$name || !$user || !$basket || empty($name)) {
            return $this->forward(
                'confirm',
                'checkout',
                null,
                ['paymentBlocked' => 'Payment error']
            ); //TODO: not executed
        }


        $language = $this->getLanguage();
        $currency = $this->getCurrencyShortName();
        $amount = $this->getAmount();
        $billing = $user['billingaddress'];
        $countryCode = $this->getCountryCode($billing['countryId']);
//        $orderId = $this->getTemporaryOrderId();
        $paymentId = $this->createPaymentUniqueId();
//        $basketSignature = $this->persistBasket();

        if($amount <= 0) {
            return $this->forward('cart');
        }

        $orderNumber = $this->saveOrder(
            $paymentId,
            $paymentId,
            self::PAYMENTSTATUSOPEN
        );

        // prepare ICEPAY Payment Object
        $this->getIcepayApiPaymentObject();
        $this->paymentObject->setAmount($amount * 100)
            ->setCountry($countryCode)
            ->setLanguage($language)
            ->setIssuer($this->getIssuerName())
            ->setPaymentMethod($name)
            ->setDescription('Merchant ' . $merchantId . ' OrderID ' . $orderNumber)
            ->setCurrency($currency)
            ->setOrderID($orderNumber)
            ->setReference($paymentId);


        $router = $this->Front()->Router();

        // prepare ICEPAY Webservice Object
        $this->getIcepayApiWebserviceObject();
        $this->webserviceObject
            ->setMerchantID($merchantId)
            ->setSecretCode($secretKey)
            ->setSuccessURL($router->assemble(['action' => 'return', 'forceSecure' => true]))
            ->setErrorURL($router->assemble(['action' => 'cancel', 'forceSecure' => true]))
            ->setupClient();

        try {

            $transactionObj = $this->webserviceObject->checkOut($this->paymentObject);

            $this->redirect($transactionObj->getPaymentScreenURL(), array('forceSecure' => true));
        } catch (\Exception $ex)  {

            if($orderNumber && $orderNumber > 0 && $paymentId)
            {
                $this->cancelOrder($orderNember, $paymentId);
            }

            $this->forward('cancel');
        }

    }

    /**
     * Return action method
     *
     * Reads the transactionResult and represents it for the customer.
     */
    public function returnAction()
    {

        /** @var IcepayService $service */
        $service = $this->container->get('icepay.icepay_service');
//        $user = $this->getUser();
//        $billing = $user['billingaddress'];

        try {
            /** @var Icepay_Result $response */
            $response = $service->createPaymentResponse($this->Request());

            switch ($response->status) { //TODO: avoid duplicate status updates on page refresh
                case 'OK':
                    $this->savePaymentStatus(
                        $response->reference,
                        $response->reference,
                        self::PAYMENTSTATUSPAID
                    );
                    $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                    break;
                case 'OPEN':
                    $this->savePaymentStatus(
                        $response->reference,
                        $response->reference,
                        self::PAYMENTSTATUSOPEN
                    );
                    $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                    break;
                default:
                    $this->forward('cancel');
                    break;
            }
        }
        catch (\Exception $ex)  {
            $this->forward('cancel');
            return;
        }

    }

    /**
     * Cancel action method
     */
    public function cancelAction()
    {

        try {
            /** @var IcepayService $service */
            $service = $this->container->get('icepay.icepay_service');
            /** @var PaymentResponse $response */
            $response = $service->createPaymentResponse($this->Request());
            if($response)
            {
                $this->cancelOrder($response->orderID, $response->reference);
            }

            $this->View()->assign([
                'errorMessage' => $response->statusCode
            ]);

        } catch (\Exception $ex) {
            $this->View()->assign([
                'errorMessage' => "We encountered an error processing your payment."
            ]);
        }
    }


    /**
     * postback action method
     */
    public function postbackAction()
    {
        if(!$this->Request()->isPost())
        {
            die('Postback URL installed correctly');
        }

        /** @var IcepayService $service */
        $service = $this->container->get('icepay.icepay_service');

        try {
            /** @var Icepay_Postback $postback */
            $postback = $service->createPostbackRequest($this->Request());

            switch ($postback->status) {
                case 'OK':
                    $this->saveOrder(
                        $postback->reference,
                        $postback->reference,
                        self::PAYMENTSTATUSPAID
                    );
                    break;
                case 'OPEN':
                    $this->saveOrder(
                        $postback->reference,
                        $postback->reference,
                        self::PAYMENTSTATUSOPEN
                    );
                    break;
                default:
                    $this->savePaymentStatus(
                        $postback->reference,
                        $postback->reference,
                        self::PAYMENTSTATUSCANCELED
                    );
                    break;
            }
        }
        catch (\Exception $ex)  {;
            throw new \Exception($ex->message);
            //return;
        }

        die();;

    }


    /**
     * @return \Icepay_PaymentObject
     */
    protected function getIcepayApiPaymentObject()
    {
        if (null === $this->paymentObject) {
            $this->paymentObject = new Icepay_PaymentObject();
        }
        return $this->paymentObject;
    }

    /**
     * @return \Icepay_Webservice_Pay
     */
    protected function getIcepayApiWebserviceObject()
    {
        if (null === $this->webserviceObject)
        {
            $this->webserviceObject = new Icepay_Webservice_Pay();
        }
        return $this->webserviceObject;
    }

    protected function getLanguage()
    {
        $locale = Shopware()->Shop()->getLocale()->getLocale();
        $language = strtoupper($locale);
        if (strlen($language) > 2) {
            return substr($language, 0, 2);
        } else {
            return 'EN';
        }

    }

    protected function getCountryCode($countryId)
    {
        $countryRepository = $this->get('shopware_storefront.country_gateway');
        $context = $this->get('shopware_storefront.context_service')->getShopContext();

        $country = $countryRepository->getCountry($countryId, $context);

        return $country->getIso();
    }

    /**
     * Returns the current card issuer name.
     *
     * @return string
     */
    public function getIssuerName()
    {
        $user = $this->getUser();
        if ($user != null && !empty($user['additional']['payment']['id'])) {

            $paymentMeanId = $user['additional']['payment']['id'];
            $getPaymentDetails = $this->admin->sGetPaymentMeanById($paymentMeanId);

            $paymentClass = $this->admin->sInitiatePaymentClass($getPaymentDetails);
            if ($paymentClass instanceof \Icepay\Components\IcepayPayment\IcepayPaymentMethod) {
                $data = $paymentClass->getCurrentPaymentDataAsArray(Shopware()->Session()->sUserId);

                if (!empty($data) && isset($data['sIcepayIssuer'][$paymentMeanId])) {
                    return $data['sIcepayIssuer'][$paymentMeanId];
                }
            }
        }
        return 'DEFAULT';
    }

    private function cancelOrder($orderNumber, $paymentId)
    {
        $this->savePaymentStatus($paymentId, $paymentId, self::PAYMENTSTATUSCANCELED );

        $orderRepository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        /** @var $order \Shopware\Models\Order\Order */
        $order = $orderRepository->findOneBy(['number' => $orderNumber]);
        if($order){
            $statusCanceled = Shopware()->Models()->getRepository(Status::class)->find(Status::ORDER_STATE_CANCELLED_REJECTED);
            $order->setOrderStatus($statusCanceled);
            Shopware()->Models()->flush();
        }
    }


//    /**
//     * {@inheritdoc}
//     */
//    public function createPaymentUniqueId()
//    {
//        //TODO: use sequence to avoid collisions
//        return Random::getAlphanumericString(10);
//    }

//
//    public function getTemporaryOrderId()
//    {
//        $temporaryOrderId = $this->getSession()->offsetGet('sessionId');
//
//        $orderRepository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
//        $model = $orderRepository->findOneBy(['temporaryID' => $temporaryOrderId]); //TODO: if multiple orders found
//
//        if($model)
//        {
//            return $model->getId();
//        }
//
//    }


}
