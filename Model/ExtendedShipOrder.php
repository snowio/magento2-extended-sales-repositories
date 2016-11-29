<?php
namespace SnowIO\ExtendedSalesRepositories\Api\Data;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use SnowIO\ExtendedSalesRepositories\Api\ExtendedShipOrderInterface;


class ExtendedShipOrder implements ExtendedShipOrderInterface
{

    private $shipOrder;
    private $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository, ShipOrderInterface $shipOrder)
    {
        $this->orderRepository = $orderRepository;
        $this->shipOrder = $shipOrder;
    }


    /**
     * Creates new Shipment for given Order.
     *
     * @param string $orderIncrementId
     * @param \Magento\Sales\Api\Data\ShipmentItemCreationInterface[] $items
     * @param bool $notify
     * @param bool $appendComment
     * @param \Magento\Sales\Api\Data\ShipmentCommentCreationInterface|null $comment
     * @param \Magento\Sales\Api\Data\ShipmentTrackCreationInterface[] $tracks
     * @param \Magento\Sales\Api\Data\ShipmentPackageCreationInterface[] $packages
     * @param \Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface|null $arguments
     * @return int Id of created Shipment.
     */
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
        $this->shipOrder->execute(
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

        return $order[0];
    }
}