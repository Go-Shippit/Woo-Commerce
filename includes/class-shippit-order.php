<?php

/**
 * Mamis - https://www.mamis.com.au
 * Copyright © Mamis 2023-present. All rights reserved.
 * See https://www.mamis.com.au/license
 */

class Mamis_Shippit_Order
{
    /**
     * @var Mamis_Shippit_Api
     */
    private $api;

    /**
     * Initialise the class
     */
    public function __construct()
    {
        $this->api = new Mamis_Shippit_Api();
    }

    /**
     * Add the order to be sycned with Shippit
     *
     * @param integer $orderId
     * @return void
     */
    public function addPendingSync(int $orderId)
    {
        $isEnabled = get_option('wc_settings_shippit_enabled');
        $autoSyncOrders = get_option('wc_settings_shippit_auto_sync_orders');

        // If the plugin is disabled, or auto-sync is disabled, return early
        if ($isEnabled != 'yes' || $autoSyncOrders == 'no') {
            return;
        }

        $order = new WC_Order($orderId);

        // If the order has already synced, return early
        if ($order->get_meta('_mamis_shippit_sync') === 'true') {
            return;
        }

        // Only add the order as pending when it's in a "processing" status
        if (!$order->has_status('processing')) {
            return;
        }

        // If we are only syncing shippit quoted orders, ensure it's a shippit quoted order
        if ($autoSyncOrders == 'all_shippit' && !$this->isShippitShippingMethod($order)) {
            return;
        }

        $order->update_meta_data('_mamis_shippit_sync', 'false');
        $order->save_meta_data();

        // attempt to sync the order now
        $this->syncOrder($orderId);
    }

    /**
     * Removes the order from a pending sync with Shippit
     * if the order has not already been synced
     *
     * @param int $orderId
     * @return void
     */
    public function removePendingSync(int $orderId)
    {
        $order = new WC_Order($orderId);

        if (
            $order->meta_exists('_mamis_shippit_sync')
            && $order->get_meta('_mamis_shippit_sync') === 'false'
        ) {
            $order->delete_meta_data('_mamis_shippit_sync');
            $order->save_meta_data();
        }
    }

    /**
     * Sync orders that are..
     * - In a status of processing
     * - Are pending sync with Shippit
     *
     * @return void
     */
    public function syncOrders()
    {
        $orderIds = $this->isOrderStorageHpos()
            ? $this->getPendingOrderIdsHpos()
            : $this->getPendingOrderIdsLegacy();

        foreach ($orderIds as $orderId) {
            $this->syncOrder($orderId);
        }
    }

    /**
     * Determines if order currently active order storage mode is HPOS
     *
     * @return boolean
     */
    protected function isOrderStorageHpos(): bool
    {
        return (
            class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
        );
    }

    /**
     * Retrieve the orders pending sync with Shippit
     * using the high performance order storage query
     *
     * @return array<int>
     */
    protected function getPendingOrderIdsHpos()
    {
        return wc_get_orders(
            [
                'status' => ['wc-processing'],
                'type' => 'shop_order',
                'meta_query' => [
                    [
                        'key' => '_mamis_shippit_sync',
                        'value' => 'false',
                        'compare' => '=',
                    ]
                ],
                'return' => 'ids'
            ]
        );
    }

    /**
     * Retrieve the orders pending sync with Shippit
     * using the legacy order storage query
     *
     * @return array<int>
     */
    protected function getPendingOrderIdsLegacy()
    {
        return get_posts(
            [
                'post_status' => 'wc-processing',
                'post_type' => 'shop_order',
                'meta_query' => [
                    [
                        'key' => '_mamis_shippit_sync',
                        'value' => 'false',
                        'compare' => '='
                    ]
                ],
                'fields' => 'ids',
            ]
        );
    }

    /**
     * Send an order to Shippit
     * - Typically triggered via a manual user action
     *
     * @param WC_Order $order
     * @return void
     */
    public function sendOrder(WC_Order $order)
    {
        $order->update_meta_data('_mamis_shippit_sync', 'false');
        $order->save_meta_data();

        $orderId = $order->get_id();

        // attempt to sync the order now
        $this->syncOrder($orderId);
    }

    /**
     * Send one or more orders to Shippit
     * - Typically triggered via a manual user action
     *
     * @param string $redirectTo
     * @param string $action
     * @param array $orderIds
     * @return void
     */
    public function sendOrders(string $redirectTo, string $action, array $orderIds)
    {
        // only process when the action is a shippit bulk-orders action
        if ($action != 'shippit_bulk_orders_action') {
            return $redirectTo;
        }

        foreach ($orderIds as $orderId) {
            $order = new WC_Order($orderId);
            $order->update_meta_data('_mamis_shippit_sync', 'false');
            $order->save_meta_data();
        }

        // Create the schedule for the orders to sync
        wp_schedule_single_event(
            current_time('timestamp', true),
            'syncOrders'
        );

        return add_query_arg(
            [
                'shippit_sync' => '2'
            ],
            $redirectTo
        );
    }

    /**
     * Sync the order with Shippit
     *
     * @param integer $orderId
     * @return void
     */
    public function syncOrder(int $orderId)
    {
        // Get the orders_item_id meta with key shipping
        $order = new WC_Order($orderId);
        $orderItems = $order->get_items();

        // If there are no order items, return early
        if (count($orderItems) === 0) {
            $order->update_meta_data('_mamis_shippit_sync', 'true');
            $order->save_meta_data();

            return;
        }

        $orderData = (new Mamis_Shippit_Data_Mapper_Order())
            ->__invoke($order);

        // Send the API request
        $apiResponse = $this->api->createOrder(
            $orderData->toArray()
        );

        if ($apiResponse && $apiResponse->tracking_number) {
            $order->update_meta_data('_mamis_shippit_sync', 'true');
            $order->save_meta_data();

            $orderComment = 'Order Synced with Shippit. Tracking number: ' . $apiResponse->tracking_number . '.';
            $order->add_order_note($orderComment);
        }
        else {
            $orderComment = 'Order Failed to sync with Shippit.';

            if ($apiResponse && isset($apiResponse->messages)) {
                $messages = $apiResponse->messages;

                foreach ($messages as $field => $message) {
                    $orderComment .= sprintf(
                        '%c%s - %s',
                        10, // ASCII Code for NewLine
                        $field,
                        implode(', ', $message)
                    );
                }
            }
            elseif ($apiResponse && isset($apiResponse->error) && isset($apiResponse->error_description)) {
                $orderComment .= sprintf(
                    '%c%s - %s',
                    10, // ASCII Code for NewLine
                    $apiResponse->error,
                    $apiResponse->error_description
                );
            }

            $order->add_order_note($orderComment);
        }
    }

    /**
     * Determines if the order is assigned to the Shippit Live Quote shipping method
     *
     * @param WC_Order $order
     * @return boolean
     */
    protected function isShippitShippingMethod(WC_Order $order): bool
    {
        $shippingMethods = $order->get_shipping_methods();
        $standardShippingMethods = get_option('wc_settings_shippit_standard_shipping_methods');
        $expressShippingMethods = get_option('wc_settings_shippit_express_shipping_methods');
        $clickandcollectShippingMethods = get_option('wc_settings_shippit_clickandcollect_shipping_methods');
        $plainlabelShippingMethods = get_option('wc_settings_shippit_plainlabel_shipping_methods');

        foreach ($shippingMethods as $shippingMethod) {
            // Since Woocommerce v3.4.0, the instance_id is saved in a seperate property of the shipping method
            // To add support for v3.4.0, we'll append the instance_id, as this is how we store a mapping in Shippit
            if (isset($shippingMethod['instance_id']) && !empty($shippingMethod['instance_id'])) {
                $shippingMethodId = sprintf(
                    '%s:%s',
                    $shippingMethod['method_id'],
                    $shippingMethod['instance_id']
                );
            }
            else {
                $shippingMethodId = $shippingMethod['method_id'];
            }

            if (!empty($standardShippingMethods)
                && in_array($shippingMethodId, $standardShippingMethods)) {
                return true;
            }

            if (!empty($expressShippingMethods)
                && in_array($shippingMethodId, $expressShippingMethods)) {
                return true;
            }

            if (!empty($clickandcollectShippingMethods)
                && in_array($shippingMethodId, $clickandcollectShippingMethods)) {
                return true;
            }

            if (!empty($plainlabelShippingMethods)
                && in_array($shippingMethodId, $plainlabelShippingMethods)) {
                return true;
            }

            // Check if the shipping method chosen is a shippit method
            if (stripos($shippingMethod['method_id'], 'Mamis_Shippit') !== FALSE) {
                return true;
            }
        }

        return false;
    }
}
