<?php

namespace SnowIO\ExtendedSalesRepositories\Test\Integration\Plugin;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use SnowIO\ExtendedSalesRepositories\Api\ShipOrderByIncrementIdInterface;
use SnowIO\ExtendedSalesRepositories\Test\TestCase;

class ShipOrderByIncrementIdTest extends TestCase
{
    private $objectManager;
    /** @var ShipOrderByIncrementIdInterface */
    private $shipOrderByIncrementId;
    /** @var ShipmentRepositoryInterface */
    private $shipmentRepository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->shipOrderByIncrementId = $this->objectManager->get(ShipOrderByIncrementIdInterface::class);
        $this->shipmentRepository = $this->objectManager->get(ShipmentRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture SnowIO_ExtendedSalesRepositories::Test/Integration/_files/order.php
     * @dataProvider getStandardCaseTestData
     * @param array $items
     * @param array $assertions
     */
    public function testStandardCase(array $items, array $assertions)
    {
        $shipmentId = $this->shipOrderByIncrementId->execute("100000001", $items);
        foreach ($assertions as $assertion) {
            $assertion($items, $shipmentId);
        }
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

    public function getStandardCaseTestData()
    {
        return [
            "should ship an order using the increment id" => [
                [
                    $this->objectManager
                        ->create(ShipmentItemCreationInterface::class)
                        ->setOrderItemId(1)
                        ->setQty(1)
                ],
                [
                    $this->assertShipmentContainsCorrectItems()
                ]
            ]
        ];
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

    public function assertShipmentContainsCorrectItems()
    {
        return function (array $items, string $shipmentId) {
            /** @var ShipmentInterface $shipment */
            $shipment = $this->shipmentRepository->get($shipmentId);

            $shipmentItemByOrderItemId = [];
            foreach ($shipment->getItems() as $shipmentItem) {
                $shipmentItemByOrderItemId[$shipmentItem->getOrderItemId()] = [
                    'qty' => $shipmentItem->getQty(),
                ];
            }

            var_dump($shipmentItemByOrderItemId);

            $inputItemsBySku = [];
            foreach ($items as $item) {
                $inputItemsBySku[$item->getOrderItemId()] = [
                    'qty' => $item->getQty()
                ];
            }

            self::assertEquals($inputItemsBySku, $shipmentItemByOrderItemId);
        };
    }
}
