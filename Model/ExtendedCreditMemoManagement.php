<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;

class ExtendedCreditMemoManagement
{
    /**
     * Never return the shipping amount
     * @param CreditmemoInterface $creditmemo
     * @author Alexander Wanyoike <aw@amp.co>
     */
    public static function resetAmounts(CreditmemoInterface $creditmemo)
    {
        $creditmemo
            ->setBaseShippingAmount(0)
            ->setShippingAmount(0)
            ->setBaseSubtotal(0)
            ->setBaseGrandTotal(0)
            ->setGrandTotal(0);
    }

    /**
     * Reset tax calculations
     * @param CreditmemoInterface $creditmemo
     * @author Alexander Wanyoike <aw@amp.co>
     */
    public static function resetTax(CreditmemoInterface $creditmemo)
    {
        $creditmemo->setBaseTaxAmount(0)
            ->setTaxAmount(0)
            ->setBaseShippingTaxAmount(0)
            ->setShippingTaxAmount(0)
            ->setBaseShippingInclTax(0)
            ->setShippingInclTax(0)
            ->setBaseSubtotalInclTax(0);
    }
}