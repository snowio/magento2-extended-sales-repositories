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

        if ($hasAdjustment) {
            return !empty($creditmemo->getItems()) ?
                $this->determineValidItemsToRefund($order, $creditmemo, $availableQuantity, $hasAdjustment) :
                $this->getItemsWithoutQuantities($order);
        }

        return $this->determineValidItemsToRefund($order, $creditmemo, $availableQuantity);
    }

    /**
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditmemo
     * @param array $availableQuantity
     * @param bool $isAdjustment
     * @return array
     * @author Alexander Wanyoike <amw@amp.co>
     */
    private function determineValidItemsToRefund(
        OrderInterface $order,
        CreditmemoInterface $creditmemo,
        array $availableQuantity,
        $isAdjustment = false
    ) {
        $validItemsToRefund = [];
        foreach ($order->getAllItems() as $orderItem) {
            foreach ($creditmemo->getItems() as $creditmemoItem) {
                if ($this->cantBeRefunded($orderItem, $creditmemoItem, $availableQuantity)) {
                    continue;
                }

                $validItemsToRefund[$orderItem->getId()] = $isAdjustment ? 0 : $creditmemoItem->getQty();
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

    /**
     * If the credit memo is an adjustment
     * We will not do any returns thus all specified skus
     * in the order will have a zero quantity.
     * @param OrderInterface $order
     * @return array
     * @author Alexander Wanyoike <amw@amp.co>
     */
    private function getItemsWithoutQuantities(OrderInterface $order)
    {
        $validItemsToRefund = [];
        foreach ($order->getAllItems() as $orderItem) {
            $validItemsToRefund[$orderItem->getId()] = 0;
        }

        return $validItemsToRefund;
    }
}
