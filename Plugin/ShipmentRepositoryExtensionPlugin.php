<?php
namespace SnowIO\ExtendedSalesRepositories\Plugin;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class ShipmentRepositoryExtensionPlugin
{
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function beforeSave(
        ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Sales\Api\Data\ShipmentInterface $shipment
    ) {
        if ($extensionAttributes = $shipment->getExtensionAttributes()) {
            $incrementId = $extensionAttributes->getOrderIncrementId();
            if (null !== $incrementId) {
                $order = $this->loadOrderByIncrementId($incrementId);
                $orderId = $order->getEntityId();
                $shipment->setOrderId($orderId);
            }
        }

        return [$shipment];
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

        return $order[0];
    }
}
