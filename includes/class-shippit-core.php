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
     * @var Mamis_Shippit_Log
     */
    protected $log;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        // Check if WooCommerce is active
        if (!class_exists('woocommerce')) {
            return;
        }

        $this->log = new Mamis_Shippit_Log(['area' => 'core']);

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

        function authority_to_leave_display_admin_order_meta(WC_Order $order)
        {
            echo '<p><strong>' . __('Authority to leave') . ':</strong> ' . $order->get_meta('authority_to_leave') . '</p>';
        }

        // Add the shippit settings tab functionality
        add_action('woocommerce_settings_tabs_shippit_settings_tab', 'Mamis_Shippit_Settings::addFields');
        add_action('woocommerce_update_options_shippit_settings_tab', 'Mamis_Shippit_Settings::updateSettings');

        // Validate the api key when the setting is changed, the api key
        // is validated before it is saved to the database, this also
        // enables preventing storage of incorrect api credentials
        add_action('pre_update_option_wc_settings_shippit_api_key', array($this, 'before_api_key_update'), 10, 2);

        // Action for when the api key gets updated successfully
        add_action('update_option_wc_settings_shippit_api_key', array($this, 'after_api_key_update'), 10, 2);


        //**********************/
        // Webhook functionality
        //**********************/

        // create filter to get $_GET['shippit_api_key']
        add_filter('query_vars', array($this, 'add_query_vars'), 0);

        // handle API request if 'shippit_api_key' is set
        add_action('parse_request', array($this, 'handle_webhook_request'), 0);

        // create 'shippit/shipment_create' endpoint
        add_action('init', array($this, 'add_webhook_endpoint'), 0);

        // Add Send to Shippit order action
        add_action('woocommerce_order_actions', array($this, 'shippit_add_order_meta_box_action') );

        // Process Shippit send order action
        add_action('woocommerce_order_action_shippit_order_action', array($order, 'sendOrder') );

        // Add Bulk Send to Shippit orders action
        add_action('bulk_actions-edit-shop_order', array($this, 'shippit_send_bulk_orders_action'), 20, 1);

        // Process Shippit bulk orders send action
        add_action('handle_bulk_actions-edit-shop_order', array($order, 'sendOrders'), 10, 3 );

        add_action('admin_notices', array($this, 'order_sync_notice') );

        if (get_option('wc_settings_shippit_shippingcalculator_city_enabled') == 'yes') {
            // Enable suburb/city field for Shipping calculator
            add_filter('woocommerce_shipping_calculator_enable_city', '__return_true');
        }

        // Add the shipment meta boxes when viewing an order.
        add_action('add_meta_boxes_shop_order', array($this, 'mamis_add_shipment_meta_box'));

        // Add notification if the merchant has this shipping method still enabled
        add_action('admin_notices', array($this, 'add_depreciation_notice'));
        add_action('network_admin_notices', array($this, 'add_depreciation_notice'));
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
     *
     * @param WP_Post $post
     * @return void
     */
    function mamis_add_shipment_meta_box_content(WP_Post $post)
    {
        // Retrieve the order using the Post ID
        $order = new WC_Order($post->ID);

        $shipmentData = $order->get_meta('_mamis_shippit_shipment', true);
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

            if (!empty($shipment['courier_tracking_number'])) {
                $shipmentDetails .= '<strong>Courier Track #:</strong>&nbsp;';
                $shipmentDetails .= $shipment['courier_tracking_number'];
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

    public function add_webhook_endpoint()
    {
        add_rewrite_rule('^shippit/shipment_create/?([0-9]+)?/?', 'index.php?shippit_api_key=$matches[1],', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'shippit_api_key';

        return $vars;
    }

    public function handle_webhook_request()
    {
        global $wp;

        if (isset($wp->query_vars['shippit_api_key'])) {
            $shipment = new Mamis_Shippit_Shipment();
            $shipment->handle();

            exit;
        }
    }

    /**
     * Validate the api key credentials for the configured environment
     * 
     * @param string $newApiKey
     * @param string|null $oldApiKey
     */
    public function before_api_key_update(string $newApiKey, ?string $oldApiKey)
    {
        // Retrieve the environment setting from the POST request,
        // as this may not yet be saved if it was changed in the same request
        $environment = $_POST['wc_settings_shippit_environment'];

        $isValidApiKey = $this->validate_credentials($newApiKey, $environment);

        // Return null since we do not want to save the incorrect api key
        // for the currently chosen environnment
        if ($isValidApiKey == false) {
            $this->show_api_notice($isValidApiKey);

            return $oldApiKey;
        }

        $this->show_api_notice($isValidApiKey);

        return $newApiKey;
    }

    /**
    * Validate the api key credentials for the configured environment
    * 
    * @param string $newApiKey
    * @param string|null $oldApiKey
    */
    public function after_api_key_update($newApiKey, $oldApiKey)
    {
        // Retrieve the environment setting from the POST request,
        // as this may not yet be saved if it was changed in the same request
        $environment = $_POST['wc_settings_shippit_environment'];

        $this->update_merchant($newApiKey, $environment);
    }

    /**
     * Update the merchant details in Shippit
     * - If the integration is enabled, register a webhook url + shopping cart
     *   - The webhook url is conditionally registered based on fulfillment settings
     * - If the integration is disabled, deregister the webhook url + shopping cart
     *
     * @param string $apiKey        The api key to be validated
     * @param string $environment   The enviornment in which the request is performed against
     * @return bool
     */
    protected function update_merchant($apiKey = null, $environment = null)
    {
        $apiService = new Mamis_Shippit_Api($apiKey, $environment);
        $isEnabled = $_POST['wc_settings_shippit_enabled'] === 'yes';

        if ($isEnabled) {
            $isFulfillmentEnabled = $_POST['wc_settings_shippit_fulfillment_enabled'] === 'yes';
         
            $requestData = [
                'webhook_url' => (
                    $isFulfillmentEnabled
                        ? sprintf(
                            '%s/shippit/shipment_create?shippit_api_key=%s',
                            get_site_url(),
                            $apiKey
                        )
                        : null
                ),            
                'shipping_cart_method_name' => 'woocommerce',
            ];
        }
        else {
            $requestData = [
                'webhook_url' => null,
                'shipping_cart_method_name' => null,
            ];
        }

        $this->log->info(
            'Update Merchant Details',
            $requestData
        );

        try {
            $apiResponse = $apiService->updateMerchant($requestData);

            if (
                $apiResponse
                && !property_exists($apiResponse, 'error')
                && property_exists($apiResponse, 'response')
            ) {
                $this->log->info(
                    'Update Merchant Successful',
                    [
                        'webhook_url' => $webhookUrl,
                    ]
                );

                return true;
            }
            else {
                $this->log->error(
                    'An error occurred while attempting to update the merchant',
                    $requestData
                );

                return false;
            }
        }
        catch (Exception $e) {
            $this->log->exception($e);
        }

        return false;
    }

    /**
     * Determines if the credentials are valid for the environment
     *
     * @param string $apiKey        The api key to be validated
     * @param string $environment   The enviornment in which the api key is validated against
     * @return bool
     */
    private function validate_credentials($apiKey = null, $environment = null)
    {
        $apiService = new Mamis_Shippit_Api($apiKey, $environment);

        try {
            $apiResponse = $apiService->getMerchant();

            if (
                $apiResponse
                && property_exists($apiResponse, 'error')
            ) {
                $this->log->error('Validating API Key Result - API Key is INVALID');

                return false;
            }

            if (
                $apiResponse
                && property_exists($apiResponse, 'response')
            ) {
                $this->log->info('Validating API Key Result - API Key is VALID');

                return true;
            }

        }
        catch (Exception $e) {
            $this->log->exception($e);
        }

        return false;
    }

    public function show_api_notice($isValid)
    {
        if (!$isValid) {
            echo '<div class="error notice">'
                . '<p>The Shippit API Key and Environment provided could not be verified. Please check the Shippit API key and Enviroment values and try again.</p>'
                . '</div>';
        }
        else {
            echo '<div class="updated notice">'
                . '<p>Your Shippit API Key has been validated and is correct</p>'
                . '</div>';
        }
    }

    public function show_webhook_notice($isValid)
    {
        if (!$isValid) {
            echo '<div class="error notice">'
                . '<p>Shippit Webhook could not be updated</p>'
                . '</div>';
        }
        else {
            echo '<div class="updated notice">'
                . '<p>Shippit Webhook has now been updated</p>'
                . '</div>';
        }
    }

    public function show_cart_registration_notice()
    {
        echo '<div class="error notice">'
            . '<p>The request to update the shopping cart integration name failed - please try again.</p>'
            . '</div>';
    }

    /**
     * Add the shippit order sync to the cron scheduler
     */
    public static function order_sync_schedule()
    {
        if (!wp_next_scheduled('syncOrders')) {
            wp_schedule_event(
                current_time('timestamp', true),
                'hourly',
                'syncOrders'
            );
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

        echo '<div id="authority_to_leave"><h2>' . __('Authority to leave') . '</h2>';

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

    /**
     * Add a depreciation notice for merchants that have the legacy shipping method active and in use
     *
     * @return void
     */
    public function add_depreciation_notice()
    {
        $legacyOptions = get_option('woocommerce_mamis_shippit_legacy_settings');

        // If there are no options present, we don't require the depreciation notice
        if (empty($legacyOptions)) {
            return;
        }

        // If the `enabled` configuration option is not present, or not yes, we don't require the depreciation notice
        if (!isset($legacyOptions['enabled']) || $legacyOptions['enabled'] !== 'yes') {
            return;
        }

        // Render the depreciation notice
        include_once __DIR__ . '/../views/notices/shippit-legacy-depreciation.php';
    }
}
