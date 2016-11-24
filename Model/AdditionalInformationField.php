<?php
namespace SnowIO\ExtendedSalesRepositories\Model;

use SnowIO\ExtendedSalesRepositories\Api\Data\AdditionalInformationFieldInterface;

class AdditionalInformationField implements AdditionalInformationFieldInterface
{
    private $name;
    private $value;

    /**
     * @param array $associativeArray
     * @return AdditionalInformationField[]
     */
    public static function createSet(array $associativeArray)
    {
        $set = [];

        foreach ($associativeArray as $fieldName => $value) {
            $convertedValue = self::convertValue($value);
            $set[] = self::createField($fieldName, $convertedValue);
        }

        return $set;
    }

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

    private static function createField($name, $value)
    {
        $field = new static;
        $field->setName($name);
        $field->setValue($value);

        return $field;
    }

    private static function convertValue($value)
    {
        if (is_array($value)) {
            if (self::arrayIsAssociative($value)) {
                return self::createSet($value);
            }

            return array_map(['self', 'convertValue'], $value);
        }

        return $value;
    }

    private static function arrayIsAssociative(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}
