<?php
/**
 * Created by PhpStorm.
 * User: isgn
 * Date: 12.11.2015
 * Time: 14:50
 */

 class Icepay_TransactionObject implements Icepay_WebserviceTransaction_Interface_Abstract {

    protected $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getPaymentScreenURL()
    {
        return $this->data->PaymentScreenURL;
    }

    public function getPaymentID()
    {
        return $this->data->PaymentID;
    }

    public function getProviderTransactionID()
    {
        return $this->data->ProviderTransactionID;
    }

    public function getTestMode()
    {
        return $this->data->TestMode;
    }

    public function getTimestamp()
    {
        return $this->data->Timestamp;
    }

    public function getEndUserIP()
    {
        return $this->data->EndUserIP;
    }

    public function getOrderID()
    {
        return $this->data->OrderID;
    }
}