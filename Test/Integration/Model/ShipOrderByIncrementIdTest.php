<?php

namespace SnowIO\ExtendedSalesRepositories\Test\Integration\Plugin;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Item;
use Magento\TestFramework\Helper\Bootstrap;
use SnowIO\ExtendedSalesRepositories\Api\ShipOrderByIncrementIdInterface;
use SnowIO\ExtendedSalesRepositories\Model\LoadOrderByIncrementIdTrait;
use SnowIO\ExtendedSalesRepositories\Test\TestCase;

class ShipOrderByIncrementIdTest extends TestCase
{
    use LoadOrderByIncrementIdTrait;

    private $objectManager;
    /** @var ShipOrderByIncrementIdInterface */
    private $shipOrderByIncrementId;
    /** @var ShipmentRepositoryInterface */
    private $shipmentRepository;
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->shipOrderByIncrementId = $this->objectManager->get(ShipOrderByIncrementIdInterface::class);
        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->shipmentRepository = $this->objectManager->get(ShipmentRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture SnowIO_ExtendedSalesRepositories::Test/Integration/_files/order.php
     */
    public function testStandardCase()
    {
        $orderIncrementId = "100000001";
        $order = $this->loadOrderByIncrementId($orderIncrementId);
        $items = array_map(function (Item $orderItem) {
           return $this->objectManager
                ->create(ShipmentItemCreationInterface::class)
                ->setQty(1)
                ->setOrderItemId($orderItem->getId());
        }, $order->getItems());
        $shipmentId = $this->shipOrderByIncrementId->execute($orderIncrementId, $items);
        $this->assertShipmentContainsCorrectItems($items, $shipmentId);

    }

    /**
     * @magentoDataFixture SnowIO_ExtendedSalesRepositories::Test/Integration/_files/order.php
     * @dataProvider getErroneousCaseTestData
     * @param string $orderIncrementId
     * @param array $items
     * @param string $errorClass
     */
    public function testErroneousCase(string $orderIncrementId, array $items, string $errorClass)
    {
        $this->expectException($errorClass);
        $this->shipOrderByIncrementId->execute($orderIncrementId, $items);
    }

    public function getErroneousCaseTestData()
    {
        return [
            "should fail if no order with the increment id was found" => [
                "123131415",
                [
                    $this->objectManager->create(ShipmentItemCreationInterface::class)
                        ->setOrderItemId(1)
                        ->setQty(1)
                ],
                \LogicException::class,
            ]
        ];
    }

    public function assertShipmentContainsCorrectItems(array $items, string $shipmentId)
    {
            /** @var ShipmentInterface $shipment */
            $shipment = $this->shipmentRepository->get($shipmentId);

            $shipmentItemByOrderItemId = [];
            foreach ($shipment->getItems() as $shipmentItem) {
                $shipmentItemByOrderItemId[$shipmentItem->getOrderItemId()] = [
                    'qty' => $shipmentItem->getQty(),
                ];
            }

            $inputItemsBySku = [];
            foreach ($items as $item) {
                $inputItemsBySku[$item->getOrderItemId()] = [
                    'qty' => $item->getQty()
                ];
            }

            self::assertEquals($inputItemsBySku, $shipmentItemByOrderItemId);
    }
}
