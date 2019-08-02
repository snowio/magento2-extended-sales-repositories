<?php

namespace SnowIO\ExtendedSalesRepositories\Api\Data;

use Magento\Framework;

interface OrderRelatedDataSearchResultsInterface extends Framework\Api\SearchResultsInterface
{
    /**
     * @return OrderRelatedDataInterface[]
     * @api
     */
    public function getItems();

    /**
     * @param OrderRelatedDataInterface[] $items
     *
     * @return $this
     * @api
     */
    public function setItems(array $items);
}