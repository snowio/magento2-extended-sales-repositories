<?php

namespace SnowIO\ExtendedSalesRepositories\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface;

/**
 * Class OrderRelatedDataRepositoryInterface
 *
 * @api
 */
interface OrderRelatedDataRepositoryInterface
{
    /**
     * Save Order Related Data
     *
     * @param \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface $orderRelatedData
     * @return \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface
     */
    public function save(OrderRelatedDataInterface $orderRelatedData);

    /**
     * Retrieve list of order related data matching given criteria
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Retrieve Order Related Data by Order Increment ID and Code
     *
     * @param string $orderIncrementId
     * @param string $code
     * @return \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByOrderIncrementIdAndCode($orderIncrementId, $code);

    /**
     * @param $orderIncrementId
     * @return \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface
     */
    public function getAllByIncrementId($orderIncrementId);

    /**
     * Delete Order Related Data
     *
     * @param \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface $orderRelatedData
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(OrderRelatedDataInterface $orderRelatedData);
}