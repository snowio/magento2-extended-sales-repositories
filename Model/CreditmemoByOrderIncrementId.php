<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Psr\Log\LoggerInterface;
use SnowIO\ExtendedSalesRepositories\Api\CreditmemoByOrderIncrementIdInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use SnowIO\ExtendedSalesRepositories\Exception\SnowCreditMemoException;

class CreditmemoByOrderIncrementId implements CreditmemoByOrderIncrementIdInterface
{
    /**
     * @var ExtendedCreditMemoFactory
     */
    private $extendedCreditMemoFactory;

    /**
     * @var CreditmemoSender
     */
    private $creditmemoSender;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CreditmemoByOrderIncrementId constructor.
     * @param ExtendedCreditMemoFactory $extendedCreditMemoFactory
     * @param CreditmemoSender $creditmemoSender
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param CreditmemoManagementInterface $creditmemoManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ExtendedCreditMemoFactory $extendedCreditMemoFactory,
        CreditmemoSender $creditmemoSender,
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoManagementInterface $creditmemoManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->extendedCreditMemoFactory = $extendedCreditMemoFactory;
        $this->creditmemoSender = $creditmemoSender;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Create a new creditmemo using increment_id and then create refund
     *
     * @param string $orderIncrementId
     * @param CreditmemoInterface $creditmemo
     * @return CreditmemoInterface
     * @throws LocalizedException
     */
    public function createAndRefund(
        $orderIncrementId,
        CreditmemoInterface $creditmemo
    ) {
        $order = $this->loadOrderByIncrementId($orderIncrementId);

        if(!$order->canCreditmemo()) {
            throw new SnowCreditMemoException(
                __("This order does not allow creation of creditmemo")
            );
        }

        $newCreditmemo = $this->extendedCreditMemoFactory->create($order, $creditmemo);

        $this->addBackToStockStatus($order, $creditmemo, $newCreditmemo);

        $this->applyDefaults($newCreditmemo, $creditmemo);
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
            /** @var Creditmemo $newCreditmemo */
            $this->creditmemoSender->send($newCreditmemo);
        } catch (\Exception $exception) {
            $this->logger->error($exception);
        }

        return $newCreditmemo;
    }

    /**
     * Applies defaults to the new credit memo
     * @param CreditmemoInterface $newCreditmemo
     * @param CreditmemoInterface $creditmemo
     */
    public function applyDefaults(CreditmemoInterface $newCreditmemo, CreditmemoInterface $creditmemo)
    {
        ExtendedCreditMemoManagement::applyAmounts($newCreditmemo, $creditmemo);
        ExtendedCreditMemoManagement::applyTax($newCreditmemo, $creditmemo);
        ExtendedCreditMemoManagement::applyExtensionAttributes($newCreditmemo, $creditmemo);
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
    ) {
        $itemsToBackToStock = [];
        foreach ($order->getAllItems() as $orderItem){
            /** @var Item $creditmemoItem */
            foreach ($creditmemo->getItems() as $creditmemoItem){
                if (!$this->shouldGoBackToStock($creditmemoItem, $orderItem)) {
                    continue;
                }

                $itemsToBackToStock[] = $creditmemoItem->getSku();
            }
        }

        $itemsToBackToStock = array_unique($itemsToBackToStock);
        foreach($newCreditmemo->getItems() as $memoItem) {
            if (in_array($memoItem->getSku(), $itemsToBackToStock, true)) {
                $memoItem->setBackToStock(true);
            }
        }
    }

    /**
     * @param CreditmemoItemInterface $creditmemoItem
     * @param OrderItemInterface $orderItem
     * @return bool
     */
    protected function shouldGoBackToStock(CreditmemoItemInterface $creditmemoItem, OrderItemInterface $orderItem)
    {
        $backToStockStatus = $creditmemoItem->getExtensionAttributes() ?
            $creditmemoItem->getExtensionAttributes()->getBackToStock() : 0;
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
