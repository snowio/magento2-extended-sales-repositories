<?php

namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Framework\Model\AbstractModel;
use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;

class OrderRelatedData extends AbstractModel implements IdentityInterface, OrderRelatedDataInterface
{
    const CACHE_TAG = 'snowio_order_relateddata';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_cacheTag = self::CACHE_TAG;
        $this->_eventPrefix = ResourceModel\OrderRelatedData::TABLE;
        $this->_init(ResourceModel\OrderRelatedData::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getCode()
    {
        return trim($this->getData(self::CODE));
    }

    /**
     * @param string $code
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function setCode($code)
    {
        $this->setData(self::CODE, trim($code));
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }

    /**
     * @param string $value
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function setValue($value)
    {
        $this->setData(self::VALUE, $value);
    }

    /**
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * @param string $incrementId
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function setOrderIncrementId($incrementId)
    {
        $this->setData(self::ORDER_INCREMENT_ID, $incrementId);
    }

}