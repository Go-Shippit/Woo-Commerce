<?php
/**
*  Mamis.IT
*
*  NOTICE OF LICENSE
*
*  This source file is subject to the EULA
*  that is available through the world-wide-web at this URL:
*  http://www.mamis.com.au/licencing
*
*  @category   Mamis
*  @copyright  Copyright (c) 2015 by Mamis.IT Pty Ltd (http://www.mamis.com.au)
*  @author     Matthew Muscat <matthew@mamis.com.au>
*  @license    http://www.mamis.com.au/licencing
*/

class Mamis_Shippit_Settings {

    private static $_settingsCache = null;

    /**
     * Init fields.
     *
     * Add fields to the Shippit settings page.
     *
     */
    public static function getFields()
    {
        $fields = array(
            'enabled' => array(
                'title' => __('Enabled', 'woocommerce-shippit'),
                'class'    => 'wc-enhanced-select',
                'default' => 'No',
                'type' => 'select',
                'options'  => array(
                    'no'  => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                ),
            ),

            'api_key' => array(
                'title'  => __('API Key', 'woocommerce-shippit'),
                'desc'   => '',
                'name'   => 'api_key',
                'type'   => 'text',
                'css'    => 'min-width:300px;',
            ),

            'debug' => array(
                'title'    => __('Debug', 'woocommerce-shippit'),
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => 'No',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
                ),
            ),

            'environment' => array(
                'title'    => __('Environment', 'woocommerce-shippit'),
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => 'Live',
                'type'     => 'select',
                'options'  => array(
                    'staging'  => __('Staging', 'woocommerce-shippit'),
                    'live' => __('Live', 'woocommerce-shippit'),
                ),
            ),

            'send_all_orders' => array(
                'title'    => __('Send All Orders to Shippit', 'woocommerce-shippit'),
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
               ),
            ),

            'title' => array(
                'title'    => __('Title', 'woocommerce-shippit'),
                'desc'     => '',
                'type'     => 'text',
                'default'  => 'Shippit',
                'css'      => 'min-width:300px;',
            ),

            'allowed_methods' => array(
                'title'    => __('Allowed Methods', 'woocommerce-shippit'),
                'desc'     => '',
                'id'       => 'allowed_methods',
                'type'     => 'multiselect',
                'default'  => array(
                    'standard',
                    'premium'
                ),
                'options'  => array(
                    'standard' => __('Standard', 'woocommerce-shippit'),
                    'premium'  => __('Premium', 'woocommerce-shippit'),
                ),
                'css'      => 'min-width:300px;',
            ),

            'max_timeslots' => array(
                'title'    => __('Maximum Timeslots', 'woocommerce-shippit'),
                'id'       => 'max_timeslots',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '',
                'type'     => 'select',
                'options'  => array(
                    ''  => __('-- No Max Timeslots --', 'woocommerce-shippit'),
                    '1'  => __('1 Timeslots', 'woocommerce-shippit'),
                    '2'  => __('2 Timeslots', 'woocommerce-shippit'),
                    '3'  => __('3 Timeslots', 'woocommerce-shippit'),
                    '4'  => __('4 Timeslots', 'woocommerce-shippit'),
                    '5'  => __('5 Timeslots', 'woocommerce-shippit'),
                    '6'  => __('6 Timeslots', 'woocommerce-shippit'),
                    '7'  => __('7 Timeslots', 'woocommerce-shippit'),
                    '8'  => __('8 Timeslots', 'woocommerce-shippit'),
                    '9'  => __('9 Timeslots', 'woocommerce-shippit'),
                    '10' => __('10 Timeslots', 'woocommerce-shippit'),
                    '11' => __('11 Timeslots', 'woocommerce-shippit'),
                    '12' => __('12 Timeslots', 'woocommerce-shippit'),
                    '13' => __('13 Timeslots', 'woocommerce-shippit'),
                    '14' => __('14 Timeslots', 'woocommerce-shippit'),
                    '15' => __('15 Timeslots', 'woocommerce-shippit'),
                    '16' => __('16 Timeslots', 'woocommerce-shippit'),
                    '17' => __('17 Timeslots', 'woocommerce-shippit'),
                    '18' => __('18 Timeslots', 'woocommerce-shippit'),
                    '19' => __('19 Timeslots', 'woocommerce-shippit'),
                    '20' => __('20 Timeslots', 'woocommerce-shippit'),
               ),
            ),

            'filter_enabled' => array(
                'title'    => __('Filter by enabled products', 'woocommerce-shippit'),
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => 'No',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
               ),
            ),

            'filter_enabled_products' => array(
                'title'    => __('Enabled Products', 'woocommerce-shippit'),
                'desc'     => '',
                'type'     => 'multiselect',
                'options'  => self::_getProducts(),
                'css'      => 'min-width:300px;',
            ),

            'filter_attribute' => array(
                'title'    => __('Filter by product attributes', 'woocommerce-shippit'),
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => 'No',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
               ),
            ),

            'filter_attribute_code' => array(
                'title'    => __('Filter by attribute code', 'woocommerce-shippit'),
                'desc'     => '',
                'type'     => 'select',
                'class'    => 'wc-enhanced-select',
                'options'  => self::_getAttributes(),
                'css'      => 'min-width:300px;',
            ),

            'filter_attribute_value' => array(
                'title'    => __('Filter by attribute value', 'woocommerce-shippit'),
                'desc'     => '',
                'type'     => 'text',
                'css'      => 'min-width:300px;',
            ),
        );

        return $fields;
    }

    /**
     * Get products with id/name for a multiselect
     * 
     * @return array     An associative array of product ids and name
     */
    private static function _getProducts()
    {
        $productArgs = array(
            'post_type' => 'product',
            'posts_per_page' => -1
        );

        $products = get_posts($productArgs);

        $productOptions = array();

        foreach($products as $product) {
            $productOptions[$product->ID] = __($product->post_title, 'woocommerce-shippit');
        }

        return $productOptions;
    }

    public static function _getAttributes()
    {
        $productAttributes = array();

        $attributeTaxonomies = wc_get_attribute_taxonomies();
        foreach($attributeTaxonomies as $tax) {
            $productAttributes[$tax->attribute_name] = __($tax->attribute_name, 'woocommerce-shippit');
        }
        return $productAttributes;
    }

    public static function getSettings()
    {
        if (is_null(self::$_settingsCache)) {
            self::$_settingsCache = get_option('woocommerce_mamis_shippit_settings');
        }

        return self::$_settingsCache;
    }

    public static function getSetting($key)
    {
        $settings = self::getSettings();

        if ( isset($settings[$key]) ) {
            return $settings[$key];
        }

        return null;
    }
}