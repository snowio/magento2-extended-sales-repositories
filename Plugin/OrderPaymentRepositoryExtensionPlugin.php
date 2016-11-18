<?php
namespace SnowIO\ExtendedSalesRepositories\Plugin;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use SnowIO\ExtendedSalesRepositories\Model\AdditionalPaymentInformationExtender;

class OrderPaymentRepositoryExtensionPlugin
{
    private $additionalPaymentInformationExtender;

    public function __construct(AdditionalPaymentInformationExtender $additionalPaymentInformationExtender)
    {
        $this->additionalPaymentInformationExtender = $additionalPaymentInformationExtender;
    }

    public function afterGet(
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderPaymentInterface $orderPayment
    ) {
        $this->additionalPaymentInformationExtender->extendPayment($orderPayment);
        return $orderPayment;
    }
}
