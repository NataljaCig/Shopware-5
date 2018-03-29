<?php


namespace Icepay\Components;

use Icepay\Icepay;
use Shopware\Models\Payment\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Icepay\Models\PaymentMethod;
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
    public function __construct(ContainerInterface $container, ConfigReader $configReader, ModelManager $modelManager, PaymentInstaller $paymentInstaller, \Icepay\API\Client $api)
    {
        $this->container = $container;
        $this->configReader = $configReader;
        $this->modelManager = $modelManager;
        $this->paymentInstaller = $paymentInstaller;
//        $this->plugin = Shopware()->Plugins()->Backend()->Icepay();
//        $this->apiClient = new \Icepay\API\Client();
        $pluginManager = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->plugin = $pluginManager->getPluginByName('Icepay');
    }


    public function getIcepayPaymentMethods($merchantId, $secretCode)
    {
            $icepay = new \Icepay\API\Client();
            $icepay->setApiSecret($secretCode);
            $icepay->setApiKey($merchantId);
            $icepay->setCompletedURL('...');
            $icepay->setErrorURL('...');
            return $icepay->payment->getMyPaymentMethods();
    }
    
    public function synchronize()
    {

        $pluginConfig = $this->configReader->getByPluginName('Icepay');
            $merchantId = $pluginConfig['merchantId'];
            $secretKey = $pluginConfig['secretKey'];

        //TODO: try catch
            $paymentMethods = $this->getIcepayPaymentMethods($merchantId, $secretKey);

        if (!isset($paymentMethods->PaymentMethods) || !is_array($paymentMethods->PaymentMethods)) {
            return; //TODO: error
        }

        $collection = $this->modelManager->getRepository(PaymentMethod::class)->findAll();

        //TODO: Antipattern: Overwriting payment methods: Overwriting or deleting payment methods is absolutely prohibited.
        foreach ($collection as $item) {
            Shopware()->Models()->remove($item);
        }

        //Payment methods should be deleted only in exceptional cases. We recommend only disabling a method,
        // as deletion will create complications with customer accounts who have already made a purchase with
        // that method in your online shop.
        //Todo: delete with one query
        $payment = $this->modelManager->getRepository(Payment::class)->getAllPaymentsQuery(array('pluginId' => $this->plugin->getId()))->getArrayResult();
        foreach ($payment as $item) {
            $model = $this->modelManager->getRepository(Payment::class)->find($item['id']);
            Shopware()->Models()->remove($model);
        }

        Shopware()->Models()->flush();

        for ($i = 0; $i < count($paymentMethods->PaymentMethods); $i++)
        {

            $paymentMethodDescription = $paymentMethods->PaymentMethods[$i];

            $paymentMean = $this->paymentInstaller->createOrUpdate($this->plugin->getName(), array(
                'name' => 'icepay_'.$paymentMethodDescription->PaymentMethodCode,
                'description' => $paymentMethodDescription->Description,
                'action' => 'payment_icepay',
                'template' => 'icepay_payment.tpl',
                'class' => 'icepay',
                'active' => 0,
                //'position' => 1,
                'additionalDescription' =>
                    '<!-- paymentLogo -->
			<img src="https://example.com/payment_logo.gif"/>
			<!-- paymentLogo --><br/><br/>' .
                    '<div id="payment_desc">
				Pay save and secured through our payment service.
			</div>'
            ));


            $paymentmethod = new PaymentMethod();
            $paymentmethod->setPaymentMean($paymentMean);
            $paymentmethod->setCode($paymentMethodDescription->PaymentMethodCode);
//            $paymentmethod->setName($paymentMethodDescription->Description);
//            $paymentmethod->setDisplayName($paymentMethodDescription->Description);
//            $paymentmethod->setPosition($i);
//            $paymentmethod->setShop($shop);
            $paymentmethod->setRawPmData(serialize($paymentMethods->PaymentMethods));
//            $paymentmethod->setActive(false);
            $this->modelManager->persist($paymentmethod);

//            this->modelManager->persist($paymentmethod);
//            $configuration->setPayment($payment);

        };

        Shopware()->Models()->flush();
        
    }
    

}
