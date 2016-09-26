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
    private $api;
    private $s;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->api = new Mamis_Shippit_Api();
        $this->s = new Mamis_Shippit_Settings();
        $this->log = new Mamis_Shippit_Log();

        $this->id                   = 'mamis_shippit';
        $this->title                = __('Shippit', 'woocommerce-shippit');
        $this->method_title         = __('Shippit', 'woocommerce-shippit');
        $this->method_description   = __('Configure Shippit');

        $this->init();
    }

    /**
     * Initialize plugin parts.
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Load the settings form, but only when the settings form fields is required
        add_filter('woocommerce_settings_api_form_fields_mamis_shippit', array($this, 'init_form_fields'));
        
        $this->init_settings();
    
        // *****************
        // Shipping Method
        // *****************

        // add shipping method
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));

        // *****************
        // Shipping Method Save Event
        // *****************

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {
        $this->form_fields = $this->s->getFields();

        return $this->form_fields;
    }

    /**
     * Add shipping method.
     *
     * Add shipping method to WooCommerce.
     *
     */
    public function add_shipping_method($methods)
    {
        if (class_exists('Mamis_Shippit_Method')) {
            $methods[] = 'Mamis_Shippit_Method';
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
        if ($this->enabled != 'yes') {
            return;
        }

        $allowedMethods = $this->s->getSetting('allowed_methods');

        // Ensure we have a shipping method available for use
        if (empty($allowedMethods)) {
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

    private function _getItemDetails($items)
    {
        $itemDetails = array();

        foreach($items as $item => $values) {

            $_product = $values['data']->post;

            // If product is variation grab the variation details
            if ($values['variation_id']) {
                $cartItemDetails = wc_get_product( $values['variation_id'] );
            }

            else {
                $cartItemDetails = wc_get_product( $values['product_id'] );
            }

            $itemDetails[] = array(
                'product_id' => $values['product_id'],
                'weight' => $this->_wooWeightNormal($cartItemDetails->get_weight(), 'kg'),
                'height' => $this->_wooDimNormal($cartItemDetails->get_height(),'cm'),
                'length' => $this->_wooDimNormal($cartItemDetails->get_length(), 'cm'),
                'width' => $this->_wooDimNormal($cartItemDetails->get_width(), 'cm')
            );
        }

        return $itemDetails;
    }

    // https://gist.github.com/mbrennan-afa/1812521
    /**
    *
    * Normalise dimensions, unify to cm then convert to wanted unit value
    * $unit: 'inch', 'm', 'cm', 'm'
    * Usage: wooDimNormal(55, 'inch');
    *
    */
    private function _wooDimNormal($dim, $unit) {
        $wooDimUnit = strtolower($current_unit = get_option('woocommerce_dimension_unit'));
        $unit = strtolower($unit);
        if ($wooDimUnit !== $unit) {
            //Unify all units to cm first
            switch ($wooDimUnit) {
                case 'inch':
                    $dim *= 2.54;
                    break;
                case 'm':
                    $dim *= 100;
                    break;
                case 'mm':
                    $dim *= 0.1;
                    break;
            }
            //Output desired unit
            switch ($unit) {
                case 'inch':
                    $dim *= 0.3937;
                    break;
                case 'm':
                    $dim *= 0.01;
                    break;
                case 'mm':
                    $dim *= 10;
                    break;
            }
        }
        return $dim;
    }

    // https://gist.github.com/mbrennan-afa/1812521
    /**
    *
    * Normalise weight, unify to kg then convert to wanted to unit
    * $unit: 'g', 'kg', 'lbs'
    * Useage: wooWeightNormal(55,'lbs');
    *
    */
    private function _wooWeightNormal($weight, $unit) {
        $wooWeightUnit = strtolower($current_unit = get_option('woocommerce_weight_unit'));
        $unit = strtolower($unit);
        if ($wooWeightUnit !== $unit) {
            //Unify all units to kg first
            switch ($wooWeightUnit) {
                case 'g':
                    $weight *= 0.001;
                    break;
                case 'lbs':
                    $weight *= 0.4535;
                    break;
            }
            //Output desired unit
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

    private function _processShippingQuotes($quoteDestination, $quoteCart)
    {
        $isPremiumAvailable = in_array('premium', $this->s->getSetting('allowed_methods'));
        $isStandardAvailable = in_array('standard', $this->s->getSetting('allowed_methods'));

        $dropoffSuburb = $quoteDestination['city'];
        $dropoffPostcode = $quoteDestination['postcode'];
        $dropoffState = $quoteDestination['state'];

        $weight = WC()->cart->cart_contents_weight;
        
        $items = WC()->cart->get_cart();

        $itemsCart = $this->_getItemDetails($items);

        $quoteData = array(
            'order_date' => '', // get all available dates
            'dropoff_suburb' => $dropoffSuburb,
            'dropoff_postcode' => $dropoffPostcode,
            'dropoff_state' => $dropoffState,
            'parcel_attributes' => $itemsCart
        );

        $shippingQuotes = $this->api->getQuote($quoteData);

        if ($shippingQuotes) {
            foreach($shippingQuotes as $shippingQuote) {
                if ($shippingQuote->success) {
                    if ($shippingQuote->courier_type == 'Bonds'
                        && $isPremiumAvailable) {
                        $this->_addPremiumQuote($shippingQuote);
                    }
                    elseif ($shippingQuote->courier_type != 'Bonds'
                        && $isStandardAvailable) {
                        $this->_addStandardQuote($shippingQuote);
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
        foreach ($shippingQuote->quotes as $standardQuote) {
            $quotePrice = $this->_getQuotePrice($standardQuote->price);
            
            $rate = array(
                'id'    => 'Mamis_Shippit_' . $shippingQuote->courier_type,
                'label' => 'Standard',
                'cost'  => $quotePrice,
                'taxes' => false,
            );

            $this->add_rate($rate);
        }
    }

    private function _addPremiumQuote($shippingQuote)
    {
        $timeSlotCount = 0;
        $maxTimeSlots = $this->s->getSetting('max_timeslots');

        foreach ($shippingQuote->quotes as $premiumQuote) {
            if (!empty($maxTimeSlots) && $maxTimeSlots <= $timeSlotCount) {
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
                'taxes' => false,
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
        switch ($this->s->getSetting('margin')) {
            case 'yes-fixed':
                $quotePrice += (float) $this->s->getSetting('margin_amount');
                break;
            case 'yes-percentage':
                $quotePrice *= (1 + ( (float) $this->s->getSetting('margin_amount') / 100));
        }

        // ensure we get the lowest price, but not below 0.
        $quotePrice = max(0, $quotePrice);

        return $quotePrice;
    }

    /**
     * Checks if we can ship the products in the cart
     * @return [type] [description]
     */
    private function _canShipEnabledProducts($package)
    {
        if ($this->s->getSetting('filter_enabled') == 'no') {
            return true;
        }

        if ($this->s->getSetting('filter_enabled_products') == null) {
            return false;
        }

        $allowedProducts = $this->s->getSetting('filter_enabled_products');

        $products = $package['contents'];
        $productIds = array();

        foreach ($products as $itemKey => $product) {
            $productIds[] = $product['product_id'];
        }

        if (count($allowedProducts) > 0) {
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
        if ($this->s->getSetting('filter_attribute') == 'no') {
            return true;
        }

        $attributeCode = $this->s->getSetting('filter_attribute_code');

        // Check if there is an attribute code set
        if (empty($attributeCode)) {
            return true;
        }

        $attributeValue = $this->s->getSetting('filter_attribute_value');

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