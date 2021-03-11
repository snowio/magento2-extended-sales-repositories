<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface;
use SnowIO\ExtendedSalesRepositories\Api\CreditmemoByOrderIncrementIdInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use SnowIO\ExtendedSalesRepositories\Exception\SnowCreditMemoException;

class CreditmemoByOrderIncrementId implements CreditmemoByOrderIncrementIdInterface
{
    /**
     * @var CreditmemoManagementInterface
     */
    private $creditmemoManagement;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreditmemoByOrderIncrementId constructor.
     * @param RefundableItemsFilter $refundableItemsFilter
     * @param ApplyAdjustments $applyAdjustments
     * @param CreditmemoSender $creditmemoSender
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param CreditmemoManagementInterface $creditmemoManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoFactory $creditmemoFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        RefundableItemsFilter $refundableItemsFilter,
        ApplyAdjustments $applyAdjustments,
        CreditmemoSender $creditmemoSender,
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoManagementInterface $creditmemoManagement,
        OrderRepositoryInterface $orderRepository,
        CreditmemoFactory $creditmemoFactory,
        LoggerInterface $logger
    ) {
        $this->refundableItemsFilter = $refundableItemsFilter;
        $this->applyAdjustments = $applyAdjustments;
        $this->creditmemoSender = $creditmemoSender;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->orderRepository = $orderRepository;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->logger = $logger;
    }

    /**
     * Create a new creditmemo using increment_id and then create refund
     *
     * @param string $orderIncrementId
     * @param CreditmemoInterface $creditmemo
     * @return CreditmemoInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createAndRefund(
        $orderIncrementId,
        CreditmemoInterface $creditmemo
    ) {
        /** @var Order $order */
        $order = $this->loadOrderByIncrementId($orderIncrementId);

        if(!$order->canCreditmemo()) {
            throw new SnowCreditMemoException(
                __("This order does not allow creation of creditmemo")
            );
        }

        $newCreditmemo = $this->createCreditMemo($order, $creditmemo);

        $this->addBackToStockStatus($order, $creditmemo, $newCreditmemo);
        $this->applyAdjustments->execute($newCreditmemo, $creditmemo);
        $newCreditmemo->collectTotals();
        $newCreditmemo->setState(Creditmemo::STATE_OPEN);
        $this->creditmemoRepository->save($newCreditmemo);

        $newCreditmemo = $this->creditmemoManagement->refund($newCreditmemo);

        /**
         * Called directly from with the controller
         *
         * @see \Magento\Sales\Controller\Adminhtml\Order\Creditmemo\Save
         */
        try {
            /** @var \Magento\Sales\Model\Order\Creditmemo $newCreditmemo */
            $this->creditmemoSender->send($newCreditmemo);
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }

        return $newCreditmemo;
    }

    private function createCreditMemo(Order $order, CreditmemoInterface $creditmemo): Creditmemo
    {
        $refundableItems = $this->refundableItemsFilter->filter($order, $creditmemo);
        if (empty($refundableItems)) {
            throw new SnowCreditMemoException(__('No items available to refund'));
        }

        if ($orderInvoice = $this->getLatestPaidInvoiceForOrder($order)) {
            return $this->creditmemoFactory->createByInvoice($orderInvoice, [
                'qtys' => $refundableItems
            ]);
        } else {
            return $this->creditmemoFactory->createByOrder($order, [
                'qtys' => $refundableItems
            ]);
        }
    }

    /**
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditmemo
     * @param CreditmemoInterface $newCreditmemo
     */
    protected function addBackToStockStatus(
        OrderInterface $order,
        CreditmemoInterface $creditmemo,
        CreditmemoInterface $newCreditmemo
    ): void {
        $itemsToBackToStock = [];
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($order->getAllItems() as $orderItem){
            /** @var \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem */
            foreach ($creditmemo->getItems() as $creditmemoItem){
                if (!$this->shouldGoBackToStock($creditmemoItem, $orderItem)) {
                    continue;
                }

                $itemsToBackToStock[] = $creditmemoItem->getSku();
            }
        }

        $itemsToBackToStock = array_unique($itemsToBackToStock);
        foreach($newCreditmemo->getItems() as $memoItem) {
            if (in_array($memoItem->getSku(), $itemsToBackToStock)) {
                $memoItem->setBackToStock(true);
            }
        }
    }

    /**
     * @param CreditmemoItemInterface $creditmemoItem
     * @param OrderItemInterface $orderItem
     * @return bool
     */
    protected function shouldGoBackToStock(CreditmemoItemInterface $creditmemoItem, OrderItemInterface $orderItem): bool
    {
        $backToStockStatus = $creditmemoItem->getExtensionAttributes() ?
            $creditmemoItem->getExtensionAttributes()->getBackToStock() : 0;
        return $orderItem->getSku() === $creditmemoItem->getSku()
            && $backToStockStatus;
    }

    /**
     * Get latest invoice for order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Model\Order\Invoice|null
     */
    private function getLatestPaidInvoiceForOrder(Order $order) : ?Invoice
    {
        /** @var \Magento\Sales\Model\Order\Invoice $latestInvoice */
        $latestInvoice = $order->getInvoiceCollection()
            ->addAttributeToFilter('state', ['eq' => Invoice::STATE_PAID])
            ->setPageSize(1)
            ->setCurPage(1)
            ->getLastItem();

        return $latestInvoice->getId() ? $latestInvoice : null;
    }

    protected function loadOrderByIncrementId(string $incrementId): Order
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
