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
 * @copyright  Copyright (c) by Mamis.IT Pty Ltd (http://www.mamis.com.au)
 * @author     Matthew Muscat <matthew@mamis.com.au>
 * @license    http://www.mamis.com.au/licencing
 */

class Mamis_Shippit_Settings
{
    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settingsTab Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settingsTab Array of WooCommerce setting tabs & their labels, including the Subscription tab.
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
     * @uses self::get_settings()
     */
    public static function addFields()
    {
        woocommerce_admin_fields(self::getFields());
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function updateSettings()
    {
        woocommerce_update_options(self::getFields());
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function getFields()
    {
        // @TODO: Review if possible to remove global var for php5.5 support
        global $shippitOtherShippingMethods;

        $settings = array(
            'title_general' => array(
                'id'       => 'shippit-settings-general-title',
                'name'     => __( 'General Settings', 'woocommerce-shippit' ),
                'type'     => 'title',
                'desc'     => 'General Settings allow you to connect your WooCommerce store with Shippit.',
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

            'title_order' => array(
                'id' => 'shippit-settings-orders-title',
                'name' => __( 'Order Sync Settings', 'woocommerce-shippit' ),
                'type' => 'title',
                'desc' => 'Order Sync Settings refers to your prefernces for when an order should be sent to Shippit and the type of Shipping Service to utilise.',
            ),

            'send_all_orders' => array(
                'id' => 'wc_settings_shippit_send_all_orders',
                'title' => __('Auto-Sync New Orders', 'woocommerce-shippit'),
                'desc' => __('Automatically sync all new order to Shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
               ),
            ),

            'standard_shipping_methods' => array(
                'id' => 'wc_settings_shippit_standard_shipping_methods',
                'title' => __('Standard Shipping Methods', 'woocommerce-shippit'),
                'desc' => __('The third party shipping methods that should be allocated to an "Standard" Shippit service level', 'woocommerce-shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'multiselect',
                'options' => $shippitOtherShippingMethods,
                'class' => 'wc-enhanced-select',
            ),

            'express_shipping_methods' => array(
                'id' => 'wc_settings_shippit_express_shipping_methods',
                'title' => __('Express Shipping Methods', 'woocommerce-shippit'),
                'desc' => __('The third party shipping methods that should be allocated to an "Express" Shippit service level', 'woocommerce-shippit'),
                'desc_tip' => true,
                'default' => '',
                'type' => 'multiselect',
                'options' => $shippitOtherShippingMethods,
                'class' => 'wc-enhanced-select',
            ),

            'section_order_end' => array(
                 'id' => 'shippit-settings-order-end',
                 'type' => 'sectionend',
            ),

            'title_fulfillment' => array(
                'id' => 'shippit-settings-fulfillment-title',
                'name' => __( 'Fulfillment Settings', 'woocommerce-shippit' ),
                'type' => 'title',
                'desc' => 'Enable this setting to mark orders as fulfilled in WooCommerce. With this setting enabled, Shippit will update all tracking information against the order as it is fulfilled.',
                'desc_tip' => true,
            ),

            'fulfillment_enabled' => array(
                'id' => 'wc_settings_shippit_fulfillment_enabled',
                'title' => __('Enabled', 'woocommerce-shippit'),
                'class' => 'wc-enhanced-select',
                'default' => 'yes',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                )
            ),

            'section_end' => array(
                 'id' => 'shippit-settings-fulfillment-end',
                 'type' => 'sectionend',
            )
        );

        return apply_filters('wc_settings_shippit_settings', $settings);
    }
}