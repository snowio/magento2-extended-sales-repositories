<?php

namespace SnowIO\ExtendedSalesRepositories\Api;

/**
 * Class CreditmemoByOrderIncrementIdInterface
 *
 * @api
 */
interface CreditmemoByOrderIncrementIdInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Creates new Creditmemo and Refund it for given Order.
     *
     * @param string $orderIncrementId
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     */
    public function createAndRefund(
        $orderIncrementId,
        \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
    );
}
