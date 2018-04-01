<?php
namespace Icepay\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Payment\Payment;
use Icepay\Models\RawData;
use Symfony\Component\DependencyInjection\ContainerInterface;

require_once(dirname(__FILE__) . '/../../restapi/src/Icepay/API/Autoloader.php');


class Checkout implements SubscriberInterface
{

    /**
     *
     * @var string
     */
    private $pluginDirectory;

    /**
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var array
     */
    private $filteredPaymentMethods;




    public function __construct($pluginDirectory, ModelManager $modelManager, ContainerInterface $container)
    {
        $this->pluginDirectory = $pluginDirectory;;
        $this->modelManager = $modelManager;
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatch',
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onDataFilter'

        ];
    }


    /**
     * Filter ICEPAY payment methods by country, amount and etc.
     * @param \Enlight_Event_EventArgs $args
     * @return mixed
     */
    public function onDataFilter(\Enlight_Event_EventArgs $args)
    {
        $paymentMeans = $args->getReturn();

        //get list of available ICEPAY payment methods
        $availablePaymentMethods = array();
        foreach ($this->getFilteredPaymentmethods() as $pm) {
            $availablePaymentMethods[] = $pm->PaymentMethodCode;
        }

        //filter out unavailable ICEPAY payment methods
        $filteredPaymentMeans = [];
        foreach ($paymentMeans as $paymentMean) {
            if (substr($paymentMean['class'], 0, 7) === "icepay_"
                && !in_array(substr($paymentMean['name'], 7), $availablePaymentMethods)
            ) {
                continue;
            }
            $filteredPaymentMeans[] = $paymentMean;
        }

        $args->setReturn($filteredPaymentMeans);
        return $args->getReturn();
    }


    /**
     * Prepare data for credit card issuer drop-down-lists
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onPostDispatch(\Enlight_Hook_HookArgs $args)
    {

        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->get('subject');
        $view = $controller->View();
        
        $availableIssuers = array();
        foreach ($this->getFilteredPaymentmethods() as $pm) {

            if(sizeof($pm->Issuers) > 1) {
                foreach($pm->Issuers as $issuer) {
                    $availableIssuers['icepay_'.$pm->PaymentMethodCode][] = array( 'key' =>  $issuer->IssuerKeyword, 'description' => $issuer->Description);
                }

            }
        }

        $view->assign('sIssuers', $availableIssuers);
        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');

    }


    /**
     * Get list of available ICEPAY payment methods
     * @return array
     */
    protected function getFilteredPaymentmethods()
    {

        if (!isset($this->filteredPaymentMethods)) {

            $basket = Shopware()->Modules()->Basket();
            $session = Shopware()->Session();
            $countryId = $session->get('sCountry'); //Todo: check null

            $amount = Shopware()->Modules()->Basket()->sGetAmount();
            $totalAmount = empty($amount) ? 0 : array_shift($amount);

            $currency = Shopware()->Shop()->getCurrency()->getCurrency();
            $country = $this->getCountryCode($countryId);

            $rawData = $this->modelManager->getRepository(RawData::class)->findOneBy(array('scope' => 1));  //TODO: if null

            $filter = new \Icepay_Webservice_Paymentmethod();
            $filter->loadFromArray(unserialize($rawData->getRawPmData()));
            $filter->filterByCurrency($currency)
                ->filterByCountry($country)
                ->filterByAmount((int)(string)($totalAmount * 100));

            $this->filteredPaymentMethods = $filter->getFilteredPaymentmethods();
        }

        return $this->filteredPaymentMethods;
    }


    /**
     * Get ISO country code by Shopware country ID
     *
     * @param $countryId
     * @return string
     */
    private function getCountryCode($countryId)
    {
        $countryRepository = $this->container->get('shopware_storefront.country_gateway');
        $context = $this->container->get('shopware_storefront.context_service')->getShopContext();

        $country = $countryRepository->getCountry($countryId, $context);

        return $country->getIso();
    }


}