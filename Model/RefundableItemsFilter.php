<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class RefundableItemsFilter
 *
 *
 * @package SnowIO\ExtendedSalesRepositories\Model
 */
class RefundableItemsFilter
{
    /**
     * @var \SnowIO\ExtendedSalesRepositories\Model\AvailableQuantityProvider
     */
    private $availableQuantityProvider;

    /**
     * RefundableItemsFilter constructor.
     *
     * @param \SnowIO\ExtendedSalesRepositories\Model\AvailableQuantityProvider $availableQuantityProvider
     */
    public function __construct(
        AvailableQuantityProvider $availableQuantityProvider
    ) {
        $this->availableQuantityProvider = $availableQuantityProvider;
    }

    /**
     * Filter valid items to refund
     *
     * @param \Magento\Sales\Api\Data\OrderInterface      $order
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return array
     * @author Daniel Doyle <dd@amp.co>
     */
    public function filter(OrderInterface $order, CreditmemoInterface $creditmemo) : array
    {
        $availableQuantity = $this->availableQuantityProvider->provide($order);

        $validItemsToRefund = [];
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            if (!$this->isOrderItemRefundable($orderItem)) {
                continue;
            }

            foreach ($creditmemo->getItems() as $creditmemoItem) {
                if ($orderItem->getSku() !== $creditmemoItem->getSku()
                    || $creditmemoItem->getQty() <= 0
                    || $creditmemoItem->getQty() > $availableQuantity[$orderItem->getId()]
                ) {
                    continue;
                }

                $validItemsToRefund[$orderItem->getId()] = $creditmemoItem->getQty();
            }
        }

        return $validItemsToRefund;
    }

    /**
     * Check if order item is refundable
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface|null $orderItem
     * @return bool
     * @author Daniel Doyle <dd@amp.co>
     */
    private function isOrderItemRefundable(?OrderItemInterface $orderItem) : bool
    {
        if (!$orderItem) {
            return true;
        }

        $orderItemExtensionAttributes = $orderItem->getExtensionAttributes();
        if (!$orderItemExtensionAttributes) {
            return true;
        }

        return !filter_var($orderItemExtensionAttributes->getNotRefundable(), FILTER_VALIDATE_BOOLEAN)
            && $this->execute($orderItem->getParentItem());
    }
}