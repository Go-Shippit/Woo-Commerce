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

class Mamis_Shippit_Settings
{
    private $_settingsCache = null;

    /**
     * Init fields.
     *
     * Add fields to the Shippit settings page.
     *
     */
    public function getFields()
    {
        // @TODO: Review if possible to remove global var for php5.5 support
        global $shippitOtherShippingMethods;

        $fields = array(
            'title' => array(
                'title' => __('Title', 'woocommerce-shippit'),
                'type' => 'text',
                'default' => 'Shippit',
            ),

            'allowed_methods' => array(
                'title' => __('Allowed Methods', 'woocommerce-shippit'),
                'id' => 'allowed_methods',
                'class' => 'wc-enhanced-select',
                'type' => 'multiselect',
                'default' => array(
                    'standard',
                    'priority',
                    'express,'
                ),
                'options' => array(
                    'standard' => __('Standard', 'woocommerce-shippit'),
                    'express' => __('Express', 'woocommerce-shippit'),
                    'priority' => __('Priority', 'woocommerce-shippit'),
                ),
            ),

            'max_timeslots' => array(
                'title' => __('Maximum Timeslots', 'woocommerce-shippit'),
                'description' => __('The maximum amount of timeslots to display', 'woocommerce-shippit'),
                'desc_tip' => true,
                'id' => 'max_timeslots',
                'class' => 'wc-enhanced-select',
                'default' => '',
                'type' => 'select',
                'options' => array(
                    '' => __('-- No Max Timeslots --', 'woocommerce-shippit'),
                    '1' => __('1 Timeslots', 'woocommerce-shippit'),
                    '2' => __('2 Timeslots', 'woocommerce-shippit'),
                    '3' => __('3 Timeslots', 'woocommerce-shippit'),
                    '4' => __('4 Timeslots', 'woocommerce-shippit'),
                    '5' => __('5 Timeslots', 'woocommerce-shippit'),
                    '6' => __('6 Timeslots', 'woocommerce-shippit'),
                    '7' => __('7 Timeslots', 'woocommerce-shippit'),
                    '8' => __('8 Timeslots', 'woocommerce-shippit'),
                    '9' => __('9 Timeslots', 'woocommerce-shippit'),
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
                'title' => __('Filter by enabled products', 'woocommerce-shippit'),
                'description' => __('Filter products that are enabled for quoting by shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
               ),
            ),

            'filter_enabled_products' => array(
                'title' => __('Enabled Products', 'woocommerce-shippit'),
                'description' => __('The products enabled for quoting by Shippit', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'type' => 'multiselect',
                'options' => $this->_getProducts(),
            ),

            'filter_attribute' => array(
                'title' => __('Filter by product attributes', 'woocommerce-shippit'),
                'description' => __('Filter products that are enabled for quoting by shippit via their attributes', 'woocommerce-shippit'),
                'desc_tip' => true,
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes' => __('Yes', 'woocommerce-shippit'),
               ),
            ),

            'filter_attribute_code' => array(
                'title' => __('Filter by attribute code', 'woocommerce-shippit'),
                'description' => __('The product attribute code', 'woocommerce-shippit'),
                'desc_tip' => true,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => $this->_getAttributes(),
            ),

            'filter_attribute_value' => array(
                'title' => __('Filter by attribute value', 'woocommerce-shippit'),
                'description' => __('The product attribute value', 'woocommerce-shippit'),
                'desc_tip' => true,
                'type' => 'text',
            ),

            'margin' => array(
                'title' => __('Margin'),
                'class' => 'wc-enhanced-select',
                'default' => 'no',
                'description' => __('Add a margin to the quoted shipping amounts', 'woocommerce-shippit'),
                'desc_tip' => true,
                'type' => 'select',
                'options' => array(
                    'no' => __('No', 'woocommerce-shippit'),
                    'yes-percentage' => __('Yes - Percentage', 'woocommerce-shippit'),
                    'yes-fixed' => __('Yes - Fixed Dollar Amount', 'woocommerce-shippit'),
               ),
            ),

            'margin_amount' => array(
                'title' => __('Margin Amount', 'woocommerce-shippit'),
                'description' => __('Please enter a margin amount, in either a whole dollar amount (ie: 5.50) or a percentage amount (ie: 5)', 'woocommerce-shippit'),
                'desc_tip' => true,
                'type' => 'text',
            ),
        );

        return $fields;
    }

    /**
     * Get products with id/name for a multiselect
     *
     * @return array     An associative array of product ids and name
     */
    private function _getProducts()
    {
        $productArgs = array(
            'post_type' => 'product',
            'posts_per_page' => -1
        );

        $products = get_posts($productArgs);

        $productOptions = array();

        foreach ($products as $product) {
            $productOptions[$product->ID] = __($product->post_title, 'woocommerce-shippit');
        }

        return $productOptions;
    }

    public function _getAttributes()
    {
        $productAttributes = array();

        $attributeTaxonomies = wc_get_attribute_taxonomies();

        foreach ($attributeTaxonomies as $tax) {
            $productAttributes[$tax->attribute_name] = __($tax->attribute_name, 'woocommerce-shippit');
        }

        return $productAttributes;
    }

    public function getSettings()
    {
        if (is_null($this->_settingsCache)) {
            $this->_settingsCache = get_option('woocommerce_mamis_shippit_settings');
        }

        return $this->_settingsCache;
    }

    public function getSetting($key)
    {
        $settings = $this->getSettings();

        if ( isset($settings[$key]) ) {
            return $settings[$key];
        }

        return null;
    }

    /**
     * Convert the dimension to a different unit size
     *
     * based on https://gist.github.com/mbrennan-afa/1812521
     *
     * @param  float $dimension  The dimension to be converted
     * @param  string $unit      The unit to be converted to
     * @return float             The converted dimension
     */
    public function convertDimension($dimension, $unit = 'm')
    {
        $dimensionCurrentUnit = get_option('woocommerce_dimension_unit');
        $dimensionCurrentUnit = strtolower($dimensionCurrentUnit);
        $unit = strtolower($unit);

        if ($dimensionCurrentUnit !== $unit) {
            // Unify all units to cm first
            switch ($dimensionCurrentUnit) {
                case 'inch':
                    $dimension *= 2.54;
                    break;
                case 'm':
                    $dimension *= 100;
                    break;
                case 'mm':
                    $dimension *= 0.1;
                    break;
            }

            // Output desired unit
            switch ($unit) {
                case 'inch':
                    $dimension *= 0.3937;
                    break;
                case 'm':
                    $dimension *= 0.01;
                    break;
                case 'mm':
                    $dimension *= 10;
                    break;
            }
        }

        return $dimension;
    }

    /**
     * Convert the weight to a different unit size
     *
     * based on https://gist.github.com/mbrennan-afa/1812521
     *
     * @param  float $weight     The weight to be converted
     * @param  string $unit      The unit to be converted to
     * @return float             The converted weight
     */
    public function convertWeight($weight, $unit = 'kg')
    {
        $weightCurrentUnit = get_option('woocommerce_weight_unit');
        $weightCurrentUnit = strtolower($weightCurrentUnit);
        $unit = strtolower($unit);

        if ($weightCurrentUnit !== $unit) {
            // Unify all units to kg first
            switch ($weightCurrentUnit) {
                case 'g':
                    $weight *= 0.001;
                    break;
                case 'lbs':
                    $weight *= 0.4535;
                    break;
            }

            // Output desired unit
            switch ($unit) {
                case 'g':
                    $weight *= 1000;
                    break;
                case 'lbs':
                    $weight *= 2.204;
                    break;
            }
        }

        return $weight;
    }
}