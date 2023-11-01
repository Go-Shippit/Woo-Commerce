<?php

/**
 * Mamis - https://www.mamis.com.au
 * Copyright © Mamis 2023-present. All rights reserved.
 * See https://www.mamis.com.au/license
 */

class Mamis_Shippit_Settings_Method
{
    /**
     * Initialize the fields for the Shipping Method settings
     *
     * @return array
     */
    public function getFields()
    {
        $fields['title'] = [
            'title' => __('Title', 'woocommerce-shippit'),
            'type' => 'text',
            'default' => 'Shippit',
        ];

        $fields['allowed_methods'] = [
            'title' => __('Allowed Methods', 'woocommerce-shippit'),
            'id' => 'allowed_methods',
            'class' => 'wc-enhanced-select',
            'type' => 'multiselect',
            'default' => [
                'standard',
                'express',
                'priority'
            ],
            'options' => [
                'standard' => __('Standard', 'woocommerce-shippit'),
                'express' => __('Express', 'woocommerce-shippit'),
                'priority' => __('Priority', 'woocommerce-shippit'),
            ],
        ];

        $fields['max_timeslots'] = [
            'title' => __('Maximum Timeslots', 'woocommerce-shippit'),
            'description' => __('The maximum amount of timeslots to display', 'woocommerce-shippit'),
            'desc_tip' => true,
            'id' => 'max_timeslots',
            'class' => 'wc-enhanced-select',
            'default' => '',
            'type' => 'select',
            'options' => [
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
            ],
        ];

        $fields['filter_attribute'] = [
            'title' => __('Filter by product attributes', 'woocommerce-shippit'),
            'description' => __('Filter products that are enabled for quoting by shippit via their attributes', 'woocommerce-shippit'),
            'desc_tip' => true,
            'class' => 'wc-enhanced-select',
            'default' => 'no',
            'type' => 'select',
            'options' => [
                'no' => __('No', 'woocommerce-shippit'),
                'yes' => __('Yes', 'woocommerce-shippit'),
            ],
        ];

        $fields['filter_attribute_code'] = [
            'title' => __('Filter by attribute code', 'woocommerce-shippit'),
            'description' => __('The product attribute code', 'woocommerce-shippit'),
            'desc_tip' => true,
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'default' => '',
            'options' => wc_get_attribute_taxonomy_labels(),
        ];

        $fields['filter_attribute_value'] = [
            'title' => __('Filter by attribute value', 'woocommerce-shippit'),
            'description' => __('The product attribute value', 'woocommerce-shippit'),
            'desc_tip' => true,
            'default' => '',
            'type' => 'text',
        ];

        $fields['margin'] = [
            'title' => __('Margin'),
            'class' => 'wc-enhanced-select',
            'default' => 'no',
            'description' => __('Add a margin to the quoted shipping amounts', 'woocommerce-shippit'),
            'desc_tip' => true,
            'type' => 'select',
            'options' => [
                'no' => __('No', 'woocommerce-shippit'),
                'yes-percentage' => __('Yes - Percentage', 'woocommerce-shippit'),
                'yes-fixed' => __('Yes - Fixed Dollar Amount', 'woocommerce-shippit'),
            ],
        ];

        $fields['margin_amount'] = [
            'title' => __('Margin Amount', 'woocommerce-shippit'),
            'description' => __('Please enter a margin amount, in either a whole dollar amount (ie: 5.50) or a percentage amount (ie: 5)', 'woocommerce-shippit'),
            'desc_tip' => true,
            'default' => '',
            'type' => 'text',
        ];

        return $fields;
    }
}