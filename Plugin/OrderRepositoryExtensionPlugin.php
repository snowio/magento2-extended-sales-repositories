<?php
namespace SnowIO\ExtendedSalesRepositories\Plugin;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use SnowIO\ExtendedSalesRepositories\Model\AdditionalPaymentInformationExtender;

class OrderRepositoryExtensionPlugin
{
    private $additionPaymentInformationExtender;

    public function __construct(AdditionalPaymentInformationExtender $additionalPaymentInformationExtender)
    {
        $this->additionPaymentInformationExtender = $additionalPaymentInformationExtender;
    }

    public function afterGet(OrderRepositoryInterface $orderRepository, OrderInterface $order)
    {
        if ($payment = $order->getPayment()) {
            $this->additionPaymentInformationExtender->extendPayment($payment);
        }

        return $order;
    }
}
