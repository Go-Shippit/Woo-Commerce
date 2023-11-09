<?php

/**
 * Mamis - https://www.mamis.com.au
 * Copyright © Mamis 2023-present. All rights reserved.
 * See https://www.mamis.com.au/license
 */

class Mamis_Shippit_Settings
{
    /**
     * @var Mamis_Shippit_Log
     */
    protected $log;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->log = new Mamis_Shippit_Log(['area' => 'settings']);
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settingsTab Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array
     */
    public static function addSettingsTab($settingsTab)
    {
        $settingsTab['shippit_settings_tab'] = __('Shippit', 'woocommerce-shippit');

        return $settingsTab;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     */
    public function addFields()
    {
        woocommerce_admin_fields($this->getFields());

        // include custom script on shippit settings page
        wp_enqueue_script('shippit-script');
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     */
    public function updateSettings()
    {
        $apiKey = $_POST['wc_settings_shippit_api_key'];
        $environment = $_POST['wc_settings_shippit_environment'];

        // Validate the api key + environment variable combination
        if ($this->validateCredentails($apiKey, $environment) === false) {
            add_action('admin_notices', array($this, 'noticeCredentialsInvalid'));

            return;
        }

        // If the API Key or Environment being saved differs to the current key,
        // unregister the merchant associated with the current key
        if (
            get_option('wc_settings_shippit_api_key') !== ''
            && (
                $apiKey != get_option('wc_settings_shippit_api_key')
                || $environment != get_option('wc_settings_shippit_environment')
            )
        ) {
            $this->unregisterMerchant($apiKey, $environment);
        }

        woocommerce_update_options(self::getFields());

        $this->registerMerchant();
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public function getFields(): array
    {
        $shippingMethodOptions = self::getShippingMethodOptions();

        $settings = array(
            'title_general' => array(
                'id' => 'shippit-settings-general-title',
                'name' => __( 'General Settings', 'woocommerce-shippit' ),
                'type' => 'title',
                'desc' => 'General Settings allow you to connect your WooCommerce store with Shippit.',
                'desc_tip' => true,
            ),

            'enabled' => array(
                'id' => 'wc_settings_shippit_enabled',
                'title' => __('Enabled', 'woocommerce-shippit'),
                'desc'     => 'Determines whether live quoting, order sync and fulfillment sync are enabled and active.',
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                ),
            ),

            'api_key' => array(
                'id' => 'wc_settings_shippit_api_key',
                'title' => __('API Key', 'woocommerce-shippit'),
                'desc' => 'Your Shippit API Key',
                'desc_tip' => true,
                'default' => '',
                'name' => 'api_key',
                'type' => 'text',
                'css' => 'min-width: 350px; border-radius: 3px;',
            ),

            'debug' => array(
                'id' => 'wc_settings_shippit_debug',
                'title' => __('Debug Mode', 'woocommerce-shippit'),
                'desc' => __('If debug mode is enabled, all events and requests are logged to the debug log file', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                ),
            ),

            'environment' => array(
                'id' => 'wc_settings_shippit_environment',
                'title' => __('Environment', 'woocommerce-shippit'),
                'desc' => __('The environment to connect to for all quotes and order sync operations', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'live',
                'type' => 'select',
                'options' => array(
                    'sandbox' => __('Sandbox', 'woocommerce-shippit'),
                    'live' => __('Live', 'woocommerce-shippit'),
                ),
            ),

            'section_general_end' => array(
                 'id' => 'shippit-settings-general-end',
                 'type' => 'sectionend',
            ),

            'title_cart_checkout' => array(
                'id' => 'shippit-settings-checkout-title',
                'name' => __( 'Cart & Checkout Options', 'woocommerce-shippit' ),
                'type' => 'title',
                'desc' => 'Show/hide additional fields in the cart & checkout areas.',
                'desc_tip' => true,
            ),

            'atl_enabled' => array(
                'id' => 'wc_settings_shippit_atl_enabled',
                'title' => __('Display Authority to Leave', 'woocommerce-shippit'),
                'desc'     => 'Determines whether to show Auhtority to Leave field in the checkout or not.',
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'yes',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                ),
            ),

            'section_cart_checkout_end' => array(
                 'id' => 'shippit-settings-cart-checkout-end',
                 'type' => 'sectionend',
            ),

            'title_order' => array(
                'id' => 'shippit-settings-orders-title',
                'name' => __( 'Order Sync Settings', 'woocommerce-shippit' ),
                'type' => 'title',
                'desc' => 'Order Sync Settings refers to your preferences for when an order should be sent to Shippit and the type of Shipping Service to utilise.',
            ),

            'auto_sync_orders' => array(
                'id' => 'wc_settings_shippit_auto_sync_orders',
                'title' => __('Auto-Sync New Orders', 'woocommerce-shippit'),
                'desc' => __('Determines whether to automatically sync all orders, or only Shippit Quoted or Mapped orders to Shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'all' => __('Yes - Auto-sync all new orders', 'woocommerce-shippit'),
                    'all_shippit' => __('Yes - Auto-sync only orders with Shippit Shipping Methods', 'woocommerce-shippit'),
               ),
            ),

            'section_order_sync_settings_end' => array(
                'id' => 'shippit-settings-order-sync-settings-end',
                'type' => 'sectionend',
           ),

            'title_shipping_options' => array(
                'id' => 'shippit-settings-shipping-options-title',
                'name' => __( 'Shipping Options', 'woocommerce-shippit' ),
                'type' => 'title',
                'desc' => 'Match your store\'s shipping options to a Shippit service type so we can automatically allocate the correct shipping service for your orders.',
            ),

            'standard_shipping_methods' => array(
                'id' => 'wc_settings_shippit_standard_shipping_methods',
                'title' => __('Standard Shipping Methods', 'woocommerce-shippit'),
                'desc' => __('The third party shipping methods that should be allocated to a "Standard" Shippit Service', 'woocommerce-shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'multiselect',
                'options' => $shippingMethodOptions,
                'class' => 'wc-enhanced-select',
            ),

            'express_shipping_methods' => array(
                'id' => 'wc_settings_shippit_express_shipping_methods',
                'title' => __('Express Shipping Methods', 'woocommerce-shippit'),
                'desc' => __('The third party shipping methods that should be allocated to a "Express" Shippit Service', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'multiselect',
                'options' => $shippingMethodOptions,
                'class' => 'wc-enhanced-select',
            ),

            'clickandcollect_shipping_methods' => array(
                'id' => 'wc_settings_shippit_clickandcollect_shipping_methods',
                'title' => __('Click & Collect Shipping Methods', 'woocommerce-shippit'),
                'desc' => __('The third party shipping methods that should be allocated to a "Click and Collect" Shippit service level', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'multiselect',
                'options' => $shippingMethodOptions,
                'class' => 'wc-enhanced-select',
            ),

            'plainlabel_shipping_methods' => array(
                'id' => 'wc_settings_shippit_plainlabel_shipping_methods',
                'title' => __('Plain Label Shipping Methods', 'woocommerce-shippit'),
                'desc' => __('The third party shipping methods that should be allocated to the "Plain Label" Shippit Service', 'woocommerce-shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'multiselect',
                'options' => $shippingMethodOptions,
                'class' => 'wc-enhanced-select',
            ),

            'section_order_end' => array(
                 'id' => 'shippit-settings-order-end',
                 'type' => 'sectionend',
            ),

            'title_order_items' => array(
                'id' => 'shippit-settings-items-title',
                'name' => __( 'Item Sync Settings', 'woocommerce-shippit' ),
                'type' => 'title',
            ),

            'tariff_code_attribute' => array(
                'id' => 'wc_settings_shippit_tariff_code_attribute',
                'title' => __('Tariff Code Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Attribute to be used for Tariff Code information sent to Shippit.', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'select',
                'options' => self::getProductAttributes(),
                'class' => 'wc-enhanced-select',
            ),

            'tariff_code_custom_attribute' => array(
                'id' => 'wc_settings_shippit_tariff_code_custom_attribute',
                'title' => __('Tariff Code Custom Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Custom Attribute to be used for Tariff Code information sent to Shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'name' => 'tariff_code_custom_attribute',
                'type' => 'text',
            ),

            'origin_country_code_attribute' => array(
                'id' => 'wc_settings_shippit_origin_country_code_attribute',
                'title' => __('Origin Country Code Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Attribute to be used for Origin Country Code information sent to Shippit.', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'select',
                'options' => self::getProductAttributes(),
                'class' => 'wc-enhanced-select',
            ),

            'origin_country_code_custom_attribute' => array(
                'id' => 'wc_settings_shippit_origin_country_code_custom_attribute',
                'title' => __('Origin Country Code Custom Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Custom Attribute to be used for Origin Country Code information sent to Shippit.', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'name' => 'origin_country_code_custom_attribute',
                'type' => 'text',
            ),

            'dangerous_goods_code_attribute' => array(
                'id' => 'wc_settings_shippit_dangerous_goods_code_attribute',
                'title' => __('Dangerous Goods Code Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Attribute to be used for Dangerous Goods Code information sent to Shippit.', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'select',
                'options' => self::getProductAttributes(),
                'class' => 'wc-enhanced-select',
            ),

            'dangerous_goods_code_custom_attribute' => array(
                'id' => 'wc_settings_shippit_dangerous_goods_code_custom_attribute',
                'title' => __('Dangerous Goods Code Custom Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Custom Attribute to be used for Dangerous Goods Code information sent to Shippit.', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'name' => 'dangerous_goods_code_custom_attribute',
                'type' => 'text',
            ),

            'dangerous_goods_text_attribute' => array(
                'id' => 'wc_settings_shippit_dangerous_goods_text_attribute',
                'title' => __('Dangerous Goods Text Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Attribute to be used for Dangerous Goods Text information sent to Shippit.', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'select',
                'options' => self::getProductAttributes(),
                'class' => 'wc-enhanced-select',
            ),

            'dangerous_goods_text_custom_attribute' => array(
                'id' => 'wc_settings_shippit_dangerous_goods_text_custom_attribute',
                'title' => __('Dangerous Goods Text Custom Attribute', 'woocommerce-shippit'),
                'desc' => __('The Product Custom Attribute to be used for Dangerous Goods Text information sent to Shippit.', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'name' => 'dangerous_goods_text_custom_attribute',
                'type' => 'text',
            ),

            'section_order_items_end' => array(
                'id' => 'shippit-settings-order-items-end',
                'type' => 'sectionend',
           ),

            'title_fulfillment' => array(
                'id' => 'shippit-settings-fulfillment-title',
                'name' => __( 'Fulfillment Settings', 'woocommerce-shippit' ),
                'type' => 'title',
            ),

            'fulfillment_enabled' => array(
                'id' => 'wc_settings_shippit_fulfillment_enabled',
                'title' => __('Enabled', 'woocommerce-shippit'),
                'class' => 'wc-enhanced-select',
                'default' => 'yes',
                'type' => 'select',
                'desc' => 'Enable this setting to mark orders as fulfilled in WooCommerce. With this setting enabled, Shippit will update all tracking information against the order as it is fulfilled.',
                'desc_tip' => true,
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                )
            ),

            'fulfillment_tracking_reference' => array(
                'id' => 'wc_settings_shippit_fulfillment_tracking_reference',
                'title' => __('Tracking Reference', 'woocommerce-shippit'),
                'class' => 'wc-enhanced-select',
                'default' => 'shippit_tracking_reference',
                'type' => 'select',
                'desc' => 'The tracking reference that you wish to capture from Shippit and communicate with customers.',
                'desc_tip' => true,
                'options' => array(
                    'shippit_tracking_reference' => __('Shippit Tracking Reference', 'woocommerce-shippit'),
                    'courier_tracking_reference' => __('Courier Tracking Reference', 'woocommerce-shippit'),
                )
            ),

            'section_end' => array(
                'id' => 'shippit-settings-fulfillment-end',
                'type' => 'sectionend',
            ),
        );

        return apply_filters('wc_settings_shippit_settings', $settings);
    }

    /**
     * Get WooCommerce Product Attributes
     *
     * @return array
     */
    public static function getProductAttributes()
    {
        $productAttributes = array();
        $placeHolder = array('' => '-- Please Select --');

        $attributeTaxonomies = wc_get_attribute_taxonomies();

        if (empty($attributeTaxonomies)) {
            return $placeHolder;
        }

        foreach ($attributeTaxonomies as $tax) {
            $productAttributes[$tax->attribute_name] = __($tax->attribute_label, 'woocommerce-shippit');
        }

        // Add custom attribute as option
        $productAttributes['_custom'] = 'Use custom product attribute';

        return array_merge($placeHolder, $productAttributes);
    }

    /**
     * Get the shipping method options that should
     * be available for shipping method mapping
     *
     * @return array
     */
    public static function getShippingMethodOptions()
    {
        $shippingMethodsWithZones = self::getShippingMethodsWithZones();
        $shippingMethodsWithoutZones = self::getShippingMethodsWithoutZones();

        return array_merge($shippingMethodsWithZones, $shippingMethodsWithoutZones);
    }

    /**
     * Get the shipping method options with zone details
     *
     * @return array
     */
    protected static function getShippingMethodsWithZones()
    {
        $shippingMethodOptions = array();
        $zones = WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            $shippingMethods = $zone['shipping_methods'];

            foreach ($shippingMethods as $shippingMethod) {
                if ($shippingMethod->id == 'mamis_shippit') {
                    continue;
                }

                $shippingMethodKey = $shippingMethod->id . ':' . $shippingMethod->instance_id;
                $shippingMethodLabel = (property_exists($shippingMethod, 'title') ? $shippingMethod->title : $shippingMethod->method_title);

                $shippingMethodOptions[$shippingMethodKey] = sprintf(
                    '%s Zone — %s',
                    $zone['zone_name'],
                    $shippingMethodLabel
                );
            }
        }

        return $shippingMethodOptions;
    }

    /**
     * Get the shipping method options without zone details
     * - used to support legacy methods used in a zone-supported environment
     *
     * @return array
     */
    protected static function getShippingMethodsWithoutZones()
    {
        $shippingMethodOptions = array();
        $shippingMethods = WC_Shipping_Zones::get_zone_by()->get_shipping_methods();

        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->id == 'mamis_shippit' || $shippingMethod->id == 'mamis_shippit_legacy') {
                continue;
            }

            $shippingMethodKey = $shippingMethod->id. ':' . $shippingMethod->instance_id;
            $shippingMethodLabel = (property_exists($shippingMethod, 'title') ? $shippingMethod->title : $shippingMethod->method_title);

            $shippingMethodOptions[$shippingMethodKey] = sprintf(
                'Default Zone - %s',
                $shippingMethodLabel
            );
        }

        return $shippingMethodOptions;
    }

    /**
     * Register the merchant to the WooCommerce Integration
     *
     * @param string|null $apiKey
     * @param string|null $environment
     * @return bool
     */
    public function registerMerchant($apiKey = null, $environment = null): bool
    {
        $apiService = new Mamis_Shippit_Api($apiKey, $environment);

        $requestData = [
            'webhook_url' => (
                get_option('wc_settings_shippit_fulfillment_enabled') === 'yes'
                    ? sprintf(
                        '%s/shippit/shipment_create?shippit_api_key=%s',
                        get_site_url(),
                        get_option('wc_settings_shippit_api_key')
                    )
                    : null
            ),
            'shipping_cart_method_name' => 'woocommerce',
        ];

        $this->log->info(
            'Registering Merchant',
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
                    'Merchant registration successful',
                    $requestData
                );

                add_action('admin_notices', [$this, 'noticeRegisteredMerchant']);

                return true;
            }
            else {
                $this->log->error(
                    'Merchant registration failed',
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
     * Unregister the merchant from the WooCommerce Integration
     *
     * @param string|null $apiKey
     * @param string|null $environment
     * @return bool
     */
    public function unregisterMerchant($apiKey = null, $environment = null): bool
    {
        $apiService = new Mamis_Shippit_Api($apiKey, $environment);

        // Merchant Details
        $requestData = [
            'webhook_url' => null,
            'shipping_cart_method_name' => null,
        ];

        try {
            $apiService->updateMerchant($requestData);

            add_action('admin_notices', [$this, 'noticeUnregisteredMerchant']);

            return true;
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
    protected function validateCredentails($apiKey = null, $environment = null)
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

    /**
     * Show a notice stating the credentials are invalid
     *
     * @return void
     */
    public function noticeCredentialsInvalid()
    {
        echo '<div class="notice notice-error">'
            . '<p>The Shippit API Key / Environment provided could not be verified. Please check the Shippit API key and Enviroment values and try again.</p>'
            . '</div>';
    }

    /**
     * Show a notice stating the credentials are invalid
     *
     * @return void
     */
    public function noticeUnregisteredMerchant()
    {
        echo '<div class="notice notice-warning">'
            . '<p>We\'ve unregistered the Shippit Store previously associated with the Shippit API Key / Environment settings.</p>'
            . '</div>';
    }

    /**
     * Show a notice stating the credentials are invalid
     *
     * @return void
     */
    public function noticeRegisteredMerchant()
    {
        echo '<div class="notice notice-success">'
            . '<p>Your WooCommerce Store has been successfully registered with your Shippit store.</p>'
            . '</div>';
    }
}
