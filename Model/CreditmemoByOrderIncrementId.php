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
use SnowIO\ExtendedSalesRepositories\Api\CreditmemoByOrderIncrementIdInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class CreditmemoByOrderIncrementId implements CreditmemoByOrderIncrementIdInterface
{
    /** @var CreditmemoManagementInterface */
    private $creditmemoManagement;

    /** @var CreditmemoRepositoryInterface */
    private $creditmemoRepository;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var CreditmemoFactory */
    private $creditmemoFactory;

    /**
     * CreditmemoByOrderIncrementId constructor.
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param CreditmemoManagementInterface $creditmemoManagement
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoManagementInterface $creditmemoManagement,
        OrderRepositoryInterface $orderRepository,
        CreditmemoFactory $creditmemoFactory
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->orderRepository = $orderRepository;
        $this->creditmemoFactory = $creditmemoFactory;
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
            throw new \Magento\Framework\Exception\LocalizedException(
                __("This order does not allow creation of creditmemo")
            );
        }

        /** @var CreditmemoInterface $creditmemo */
        $newCreditmemo = $this->creditmemoFactory->createByOrder($order, [
            'qtys' => $this->filterItemsToBeRefunded($order, $creditmemo)
        ]);
        $newCreditmemo->setState(Creditmemo::STATE_OPEN);

        $this->addBackToStockStatus($order, $creditmemo, $newCreditmemo);

        $this->creditmemoRepository->save($newCreditmemo);

        return $this->creditmemoManagement->refund($newCreditmemo);
    }

    /**
     * Get the sku and qty from the input payload to decide which item and its quantity to be refunded.
     * This allow partial creditmemo/refund
     *
     * @param Order $order
     * @param CreditmemoInterface $creditmemo
     * @return array
     */
    public function filterItemsToBeRefunded(Order $order, CreditmemoInterface $creditmemo)
    {
        $selectedItemsToRefund = [];
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach($order->getAllItems() as $orderItem){
            /** @var \Magento\Sales\Model\Order\Creditmemo\Item $inputItem */
            foreach($creditmemo->getItems() as $inputItem){
                if($orderItem->getSku() === $inputItem->getSku() && $inputItem->getQty() > 0){
                    $selectedItemsToRefund[$orderItem->getId()] = $inputItem->getQty();
                }
            }
        }
        return $selectedItemsToRefund;
    }

    /**
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditmemo
     * @param CreditmemoInterface $newCreditmemo
     */
    protected function addBackToStockStatus(OrderInterface $order, CreditmemoInterface $creditmemo, CreditmemoInterface $newCreditmemo)
    {
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
        $backToStockStatus = $creditmemoItem->getExtensionAttributes() ? $creditmemoItem->getExtensionAttributes()->getBackToStock() : 0;
        return $orderItem->getSku() === $creditmemoItem->getSku()
            && $backToStockStatus;
    }


    protected function loadOrderByIncrementId(string $incrementId)
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
