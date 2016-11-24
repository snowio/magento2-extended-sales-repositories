<?php

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

class ShipmentRepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $objectManager;
    /** @var OrderRepositoryInterface */
    private $orderRepository;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture SnowIO/ExtendedSalesRepository/Test/Integration/_files/order.php
     */
    public function testStandardCase()
    {
        $order = $this->orderRepository->get(1);

        $payment = $order->getPayment();
        $paymentInfoBlock = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get('Magento\Payment\Helper\Data')
            ->getInfoBlock($payment);
        $payment->setBlockMock($paymentInfoBlock);

        /** @var ShipmentInterface $shipment */
        $shipment = $this->objectManager->create(ShipmentInterface::class);
        $shipment->setOrderId($order->getEntityId());
        $shipmentItem = $this->objectManager->create(ShipmentItemInterface::class);
        $shipmentItem->setOrderItem($order->getItems()[0]);
        $shipment->addItem($shipmentItem);
        $shipment->setPackages([['1'], ['2']]);
        $shipment->setShipmentStatus(\Magento\Sales\Model\Order\Shipment::STATUS_NEW);
        $shipment->save();
    }

}