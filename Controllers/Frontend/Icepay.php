<?php

use Icepay\Components\IcepayPayment\PaymentResponse;
use Icepay\Components\IcepayPayment\IcepayPaymentService;
use Shopware\Components\Random;


class Shopware_Controllers_Frontend_Icepay extends Shopware_Controllers_Frontend_Payment
{
    const PAYMENTSTATUSPAID = 12;
    const PAYMENTSTATUSOPEN = 17;
    const PAYMENTSTATUSCANCELAD = 35;


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
        $paymentId = $this->createPaymentUniqueId();
//        $basketSignature = $this->persistBasket();

        // prepare ICEPAY Payment Object
        $this->getIcepayApiPaymentObject();
        $this->paymentObject->setAmount($amount * 100)
            ->setCountry($countryCode)
            ->setLanguage($language)
            ->setIssuer($this->getIssuerName())
            ->setPaymentMethod($name)
            ->setDescription('Merchant ' . $merchantId . ' OrderID ' . $orderId)
            ->setCurrency($currency)
            ->setOrderID($paymentId)
            ->setReference('Order: ' . $orderId . ', Customer: ' . $billing['userID']);


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
            return $this->forward(
                'confirm',
                'checkout',
                null,
                ['paymentBlocked' => 'Payment error']); //TODO:
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
        $user = $this->getUser();
        $billing = $user['billingaddress'];

        try {
            /** @var PaymentResponse $response */
            $response = $service->createPaymentResponse($this->Request());

//            $signature = $response->reference;
//            $basket = $this->loadBasketFromSignature($signature);
//            $this->verifyBasketSignature($signature, $basket);

//            if($this->getAmount())

            switch ($response->status) {
                case 'OK':
                    $this->saveOrder(
                        $response->transactionID,
                        $response->orderID,
                        self::PAYMENTSTATUSPAID
                    );
                    $this->redirect(['controller' => 'checkout', 'action' => 'finish']);
                    break;
                case 'OPEN':
                    $this->saveOrder(
                        $response->transactionID,
                        $response->paymentID,
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

    /**
     * {@inheritdoc}
     */
    public function createPaymentUniqueId()
    {
        //TODO: use sequence to avoid collisions
        return Random::getAlphanumericString(10);
    }

}
