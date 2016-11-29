<?php

namespace SnowIO\ExtendedSalesRepositories\Api;

/**
 * Class ShipOrderInterface
 *
 * @api
 */
interface ShipOrderByIncrementIdInterface
{
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
    );
}
