<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;

trait LoadOrderByIncrementIdTrait
{
    private function loadOrderByIncrementId(string $incrementId)
    {
        $searchCriteria = (new SearchCriteria())
            ->setFilterGroups([
                (new FilterGroup)->setFilters([
                    (new Filter)
                        ->setField('increment_id')
                        ->setConditionType('eq')
                        ->setValue($incrementId)
                ])
            ]);

        $order = $this->orderRepository->getList($searchCriteria)->getItems();

        if (empty($order)) {
            throw new \LogicException("No order exists with increment ID '$incrementId'.");
        }

        return reset($order);
    }
}
