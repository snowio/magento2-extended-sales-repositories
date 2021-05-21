<?php

namespace SnowIO\ExtendedSalesRepositories\Test\Integration\Plugin;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\TestFramework\Helper\Bootstrap;

class ShipmentRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $objectManager;
    /** @var OrderFactory */
    private $orderFactory;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderFactory = $this->objectManager->get(OrderFactory::class);
    }

    /**
     * @magentoDataFixture SnowIO_ExtendedSalesRepositories::Test/Integration/_files/order.php
     */
    public function testStandardCase()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $this->assertEquals('100000001', $order->getIncrementId(), 'The order was not loaded');
        $payment = $order->getPayment();
        $paymentInfoBlock = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get('Magento\Payment\Helper\Data')
            ->getInfoBlock($payment);
        $payment->setBlockMock($paymentInfoBlock);

        /** @var ShipmentInterface $shipment */
        $shipment = $this->objectManager->create(ShipmentInterface::class);
        $shipment->setOrderId($order->getEntityId());
        foreach ($order->getItems() as $orderItem) {
            $shipmentItem = $this->objectManager->create(ShipmentItemInterface::class);
            $shipmentItem->setOrderItem($orderItem);
            $shipment->addItem($shipmentItem);
        }
        $shipment->setPackages([['1'], ['2']]);
        $shipment->setShipmentStatus(\Magento\Sales\Model\Order\Shipment::STATUS_NEW);
        $shipment->save();
    }
}
