<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * Class ApplyAdjustments
 *
 * @package SnowIO\ExtendedSalesRepositories\Model
 */
class ApplyAdjustments
{
    /**
     * @param CreditmemoInterface $newCreditMemo
     * @param CreditmemoInterface $oldCreditMemo
     * @author Alex Wanyoike <aw@amp.co>
     */
    public function execute(CreditmemoInterface $newCreditMemo, CreditmemoInterface $oldCreditMemo): void
    {
        $newCreditMemo->setAdjustment($oldCreditMemo->getBaseAdjustment())
            ->setAdjustmentPositive($oldCreditMemo->getBaseAdjustmentPositive())
            ->setAdjustmentNegative($oldCreditMemo->getBaseAdjustmentNegative());
    }
}