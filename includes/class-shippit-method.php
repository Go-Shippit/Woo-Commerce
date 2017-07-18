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

class Mamis_Shippit_Method extends WC_Shipping_Method
{
    protected $api;
    protected $helper;

    /**
     * Constructor.
     */
    public function __construct($instance_id = 0)
    {
        $this->api = new Mamis_Shippit_Api();
        $this->log = new Mamis_Shippit_Log();
        $this->helper = new Mamis_Shippit_Helper();

        $settings = new Mamis_Shippit_Settings_Method();

        $this->id                   = 'mamis_shippit';
        $this->instance_id          = absint($instance_id);
        $this->instance_form_fields = $settings->getFields(true);
        $this->title                = __('Shippit', 'woocommerce-shippit');
        $this->method_title         = __('Shippit', 'woocommerce-shippit');
        $this->method_description   = __('Have Shippit provide you with live quotes directly from the carriers. Simply enable live quoting and set your preferences to begin.');
        $this->supports              = array(
            'shipping-zones',
            'instance-settings',
            // Disable instance modal settings due to array not saving correctly
            // https://github.com/bobbingwide/woocommerce/commit/1e8d9d4c95f519df090e3ec94d8ea08eb8656c9f
            // 'instance-settings-modal',
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

        // Use property "quote_enabled", as "enabled" is used by the parent method
        $this->quote_enabled           = $this->get_option('enabled');

        $this->title                   = $this->get_option('title');
        $this->allowed_methods         = $this->get_option('allowed_methods');
        $this->max_timeslots           = $this->get_option('max_timeslots');
        $this->filter_enabled          = 'no'; // depreciated
        $this->filter_enabled_products = array(); // depreciated
        $this->filter_attribute        = $this->get_option('filter_attribute');
        $this->filter_attribute_code   = $this->get_option('filter_attribute_code');
        $this->filter_attribute_value  = $this->get_option('filter_attribute_value');
        $this->margin                  = $this->get_option('margin');
        $this->margin_amount           = $this->get_option('margin_amount');

        // *****************
        // Shipping Method
        // *****************

        // *****************
        // Shipping Method Save Event
        // *****************

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
        if (get_option('wc_settings_shippit_enabled') != 'yes'
            || $this->quote_enabled != 'yes') {
            return;
        }

        // Ensure we have a shipping method available for use
        if (empty($this->allowed_methods)) {
            return;
        }

        $quoteDestination = $package['destination'];
        $quoteCart = $package['contents'];

        // error_log(print_r($package, true));

        // Check if we can ship the products by enabled filtering
        if (!$this->_canShipEnabledProducts($package)) {
            return;
        }

        // Check if we can ship the products by attribute filtering
        if (!$this->_canShipEnabledAttributes($package)) {
            return;
        }

        // $this->_getItemAttributes($package);

        $this->_processShippingQuotes($quoteDestination, $quoteCart);
    }

    private function getParcelAttributes($items)
    {
        $itemDetails = array();

        foreach ($items as $cartItemId => $item) {
            $itemDetail = array();

            // If product is variation, load variation ID
            if ($item['variation_id']) {
                $cartItem = wc_get_product($item['variation_id']);
            }
            else {
                $cartItem = wc_get_product($item['product_id']);
            }

            $itemWeight = $cartItem->get_weight();
            $itemHeight = $cartItem->get_height();
            $itemLength = $cartItem->get_length();
            $itemWidth = $cartItem->get_width();

            $itemDetail['qty'] = $item['quantity'];

            if (!empty($itemWeight)) {
                $itemDetail['weight'] = $this->helper->convertWeight($itemWeight);
            }
            else {
                // stub weight to 0.2kg
                $itemDetail['weight'] = 0.2;
            }

            if (!defined('SHIPPIT_IGNORE_ITEM_DIMENSIONS')
                || !SHIPPIT_IGNORE_ITEM_DIMENSIONS) {
                if (!empty($itemHeight)) {
                    $itemDetail['depth'] = $this->helper->convertDimension($itemHeight);
                }

                if (!empty($itemLength)) {
                    $itemDetail['length'] = $this->helper->convertDimension($itemLength);
                }

                if (!empty($itemWidth)) {
                    $itemDetail['width'] = $this->helper->convertDimension($itemWidth);
                }
            }

            $itemDetails[] = $itemDetail;
        }

        return $itemDetails;
    }

    private function _processShippingQuotes($quoteDestination, $quoteCart)
    {
        $isPriorityAvailable = in_array('priority', $this->allowed_methods);
        $isExpressAvailable = in_array('express', $this->allowed_methods);
        $isStandardAvailable = in_array('standard', $this->allowed_methods);

        $dropoffSuburb = $quoteDestination['city'];
        $dropoffPostcode = $quoteDestination['postcode'];
        $dropoffState = $quoteDestination['state'];
        $items = WC()->cart->get_cart();

        $quoteData = array(
            'order_date' => '', // get all available dates
            'dropoff_suburb' => $dropoffSuburb,
            'dropoff_postcode' => $dropoffPostcode,
            'dropoff_state' => $dropoffState,
            'parcel_attributes' => $this->getParcelAttributes($items)
        );

        $shippingQuotes = $this->api->getQuote($quoteData);

        if ($shippingQuotes) {
            foreach ($shippingQuotes as $shippingQuote) {
                if ($shippingQuote->success) {
                    switch ($shippingQuote->service_level) {
                        case 'priority':
                            if ($isPriorityAvailable) {
                                $this->_addPriorityQuote($shippingQuote);
                            }

                            break;
                        case 'express':
                            if ($isExpressAvailable) {
                                $this->_addExpressQuote($shippingQuote);
                            }

                            break;
                        case 'standard':
                            if ($isStandardAvailable) {
                                $this->_addStandardQuote($shippingQuote);
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

    private function _addStandardQuote($shippingQuote)
    {
        foreach ($shippingQuote->quotes as $quote) {
            $quotePrice = $this->_getQuotePrice($quote->price);

            $rate = array(
                'id'    => 'Mamis_Shippit_' . $shippingQuote->courier_type,
                'label' => 'Standard',
                'cost'  => $quotePrice,
            );

            $this->add_rate($rate);
        }
    }

    private function _addExpressQuote($shippingQuote)
    {
        foreach ($shippingQuote->quotes as $quote) {
            $quotePrice = $this->_getQuotePrice($quote->price);

            $rate = array(
                'id'    => 'Mamis_Shippit_' . $shippingQuote->courier_type,
                'label' => 'Express',
                'cost'  => $quotePrice,
            );

            $this->add_rate($rate);
        }
    }

    private function _addPriorityQuote($shippingQuote)
    {
        $timeSlotCount = 0;

        foreach ($shippingQuote->quotes as $premiumQuote) {
            if (!empty($this->max_timeslots) && $this->max_timeslots <= $timeSlotCount) {
                break;
            }

            if (property_exists($premiumQuote, 'delivery_date')
                && property_exists($premiumQuote, 'delivery_window')
                && property_exists($premiumQuote, 'delivery_window_desc')) {
                $timeSlotCount++;
                $carrierTitle = $shippingQuote->courier_type;
                $method = $shippingQuote->courier_type . '_' . $premiumQuote->delivery_date . '_' . $premiumQuote->delivery_window;
                $premiumQuoteDeliveryDate = $premiumQuote->delivery_date;
                $premiumQuoteDeliveryDate = date('d/m/Y',strtotime($premiumQuoteDeliveryDate));
                $methodTitle = 'Scheduled' . ' - Delivered ' . $premiumQuoteDeliveryDate. ' between ' . $premiumQuote->delivery_window_desc;
            }
            else {
                $carrierTitle = $shippingQuote->courier_type;
                $method = $shippingQuote->courier_type;
                $methodTitle = 'Scheduled';
            }

            $quotePrice = $this->_getQuotePrice($premiumQuote->price);

            $rate = array(
                'id'    => 'Mamis_Shippit_'.$carrierTitle .'_' . $premiumQuote->delivery_date . '_' . $premiumQuote->delivery_window,
                'label' => $methodTitle,
                'cost'  => $quotePrice,
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
    private function _getQuotePrice($quotePrice)
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
     * Checks if we can ship the products in the cart
     *
     * @depreciated - this functionality is only available on
     * the legacy shipping method - it will be removed in Q1 2018
     */
    private function _canShipEnabledProducts($package)
    {
        if ($this->filter_enabled == 'no') {
            return true;
        }

        if ($this->filter_enabled_products == null) {
            return false;
        }

        $allowedProducts = $this->filter_enabled_products;

        $products = $package['contents'];
        $productIds = array();

        foreach ($products as $itemKey => $product) {
            $productIds[] = $product['product_id'];
        }

        if (!empty($allowedProducts)) {
            // If item is not enabled return false
            if ($productIds != array_intersect($productIds, $allowedProducts)) {
                $this->log->add(
                    'Can Ship Enabled Products',
                    'Returning false'
                );

                return false;
            }
        }

        $this->log->add(
            'Can Ship Enabled Products',
            'Returning true'
        );

        return true;
    }

    private function _canShipEnabledAttributes($package)
    {
        if ($this->filter_attribute == 'no') {
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

        $products = $package['contents'];

        foreach ($products as $itemKey => $product) {
            $productObject = new WC_Product($product['product_id']);
            $productAttributeValue = $productObject->get_attribute($attributeCode);

            if (strpos($productAttributeValue, $attributeValue) === false) {
                $this->log->add(
                    'Can Ship Enabled Attributes',
                    'Returning false'
                );

                return false;
            }
        }

        $this->log->add(
            'Can Ship Enabled Attributes',
            'Returning true'
        );

        return true;
    }
}