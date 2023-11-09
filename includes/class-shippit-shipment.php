<?php

/**
 * Mamis - https://www.mamis.com.au
 * Copyright © Mamis 2023-present. All rights reserved.
 * See https://www.mamis.com.au/license
 */

class Mamis_Shippit_Shipment
{
    /**
     * Response Messages
     */
    const ERROR_API_KEY_MISSING = 'An API Key is required';
    const ERROR_API_KEY_MISMATCH = 'The API Key provided does not match the configured API Key';
    const ERROR_BAD_REQUEST = 'An invalid request was recieved';
    const ERROR_ORDER_MISSING = 'The order requested was not found or has a status that is not available for shipping';
    const NOTICE_SHIPMENT_STATUS = 'Ignoring the order status update, as we only respond to ready_for_pickup state';
    const SUCCESS_SHIPMENT_CREATED = 'The shipment record was created successfully.';
    const SUCCESS_SHIPMENT_SKIPPED = 'Ignoring the webhook request — fulfillment sync is turned off.';

    protected $log;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->log = new Mamis_Shippit_Log(['area' => 'shipment']);
    }

    /**
     * Handle the request to the Shippit Shipment Endpoint
     *
     * @return void
     */
    public function handle()
    {
        global $wp;

        // Check the request API key is present and matches the configured API key
        $this->checkApiKey();

        $this->checkIfFulfillmentSyncIsEnabled();

        // Get the JSON data posted
        $requestData = json_decode(file_get_contents('php://input'));

        // Validate the request is valid and in a state that can be used to update shipping details
        $this->checkRequest($requestData);

        $order = $this->getOrder($requestData);

        // Ensure we have a valid order object
        $this->checkOrder($order);

        // Update the order with the shipment details
        $this->updateOrder($order, $requestData);

        // Return a response to the actions completed
        wp_send_json_success(
            array(
                'message' => self::SUCCESS_SHIPMENT_CREATED
            ),
            202
        );
    }

    /**
     * Retrieve the WooCommerce Order, based on the request data payload provided
     *
     * @param object $requestData
     * @return WC_Order|void
     */
    public function getOrder($requestData)
    {
        $retailerOrderNumber = $this->getRequestRetailerOrderNumber($requestData);
        $retailerReference = $this->getRequestRetailerReference($requestData);

        // If a retailer_reference is available and numeric, do a direct lookup via the database identifier
        // Note: Numeric check is required as WooCommerce will ignore queries filters for non-numeric identifiers
        if (!empty($retailerReference) && is_numeric($retailerReference)) {
            $order = $this->getOrderByReference($retailerReference);
        }
        // Otherwise, attempt to locate the order using the friendly reference number
        else {
            // If the WordPress JetPack module is installed + enabled, lookup using this module's order metadata
            if (class_exists('WCJ_Order_Numbers') && get_option('wcj_order_numbers_enabled') == 'yes') {
                $order = $this->getOrderByReferenceJetpack($retailerOrderNumber);
            }
            // Otherwise, attempt the lookup using the standard wordpress lookup methods
            else {
                $order = $this->getOrderByReference($retailerOrderNumber);
            }
        }

        if (empty($order)) {
            wp_send_json_error(array(
                'message' => self::ERROR_ORDER_MISSING
            ));

            return;
        }

        return $order;
    }

    /**
     * Get the order object, querying for the order
     * using it's reference number
     *
     * @param string $orderReference
     * @return WC_Order|bool
     */
    public function getOrderByReference($orderReference)
    {
        return wc_get_order($orderReference);
    }

    /**
     * Get the WooCommerce order, querying for the order
     * using JetPack Metadata and considering it's
     * configuration options
     *
     * @param string $orderReference
     * @return WC_Order|void
     */
    public function getOrderByReferenceJetpack($orderReference)
    {
        if (empty($orderReference)) {
            return;
        }

        // Add support for Wordpress Jetpack - Order Numbers
        $orderPrefix = get_option('wcj_order_number_prefix');
        $wcjSequentialEnabled = get_option('wcj_order_number_sequential_enabled');

        // If an order prefix is configured, remove it from the order number to be used for lookup
        if (!empty($orderPrefix)) {
            $orderReference = str_replace($orderPrefix, '', $orderReference);
        }

        if ($wcjSequentialEnabled == 'yes') {
            $orders = wc_get_orders(
                [
                    'type' => 'shop_order',
                    'status' => ['wc-processing'],
                    'meta_query' => [
                        [
                            'key' => '_wcj_order_number',
                            'value' => $orderReference,
                            'compare' => '=',
                        ]
                    ],
                    'limit' => 1,
                ]
            );

            $order = reset($orders);
        }
        else {
            $order = wc_get_order($orderReference);
        }

        // If no results are found, return early
        if (empty($order)) {
            return;
        }

        return $order;
    }

    /**
     * Update the order with the Shipment Details provided to the endpoint
     *
     * @param WC_Order $order
     * @param Object $requestData
     * @return void
     */
    public function updateOrder($order, $requestData)
    {
        // Grab item details from order
        $orderItems = $order->get_items();

        // Grab item details from request data
        $requestItems = $requestData->products;

        // Temproary storage of items shipped and shippable
        $orderItemsData = array();
        $totalItemsShippable = 0;

        // Create new array that holds the products in the order with required data
        foreach ($orderItems as $orderItem) {
            // SKU not stored in get_items so need to create new WC_Product
            // If item is a variation use variation_id in product call
            if ($orderItem['variation_id']) {
                $product = new WC_Product_Variation($orderItem['variation_id']);
            }
            else {
                $product = new WC_Product($orderItem['product_id']);
            }

            $orderItemsData[] = array (
                'name' => $orderItem['name'],
                'sku' => $product->get_sku(),
                'qty' => $orderItem['qty'],
                'variation_id' => $orderItem['variation_id']
            );

            // Count how many total items have been ordered
            $totalItemsShippable += $orderItem['qty'];
        }

        // Remove any refunded items from shippable count
        $totalItemsShippable += $order->get_total_qty_refunded();

        $this->log->info(
            'Order items marked as shipped',
            [
                'order_id' => $order->get_id(),
                'order_items' => $orderItemsData,
                'total_items_shippable' => $totalItemsShippable
            ]
        );

        // Check for count of items that have been shipped
        if ($order->meta_exists('_mamis_shippit_shipped_items')) {
            $totalItemsShipped = $order->get_meta('_mamis_shippit_shipped_items', true);
        }
        // If no items have been shipped previously set count to 0
        else {
            $order->add_meta_data('_mamis_shippit_shipped_items', 0, true);
            $totalItemsShipped = 0;
        }

        // Add order comment for when items are shipped
        $orderComment = 'The following items have been marked as Shipped in Shippit..<br>';
        $orderItemsShipped = array();

        foreach ($requestItems as $requestItem) {
            // skip requests for quantities not present or less than or equal to 0
            if (!property_exists($requestItem, 'quantity') || $requestItem->quantity <= 0) {
                continue;
            }

            $skuData = null;
            $productSku = (isset($requestItem->sku) ? $requestItem->sku : null);
            $productVariationId = null;

            if (strpos($productSku, '|') !== false) {
                $skuData = explode('|', $productSku);
                $productVariationId = end($skuData);

                // remove the variation id
                array_pop($skuData);

                $productSku = implode('|', $skuData);
            }

            // If we have product sku data, attempt to match based
            // on the product sku + variation id, or product sku
            if (!empty($productSku)) {
                foreach ($orderItemsData as $orderItemData) {
                    if (
                        // If the product is a variation, match sku and variation_id
                        (!is_null($productVariationId)
                            && $productSku == $orderItemData['sku']
                            && $productVariationId == $orderItemData['variation_id'])
                        // Otherwise, match by the sku only
                        || $productSku == $orderItemData['sku']
                    ) {
                        $orderComment .= $requestItem->quantity . 'x of ' . $requestItem->title . '<br>';
                        $totalItemsShipped += $requestItem->quantity;
                        $orderItemsShipped[] = array(
                            'sku' => $requestItem->sku,
                            'quantity' => $requestItem->quantity,
                            'title' => $requestItem->title
                        );
                    }
                }
            }
            // Otherwise, don't attempt matching to order items
            // Use the requestItem qty to determine the
            // number of items that were shipped
            else {
                $orderComment .= $requestItem->quantity . 'x of ' . (isset($requestItem->title) ? $requestItem->title : 'Unknown Item') . '<br>';
                $totalItemsShipped += $requestItem->quantity;
                $orderItemsShipped[] = array(
                    'quantity' => $requestItem->quantity
                );
            }
        }

        if ($totalItemsShipped == 0) {
            wp_send_json_error(array(
                'message' => self::ERROR_BAD_REQUEST
            ));
        }

        $this->log->info(
            'Order items marked as shipped',
            [
                'order_id' => $order->get_id(),
                'order_items' => $orderItemsData,
                'order_items_shipped' => $orderItemsShipped,
                'total_items_shipped' => $totalItemsShipped
            ]
        );

        // Update Order Shipments Metadata
        $this->updateShipmentMetadata($order, $requestData, $totalItemsShipped);

        // Add order comment for shipped items
        $order->add_order_note($orderComment, 0);

        // If all items have been shipped, change the order status to completed
        if ($totalItemsShipped >= $totalItemsShippable) {
            $order->update_status('completed', 'Order has been shipped with Shippit');

            add_action(
                'woocommerce_order_status_completed_notification',
                'action_woocommerce_order_status_completed_notification',
                10,
                2
            );
        }

        // Update the total of all items shipped
        $order->update_meta_data('_mamis_shippit_shipped_items', $totalItemsShipped);
        $order->save_meta_data();
    }

    /**
     * Add shipment information for the order or update the
     * shipment information if some data already exists
     *
     * @param  WC_Order $order              The woocommerce order
     * @param  object $requestData          The webhook request data
     * @param  object $totalItemsShipped    The webhook request data
     * @return mixed                        The response from update_post_meta,
     *                                      or null if request data is empty
     */
    public function updateShipmentMetadata($order, $requestData, $totalItemsShipped)
    {
        $statusHistory = $requestData->status_history;

        $status = array_filter($statusHistory, function($statusItem) {
            return ($statusItem->status == 'ready_for_pickup');
        });

        $readyForPickUp = reset($status);

        $shipmentData['tracking_number'] = $requestData->tracking_number;
        $shipmentData['tracking_url'] = $requestData->tracking_url;
        $shipmentData['courier_name'] = $requestData->courier_name;

        if (get_option('wc_settings_shippit_fulfillment_tracking_reference') == 'courier_tracking_reference') {
            $shipmentData['courier_tracking_number'] = $requestData->courier_job_id;
        }

        if (!empty($readyForPickUp)) {
            $shipmentData['booked_at'] = date("d-m-Y H:i:s", strtotime($readyForPickUp->time));
        }

        $shipments = array();

        if ($order->meta_exists('_mamis_shippit_shipment')) {
            $shipments[] = $order->get_meta_data('_mamis_shippit_shipment', true);
        }

        // Append the new shipment data
        $shipments[] = $shipmentData;

        // Update the total of all items shipped
        $order->update_meta_data('_mamis_shippit_shipped_items', $totalItemsShipped);

        // Update the order shipment metadata
        $order->update_meta_data('_mamis_shippit_shipment', $shipments);
    }

    /**
     * Check the api key provided on the request matches the configured api key for the store
     *
     * @return void
     */
    public function checkApiKey()
    {
        global $wp;

        // Get the configured api key
        $apiKey = get_option('wc_settings_shippit_api_key');

        // Grab the posted shippit API key
        $requestApiKey = $wp->query_vars['shippit_api_key'];

        // ensure an api key has been retrieved in the request
        if (empty($requestApiKey)) {
            wp_send_json_error(array(
                'message' => self::ERROR_API_KEY_MISSING
            ));
        }

        // check that the request api key matches the stored api key
        if ($apiKey != $requestApiKey) {
            wp_send_json_error(array(
                'message' => self::ERROR_API_KEY_MISMATCH
            ));
        }
    }

    /**
     * Ensures to reject shipment request if the shipment sync is turned off
     *
     * @param object $requestData
     * @return string|void
     */
    public function checkIfFulfillmentSyncIsEnabled()
    {
        if (
            get_option('wc_settings_shippit_enabled') != 'yes'
            || get_option('wc_settings_shippit_fulfillment_enabled') != 'yes'
        ) {
            // Return a response to the actions completed
            wp_send_json_success(
                array(
                    'message' => self::SUCCESS_SHIPMENT_SKIPPED
                ),
                200
            );
        }
    }

    public function getRequestRetailerOrderNumber($requestData)
    {
        if (isset($requestData->retailer_order_number)) {
            return $requestData->retailer_order_number;
        }
    }

    /**
     * Retrieve the retailer_reference from the request
     *
     * @param object $requestData
     * @return string|void
     */
    public function getRequestRetailerReference($requestData)
    {
        if (isset($requestData->retailer_reference)) {
            return $requestData->retailer_reference;
        }
    }

    /**
     * Retrieve the current state of the order shipment update
     *
     * @param object $requestData
     * @return string|void
     */
    public function getRequestCurrentState($requestData)
    {
        if (isset($requestData->current_state)) {
            return $requestData->current_state;
        }
    }

    /**
     * Checks the request data is valid and in a state that can be worked with
     *
     * @param object $requestData
     * @return void
     */
    public function checkRequest($requestData)
    {
        global $wp;

        // Grab the posted shippit API key
        $requestApiKey = $wp->query_vars['shippit_api_key'];

        $this->log->debug(
            'Webhook Request Received',
            [
                'url' => get_site_url() . '/shippit/shipment_create',
                'request' => $requestData,
            ]
        );

        // Check if the request has a body of data
        if (empty($requestData)) {
            wp_send_json_error(array(
                'message' => self::ERROR_BAD_REQUEST
            ));
        }

        $retailerOrderNumber = $this->getRequestRetailerOrderNumber($requestData);
        $retailerReference = $this->getRequestRetailerReference($requestData);
        $currentState = $this->getRequestCurrentState($requestData);

        // Ensure the request had an order number / order id that we can update against
        if (empty($retailerOrderNumber) && empty($retailerReference)) {
            wp_send_json_error(array(
                'message' => self::ERROR_ORDER_MISSING
            ));
        }

        // Ensure the order update notification is a state that we wish to update against
        if (empty($currentState) || $currentState != 'ready_for_pickup') {
            wp_send_json_success(array(
                'message' => self::NOTICE_SHIPMENT_STATUS
            ));
        }
    }

    /**
     * Check the order is a valid object for shipping
     *
     * @param WC_Order|void $order
     * @return void
     */
    public function checkOrder($order)
    {
        // Check if an order is returned
        if (!$order) {
            wp_send_json_error(array(
                'message' => self::ERROR_ORDER_MISSING
            ));
        }
    }
}
