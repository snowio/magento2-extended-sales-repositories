<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use SnowIO\ExtendedSalesRepositories\Exception\SnowCreditMemoException;

/**
 * Class AvailableQuantityProvider
 *
 * @package SnowIO\ExtendedSalesRepositories\Model
 */
class ExtendedCreditMemoFactory
{
    /**
     * @var RefundableItemsFilter
     */
    private $refundableItemsFilter;

    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @param RefundableItemsFilter $refundableItemsFilter
     * @param CreditmemoFactory $creditmemoFactory
     */
    public function __construct(
        RefundableItemsFilter $refundableItemsFilter,
        CreditmemoFactory $creditmemoFactory
    ) {
        $this->refundableItemsFilter = $refundableItemsFilter;
        $this->creditmemoFactory = $creditmemoFactory;
    }

    /**
     * Create a credit memo based on the order
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditmemo
     * @return Creditmemo
     * @throws SnowCreditMemoException
     * @author Alexander Wanyoike <amw@amp.co>
     */
    public function create(OrderInterface $order, CreditmemoInterface $creditmemo)
    {
        $refundableItems = $this->refundableItemsFilter->filter($order, $creditmemo, $this->hasAdjustments($creditmemo));
        if (empty($refundableItems) && !$this->hasAdjustments($creditmemo)) {
            throw new SnowCreditMemoException(__('No items available to refund'));
        }

        if ($orderInvoice = $this->getLatestPaidInvoiceForOrder($order)) {
            return $this->creditmemoFactory->createByInvoice($orderInvoice, [
                'qtys' => $refundableItems
            ]);
        }

        return $this->creditmemoFactory->createByOrder($order, [
            'qtys' => $refundableItems
        ]);
    }

    /**
     * Get latest invoice for order
     *
     * @param OrderInterface $order
     * @return Invoice|null
     */
    private function getLatestPaidInvoiceForOrder(OrderInterface $order)
    {
        /** @var Invoice $latestInvoice */
        $latestInvoice = $order->getInvoiceCollection()
            ->addAttributeToFilter('state', ['eq' => Invoice::STATE_PAID])
            ->setPageSize(1)
            ->setCurPage(1)
            ->getLastItem();

        return $latestInvoice->getId() ? $latestInvoice : null;
    }

    private function hasAdjustments(CreditmemoInterface $creditmemo)
    {
        return $creditmemo->getBaseAdjustmentNegative() !== null ||
            $creditmemo->getBaseAdjustmentPositive() !== null ||
            $creditmemo->getBaseAdjustment() !== null;
    }
}