<?php

namespace SnowIO\ExtendedSalesRepositories\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Search;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface;
use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataSearchResultsInterface;
use SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataSearchResultsInterfaceFactory;
use SnowIO\ExtendedSalesRepositories\Api\OrderRelatedDataRepositoryInterface;
use SnowIO\ExtendedSalesRepositories\Model\ResourceModel\OrderRelatedData\Collection;

class OrderRelatedDataRepository implements OrderRelatedDataRepositoryInterface
{
    protected \SnowIO\ExtendedSalesRepositories\Model\ResourceModel\OrderRelatedData $resource;

    protected \SnowIO\ExtendedSalesRepositories\Model\OrderRelatedDataFactory $factory;

    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;

    private \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataSearchResultsInterfaceFactory $searchResultsFactory;

    public function __construct(
        OrderRelatedDataFactory $factory,
        ResourceModel\OrderRelatedData $resource,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRelatedDataSearchResultsInterfaceFactory $searchResultsFactory
    )
    {
        $this->factory = $factory;
        $this->resource = $resource;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface $orderRelatedData
     * @return \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface
     * @throws CouldNotSaveException
     */
    public function save(OrderRelatedDataInterface $orderRelatedData)
    {
        try {
            $savedRelatedData = $this->getByOrderIncrementIdAndCode(
                $orderRelatedData->getOrderIncrementId(),
                $orderRelatedData->getCode()
            );

            if($savedRelatedData instanceof OrderRelatedDataInterface){
                $savedRelatedData->setValue($orderRelatedData->getValue());
                $this->resource->save($savedRelatedData);
                return $savedRelatedData;
            }

            $this->resource->save($orderRelatedData);

            return $orderRelatedData;

        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()), $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var OrderRelatedDataSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();

        $searchResults->setSearchCriteria($searchCriteria);
        /** @var ResourceModel\OrderRelatedData\Collection $collection */
        $collection = $this->factory->create()->getCollection();

        // Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $field = $sortOrder->getField();
                $collection->addOrder(
                    $field,
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }

    /**
     * Retrieve Order Related Data by Order Increment ID and Code
     *
     * @param string $orderIncrementId
     * @param string $code
     * @return \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByOrderIncrementIdAndCode($orderIncrementId, $code)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderRelatedDataInterface::ORDER_INCREMENT_ID, $orderIncrementId, 'eq')
            ->addFilter(OrderRelatedDataInterface::CODE, trim($code), 'eq')
            ->create();

        $result = $this->getList($searchCriteria)->getItems();

        return current($result);
    }

    /**
     * Retrieve All Order Related Data by Order Increment ID
     *
     * @param string $orderIncrementId
     * @return \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllByIncrementId($orderIncrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderRelatedDataInterface::ORDER_INCREMENT_ID, $orderIncrementId, 'eq')
            ->create();

        return $this->getList($searchCriteria)->getItems();
    }

    /**
     * Delete by Order Related Data
     *
     * @param \SnowIO\ExtendedSalesRepositories\Api\Data\OrderRelatedDataInterface $orderRelatedData
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(OrderRelatedDataInterface $orderRelatedData)
    {
        try {
            $this->resource->delete($orderRelatedData);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()), $exception);
        }
        return true;
    }

    /**
     * Delete by Order Increment Id and Code
     *
     * @param string $orderIncrementId
     * @param string $code
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByOrderIncrementIdAndCode($orderIncrementId, $code)
    {
        $orderRelatedData = $this->getByOrderIncrementIdAndCode($orderIncrementId, $code);

        if($orderRelatedData instanceof OrderRelatedDataInterface){
            return $this->delete($orderRelatedData);
        }

        throw new NoSuchEntityException(__('Object not found'));
    }

    /**
     * @param Search\FilterGroup $filterGroup
     * @param ResourceModel\OrderRelatedData\Collection $collection
     */
    protected function addFilterGroupToCollection(Search\FilterGroup $filterGroup, Collection $collection)
    {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType();
            $fields[] = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }
        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }
}