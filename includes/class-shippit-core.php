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

class Mamis_Shippit_Core
{
    public $id = 'mamis_shippit';

    // The shipping methods
    protected static $shipping_methods;

    /**
     * Webhook Error Messages.
     */
    const ERROR_API_KEY_MISSING = 'An API Key is required';
    const ERROR_API_KEY_MISMATCH = 'The API Key provided does not match the configured API Key';
    const ERROR_BAD_REQUEST = 'An invalid request was recieved';
    const ERROR_ORDER_MISSING = 'The order id requested was not found or has a status that is not available for shipping';
    const NOTICE_SHIPMENT_STATUS = 'Ignoring the order status update, as we only respond to ready_for_pickup state';
    const ERROR_SHIPMENT_FAILED = 'The shipment record was not able to be created at this time, please try again.';
    const SUCCESS_SHIPMENT_CREATED = 'The shipment record was created successfully.';

    /**
     * Instace of Mamis Shippit
     */
    private static $instance;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        // Check if WooCommerce is active
        if ( !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
            if (!is_plugin_active_for_network('woocommerce/woocommerce.php')) {
                return;
            }
        }

        $this->s = new Mamis_Shippit_Settings();
        $this->log = new Mamis_Shippit_Log();

        $this->init();
    }

    /**
     * Instance.
     *
     * An global instance of the class. Used to retrieve the instance
     * to use on other files/plugins/themes.
     *
     * @since 1.0.0
     *
     * @return object Instance of the class.
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin parts.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('syncOrders', array($this, 'syncOrders'));

        // *****************
        // Order Sync
        // *****************

        $order = new Mamis_Shippit_Order;
        // If a new order is recieved, add pending sync
        add_action('woocommerce_checkout_update_order_meta', array($order, 'addPendingSync'));
        // If a order transitions into "processing", add pending sync
        add_action('woocommerce_order_status_processing', array($order, 'addPendingSync'));
        // If the order transition into "on-hold", remove pending sync
        add_action('woocommerce_order_status_on-hold', array($order, 'removePendingSync'));

        // *****************
        // Authority To Leave
        // *****************

        // Add authority to leave field to checkout
        add_action('woocommerce_after_order_notes', array($this, 'add_authority_to_leave'));

        // Update the order meta with authority to leave value
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_authority_to_leave_order_meta'));

        // Display the authority to leave on the orders edit page
        add_action('woocommerce_admin_order_data_after_shipping_address', 'authority_to_leave_display_admin_order_meta', 10, 1);

        function authority_to_leave_display_admin_order_meta($order)
        {
            if (version_compare(WC()->version, '3.0.0') >= 0) {
                $orderId = $order->get_id();
            }
            else {
                $orderId = $order->id;
            }

            echo '<p><strong>'.__('Authority to leave').':</strong> ' . get_post_meta( $orderId, 'authority_to_leave', true ) . '</p>';
        }

        // Add the shippit settings tab functionality
        add_action('woocommerce_settings_tabs_shippit_settings_tab', 'Mamis_Shippit_Settings::addFields');
        add_action('woocommerce_update_options_shippit_settings_tab', 'Mamis_Shippit_Settings::updateSettings');

        // Validate the api key when the setting is changed
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'after_options_save'));
        add_action('woocommerce_update_options_shippit_settings_tab', array($this, 'after_options_save'));


        //**********************/
        // Webhook functionality
        //**********************/

        // create filter to get $_GET['shippit_api_key']
        add_filter('query_vars', array($this, 'add_query_vars'), 0);

        // handle API request if 'shippit_api_key' is set
        add_action('parse_request', array($this, 'handle_request'), 0);

        // create 'shippit/shipment_create' endpoint
        add_action('init', array($this, 'add_endpoint'), 0);

        // Add Send to Shippit order action
        add_action('woocommerce_order_actions', array($this, 'shippit_add_order_meta_box_action') );

        // Process Shippit send order action
        add_action('woocommerce_order_action_shippit_order_action', array($order, 'sendOrder') );

        // Add Bulk Send to Shippit orders action
        add_action('bulk_actions-edit-shop_order', array($this, 'shippit_send_bulk_orders_action'), 20, 1);

        // Process Shippit bulk orders send action
        add_action('handle_bulk_actions-edit-shop_order', array($order, 'sendBulkOrders'), 10, 3 );

        add_action('admin_notices', array($this, 'order_sync_notice') );

        if (get_option('wc_settings_shippit_shippingcalculator_city_enabled') == 'yes') {
            // Enable suburb/city field for Shipping calculator
            add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');
        }

        // Add the shipment meta boxes when viewing an order.
        add_action('add_meta_boxes_shop_order', array($this, 'mamis_add_shipment_meta_box'));
    }

    /**
     * Add the Shippit Shipment Meta Box
     */
    function mamis_add_shipment_meta_box()
    {
        $orderId = get_the_ID();
        $shipmentData = get_post_meta($orderId, '_mamis_shippit_shipment', true);

        if (empty($shipmentData)) {
            return;
        }

        add_meta_box(
            'mamis_shipment_fields',
            __('Shipments - Powered by Shippit', 'woocommerce-shippit'),
            array(
                $this,
                'mamis_add_shipment_meta_box_content'
            ),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render the Shippit Shipment Meta Box Content
     */
    function mamis_add_shipment_meta_box_content($order)
    {
        $shipmentData = get_post_meta($order->ID, '_mamis_shippit_shipment', true);
        $count = count($shipmentData);
        $shipmentDetails = '';
        $i = 1;

        foreach ($shipmentData as $shipment) {
            // Render the Courier Name
            if (!empty($shipment['courier_name'])) {
                $shipmentDetails .= '<strong>Courier:</strong>&nbsp;';
                $shipmentDetails .= '<span>' .$shipment['courier_name']. '</span>';
                $shipmentDetails .= '<br/>';
            }

            // Render the Courier Job ID
            if (!empty($shipment['booked_at'])) {
                $shipmentDetails .= '<strong>Booked At:</strong>&nbsp;';
                $shipmentDetails .= '<span>' .$shipment['booked_at']. '</span>';
                $shipmentDetails .= '<br/>';
            }

            // Render the Shippit Tracking Link
            if (!empty($shipment['tracking_number'])) {
                $shipmentDetails .= '<strong>Shippit Track #:</strong>&nbsp;';
                $shipmentDetails .= '<a target="_blank" href="'. $shipment['tracking_url']. '">';
                $shipmentDetails .= $shipment['tracking_number'];
                $shipmentDetails .= '</a><br/>';
            }

            if ($i < $count) {
                $shipmentDetails .= '<hr/>';
            }

            $i++;
        }

        echo $shipmentDetails;
    }

    /**
     * Add a custom action to order actions select box
     *
     * @param  array $actions order actions array to display
     * @return array updated actions
     */
    public function shippit_add_order_meta_box_action($actions)
    {
        // add "Send to Shippit" custom order action
        $actions['shippit_order_action'] = __('Send to Shippit');

        return $actions;
    }

    /**
     * Add a custom bulk order action to order actions select box on orders list page
     *
     * @param  array $actions order actions array to display
     * @return array updated actions
     */
    public function shippit_send_bulk_orders_action($actions)
    {
        // add "Send to Shippit" bulk action on the orders listing page
        $actions['shippit_bulk_orders_action'] = __('Send to Shippit');

        return $actions;
    }

    public function order_sync_notice()
    {
        if (!isset($_GET['shippit_sync'])) {
            return;
        }

        echo '<div class="updated notice is-dismissable">'
                . '<p>Orders have been scheduled to sync with Shippit - they will be synced shortly.</p>'
            . '</div>';
    }

    public function add_endpoint()
    {
        add_rewrite_rule('^shippit/shipment_create/?([0-9]+)?/?', 'index.php?shippit_api_key=$matches[1],', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'shippit_api_key';
        return $vars;
    }

    // Validate api key, validate order id and validate the state
    public function handle_request()
    {
        global $wp;

        if (isset($wp->query_vars['shippit_api_key'])) {
            $this->create_shipment();
            exit;
        }
    }

    public function create_shipment()
    {
        global $wp;

        // Get the configured api key
        $apiKey = get_option('wc_settings_shippit_api_key');

        // Grab the posted shippit API key
        $requestApiKey = $wp->query_vars['shippit_api_key'];

        // Get the JSON data posted
        $requestData = json_decode(file_get_contents('php://input'));

        $this->log->add(
            'SHIPPIT - WEBHOOK REQUEST',
            'Webhook Request Received',
            array(
                'url' => get_site_url() . '/shippit/shipment_create?shippit_api_key=' . $requestApiKey,
                'requestData' => $requestData
            )
        );

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

        if (empty($requestData)) {
            wp_send_json_error(array(
                'message' => self::ERROR_BAD_REQUEST
            ));
        }

        // Grab the values from the posted JSON object
        $orderNumber = $requestData->retailer_order_number;
        $orderStatus = $requestData->current_state;

        if (empty($orderNumber)) {
            wp_send_json_error(array(
                'message' => self::ERROR_ORDER_MISSING
            ));
        }

        if (empty($orderStatus) || $orderStatus != 'ready_for_pickup') {
            wp_send_json_success(array(
                'message' => self::NOTICE_SHIPMENT_STATUS
            ));
        }

        // Get the order by the request order id passed,
        // ensure it's status is processing
        $order = $this->get_order($orderNumber, 'wc-processing');

        // Check if an order is returned
        if (!$order) {
            wp_send_json_error(array(
                'message' => self::ERROR_ORDER_MISSING
            ));
        }

        $orderId = $order->ID;
        $wcOrder = wc_get_order($orderId);

        // Don't update status unless all items are shipped

        // Grab item details from order
        $orderItems = $wcOrder->get_items();
        $orderItemsData = array();

        // Grab item details from request data
        $requestItems = $requestData->products;

        // Store how many items are shippable
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
        $totalItemsShippable -= $wcOrder->get_total_qty_refunded();

        $this->log->add(
            'SHIPPIT - WEBHOOK REQUEST',
            'Order Contents',
            array(
                'orderItems' => $orderItemsData,
                'totalItemsShippable' => $totalItemsShippable
            )
        );

        // Check for count of items that have been shipped
        if (get_post_meta($orderId, '_mamis_shippit_shipped_items', true)) {
            $totalItemsShipped = get_post_meta($orderId, '_mamis_shippit_shipped_items', true);
        }
        // If no items have been shipped previously set count to 0
        else {
            add_post_meta($orderId, '_mamis_shippit_shipped_items', 0, true);
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

        // Save/Update Shipments Data
        $this->save_shipment_data($orderId, $requestData);

        $this->log->add(
            'SHIPPIT - WEBHOOK REQUEST',
            'Items Shipped',
            array(
                'orderItems' => $orderItemsData,
                'orderItemsShipped' => $orderItemsShipped,
                'totalItemsShipped' => $totalItemsShipped
            )
        );

        // Update the total of all items shipped
        update_post_meta($orderId, '_mamis_shippit_shipped_items', $totalItemsShipped);

        // Add order comment for shipped items
        $order = new WC_Order($orderId);
        $order->add_order_note($orderComment, 0);

        // If all items have been shipped, change the order status to completed
        if ($totalItemsShipped >= $totalItemsShippable) {
            $wcOrder->update_status('completed', 'Order has been shipped with Shippit');

            add_action(
                'woocommerce_order_status_completed_notification',
                'action_woocommerce_order_status_completed_notification',
                10,
                2
            );
        }

        wp_send_json_success(array(
            'message' => self::SUCCESS_SHIPMENT_CREATED
        ));
    }

    /**
     * Add shipment information for the order
     * Update the shipment information if already exist
     *
     * @param  object $requestData
     * @return null
     */
    /**
     * Add shipment information for the order or update the
     * shipment information if some data already exists
     *
     * @param  string $orderId     The order id
     * @param  object $requestData The webhook request data
     * @return mixed               The response from update_post_meta,
     *                             or null if request data is empty
     */
    public function save_shipment_data($orderId, $requestData)
    {
        if (empty($requestData)) {
            return;
        }

        $statusList = $requestData->status_history;
        $status =  array_filter($statusList, function($statusItem) {
            return ($statusItem->status == 'ready_for_pickup');
        });
        $readyForPickUp = reset($status);

        $shipmentData['tracking_number'] = $requestData->tracking_number;
        $shipmentData['tracking_url'] = $requestData->tracking_url;
        $shipmentData['courier_name'] = $requestData->courier_name;

        if (!empty($readyForPickUp)) {
            $shipmentData['booked_at'] = date("d-m-Y H:i:s", strtotime($readyForPickUp->time));
        }

        $shipments = array();
        $existingShipment = get_post_meta($orderId, '_mamis_shippit_shipment', false);

        // Retrieve the existing shipment data if it's available
        if (!empty($existingShipment)) {
            $shipments = reset($existingShipment);
        }

        // Append the new shipment data
        $shipments[] = $shipmentData;

        return update_post_meta($orderId, '_mamis_shippit_shipment', $shipments);
    }

    public function get_order($orderId, $orderStatus = 'wc-processing')
    {
        global $woocommerce;
        global $post;

        // Add support for Wordpress Jetpack - Order Numbers
        if (class_exists('WCJ_Order_Numbers') && get_option('wcj_order_numbers_enabled')) {
            $queryArgs = array(
                'meta_key' => '_wcj_order_number',
                'meta_value' => $orderId,
                'post_type' => 'shop_order',
                'post_status' => $orderStatus,
                'posts_per_page' => 1
            );
        }
        else {
            $queryArgs = array(
                'p' => $orderId,
                'post_type' => 'shop_order',
                'post_status' => $orderStatus,
                'posts_per_page' => 1
            );
        }

        // Get the woocommerce order if it's processing
        $order = get_posts($queryArgs);

        if (!empty($order)) {
            return reset($order);
        }

        return false;
    }

    public function after_options_save()
    {
        // Get key after the options have saved
        $currentApiKey = get_option('wc_settings_shippit_api_key');
        $newApiKey = $_POST['wc_settings_shippit_api_key'];

        $environment = $_POST['wc_settings_shippit_environment'];
        $isValidApiKey = null;

        if ($newApiKey != $currentApiKey) {
            $isValidApiKey = $this->validate_apikey($newApiKey, $currentApiKey, $environment);
        }

        if ($isValidApiKey == true || is_null($isValidApiKey)) {
            $this->register_webhook($newApiKey, $environment);
        }
    }

    private function register_webhook($newApiKey, $environment = null)
    {
        $this->api = new Mamis_Shippit_Api();

        // Set the api key temporarily to the requested key
        $this->api->setApiKey($newApiKey);

        if (!empty($environment)) {
            // use the environment passed
            $this->api->setEnvironment($environment);
        }

        $webhookUrl = get_site_url() . '/shippit/shipment_create?shippit_api_key=' . $newApiKey;

        $requestData = array(
            'webhook_url' => $webhookUrl
        );

        $this->log->add(
            'Registering Webhook Url',
            $newApiKey,
            array(
                'webhook_url' => $webhookUrl
            )
        );

        try {
            $apiResponse = $this->api->putMerchant($requestData);

            if ($apiResponse
                && !property_exists($apiResponse, 'error')
                && property_exists($apiResponse, 'response')) {
                $this->log->add(
                    'Registering Web Hook Response',
                    'Webhook Registration Successful'
                );

                $this->show_webhook_notice(true);

                return true;
            }
            else {
                $this->log->add(
                    'Registering Web Hook Response',
                    'An error occurred during webhook register'
                );

                $this->show_webhook_notice(false);

                return false;
            }
        }
        catch (Exception $e) {
            $this->log->exception($e);
        }
    }

    private function validate_apikey($newApiKey, $oldApiKey = null, $environment = null)
    {
        if (is_null($oldApiKey)) {
            $oldApiKey = get_option('wc_settings_shippit_api_key');
        }

        $this->log->add(
            'Validating API Key',
            $newApiKey,
            array(
                'old_api_key' => $oldApiKey,
                'new_api_key' => $newApiKey
            )
        );

        $this->api = new Mamis_Shippit_Api();
        // Set the api key temporarily to the requested key
        $this->api->setApiKey($newApiKey);

        if (!empty($environment)) {
            // use the environment passed
            $this->api->setEnvironment($environment);
        }

        try {
            $apiResponse = $this->api->getMerchant();

            if (property_exists($apiResponse, 'error')) {
                $this->log->add(
                    'Validating API Key Result',
                    'API Key ' . $newApiKey . 'is INVALID'
                );

                $this->show_api_notice(false);

                return false;
            }

            if (property_exists($apiResponse, 'response')) {
                $this->log->add(
                    'Validating API Key Result',
                    'API Key ' . $newApiKey . 'is VALID'
                );

                $this->show_api_notice(true);

                return true;
            }

        }
        catch (Exception $e) {
            $this->log->exception($e);
        }
    }

    public function show_api_notice($isValid)
    {
        if (!$isValid) {
            echo '<div class="error notice">'
                . '<p>Invalid Shippit API Key detected - Shippit will not function correctly.</p>'
                . '</div>';
        }
        else {
            echo '<div class="updated notice">'
                . '<p>Shippit API Key is Valid</p>'
                . '</div>';
        }
    }

    public function show_webhook_notice($isValid)
    {
        if (!$isValid) {
            echo '<div class="error notice">'
                . '<p>Shippit Webhook ' . get_site_url() . '/shippit/shipment_create was not registered</p>'
                . '</div>';
        }
        else {
            echo '<div class="updated notice">'
                . '<p>Shippit Webhook ' . get_site_url() . '/shippit/shipment_create has now been registered</p>'
                . '</div>';
        }
    }

    /**
     * Add the shippit order sync to the cron scheduler
     */
    public static function order_sync_schedule()
    {
        if (!wp_next_scheduled('syncOrders')) {
            wp_schedule_event(current_time('timestamp'), 'hourly', 'syncOrders');
        }
    }

    /**
     * Remove the shippit order sync to the cron scheduler
     */
    public static function order_sync_deschedule()
    {
        wp_clear_scheduled_hook('syncOrders');
    }

    public function syncOrders()
    {
        if (class_exists('WC_Order')) {
            $orders = new Mamis_Shippit_Order();
            $orders->syncOrders();
        }
    }

    public function add_authority_to_leave($checkout)
    {
        if (get_option('wc_settings_shippit_atl_enabled') != 'yes') {
            return;
        }

        echo '<div id="authority_to_leave"><h3>' . __('Authority to leave') . '</h2>';

        woocommerce_form_field( 'authority_to_leave', array(
            'type'          => 'select',
            'class'         => array('my-field-class form-row-wide'),
            'options'       => array(
                'No' => 'No',
                'Yes'  => 'Yes'
                ),
            ),
        $checkout->get_value( 'authority_to_leave' ));

        echo '</div>';
    }

    public function update_authority_to_leave_order_meta($order_id)
    {
        if (!empty($_POST['authority_to_leave'])) {
            update_post_meta(
                $order_id,
                'authority_to_leave',
                sanitize_text_field($_POST['authority_to_leave'])
            );
        }
    }
}
