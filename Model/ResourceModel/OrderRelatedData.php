<?php
namespace SnowIO\ExtendedSalesRepositories\Model\ResourceModel;

use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface;
use Magento\Framework\Model\ResourceModel\Db;

class OrderRelatedData extends Db\AbstractDb
{
    const TABLE = 'snowio_order_relateddata';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, OrderRelatedDataInterface::ID);
    }
}