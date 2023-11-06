# Integration Settings

The Shippit WooCommerce Plugin settings can be accessed within a WooCommerce Environment by navigating to **WooCommerce → Settings** and Selecting the **“Shippit” tab** at the top of the UI

## General Settings

General Settings provides settings that will apply across all functionality offered by the plugin.

| Setting | Default Value | Description |
| --- | --- | --- |
| `Enabled` | `No` | Determines if the Shippit plugin is currently active and will perform sync and quoting functionality. |
| `API Key` | _Empty_ | The API Key credential used by the plugin when communicating with the Shippit API.
| `Environment` | `Live` | Determines which instance of Shippit the plugin operates with. <br><br> When set to `Live`, the plugin operates with `https://app.shippit.com`. <br> When set to `Sandbox`, the plugin operates with `https://app.staging.shippit.com` |
| `Debug Mode` | `No` | A configuration flag that enables verbose debug logging for any actions completed by the plugin.<br>- By default, logging is only captured by the plugin for log entries of severity “error” or higher.<br>- If “Debug Mode” is set to yes — all log event severities are captured.<br><br>All log files generated are accessible in **WooCommerce → Status → Logs** |

## Cart & Checkout Options

The Cart and Checkout Options section covers configuration optiosn related to items displayed to customers in the WooCommerce Cart and Checkout frontend.

| Setting | Default Value | Description |
| --- | --- | --- |
| `Display Authority to Leave` | `Yes` | Determines whether a checkbox is displayed in the WooCommerce Checkout frontend, allowing customers to indicate if they wish to authorize the package to be delivered without a person present at the delivery address. |

## Order Sync Settings

The Order Sync Settings area relates to configuration options regarding syncing behaviour between WooCommerce and Shippit.

| Setting | Default Value | Description |
| --- | --- | --- |
| `Auto-Sync New Orders` | `Yes` | Determines if and which orders must be synced to Shippit.<br> - When set to **`No`**, no orders are synced to Shippit automatically.<br>- When set to **`Yes - Auto-sync all new orders`**, all orders in a state of "Processing" are synced to Shippit automatically.<br>- When set to **`Yes - Auto-sync only orders with Shippit Shipping Methods”`** only Shippit Live Quoted orders are synced automatically. |

## Shipping Options

Enables merchants to configure external shipping options and how they will be routed to Shippit.
If a merchant is only using live quotes, this configuration is not required.
This configuration is intended to be used when a merchant offers their own shipping rates at checkout (ie: free express shipping), allowing them to map and configure the external shipping method to be associated with a Shippit Service Level

| Setting | Default Value | Description |
| --- | --- | --- |
| `Standard Shipping Methods` | _Empty_ | Determines the third party shipping methods (ie: Shipping Methods not provided by Shippit) that should be associated with a “Standard Service Level” within Shippit. |
| `Express Shipping Methods` | _Empty_ | Determines the third party shipping methods (ie: Shipping Methods not provided by Shippit) that should be associated with a “Express Service Level” within Shippit. |
| `Click & Collect Shipping Methods` | _Empty_ | Determines the third party shipping methods (ie: Shipping Methods not provided by Shippit) that should be associated with a "Click & Collect Service Level” within Shippit. |
| `Plain Label Shipping Methods` | _Empty_ | Determines the third party shipping methods (ie: Shipping Methods not provided by Shippit) that should be associated with a “Plain Label Service Level” within Shippit. |

## Item Sync Settings

The Item Sync Settings section relates to configuration options available regarding the syncing behavior of item details sent to Shippit in an order.

| Setting | Default Value | Description |
| --- | --- | --- |
| `Tariff Code Attribute` | _Empty_ | Displays a list of WooCommerce product attributes that can be used to source information about a product’s tariff code details.<br><br> Values within the attribute are expected to be a valid internationally recognised tariff code. |
| `Origin Country Code Attribute` | _Empty_ | Displays a list of WooCommerce product attributes that can be used to source information about a product’s origin country code details.<br><br> Values within the attribute are expected to be a valid ISO country code. |
| `Dangerous Goods Code Attribute` | _Empty_ | Displays a list of WooCommerce product attributes that can be used to source information about a product’s dangerous goods details.<br><br> Values within the attribute are expected to be a valid dangerous goods code supported by Shippit |
| `Dangerous Goods Text Attribute` | _Empty_ | Displays a list of WooCommerce product attributes that can be used to source information about a product’s dangerous goods details.<br><br> Values within the attribute are expected to be a valid dangerous goods description supported by Shippit |

## Fulfillment Settings

Enables merchants to configure the fulfillment sync behaviour offered by the Shippit WooCommerce Plugin.

| Setting | Default Value | Description |
| --- | --- | --- |
| `Enabled` | `Yes` | When enabled, orders that have been “booked” within Shippit will be communicated via a webhook with the WooCommerce store.<br><br> The Shippit Plugin will update the order details with a confirmation of the tracking details, items shipped, and a transition of the order to the “completed” status once all items have been booked for delivery.
| `Tracking Reference` | `Shippit Tracking Reference` | The Tracking Reference Number to be captured when updating the order with shipment confirmation.<br><br>Available options include…<ul><li>Shippit Tracking Reference (default)<ul><li>When selected, the shippit tracking reference is captured and stored with the shipment confirmation.</li></ul></li><li>Courier Tracking Reference<ul><li>When selected, the shippit + courier tracking references are captured on stored with the shipment confirmation.</li></ul></li></ul> |
