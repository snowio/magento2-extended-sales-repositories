<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use SnowIO\ExtendedSalesRepositories\Api\Data\AdditionalInformationFieldInterface;

class AdditionalInformationField implements AdditionalInformationFieldInterface
{
    private $name;
    private $value;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
