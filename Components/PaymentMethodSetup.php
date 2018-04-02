<?php


namespace Icepay\Components;

use Icepay\Icepay;
use Shopware\Models\Payment\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Icepay\Models\PaymentMethod;
use Icepay\Models\RawData;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Shop\Shop;

class PaymentMethodSetup
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var PaymentInstaller
     */
    private $paymentInstaller;

    /**
     *
     * @var PaymentMethodProvider
     */
    private $paymentMethodProvider;

    /**
     * @var Icepay
     */
    private $plugin;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ConfigReader $configReader
     * @param ModelManager $modelManager
     * @param PaymentInstaller $paymentInstaller
     * @param ApiClient $apiClient
     * @param PaymentMethodProvider $paymentMethodProvider
     * @param Translator $translator
     * @param Resource $resource
     */
    public function __construct(
        ContainerInterface $container,
        ConfigReader $configReader,
        ModelManager $modelManager,
        PaymentInstaller $paymentInstaller,
        \Icepay\API\Client $api
    ) {
        $this->container = $container;
        $this->configReader = $configReader;
        $this->modelManager = $modelManager;
        $this->paymentInstaller = $paymentInstaller;
//        $this->plugin = Shopware()->Plugins()->Backend()->Icepay();
//        $this->apiClient = new \Icepay\API\Client();
        $pluginManager = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->plugin = $pluginManager->getPluginByName('Icepay');
    }


    public function synchronize()
    {

        $pluginConfig = $this->configReader->getByPluginName('Icepay');
        $merchantId = $pluginConfig['merchantId'];
        $secretKey = $pluginConfig['secretKey'];

        //TODO: try catch

        //Get list of all payment methods available for specific Merchant ID
        $availablePaymentMethods = $this->getIcepayPaymentMethods($merchantId, $secretKey);

        if (!isset($availablePaymentMethods->PaymentMethods) || !is_array($availablePaymentMethods->PaymentMethods)) {
            return; //TODO: error
        }

        $rawData = $this->modelManager->getRepository(RawData::class)->findOneBy(array('scope' => 1));
        if (!$rawData) {
            $rawData = new RawData();
            $rawData->setScope(1);
        }

        $rawData->setRawPmData(serialize($availablePaymentMethods->PaymentMethods));
        $this->modelManager->persist($rawData);

        //Get list of ICEPAY payment means available in Shopware
        $icepayPaymentMeans = $this->modelManager->getRepository(Payment::class)
            ->findBy(array('pluginId' => $this->plugin->getId()));

        foreach ($icepayPaymentMeans as $icepayPaymentMean) {
            $icepayPaymentMean->setActive(0);
            $icepayPaymentMean->setHide(1);
        }

//        $existingIcepayPaymentMethods = $this->modelManager->getRepository(PaymentMethod::class)->findBy(['scope' => 1]);
        foreach ($availablePaymentMethods->PaymentMethods as $availablePaymentMethod) {

            $paymentMean = $this->paymentInstaller->createOrUpdate($this->plugin->getName(), array(
                'name' => 'icepay_' . $availablePaymentMethod->PaymentMethodCode,
                'description' => $availablePaymentMethod->Description,
                'action' => 'icepay',
                'template' => 'icepay_payment.tpl',
                'class' => 'icepay_'.strtolower($availablePaymentMethod->PaymentMethodCode),  //TODO: not supported message
                'active' => 0,
                //'position' => 1,
                'additionalDescription' => '<img src="{link file=\'frontend/plugins/payment/img/logo/'.strtolower($availablePaymentMethod->PaymentMethodCode).'.png\'}"/>'
            ));

            $pm = $this->modelManager->getRepository(PaymentMethod::class)->findOneBy(['code' => $availablePaymentMethod->PaymentMethodCode]); //TODO: scope
            if (!$pm) {
                $paymentmethod = new PaymentMethod();
                $paymentmethod->setPaymentMean($paymentMean);
                $paymentmethod->setCode($availablePaymentMethod->PaymentMethodCode);
                $this->modelManager->persist($paymentmethod);

            }

        }

        Shopware()->Models()->flush();


    }


    private function getIcepayPaymentMethods($merchantId, $secretCode)
    {
        $icepay = new \Icepay\API\Client();
        $icepay->setApiSecret($secretCode);
        $icepay->setApiKey($merchantId);
        $icepay->setCompletedURL('...');
        $icepay->setErrorURL('...');
        return $icepay->payment->getMyPaymentMethods();
    }


}
