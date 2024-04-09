# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


### Fixed
- Updates the shipping cost rate mappers to calculate inclusive taxes for shipping rates  (shippit/woocommerce#29)


## [v2.0.1]

### Fixed
- Fixed an issue whereby orders in a processing state would sync every hour if operating in legacy storage mode (shippit/woocommerce#27)


## [v2.0.0]

### Added
- Added support for WooCommerce v8
- Added support for High Performance Order Storage (HPOS) mode

### Changed
- BREAKING CHANGE: We now require WooCommerce v6.0+, and Wordpress v4.0+
- BREAKING CHANGE: The legacy shipping method option has been removed, with Shipping Methods now only available via Shipping Method Zones
- We now require least PHP v7.0, matching the minimum requirements specified by WooCommerce v6

### Fixed
- Fixed an issue where when updating Shippit Plugin Settings, the api credentials validation + webhook may not have always validated credentials + managed webhook registrations as expected
- Fixed an issue where disabling fulfillment sync would still allow for webhooks to be received on the webhook endpoint


## [v1.9.0]

### Added
- Added a depreciation notice for users using the "Shippit (Legacy)" shipping method, with details on using Shipping Zones
- Add CI linter coverage for PHP 7.2, 7.3, 7.4, 8.0 and 8.1

### Changed
- Removed the "filter by product" configuration option if this option is not already configured, resolving a performance issue for large stores


## [v1.8.1]

### Fixed
- Resolved an issue where incorrect version metadata was set on the release


## [v1.8.0]

### Added
- Updated tested upto tag to indicate support for WooCommerce 6.9.4


## [v1.7.2]

### Fixed
- Resolved an issue where orders containing a partial refund would not be marked as completed when goods are shipped


## [v1.7.1]

### Fixed
- Bumped internal version number in metadata to v1.7.1


## [v1.7.0]

### Added
- Support for capturing the courier tracking number when creating a shipment


## [v1.6.7]

### Changed
- Validated plugin is tested on Wordpress v6 and WooCommerce v6.8


## [v1.6.6]

### Fixed
- Resolved an issue whereby DB upgrades may throw an error if the configuration is empty / not set


## [v1.6.5]

### Fixed
- Resolved an issue whereby Bulk Sync actions may take a number of hours to run after they are scheduled


## [v1.6.4]

### Fixed
- Remove calls to depreciated woocommerce methods
- Re-add module resource assets to installation


## [v1.6.3]

### Changed
- Improve handling on `api_key` or `webhook` setting updates


## [v1.6.2]

### Changed
- Removed `state/region` as a required field for live quotes, ensuring live quotes are available in countries without states/regions.


## [v1.6.1]

### Changed
- We have validated this release for WooCommerce version v5.4, and Wordpress v5.7.2


## [v1.6.0]

### Changed
- We've updated the way we authenticate with the Shippit API - we'll now utilize header-based bearer authorization


## [v1.5.6]

### Fixed
- Resolved an issue whereby multiple instances of the same "Shipping Method" in the "Default Zone" could not be mapped using Shipping Method Mapping


## [v1.5.5]

### Added
- Added the ability to only sync orders mapped to a Shippit Service
- Added support for mapping shipping methods from the "Default Zone" in WooCommerce

### Changed
- We have improved the display of shipping methods in our Shipping Method Mapping configuration area to make it easier to identify shipping methods across zones
- We will now avoid making a Live Quote request to Shippit if required address details are missing

### Fixed
- Resolved an issue whereby an item's price details was sent to Shippit without GST, item prices will now include any applicable taxes when sent to Shippit
- Resolved an issue whereby manual orders may result the incorrect order may be marked as shipped in WooCommerce


## [v1.5.4]

### Added
- We have added the ability to capture the language and currency code of orders
- We have added the ability to capture a products `Country of Origin`, `Tariff Code` and `Dangerous Goods` Details
- We now capture the `Dutiable Amount` of an order during live quoting, this is based on the product's value in the cart, enabling Live Quotes to consider duties such as customs


## [v1.5.3]

### Added
- We now update your merchant account to indicate it's connected with a woocommerce store

### Changed
- We've adjusted the way we trigger validation of your Shippit API key when updating it's value in the backend settings



## [v1.5.2]

### Added
- Added the street address to live quote requests, which can now be utilised by on-demand delivery services
- Added dutiable amounts to live quote requests


## [v1.5.1]

### Fixed
- We've resolved an issue with an item's weight not being sent to Shippit


## [v1.5.0.1]

### Changed
- We've updated the range of Wordpress versions supported by this plugin


## [v1.5.0]

### Added
- We've improved the way we handle order data mappings
- We've added support across WooCommerce v2.6 - WooCommerce v3.6

### Fixed
- Resolved an issue whereby the incorrect shipping method may be selected when utilising live quotes


## [v1.4.7]

### Added
- We'll now include both the woocommerce order internal identifier, and the friendly order reference number when communicating orders and shipments

### Fixed
- Resolved an issue whereby an incorrect order could be marked as shipped if the order id was not provided in an expected format
- Improved support for earlier versions of WooCommerce when retrieving a order items product name


## [v1.4.6]

### Added
- Added support for WooCommerce v1.4.0
### Fixed
- Resolved an issue with Shipping Method Mapping for orders created using WooCommerce v1.4.0


## [v1.4.5]

### Added
- Added a feature flag that could disable the product filtering functionality on quotes, enabling larger stores to avoid a potentially expensive query
### Fixed
- Resolved an issue that could prevent shipments from being registered in php v7.0.x environments.


## [v1.4.4]

### Added
- Added a Shipments Meta box to the Orders Admin Area, with details as to the shipments completed by Shippit


## [v1.4.3]

### Added
- Added support for the WooCommerce table rates plugin with shipping method mapping functionality


## [v1.4.2]

### Added
- Added the ability to retrieve live quotes in the cart shipping estimator


## [v1.4.1]

### Added
- Added click and collect shipping method as an available shipping method mapping

### Changed
- Updated shipping method quotes to utilise the service level name as the shipping method identifier
- Removed references to international shipping method mapping services
—-- We now use service levels of standard, express and priority to indicate service levels for domestic + international services
—-- Removes the hard-allocation of all non-AU based orders to international, as we now use the service level names
- Renamed “premium” services to “priority”

### Fixed
- Cleanup of the shipping method mapping logic to an abstracted function that processes the relevant details and returns the api data required for the order to be sent to Shippit


## [v1.4.0]

### Added
- Adds the ability to setup shipping method mapping based on the individual zone methods
- Improved messaging if a sync failure occurs

### Fixed
- Resolve an issue whereby the wrong order could be send in some environments


## [v1.3.9]

### Added
- Adds the ability to send orders manually, via the orders listing page or when editing an order directly
- Adds a new configuration option for the "Authority To Leave" field in Checkout, allowing it to be disabled if required.

### Fixed
- Resolve an issue whereby orders could be sent to Shippit without any items in the order


## [v1.3.8]

### Fixed
- Resolved an issue whereby shipping method mapping may not map correctly when using Shipping Method Instances in WooCommerce v3


## [v1.3.7]

### Added
- Adds support for WooCommerce v3
  - Ensures variation products are loaded via WC_Product_Variation on fulfillments
  - Resolves minor PHP_NOTICE errors messages due to WooCommerce v3 changes on accessing order properties

### Fixed
- Resolves undefined index “default” message when loading shipping method settings


## [v1.3.6]

### Added
Allow for shipments of orders without SKU details to be accepted and processed by the plugin


## [v1.3.5]

- Bugfix - Ensure live quotes take into account the WooCommerce Taxation preferences


## [v1.3.4]

- add plugin syntax support for PHP 5.2 and 5.3


## [v1.3.3]

### Added
- Add feature flag to enable merchants to ignore item dimensions in quotes / orders
- To enable, add "define(`SHIPPIT_IGNORE_ITEM_DIMENSIONS`, true)" to wp-config.php


## [v1.3.2]

### Fixed
Fixes a bug affecting unsupported version of PHP (< PHP 5.4)


## [v1.3.1]

### Changed
Include the taxable amount for item prices sent to Shippit


## [v1.3.0]

### Added
- Add support for shipping zones
  - You can now use shipping live quotes within WooCommerce Shipping Zones
  - We've kept the old shipping method active, however we suggest updating your shipping method to utilise the new zones functionality, as this legacy method will be removed in a future release.

### Changed
- A new "Shippit" tab will now appear in WooCommerce for all Shippit core settings, shippit shipping method options will now only contain configuration options relating to live quoting functionality, with order sync and fulfillment sync options now shown in the "Shippit" tab


## [v1.2.13]

### Fixed
- Resolve an issue where if the jetpack module was present, but disabled, custom orders numbers logic was still being used - causing the fulfillment webhook to fail to locate the order for fulfillment.


## [v1.2.12]

### Added
- Add support for shipping orders that use custom order numbers in the WooCommerce Jetpack module


## [v1.2.11]

### Fixed
- Resolve an issue where an order may not be marked as shipped, due to differing order id and woocommerce order numbers


## [v1.2.10]

### Fixed
- Resolve an issue with the product height dimensions not being synced correctly via the api


## [v1.2.9]

### Changed
- API timeout updates

### Fixed
- Resolve an issue with product dimentions when syncing orders


## [v1.2.8]

### Fixed
- Resolve an issue retrieving the product width value


## [v1.2.7]

### Fixed
- Use the property "method_title" shipping method mappings, as used in new shipping methods as of WC 2.6.x


## [v1.2.5]

- Add functionality to enable merchants to add a margin to the quoted shipping prices (fixed or percentage).
- Ensure qty, price and weight details are sent to the api as floats


## [v1.2.3]
- Fix a bug in marking orders as shipped on some webhook requests
- Improve logging information on webhook activity
- Improve logging information on api response activity


## [v1.2.2]

- Update staging to use secure staging api endpoint


## [v1.2.1]

- Adds support for orders initially created in a processing state to be synced


## [v1.2.0]

- Enables international orders to be sent to Shippit
- Allow for shipping methods to be mapped to "international"
- Add item level details to the order sync data (name, qty, price, weight)
- Add item level receive logic to the webhook sync logic
  - Includes support for partial shipping and product variations


## [v1.1.13]

- Fix an issue whereby the settings form fields logic would load whenever the page being loaded involved the shippit shipping method, settings are now loaded only on the settings page
- Avoid a php error when filter by products is enabled, but there are no products in the filter


## [v1.1.12]

- Fix an issue whereby shipping method mappings would fail to load on some version of PHP (< PHP v5.6)
- Avoid php errors when no apiResponse is recieved


## [v1.1.10]

- Fix a bug in the plugin activation due to the core files not being available early on in module init


## [v1.1.9]

- Fix a bug where if the webhook registration api request failed, no notification was shown to the user


## [v1.1.8]

- Update api endpoint url for production to use HTTPs
- Update api endpoint for staging to use the shippit domain
- Add the company name to the order sync request data


## [v1.1.7]

- Adds functionality to enable other shipping methods to be utilised and synced with Shippit


## [v1.1.6]

- Updates the quotes and order sync api calls to use the individual item weights, rather than the total weight


## [v1.1.5]

- Updates the label of a standard quote to use "Standard" instead of "Couriers Please"


## [v1.1.4]

- Adds some additional checks on the API methods before attempting to return the response


## [v1.1.3]

- Resolves an issue with the logging system containing an undefined variable


## [v1.1.2]

- Resolves an issue where shipping address line 2 was not being captured


## [v1.0.0]

- Live quoting for Standard and Scheduled deliveries
- Shippit can be enabled to accept orders not requiring live quoting
- Product filtering for live quoting on individual products or specified attributes
