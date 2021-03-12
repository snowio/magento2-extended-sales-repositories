<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class RefundableItemsFilter
 *
 * @package SnowIO\ExtendedSalesRepositories\Model
 */
class RefundableItemsFilter
{
    /**
     * @var AvailableQuantityProvider
     */
    private $availableQuantityProvider;

    /**
     * RefundableItemsFilter constructor.
     *
     * @param AvailableQuantityProvider $availableQuantityProvider
     */
    public function __construct(
        AvailableQuantityProvider $availableQuantityProvider
    ) {
        $this->availableQuantityProvider = $availableQuantityProvider;
    }

    /**
     * Filter valid items to refund
     *
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditmemo
     * @param bool $hasAdjustment
     * @return array
     * @author Daniel Doyle <dd@amp.co>
     */
    public function filter(OrderInterface $order, CreditmemoInterface $creditmemo, $hasAdjustment)
    {
        $availableQuantity = $this->availableQuantityProvider->provide($order);

        $validItemsToRefund = [];
        foreach ($order->getAllItems() as $orderItem) {
            foreach ($creditmemo->getItems() as $creditmemoItem) {
                /**
                 * If the credit memo is an adjustment
                 * We will not do any returns thus all specified skus
                 * in the credit memo will have a zero quantity.
                 */
                if ($hasAdjustment) {
                    $validItemsToRefund[$orderItem->getId()] = 0;
                } elseif ($this->cantBeRefunded($orderItem, $creditmemoItem, $availableQuantity)) {
                    continue;
                } else {
                    $validItemsToRefund[$orderItem->getId()] = $creditmemoItem->getQty();
                }
            }
        }

        return $validItemsToRefund;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @param CreditmemoItemInterface $creditmemoItem
     * @param array $availableQuantity
     * @return bool
     * @author Alexander Wanyoike <amw@amp.co>
     */
    private function cantBeRefunded(
        OrderItemInterface $orderItem,
        CreditmemoItemInterface $creditmemoItem,
        array $availableQuantity
    ) {
        return $orderItem->getSku() !== $creditmemoItem->getSku()
            || $creditmemoItem->getQty() <= 0
            || $creditmemoItem->getQty() > $availableQuantity[$orderItem->getId()];
    }
}