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

class Mamis_Shippit_Settings_Global
{
    private $_settingsCache = null;

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settingsTab Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settingsTab Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab($settingsTab) {
        $settingsTab['shippit_settings_tab'] = __('Shippit', 'shippit-settings-tab');
        return $settingsTab;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab() {
        woocommerce_admin_fields(self::get_settings());
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options(self::get_settings());
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {
        // @TODO: Review if possible to remove global var for php5.5 support
        global $shippitOtherShippingMethods;

        $settings = array(
            'section_title' => array(
                'name'     => __( 'Shippit Settings', 'shippit-settings-tab' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'shippit-settings-title'
            ),

            'enabled' => array(
                'title' => __('Enabled', 'shippit-settings-tab'),
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'shippit-settings-tab'),
                    'yes' => __('Yes', 'shippit-settings-tab'),
                ),
                'id' => 'wc_settings_shippit_global_enabled'
            ),

            'api_key' => array(
                'title' => __('API Key', 'shippit-settings-tab'),
                'desc' => '',
                'name' => 'api_key',
                'type' => 'text',
                'id' => 'wc_settings_shippit_global_api_key'
            ),

            'debug' => array(
                'title' => __('Debug', 'woocommerce-shippit'),
                'description' => __('If debug mode is enabled, all events and requests are logged to the debug log file', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                ),
                'id' => 'wc_settings_shippit_global_debug'
            ),

            'environment' => array(
                'title' => __('Environment', 'shippit-settings-tab'),
                'description' => __('The environment to connect to for all quotes and order sync operations', 'shippit-settings-tab'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'live',
                'type' => 'select',
                'options' => array(
                    'sandbox' => __('Sandbox', 'shippit-settings-tab'),
                    'live' => __('Live', 'shippit-settings-tab'),
                ),
                'id' => 'wc_settings_shippit_global_environment'
            ),

            'send_all_orders' => array(
                'title' => __('Send All Orders', 'shippit-settings-tab'),
                'description' => __('Send all orders to Shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'shippit-settings-tab'),
                    'yes' => __('Yes', 'shippit-settings-tab'),
               ),
                'id' => 'wc_settings_shippit_global_send_all_orders'
            ),

            'standard_shipping_methods' => array(
                'title' => __('Standard Shipping Methods', 'woocommerce-shippit'),
                'description' => __('Existing shipping methods mapped to Shippit\'s standard services', 'woocommerce-shippit'),
                'desc_tip' => true,
                'type' => 'multiselect',
                'options' => $shippitOtherShippingMethods,
                'class' => 'wc-enhanced-select',
                'id' => 'wc_settings_shippit_global_standard_shipping_methods'
            ),

            'express_shipping_methods' => array(
                'title' => __('Express Shipping Methods', 'woocommerce-shippit'),
                'description' => __('Existing shipping methods mapped to Shippit\'s express services', 'woocommerce-shippit'),
                'desc_tip' => true,
                'type' => 'multiselect',
                'options' => $shippitOtherShippingMethods,
                'class' => 'wc-enhanced-select',
                'id' => 'wc_settings_shippit_global_express_shipping_methods'
            ),

            'international_shipping_methods' => array(
                'title' => __('International Shipping Methods', 'woocommerce-shippit'),
                'description' => __('Existing shipping methods mapped to Shippit\'s international services', 'woocommerce-shippit'),
                'desc_tip' => true,
                'type' => 'multiselect',
                'options' => $shippitOtherShippingMethods,
                'class' => 'wc-enhanced-select',
                'id' => 'wc_settings_shippit_global_international_shipping_methods'
            ),

            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_settings_tab_demo_section_end'
            )
        );

        return apply_filters('wc_settings_shippit_settings', $settings);
    }

    // @TODO: Review usage as each item is now stored a seperate option in database rather than one option
    public function getSettings()
    {
        if (is_null($this->_settingsCache)) {
            $this->_settingsCache = get_option('woocommerce_mamis_shippit_global_settings');
        }

        return $this->_settingsCache;
    }

    // @TODO: Review usage as each item is now stored a seperate option in database rather than one option
    public function getSetting($key)
    {
        $settings = $this->getSettings();

        if ( isset($settings[$key]) ) {
            return $settings[$key];
        }

        return null;
    }
}