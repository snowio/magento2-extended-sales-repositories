<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use SnowIO\ExtendedSalesRepositories\Api\ShipOrderByIncrementalIdInterface;

class ShipOrderByIncrementalId implements ShipOrderByIncrementalIdInterface
{

    private $shipOrder;
    private $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository, ShipOrderInterface $shipOrder)
    {
        $this->orderRepository = $orderRepository;
        $this->shipOrder = $shipOrder;
    }

    public function execute(
        $orderIncrementId,
        array $items = [],
        $notify = false,
        $appendComment = false,
        \Magento\Sales\Api\Data\ShipmentCommentCreationInterface $comment = null,
        array $tracks = [],
        array $packages = [],
        \Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface $arguments = null
    ) {
        $order = $this->loadOrderByIncrementId($orderIncrementId);
        return $this->shipOrder->execute(
            $order->getEntityId(),
            $items,
            $notify,
            $appendComment,
            $comment,
            $tracks,
            $packages,
            $arguments
        );
    }

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
