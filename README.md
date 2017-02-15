#Magento 2 Extended Sales Repository
##Description
This module adds the following features
* Exposes additional payment information as a json string within `extension_attributes` in the `OrderPaymentInterface`. In order to bypass `DataObjectProcessor::buildOutputDataArray` that removes keys from the payment information associative array (see issue [5659](https://github.com/magento/magento2/issues/5659)).
* Adds an endpoint that can ship orders by **increment id**.
* Plugin that saves shipment with its corresponding order identifier based on the increment id that was specified in the `ShipmentInterface` `order_increment_id` extension attribute. 

## Prerequisites
* PHP 5.6 or newer
* Composer  (https://getcomposer.org/download/).
* `magento/framework` 100.1.2 or newer
* `magento/module-sales` 100.1.2 or newer

## Installation
```
composer require snowio/magento2-extended-sales-repositories
php bin/magento module:enable SnowIO_ExtendedSalesRepositories
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

## Usage
### ShipOrderByIncrementId
* Method: `POST`
* Endpoint Path URI : `/V1/order/:orderIncrementId/ship-by-increment-id`
* `:orderIncrementId` : The increment id of the order.

Note that this endpoint is similar to `/V1/order/:orderId/ship` but instead of specifying an `:orderId`
we specify an `:orderIncrementId`. Please note that you can use the same message body as `/V1/order/:orderId/ship`
please refer to the [salesShipOrderV1ExecutePost section](http://devdocs.magento.com/swagger/#!/salesShipOrderV1/salesShipOrderV1ExecutePost)
of the Magento 2 API for more information about the request body.

###`order_increment_id` extension attribute added to `ShipmentInterface`:
This is used by the `ShipmentRepositoryExtensionPlugin` to populate the `orderId` of the shipment before it is saved.

### Addition information json (`additional_information_json`)
This field is located in the extension attributes for the `OrderPaymentInterface`.
The field is a json encoded string that contains payment additional information. The field is used in order to bypass the issue mentioned in the description (See [5659](https://github.com/magento/magento2/issues/5659)).

## License
This software is licensed under the MIT License. [View the license](LICENSE)