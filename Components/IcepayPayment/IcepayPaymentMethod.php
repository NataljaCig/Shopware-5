<?php


namespace Icepay\Components\IcepayPayment;


use Doctrine\ORM\AbstractQuery;
use ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod;

/**
 *
 * Class IcepayPaymentMethod
 * Used to handle ICEPAY payments
 */
/*abstract*/ class IcepayPaymentMethod extends GenericPaymentMethod
{

    private $currentPaymentMethod;

    /**
     * {@inheritdoc}
     */
    public function validate($paymentData)
    {
        if(!empty($paymentData)) {
            $sErrorFlag = [];
            $issuerFieldName = "sIcepayIssuer" . (int)$paymentData['payment'];

            $value = $paymentData[$issuerFieldName] ?: '';
            $value = trim($value);

            if (empty($value)) {
                $sErrorFlag[$issuerFieldName] = true;
            }

            if (count($sErrorFlag)) {
                $sErrorMessages[] = Shopware()->Snippets()->getNamespace('frontend/account/internalMessages')
                    ->get('ErrorFillIn', 'Please fill in all red fields');

                return [
                    'sErrorFlag' => $sErrorFlag,
                    'sErrorMessages' => $sErrorMessages,
                ];
            }

            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function savePaymentData($userId, \Enlight_Controller_Request_Request $request)
    {
        $paymentId = (int)$request->getParam('payment');
        $issuerFieldName = "sIcepayIssuer".$paymentId;

        $paymentMean = Shopware()->Models()->getRepository('\Shopware\Models\Payment\Payment')->
            getPaymentsQuery(['id' => $paymentId])->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        if (substr( $paymentMean['name'], 0, 7 ) === "icepay_") {

            $this->currentPaymentMethod = $paymentMean;

            $lastPayment = $this->getCurrentPaymentDataAsArray($userId);

            $data = [
                'bankname' => $request->getParam('sIcepayIssuer'.$paymentId)
            ];

            if (!$lastPayment) {
                $date = new \DateTime();
                $data['created_at'] = $date->format('Y-m-d');
                $data['payment_mean_id'] = $paymentMean['id'];
                $data['user_id'] = $userId;
                Shopware()->Db()->insert('s_core_payment_data', $data);
            } else {
                $where = [
                    'payment_mean_id = ?' => $paymentMean['id'],
                    'user_id = ?' => $userId,
                ];

                Shopware()->Db()->update('s_core_payment_data', $data, $where);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPaymentDataAsArray($userId)
    {

        if(isset($this->currentPaymentMethod)) {

            $paymentData = Shopware()->Models()->getRepository('\Shopware\Models\Customer\PaymentData')
                ->getCurrentPaymentDataQueryBuilder($userId, $this->currentPaymentMethod['name'])->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

            if (isset($paymentData)) {
                $arrayData = [
                    'sDebitBankName' => $paymentData['bankName'],
                ];

                return $arrayData;
            }
        }
        else return []; //TODO!!
    }

    /**
     * {@inheritdoc}
     */
    public function createPaymentInstance($orderId, $userId, $paymentId)
    {
        $orderAmount = Shopware()->Models()->createQueryBuilder()
            ->select('orders.invoiceAmount')
            ->from('Shopware\Models\Order\Order', 'orders')
            ->where('orders.id = ?1')
            ->setParameter(1, $orderId)
            ->getQuery()
            ->getSingleScalarResult();

        $addressData = Shopware()->Models()->getRepository('Shopware\Models\Customer\Customer')
            ->find($userId)->getDefaultBillingAddress();

        $debitData = $this->getCurrentPaymentDataAsArray($userId);

        $date = new \DateTime();
        $data = [
            'payment_mean_id' => $paymentId,
            'order_id' => $orderId,
            'user_id' => $userId,
            'firstname' => $addressData->getFirstname(),
            'lastname' => $addressData->getLastname(),
            'address' => $addressData->getStreet(),
            'zipcode' => $addressData->getZipcode(),
            'city' => $addressData->getCity(),
            'account_number' => $debitData['sDebitAccount'],
            'bank_code' => $debitData['sDebitBankcode'],
            'bank_name' => $debitData['sDebitBankName'],
            'account_holder' => $debitData['sDebitBankHolder'],
            'amount' => $orderAmount,
            'created_at' => $date->format('Y-m-d'),
        ];

        Shopware()->Db()->insert('s_core_payment_instance', $data);

        return true;
    }
}
