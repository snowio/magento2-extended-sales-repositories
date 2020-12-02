<?php

namespace SnowIO\ExtendedSalesRepositories\Api\Data;

/**
 * Interface OrderRelatedDataInterface
 * @package SnowIO\ExtendedSalesRepositories\Api\Data
 */

interface OrderRelatedDataInterface
{
    public const ID = 'id';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const CODE = 'code';
    public const VALUE = 'value';

    /**
     * @return string
     */
    public function getOrderIncrementId();

    /**
     * @param string $orderIncrementId
     *
     * @return mixed
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * @return string
     */
    public function getCode();

    /**
     * @param string $code
     *
     * @return mixed
     */
    public function setCode($code);

    /**
     * @return string
     */
    public function getValue();

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function setValue($value);

}