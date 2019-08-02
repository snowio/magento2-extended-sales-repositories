<?php

namespace SnowIO\ExtendedSalesRepositories\Model\ResourceModel\OrderRelatedData;

use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface;
use SnowIO\ExtendedSalesRepositories\Model;
use Magento\Framework\Model\ResourceModel\Db;

class Collection extends Db\Collection\AbstractCollection
{
    protected $_idFieldName = OrderRelatedDataInterface::ID;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model\OrderRelatedData::class, Model\ResourceModel\OrderRelatedData::class);
    }
}