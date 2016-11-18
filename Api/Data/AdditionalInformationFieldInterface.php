<?php
namespace SnowIO\ExtendedSalesRepositories\Api\Data;

interface AdditionalInformationFieldInterface
{
    /**
     * Get object name
     *
     * @return string
     */
    public function getName();

    /**
     * Set object name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get object value
     *
     * @return string
     */
    public function getValue();

    /**
     * Set object value
     *
     * @param string $value
     * @return $this
     */
    public function setValue($value);
}
