<?php

namespace Icepay\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="s_icepay_raw_data")
 */
class RawData extends ModelEntity
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
     * @var integer $id
     *
     * @ORM\Column(name="scope", type="integer", nullable=false)
     */
    private $scope;

    /**
     * @var string $rawPmData
     *
     * @ORM\Column(name="rawpmdata", type="text", nullable=false)
     */
    private $rawPmData;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $pmRowData
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

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
