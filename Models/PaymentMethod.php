<?php

namespace Icepay\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_icepay_payment_method")
 */
class PaymentMethod extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $code
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=false)
     */
    private $code;



    /**
     * @ORM\OneToOne(targetEntity="Shopware\Models\Payment\Payment")
     * @ORM\JoinColumn(name="payment_mean_id", referencedColumnName="id")
     */
    private $paymentMean;


//    /**
//     * @var string $rawPmData
//     *
//     * @ORM\Column(name="rawpmdata", type="text", nullable=false)
//     */
//    private $rawPmData;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->paymentMeans = new ArrayCollection();
//        $this->active = true;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param \Shopware\Models\Payment\Payment $paymentMean
     */
    public function setPaymentMean($paymentMean)
    {
        $this->paymentMean = $paymentMean;
    }

    /**
     * @return \Shopware\Models\Payment\Payment
     */
    public function getPaymentMean()
    {
        return $this->paymentMean;
    }


//    /**
//     * @return ArrayCollection
//     */
//    public function getPaymentMeans()
//    {
//        return $this->paymentMeans;
//    }

//    /**
//     * @param ArrayCollection|array|null $paymentMeans
//     * @return \Shopware\Models\Payment\Payment
//     */
//    public function setPaymentMeans($paymentMeans)
//    {
////        return $this->setOneToMany($paymentMeans, '\Shopware\Models\Payment\Payment', 'paymentMeans');
//        $this->paymentMeans = $paymentMeans;
//        return $this;
//    }
//
//    public function addPaymentMeans(\Shopware\Models\Payment\Payment $payment)
//    {
//        if (!$this->paymentMeans->contains($payment)) {
//            $this->paymentMeans->add($payment);
//        }
//
//        return $this;
//    }


    /**
     * @param string $pmRowData
     */
    public function setRawPmData($rawPmData)
    {
        $this->rawPmData = $rawPmData;
    }

    /**
     * @return string
     */
    public function getRawPmData()
    {
        return $this->rawPmData;
    }
    

}
