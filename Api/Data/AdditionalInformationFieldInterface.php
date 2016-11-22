<?php
namespace SnowIO\ExtendedSalesRepositories\Api\Data;

interface AdditionalInformationFieldInterface
{
    /**
     * Get field name
     *
     * @return string
     */
    public function getName();

    /**
     * Set field name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get field value
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Set field value
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value);
}
