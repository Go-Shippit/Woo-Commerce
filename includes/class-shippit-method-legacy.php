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

class Mamis_Shippit_Method_Legacy extends Mamis_Shippit_Method
{
    protected $api;
    protected $helper;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->api = new Mamis_Shippit_Api();
        $this->log = new Mamis_Shippit_Log();
        $this->helper = new Mamis_Shippit_Helper();

        $this->id                   = 'mamis_shippit_legacy';
        $this->title                = __('Shippit (Legacy)', 'woocommerce-shippit');
        $this->method_title         = __('Shippit (Legacy)', 'woocommerce-shippit');
        $this->method_description   = __(
            '<p>
                Have Shippit provide you with live quotes directly from the carriers.
                Simply enable live quoting and set your preferences to begin.
            </p>'
        );

        $this->init();
    }

    /**
     * Initialize plugin parts.
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Initiate instance settings as class variables
        $this->quote_enabled           = $this->get_option('enabled');
        $this->title                   = $this->get_option('title');
        $this->allowed_methods         = $this->get_option('allowed_methods');
        $this->max_timeslots           = $this->get_option('max_timeslots');
        $this->filter_enabled          = $this->get_option('filter_enabled'); // depreciated
        $this->filter_enabled_products = $this->get_option('filter_enabled_products'); // depreciated
        $this->filter_attribute        = $this->get_option('filter_attribute');
        $this->filter_attribute_code   = $this->get_option('filter_attribute_code');
        $this->filter_attribute_value  = $this->get_option('filter_attribute_value');
        $this->margin                  = $this->get_option('margin');
        $this->margin_amount           = $this->get_option('margin_amount');

        $this->init_form_fields();
        $this->init_settings();

        // Add action hook to save the shipping method instance settings when they saved
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {
        // Filter by Products should only be available when...
        // - The merchant has it actively enabled in the current settings; and
        // - The named constant `SHIPPIT_DISABLE_PRODUCT_FILTER` is not present
        $isFilterByProductsEnabled = (
            $this->get_option('filter_enabled') === 'yes'
            && defined('SHIPPIT_DISABLE_PRODUCT_FILTER') === false
        );

        $settings = new Mamis_Shippit_Settings_Method();
        $this->form_fields = $settings->getFields($isFilterByProductsEnabled);

        return $this->form_fields;
    }

    /**
     * Add shipping method.
     *
     * Add shipping method to WooCommerce.
     *
     */
    public static function add_shipping_method($methods)
    {
        if (class_exists('Mamis_Shippit_Method_Legacy')) {
            $methods['mamis_shippit_legacy'] = 'Mamis_Shippit_Method_Legacy';
        }

        return $methods;
    }
}
