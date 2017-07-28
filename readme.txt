=== Plugin Name ===
Contributors: williamonshippit
Donate link: NA
Tags: shipping, australia post, couriers please, fastway, shipping method,
Requires at least: 3.0.0
Tested up to: 4.7.0
Stable tag: stable
License: GPLv2 or later


== Description ==

Shippit is a shipping platform that connects WooCommerce customers with a network of carriers. Retailers don't have time to waste on shipping stuff. Plug in to Shippit and forget all about negotiating rates, finding the best carriers or spending hours on the phone chasing couriers. Book, Print and Ship.

* Manage multiple delivery services easily with one account and bill
* Never leave your store again with daily pickup on all deliveries
* Keep customers happy with FREE email and SMS notifications

We've negotiated rates with the best carriers so you don't have to. No account keeping fees, no credit checks, no lock-in contracts

* National Satchels from $5.99 (ex. GST)
* National Same / Next Day from $7.99 (ex. GST)
* Metro 3-Hour Timeslot Delivery from $7.30 (ex.GST)

Automatic labelling and tracking is just the beginning. Shippit's focus on customer satisfaction will change the way you ship forever.

* Print labels, despatch and track deliveries in a jiffy with our expert-designed workflow system
* Plug in to quality delivery services with our Approved Carriers
* Keep customers happy with Shippit's unique Proactive Tracking and Notification System that is proven to reduce missed delivery rates by up to 50%!

Whether you ship from a warehouse, a store or both, we've got you covered.

* Ship from store using Shippit Send a Package with saved location support.
* Multiple user and location support enables simplified drop-shipping.

== Installation ==

You can install this plugin directly from your WordPress dashboard:

1. Navigate to Plugin section of WooCommerce admin
2. Click "Add New" next to the Plugins title
3. Upload `woocommerce-shippit.zip'
4. Navigate to WooCommerce > Settings > Shipping > Shippit
5. Select "Enable = Yes" from drop down
6. Enter API Key and saving settings

Get your API key at www.shippit.com

== Frequently Asked Questions ==

= How do I get an API key? =

Go to www.shippit.com and sign up for an account. We will email you an API key

== Screenshots ==

1. See all your orders and live courier pricing in real time as customers check out
2. You can send a package at any time with live quoting
3. You and your customers can track their deliveries simply, elegantly and easily

== Changelog ==

= 1.3.7 =

- New Features
-- Adds support for WooCommerce v3
—-- Ensures variation products are loaded via WC_Product_Variation on fulfillments
—-- resolves minor PHP_NOTICE errors messages due to WooCommerce v3 changes on accessing order properties

- Bugfixes
-- Resolves undefined index “default” message when loading shipping method settings

= 1.3.6 =

- Feature - Allow for shipments of orders without SKU details to be accepted and processed by the plugin

= 1.3.5 =

- Bugfix - Ensure live quotes take into account the WooCommerce Taxation preferences

= 1.3.4 =

- add plugin syntax support for PHP 5.2 and 5.3

= 1.3.3 =

- Feature - Add feature flag to enable merchants to ignore item dimensions in quotes / orders
-- To enable, add "define(`SHIPPIT_IGNORE_ITEM_DIMENSIONS`, true)" to wp-config.php

= 1.3.2 =

- Bugfix - Fixes a bug affecting unsupported version of PHP (< PHP 5.4)

= 1.3.1 =

- Change - Include the taxable amount for item prices sent to Shippit

= 1.3.0 =

- Feature - Add support for shipping zones - you can now use shipping live quotes within WooCommerce Shipping Zones - we've kept the old shipping method active, however we suggest updating your shipping method to utilise the new zones functionality, as this legacy method will be removed in a future release.
- Change - A new "Shippit" tab will now appear in WooCommerce for all Shippit core settings, shippit shipping method options will now only contain configuration options relating to live quoting functionality, with order sync and fulfillment sync options now shown in the "Shippit" tab

= 1.2.13 =

- Bugfix - Resolve an issue where if the jetpack module was present, but disabled, custom orders numbers logic was still being used - causing the fulfillment webhook to fail to locate the order for fulfillment.

= 1.2.12 =

- Feature - Add support for shipping orders that use custom order numbers in the WooCommerce Jetpack module

= 1.2.11 =

- Bugfix - Resolve an issue where an order may not be marked as shipped, due to differing order id and woocommerce order numbers

= 1.2.10 =

- Bugfix - Resolve an issue with the product height dimensions not being synced correctly via the api

= 1.2.9 =

- Change - API timeout updates
- Bugfix - Resolve an issue with product dimentions when syncing orders

= 1.2.8 =

* Bugfix - Resolve an issue retrieving the product width value

= 1.2.7 =

* Bugfix - Use the property "method_title" shipping method mappings, as used in new shipping methods as of WC 2.6.x

= 1.2.5 =

* Add functionality to enable merchants to add a margin to the quoted shipping prices (fixed or percentage).
* Ensure qty, price and weight details are sent to the api as floats

= 1.2.3 =

* Fix a bug in marking orders as shipped on some webhook requests
* Improve logging information on webhook activity
* Improve logging information on api response activity

= 1.2.2 =

* Update staging to use secure staging api endpoint

= 1.2.1 =

* Adds support for orders initially created in a processing state to be synced

= 1.2.0 =

* Enables international orders to be sent to Shippit
* Allow for shipping methods to be mapped to "international"
* Add item level details to the order sync data (name, qty, price, weight)
* Add item level receive logic to the webhook sync logic
** Includes support for partial shipping and product variations

= 1.1.13 =

* Fix an issue whereby the settings form fields logic would load whenever the page being loaded involved the shippit shipping method, settings are now loaded only on the settings page
* Avoid a php error when filter by products is enabled, but there are no products in the filter

= 1.1.12 =

* Fix an issue whereby shipping method mappings would fail to load on some version of PHP (< PHP v5.6)
* Avoid php errors when no apiResponse is recieved

= 1.1.10 =
* Fix a bug in the plugin activation due to the core files not being available early on in module init

= 1.1.9 =
* Fix a bug where if the webhook registration api request failed, no notification was shown to the user

= 1.1.8 =
* Update api endpoint url for production to use HTTPs
* Update api endpoint for staging to use the shippit domain
* Add the company name to the order sync request data

= 1.1.7 =
* Adds functionality to enable other shipping methods to be utilised and synced with Shippit

= 1.1.6 =
* Updates the quotes and order sync api calls to use the individual item weights, rather than the total weight

= 1.1.5 =
* Updates the label of a standard quote to use "Standard" instead of "Couriers Please"

= 1.1.4 =
* Adds some additional checks on the API methods before attempting to return the response

= 1.1.3 =
* Resolves an issue with the logging system containing an undefined variable

= 1.1.2 =
* Resolves an issue where shipping address line 2 was not being captured

= 1.0.0 =
* Live quoting for Standard and Scheduled deliveries
* Shippit can be enabled to accept orders not requiring live quoting
* Product filtering for live quoting on individual products or specified attributes

== Upgrade Notice ==

= 1.0.0 =
First iteration
