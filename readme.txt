=== Plugin Name ===
Contributors: williamonshippit
Donate link: NA
Tags: shipping, australia post, couriers please, fastway, shipping method,
Requires at least: 3.0.0
Tested up to: 4.4.2
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