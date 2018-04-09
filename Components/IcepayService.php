<?php

namespace Icepay\Components;

use Shopware\Components\Plugin\ConfigReader;

class IcepayService
{

    /**
     *
     * @var ConfigReader
     */
    private $configReader;


    public function __construct(
//        ContainerInterface $container,
        ConfigReader $configReader
    ) {
//        $this->container = $container;
        $this->configReader = $configReader;
    }
    
    /**
     * @param $request \Enlight_Controller_Request_Request
     * @return object
     */
    public function createPaymentResponse(\Enlight_Controller_Request_Request $request)
    {
        $icepayResult = new \Icepay_Result();
        
        $pluginConfig = $this->configReader->getByPluginName('Icepay');
        $merchantId = $pluginConfig['merchantId'];
        $secretKey = $pluginConfig['secretKey'];

        $result = $icepayResult->setMerchantID($merchantId)->setSecretCode($secretKey);
        if($result->validate()) {
            return $result->getResultData();
        }
        throw new \Exception('Failed to validete ICEPAY result');
    }

    /**
     * @param $request \Enlight_Controller_Request_Request
     * @return object
     */
    public function createPostbackRequest(\Enlight_Controller_Request_Request $request)
    {
        $icepayPostback = new \Icepay_Postback();

        $pluginConfig = $this->configReader->getByPluginName('Icepay');
        $merchantId = $pluginConfig['merchantId'];
        $secretKey = $pluginConfig['secretKey'];

        $postback = $icepayPostback->setMerchantID($merchantId)->setSecretCode($secretKey);
        if($postback->validate()) {
            return $postback->getPostback();
        }
        throw new \Exception('Failed to validete ICEPAY postback request');
    }
    
}
