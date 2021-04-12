<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use SnowIO\ExtendedSalesRepositories\Api\ShipOrderByIncrementIdInterface;

class ShipOrderByIncrementId implements ShipOrderByIncrementIdInterface
{
    use LoadOrderByIncrementIdTrait;

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
}
