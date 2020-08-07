<?php
/**
 * Mamis.IT
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is available through the world-wide-web at this URL:
 * http://www.mamis.com.au/licencing
 *
 * @category   Mamis
 * @copyright  Copyright (c) 2016 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.mamis.com.au/licencing
 */

class Mamis_Shippit_Order
{
    private $api;
    private $s;
    private $helper;
    private $woocommerce;

    const CARRIER_CODE = 'mamis_shippit';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->api = new Mamis_Shippit_Api();
        $this->s = new Mamis_Shippit_Settings();
        $this->helper = new Mamis_Shippit_Helper();
        $this->woocommerce = $GLOBALS['woocommerce'];
    }

    /**
     * Remove a pending sync
     *
     * Called when an order moves out from "processing"
     * status to a hold status
     *
     * @param  int     $order_id    The Order Id
     * @return boolean              True or false
     */
    public function removePendingSync($orderId)
    {
        $order = new WC_Order($orderId);

        if (get_post_meta($orderId, '_mamis_shippit_sync', true) == 'false') {
            delete_post_meta($orderId, '_mamis_shippit_sync');
        }
    }

    /**
     * Add a pending sync
     *
     * @param  int     $orderId    The Order Id
     * @return boolean              True or false
     */
    public function addPendingSync($orderId)
    {
        $isEnabled = get_option('wc_settings_shippit_enabled');
        $autoSyncOrders = get_option('wc_settings_shippit_auto_sync_orders');

        if ($isEnabled != 'yes' || $autoSyncOrders == 'no') {
            return;
        }

        if (get_post_meta($orderId, '_mamis_shippit_sync', true) == 'true') {
            return;
        }

        // Get the orders_item_id meta with key shipping
        $order = new WC_Order($orderId);

        // Only add the order as pending when it's in a "processing" status
        if (!$order->has_status('processing')) {
            return;
        }

        // If we are only syncing shippit quoted orders, ensure it's a shippit quoted order
        if ($autoSyncOrders == 'all_shippit' && !$this->isShippitShippingMethod($order)) {
            return;
        }
        
        add_post_meta($orderId, '_mamis_shippit_sync', 'false', true);
        // attempt to sync the order now
        $this->syncOrder($orderId);
    }

    protected function isShippitShippingMethod($order)
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

    protected function getShippingMethodId($order)
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

            // Check if the shipping method chosen is Mamis_Shippit
            if (stripos($shippingMethodId, 'Mamis_Shippit') !== FALSE) {
                return $shippingMethodId;
            }

            // If we have anything after shipping_method:instance_id
            // then ignore it
            if (substr_count($shippingMethodId, ':') > 1) {
                $firstOccurence = strrpos($shippingMethodId, ':');
                $secondOccurence = strpos($shippingMethodId, ':', $firstOccurence);
                $shippingMethodId = substr($shippingMethodId, 0, $secondOccurence);
            }

            // Check if shipping method is mapped to standard
            if (!empty($standardShippingMethods)
                && in_array($shippingMethodId, $standardShippingMethods)) {
                return 'standard';
            }

            // Check if shipping method is mapped to express
            if (!empty($expressShippingMethods)
                && in_array($shippingMethodId, $expressShippingMethods)) {
                return 'express';
            }

            // Check if shipping method is mapped to click and collect
            if (!empty($clickandcollectShippingMethods)
                && in_array($shippingMethodId, $clickandcollectShippingMethods)) {
                return 'click_and_collect';
            }

            // Check if shipping method is mapped to plain label
            if (!empty($plainlabelShippingMethods)
                && in_array($shippingMethodId, $plainlabelShippingMethods)) {
                return 'plainlabel';
            }
        }

        return false;
    }

    /**
     * Sync all pending orders
     *
     * Adds _mamis_shippit_sync meta key value to all orders
     * that have been scheduled for sync with shippit
     * @return [type] [description]
     */
    public function syncOrders()
    {
        global $woocommerce;

        $orderPostArg = array(
            'post_status' => 'wc-processing',
            'post_type' => 'shop_order',
            'meta_query' => array(
                array(
                    'key' => '_mamis_shippit_sync',
                    'value' => 'false',
                    'compare' => '='
                )
            ),
        );

        // Get all woocommerce orders that are processing
        $orderPosts = get_posts($orderPostArg);

        foreach ($orderPosts as $orderPost) {
            $this->syncOrder($orderPost->ID);
        }
    }

    /**
     * Manual action - Send order to shippit
     *
     * @param  object $order order to be send
     */
    public function sendOrder($order)
    {
        if (version_compare($this->woocommerce->version, '2.6.0', '<=')) {
            $orderId = $order->id;
        }
        else {
            $orderId = $order->get_id();
        }

        update_post_meta($orderId, '_mamis_shippit_sync', 'false');

        // attempt to sync the order now
        $this->syncOrder($orderId);
    }

    /**
     * Manual action - Send bulk orders to shippit
     *
     * @param  string $redirectTo return url
     * @param  string $action selected bulk order action
     */
    public function sendBulkOrders($redirectTo, $action, $orderIds)
    {
        // only process when the action is a shippit bulk-ordders action
        if ($action != 'shippit_bulk_orders_action') {
            return $redirectTo;
        }

        foreach ($orderIds as $orderId) {
            // Mark Shippit sync as false as for this manual action
            // we want to schedule orders for sync even if synced already
            update_post_meta($orderId, '_mamis_shippit_sync', 'false');
        }

        // Create the schedule for the orders to sync
        wp_schedule_single_event(current_time('timestamp'), 'syncOrders');

        return add_query_arg(array('shippit_sync' => '2'), $redirectTo);
    }

    public function syncOrder($orderId)
    {
        // Get the orders_item_id meta with key shipping
        $order = new WC_Order($orderId);
        $orderItems = $order->get_items();

        // $shippingMethodId = $this->getShippingMethodId($order);

        // if WooCommerce version is equal or less than 2.6 then use
        // different data mapper for it
        if (version_compare($this->woocommerce->version, '2.6.0', '<=')) {
            $orderData = (new Mamis_Shippit_Data_Mapper_Order_V26())->__invoke($order)->toArray();
        }
        else {
            $orderData = (new Mamis_Shippit_Data_Mapper_Order())->__invoke($order)->toArray();
        }

        // If there are no order items, return early
        if (count($orderItems) == 0) {
            update_post_meta($orderId, '_mamis_shippit_sync', 'true', 'false');

            return;
        }

        // Send the API request
        $apiResponse = $this->api->sendOrder($orderData);

        if ($apiResponse && $apiResponse->tracking_number) {
            update_post_meta($orderId, '_mamis_shippit_sync', 'true', 'false');

            $orderComment = 'Order Synced with Shippit. Tracking number: ' . $apiResponse->tracking_number . '.';
            $order->add_order_note($orderComment, 0);
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

            $order->add_order_note($orderComment, 0);
        }
    }
}
