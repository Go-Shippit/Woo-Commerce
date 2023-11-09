=== Shippit for WooCommerce ===
Contributors: shippit, matthewmuscat
Donate link: NA
Tags: shipping, australia post, couriers please, aramex
Requires at least: 4.0.0
Tested up to: 6.4.1
Stable tag: stable
Requires PHP: 7.0
License: Shippit Commercial Licence
License URI: https://www.shippit.com/terms-of-service


== Description ==

## Multi-carrier shipping technology.

Seamlessly integrated with WooCommerce, our app gives you fast access to multiple carriers, and takes care of shipping for your stores, locations and brands.

It’s mission-critical software, complete with the fulfilment automation and shipping analytics your business needs to save time and money when it comes to shipping.

Together with our intuitive tracking notifications and in-house delivery support, we help you share better post-purchase experiences that scale with your business.

- Offer live quotes for multiple delivery options at check-out.
- Discounted shipping rates with domestic and international carriers.
- One-click label printing, picklists and pack slips to fulfil orders fast.
- Smart carrier allocation and insights to keep shipping costs under control.
- Automated tracking notifications and customisable, branded tracking.


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

Go to www.shippit.com and sign up for an account. You'll find your API Key in the "Settings -> Integrations" area of your account.


== Screenshots ==

1. Discounted shipping rates with domestic and international carriers.
2. One-click label printing, picklists and pack slips to fulfil orders fast.
3. Customisable, branded tracking.
4. Automated tracking notifications.


== Changelog ==

= 2.0.0 =

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


== Upgrade Notice ==

= 2.0.0 =
Added support for WooCommerce v8 + High Performance Order Storage
