<?php

/**
* Mamis - https://www.mamis.com.au
* Copyright © Mamis 2023-present. All rights reserved.
* See https://www.mamis.com.au/license
*/

class Mamis_Shippit_Method extends WC_Shipping_Method
{
    /**
     * @var Mamis_Shippit_Api
     */
    protected $api;

    /**
     * @var Mamis_Shippit_Helper
     */
    protected $helper;

    /**
     * @var Mamis_Shippit_Log
     */
    protected $log;

    /**
     * @var array
     */
    protected $allowed_methods;

    /**
     * @var array
     */
    protected $max_timeslots;

    /**
     * @var string|null
     */
    protected $quote_enabled;

    /**
     * @var string|null
     */
    protected $filter_attribute;

    /**
     * @var string|null
     */
    protected $filter_attribute_code;

    /**
     * @var string|null
     */
    protected $filter_attribute_value;

    /**
     * @var string|null
     */
    protected $margin;

    /**
     * @var string|null
     */
    protected $margin_amount;

    /**
     * Constructor.
     */
    public function __construct(int $instance_id = 0)
    {
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];

        $this->id = 'mamis_shippit';
        $this->title = __('Shippit', 'woocommerce-shippit');
        $this->method_title = __('Shippit', 'woocommerce-shippit');
        $this->method_description = __('Have Shippit provide you with live quotes directly from the carriers. Simply enable live quoting and set your preferences to begin.');

        $settings = new Mamis_Shippit_Settings_Method();
        $this->instance_id = absint($instance_id);
        $this->instance_form_fields = $settings->getFields();

        $this->api = new Mamis_Shippit_Api();
        $this->log = new Mamis_Shippit_Log(['area' => 'live-quote']);
        $this->helper = new Mamis_Shippit_Helper();

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
        $this->title                   = $this->get_option('title');
        $this->allowed_methods         = $this->get_option('allowed_methods');
        $this->max_timeslots           = $this->get_option('max_timeslots');
        $this->filter_attribute        = $this->get_option('filter_attribute');
        $this->filter_attribute_code   = $this->get_option('filter_attribute_code');
        $this->filter_attribute_value  = $this->get_option('filter_attribute_value');
        $this->margin                  = $this->get_option('margin');
        $this->margin_amount           = $this->get_option('margin_amount');

        wp_enqueue_script(
            'shippit-live-script',
            plugin_dir_url(__DIR__) . '/assets/js/shippit-live-quote.js',
            array('jquery'),
            '1.0',
            false
        );

        // Add action hook to save the shipping method instance settings when they saved
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Add shipping method.
     *
     * Add shipping method to WooCommerce.
     *
     */
    public static function add_shipping_method($methods)
    {
        if (class_exists('Mamis_Shippit_Method')) {
            $methods['mamis_shippit'] = 'Mamis_Shippit_Method';
        }

        return $methods;
    }

    /**
     * Calculate shipping.
     *
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = array())
    {
        // Check if the module is enabled and used for shipping quotes
        if (get_option('wc_settings_shippit_enabled') != 'yes') {
            return;
        }

        // Ensure we have a shipping method available for use
        if (empty($this->allowed_methods)) {
            return;
        }

        $quoteDestination = $package['destination'];
        $quoteContents = $package['contents'];

        // Check if we can ship the products by attribute filtering
        if ($this->canShipEnabledAttributes($quoteContents) === false) {
            return;
        }

        $this->fetchQuotes($quoteDestination, $quoteContents);
    }

    /**
     * Perform a request for a shipping quotes based on the destination + contents provided
     *
     * @param array $quoteDestination
     * @param array $quoteContents
     * @return void
     */
    protected function fetchQuotes($quoteDestination, $quoteContents)
    {
        $isPriorityAvailable = in_array('priority', $this->allowed_methods);
        $isExpressAvailable = in_array('express', $this->allowed_methods);
        $isStandardAvailable = in_array('standard', $this->allowed_methods);

        $dropoffSuburb = $quoteDestination['city'];
        $dropoffPostcode = $quoteDestination['postcode'];
        $dropoffState = $quoteDestination['state'];
        $dropoffCountryCode = $quoteDestination['country'];

        // Only make a live quote request if required fields are present
        if (empty($dropoffSuburb)) {
            $this->log->debug(
                'A suburb is required for a live quote'
            );

            return;
        }
        elseif (empty($dropoffPostcode)) {
            $this->log->debug(
                'A postcode is required for a live quote'
            );

            return;
        }
        elseif (empty($dropoffCountryCode)) {
            $this->log->debug(
                'A country is required for a live quote'
            );

            return;
        }

        $quoteData = array(
            'order_date' => '', // get all available dates
            'dropoff_address' => $this->getDropoffAddress($quoteDestination),
            'dropoff_suburb' => $dropoffSuburb,
            'dropoff_postcode' => $dropoffPostcode,
            'dropoff_state' => $dropoffState,
            'dropoff_country_code' => $dropoffCountryCode,
            'parcel_attributes' => $this->getParcelAttributes($quoteContents),
            'dutiable_amount' => WC()->cart->get_cart_contents_total(),
        );

        $shippingQuotes = $this->api->getQuote($quoteData);

        if ($shippingQuotes) {
            foreach ($shippingQuotes as $shippingQuote) {
                if ($shippingQuote->success) {
                    switch ($shippingQuote->service_level) {
                        case 'priority':
                            if ($isPriorityAvailable) {
                                $this->addPriorityQuote($shippingQuote);
                            }

                            break;
                        case 'express':
                            if ($isExpressAvailable) {
                                $this->addExpressQuote($shippingQuote);
                            }

                            break;
                        case 'standard':
                            if ($isStandardAvailable) {
                                $this->addStandardQuote($shippingQuote);
                            }

                            break;
                    }
                }
            }
        }
        else {
            return false;
        }
    }

    /**
     * Retrieve the parcel attributes from the quote contents
     *
     * @param array $quoteContents
     * @return array
     */
    protected function getParcelAttributes($quoteContents)
    {
        $parcelAttributes = [];

        foreach ($quoteContents as $quoteItem) {
            $parcel = [];

            // If product is variation, load variation ID
            if ($quoteItem['variation_id']) {
                $cartItem = wc_get_product($quoteItem['variation_id']);
            }
            else {
                $cartItem = wc_get_product($quoteItem['product_id']);
            }

            $itemWeight = $cartItem->get_weight();
            $itemHeight = $cartItem->get_height();
            $itemLength = $cartItem->get_length();
            $itemWidth = $cartItem->get_width();

            $parcel['qty'] = $quoteItem['quantity'];

            if (!empty($itemWeight)) {
                $parcel['weight'] = $this->helper->convertWeight($itemWeight);
            }
            else {
                // stub weight to 0.2kg
                $parcel['weight'] = 0.2;
            }

            if (
                !defined('SHIPPIT_IGNORE_ITEM_DIMENSIONS')
                || !SHIPPIT_IGNORE_ITEM_DIMENSIONS
            ) {
                if (!empty($itemHeight)) {
                    $parcel['depth'] = $this->helper->convertDimension($itemHeight);
                }

                if (!empty($itemLength)) {
                    $parcel['length'] = $this->helper->convertDimension($itemLength);
                }

                if (!empty($itemWidth)) {
                    $parcel['width'] = $this->helper->convertDimension($itemWidth);
                }
            }

            $parcelAttributes[] = $parcel;
        }

        return $parcelAttributes;
    }

    /**
     * Get the dropoff address value for a quote
     *
     * @param array $quoteDestination
     * @return string|null
     */
    protected function getDropoffAddress($quoteDestination)
    {
        $addresses = [
            $quoteDestination['address'],
            $quoteDestination['address_2'],
        ];

        $addresses = array_filter($addresses, function ($address) {
            $address = trim($address);

            return !empty($address);
        });

        if (empty($addresses)) {
            return null;
        }

        return implode(', ', $addresses);
    }

    /**
     * Add a standard quote rate(s) to the list of available shipping methods
     *
     * @param object $shippingQuote
     * @return void
     */
    protected function addStandardQuote($shippingQuote)
    {
        foreach ($shippingQuote->quotes as $quote) {
            $quotePrice = $this->getQuotePrice($quote->price);

            $rate = array(
                // unique id for each rate
                'id' => 'Mamis_Shippit_' . $shippingQuote->service_level,
                'label' => ucwords($shippingQuote->service_level),
                'cost' => $quotePrice,
                'meta_data' => array(
                    'service_level' => $shippingQuote->service_level,
                    'courier_allocation' => $shippingQuote->courier_type,
                ),
            );

            $this->add_rate($rate);
        }
    }

    /**
     * Add a express quote rate(s) to the list of available shipping methods
     *
     * @param object $shippingQuote
     * @return void
     */
    protected function addExpressQuote($shippingQuote)
    {
        foreach ($shippingQuote->quotes as $quote) {
            $quotePrice = $this->getQuotePrice($quote->price);

            $rate = array(
                'id' => 'Mamis_Shippit_' . $shippingQuote->service_level,
                'label' => ucwords($shippingQuote->service_level),
                'cost' => $quotePrice,
                'meta_data' => array(
                    'service_level' => $shippingQuote->service_level,
                    'courier_allocation' => $shippingQuote->courier_type,
                ),
            );

            $this->add_rate($rate);
        }
    }

    /**
     * Add a priority quote rate(s) to the list of available shipping methods
     *
     * @param object $shippingQuote
     * @return void
     */
    protected function addPriorityQuote($shippingQuote)
    {
        $timeSlotCount = 0;

        foreach ($shippingQuote->quotes as $priorityQuote) {
            if (!empty($this->max_timeslots) && $this->max_timeslots <= $timeSlotCount) {
                break;
            }

            // Increase the timeslot count
            $timeSlotCount++;

            $quotePrice = $this->getQuotePrice($priorityQuote->price);

            $rate = array(
                'id' => sprintf(
                    'Mamis_Shippit_%s_%s_%s',
                    $shippingQuote->service_level,
                    $priorityQuote->delivery_date,
                    $priorityQuote->delivery_window
                ),
                'label' => sprintf(
                    'Scheduled - Delivered %s between %s',
                    date('d/m/Y', strtotime($priorityQuote->delivery_date)),
                    $priorityQuote->delivery_window_desc
                ),
                'cost' => $quotePrice,
                'meta_data' => array(
                    'service_level' => $shippingQuote->service_level,
                    'courier_allocation' => $priorityQuote->courier_type,
                    'delivery_date' => $priorityQuote->delivery_date,
                    'delivery_window' => $priorityQuote->delivery_window
                ),
            );

            $this->add_rate($rate);
        }
    }

    /**
     * Get the quote price, including the margin amount
     * @param  float $quotePrice The quote amount
     * @return float             The quote amount, with margin
     *                           if applicable
     */
    protected function getQuotePrice($quotePrice)
    {
        switch ($this->margin) {
            case 'yes-fixed':
                $quotePrice += (float) $this->margin_amount;
                break;
            case 'yes-percentage':
                $quotePrice *= (1 + ( (float) $this->margin_amount / 100));
        }

        // ensure we get the lowest price, but not below 0.
        $quotePrice = max(0, $quotePrice);

        return $quotePrice;
    }

    /**
     * Determine if the quote package content contains items we can quote on
     *
     * @param array $package
     * @return boolean
     */
    protected function canShipEnabledAttributes($products)
    {
        if ($this->filter_attribute === 'no') {
            return true;
        }

        $attributeCode = $this->filter_attribute_code;

        // Check if there is an attribute code set
        if (empty($attributeCode)) {
            return true;
        }

        $attributeValue = $this->filter_attribute_value;

        // Check if there is an attribute value set
        if (empty($attributeValue)) {
            return true;
        }

        foreach ($products as $product) {
            $productObject = new WC_Product($product['product_id']);
            $productAttributeValue = $productObject->get_attribute($attributeCode);

            if (strpos($productAttributeValue, $attributeValue) === false) {
                $this->log->info(
                    'A product in the cart does not match enabled filter attributes, skipping quoting'
                );

                return false;
            }
        }

        $this->log->debug(
            'The products in the cart matches enabled filter attributes'
        );

        return true;
    }

    /**
     * Checks if the Shippit Live Quote method is enabled
     *
     * @return boolean
     */
    private function isLiveQuotesEnabled(): bool
    {
        $zones = WC_Shipping_Zones::get_zones();

        foreach ($zones as $zone) {
            $shippingMethods = $zone['shipping_methods'];

            foreach ($shippingMethods as $shippingMethod) {
                if ($shippingMethod->id != 'mamis_shippit') {
                    continue;
                }

                return $shippingMethod->id = 'mamis_shippit'
                    && $shippingMethod->enabled == 'yes';
            }
        }
    }
}
