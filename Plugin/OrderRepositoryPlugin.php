<?php
namespace SnowIO\ExtendedSalesRepositories\Plugin;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionFactory;

class OrderRepositoryPlugin
{
    private $orderPaymentExtensionFactory;


    public function __construct(OrderPaymentExtensionFactory $orderPaymentExtensionFactory)
    {
        $this->orderPaymentExtensionFactory = $orderPaymentExtensionFactory;
    }

    /**
     * Unset additional information to avoid web services being broken by nested array
     * and add the additional information as a json encoded string to an extension attribute.
     */
    public function afterGet(OrderRepositoryInterface $orderRepository, OrderInterface $order)
    {
        $payment = $order->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $additionalInformationJson = json_encode($additionalInformation);

        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->orderPaymentExtensionFactory->create();
        }
        $extensionAttributes->setAdditionalInformationJson($additionalInformationJson);
        $payment->setExtensionAttributes($extensionAttributes);

        if (method_exists($payment, 'unsAdditionalInformation')) {
            $payment->unsAdditionalInformation();
        }

        return $order;

    }
}