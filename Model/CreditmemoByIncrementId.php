<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use SnowIO\ExtendedSalesRepositories\Api\CreditmemoByIncrementIdInterface;

class CreditmemoByIncrementId implements CreditmemoByIncrementIdInterface
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
     * CreditmemoByIncrementId constructor.
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
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createAndRefund(
        $orderIncrementId,
        \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
    ) {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->loadOrderByIncrementId($orderIncrementId);

        /** @var \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo */
        $creditmemo = $this->creditmemoFactory->createByOrder($order, $adjustments = []);
        $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);

        $this->creditmemoRepository->save($creditmemo);

        $this->creditmemoManagement->refund($creditmemo);

        return $creditmemo;
    }

    private function loadOrderByIncrementId(string $incrementId)
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
