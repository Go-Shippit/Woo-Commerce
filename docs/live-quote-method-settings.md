# Live Quote Method Settings

The Shippit WooCommerce plugin offers merchants the ability to display real-time shipping costs at the checkout, based on items that are in the customers cart.

When the Shippit Live Quote shipping method is setup in WooCommerce, the following configuration options are available for the Shipping Method.

| Setting | Default Value | Description |
| --- | --- | --- |
| `Title` | `Shippit` | Determines the title of the Shipping method displayed in the UI. | `Shippit` |
| `Allowed Methods` | `Standard`, `Express`, `Priority` | Determines the service levels customers will see rates for at the checkout. |
| `Maximum Timeslots` | `No Max Timeslots` | Determines the maximum number of Priority delivery slots that will be shown to customers at checkout. |
| `Product Attributes` |  `No` | Allows merchants to configure live quotes to only be offered based on certain product details. |
| `Attribute Code` | _Empty_ | The product attribute that will determine if a product can be used for live quoting. |
| `Attribute Value` | _Empty_ | The product attribute value that will determine if a product can be used for live quoting. |
| `Margin` | `No` | Allows merchants to configure margin to apply over the Shippit Live Quoted rates. | When set to `Yes - Percentage` a percentage value based on the value set as the `Margin Amount` is added to the Shipping Rates. <br> When set to `Yes - Fixed Dollar Amount` a fixed value set as the `Margin Amount` is added to the Shipping Rates. |
| `Margin Amount` | _Empty_ | The amount of margin to apply to the Shippit Live Quoted rates at the checkout. |

> Note: _Shippit must be added as an option in a WooCommerce Shipping Zone for the plugin to be able to provide real-time shipping costs._
