<?php
namespace SnowIO\ExtendedSalesRepositories\Plugin;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface;
use SnowIO\ExtendedSalesRepositories\Api\OrderRelatedDataRepositoryInterface;

class OrderRepositoryPlugin
{
    /** @var OrderRelatedDataRepositoryInterface */
    private $orderRelatedDataRepository;

    /** @var OrderExtensionFactory */
    private $orderExtensionFactory;

    public function __construct(
        OrderRelatedDataRepositoryInterface $orderRelatedDataRepository,
        ExtensionAttributesFactory $extensionAttributesFactory
    )
    {
        $this->orderRelatedDataRepository = $orderRelatedDataRepository;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * Append Order Related Data Collection as Extension Attributes
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $orderRepository, OrderInterface $order)
    {
        $orderExtensionAttributes = $this->getRelatedDataExtensionAttributes($order);
        $order->setExtensionAttributes($orderExtensionAttributes);
        return $order;
    }

    /**
     * Get All Order Related Data by Increment Id and return as Extension Attributes
     *
     * @param $order
     * @return \Magento\Framework\Api\ExtensionAttributesInterface
     */
    private function getRelatedDataExtensionAttributes($order)
    {
        $orderExtensionAttributes = $order->getExtensionAttributes();
        if ($orderExtensionAttributes === null) {
            $orderExtensionAttributes = $this->orderExtensionFactory->create();
        }

        $relatedDataItems = $this->orderRelatedDataRepository->getAllByIncrementId($order->getIncrementId());
        if ($relatedDataItems) {
            $relatedData = array_map(function(OrderRelatedDataInterface $item){
                return [$item->getCode() => $item->getValue()];
            }, $relatedDataItems);
            $orderExtensionAttributes->setSnowioRelateddata($relatedData);
        }
        return $orderExtensionAttributes;
    }

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $searchResult
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function afterGetList(OrderRepositoryInterface $orderRepository, $searchResult)
    {
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($searchResult->getItems() as $order) {
            $extensionAttributes = $this->getRelatedDataExtensionAttributes($order);
            $order->setExtensionAttributes($extensionAttributes);
        }
        return $searchResult;
    }
}