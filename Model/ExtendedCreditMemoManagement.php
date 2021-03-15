<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * Class ExtendedCreditMemoManagement
 *
 * @package SnowIO\ExtendedSalesRepositories\Model
 */
class ExtendedCreditMemoManagement
{
    /**
     * Apply Amounts accordingly based on input credit memo
     * @param CreditmemoInterface $creditmemo
     * @param CreditmemoInterface $inputCreditmemo
     * @author Alexander Wanyoike <aw@amp.co>
     */
    public static function applyAmounts(CreditmemoInterface $creditmemo, CreditmemoInterface $inputCreditmemo)
    {
        $creditmemo
            ->setShippingAmount($inputCreditmemo->getShippingAmount())
            ->setBaseSubtotal($inputCreditmemo->getBaseSubtotal())
            ->setBaseGrandTotal($inputCreditmemo->getBaseGrandTotal())
            ->setGrandTotal($inputCreditmemo->getGrandTotal());
    }

    /**
     * Apply tax calculations from the input
     * @param CreditmemoInterface $creditmemo
     * @param CreditmemoInterface $inputCreditmemo
     * @author Alexander Wanyoike <aw@amp.co>
     */
    public static function applyTax(CreditmemoInterface $creditmemo, CreditmemoInterface $inputCreditmemo)
    {
        $creditmemo->setBaseTaxAmount($inputCreditmemo->getBaseTaxAmount())
            ->setTaxAmount($inputCreditmemo->getTaxAmount())
            ->setBaseShippingTaxAmount($inputCreditmemo->getBaseShippingTaxAmount())
            ->setShippingTaxAmount($inputCreditmemo->getShippingTaxAmount())
            ->setShippingInclTax($inputCreditmemo->getShippingInclTax())
            ->setBaseSubtotalInclTax($inputCreditmemo->getBaseSubtotalInclTax());
    }
}
