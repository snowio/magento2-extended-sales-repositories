<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class AvailableQuantityProvider
 *
 * @package SnowIO\ExtendedSalesRepositories\Model
 */
class AvailableQuantityProvider
{
    /**
     * @var array
     */
    private $availableByOrder = [];

    /**
     * Provide available quantities
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @author Daniel Doyle <dd@amp.co>
     */
    public function provide(OrderInterface $order) : array
    {
        if (array_key_exists($order->getEntityId(), $this->availableByOrder)) {
            return $this->availableByOrder[$order->getEntityId()];
        }

        $quantityRefunded = $this->getQuantityRefunded($order);

        return $this->availableByOrder[$order->getEntityId()] = array_reduce(
            array_keys($quantityRefunded),
            static function (array $quantityAvailable, int $orderItemId) use ($quantityRefunded) : array {
                if (!array_key_exists($orderItemId, $quantityAvailable)) {
                    return $quantityAvailable;
                }

                // Sum all quantities for the order item provided by the collected credit memos
                $quantityAvailable[$orderItemId] =
                    max($quantityAvailable[$orderItemId] - array_sum($quantityRefunded[$orderItemId]), 0);

                return $quantityAvailable;
            },
            $this->getQuantityAvailable($order)
        );
    }


    /**
     * Get quantity of items refunded in all credit memos
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @author Daniel Doyle <dd@amp.co>
     */
    private function getQuantityRefunded(OrderInterface $order) : array
    {
        $refundedItems = [];
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            foreach ($creditmemo->getItems() as $creditmemoItem) {
                $quantityRefunded = (float) $creditmemoItem->getQty();
                if ($quantityRefunded < 1) {
                    continue;
                }

                // Dynamically create array of quantities for aggregation later
                $refundedItems[$creditmemoItem->getOrderItemId()][] = $quantityRefunded;
            }
        }

        return $refundedItems;
    }

    /**
     * Get quantity of available items from order. As we're querying all credit memos (including broken/invalid ones) we
     * base the refunded quantity on them instead of relying on `getQtyRefunded` set against an order item
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @author Daniel Doyle <dd@amp.co>
     */
    private function getQuantityAvailable(OrderInterface $order) : array
    {
        return array_reduce(
            $order->getAllItems(),
            function (array $refundedItems, OrderItemInterface $orderItem) : array {
                $refundedItems[$orderItem->getItemId()] = (float) $orderItem->getQtyOrdered();

                return $refundedItems;
            },
            []
        );
    }
}
