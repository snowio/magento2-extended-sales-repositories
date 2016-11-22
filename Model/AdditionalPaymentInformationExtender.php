<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionFactory;

class AdditionalPaymentInformationExtender
{
    private $paymentKeyValuePairFactory;
    private $orderPaymentExtensionFactory;

    public function __construct(
        AdditionalInformationFieldFactory $paymentKeyValuePairFactory,
        OrderPaymentExtensionFactory $orderPaymentExtensionFactory
    ) {
        $this->paymentKeyValuePairFactory = $paymentKeyValuePairFactory;
        $this->orderPaymentExtensionFactory = $orderPaymentExtensionFactory;
    }

    public function extendPayment(OrderPaymentInterface $payment)
    {
        $additionalInformation = $payment->getAdditionalInformation();
        
        if (!empty($additionalInformation)) {
            $fieldSet = AdditionalInformationField::createSet($additionalInformation);

            $paymentExtensionAttributes = $payment->getExtensionAttributes();
            if (null === $paymentExtensionAttributes) {
                $paymentExtensionAttributes = $this->orderPaymentExtensionFactory->create();
                $payment->setExtensionAttributes($paymentExtensionAttributes);
            }

            $paymentExtensionAttributes->setAdditionalInformation($fieldSet);
        }
    }
}
