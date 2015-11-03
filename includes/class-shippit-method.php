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

        $this->id                   = 'mamis_shippit';
        $this->method_title         = __('Shippit', 'woocommerce-shippit');
        $this->method_description   = __('Configure Shippit');

        $this->init();

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Initialize plugin parts.
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->shippit_api_key           = $this->s->getSetting('api_key');
        $this->debug                     = $this->s->getSetting('debug');
        $this->allowed_methods           = $this->s->getSetting('allowed_methods');
        $this->send_all_orders           = $this->s->getSetting('send_orders');
        $this->title                     = $this->s->getSetting('title');
        $this->max_timeslots             = $this->s->getSetting('max_timeslots');
        $this->filter_enabled            = $this->s->getSetting('filter_enabled');
        $this->filter_enabled_products   = $this->s->getSetting('filter_enabled_products');
        $this->filter_attribute          = $this->s->getSetting('filter_attribute');
        $this->filter_attribute_code     = $this->s->getSetting('filter_attribute_code');
        $this->filter_attribute_value    = $this->s->getSetting('filter_attribute_value');
        
        // *****************
        // Shipping Method
        // *****************

        // add shipping method
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
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

    public function init_form_fields()
    {
        // parent::init_form_fields();
        $this->form_fields = Mamis_Shippit_Settings::getFields();
    }

    public function init_shippit_settings()
    {
        $this->shippit_api_key           = $this->s->getSetting('api_key');
        $this->debug                     = $this->s->getSetting('debug');
        $this->allowed_methods           = $this->s->getSetting('allowed_methods');
        $this->shippit_send_all_orders   = $this->s->getSetting('send_orders');
        $this->shippit_title             = $this->s->getSetting('title');
        $this->max_timeslots             = $this->s->getSetting('max_timeslots');
        $this->filter_enabled            = $this->s->getSetting('filter_enabled');
        $this->filter_enabled_products   = $this->s->getSetting('filter_enabled_products');
        $this->filter_attribute          = $this->s->getSetting('filter_attribute');
        $this->filter_attribute_code     = $this->s->getSetting('filter_attribute_code');
        $this->filter_attribute_value    = $this->s->getSetting('filter_attribute_value');
    }

    /**
     * Calculate shipping.
     *
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package)
    {
        error_log('calculate_shipping');

        // Check if the module is enabled and used for shipping quotes
        if ($this->enabled != 'yes') {// || !$this->allowed_methods) {
            return;
        }
        
        $quoteDestination = $package['destination'];
        $quoteCart = $package['contents'];

        // Check if we can ship the products by enabled filtering
        if (!$this->canShipEnabledProducts($package)) {
            return;
        }

        // Check if we can ship the products by attribute filtering
        if (!$this->canShipEnabledAttributes($package)) {
            return;
        }

        $this->_processShippingQuotes($quoteDestination, $quoteCart);
    }

    private function _processShippingQuotes($quoteDestination, $quoteCart)
    {
        $allowedMethods = $this->allowed_methods;
        $apiKey = $this->shippit_api_key;
        $debug = $this->debug;

        $isPremiumAvailable = in_array('premium', $this->allowed_methods);
        $isStandardAvailable = in_array('standard', $this->allowed_methods);

        $dropoff_suburb = $quoteDestination['city'];
        $dropoff_postcode = $quoteDestination['postcode'];
        $dropoff_state = $quoteDestination['state'];

        $qty = WC()->cart->cart_contents_count;
        $weight = WC()->cart->cart_contents_weight;

        if ($weight == 0) {
            // override the weight to 1kg
            $weight = 1;
        }

        $quoteData = array(
            'order_date' => '', // get all available dates
            'dropoff_suburb' => $dropoff_suburb,
            'dropoff_postcode' => $dropoff_postcode,
            'dropoff_state' => $dropoff_state,
            'parcel_attributes' => array(
                array(
                    'qty' => $qty,
                    'weight' => $weight
                )
            ),
        );

        try {
            $shippingQuotes = $this->api->getQuote($quoteData);
        }
        catch (Exception $e) {
            // if ($this->helper->isDebugActive() && $this->bugsnag) {
            //     $this->bugsnag->notifyError('API - Quote Request', $e->getMessage());
            // }
            error_log($e);

            return false;
        }

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
            $rate = array(
                'id'    => 'Mamis_Shippit_' . $shippingQuote->courier_type,// . '_' . uniqid(),
                'label' => 'Couriers Please',
                'cost'  => $standardQuote->price,
                'taxes' => false,
            );

            $this->add_rate($rate);
        }
    }

    private function _addPremiumQuote($shippingQuote)
    {
        $timeSlotCount = 0;
        $maxTimeSlots = $this->max_timeslots;

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
                $methodTitle = 'Premium' . ' - Delivered ' . $premiumQuote->delivery_date. ' Between ' . $premiumQuote->delivery_window_desc;
            }
            else {
                $carrierTitle = $shippingQuote->courier_type;
                $method = $shippingQuote->courier_type;
                $methodTitle = 'Premium';
            }

            $rate = array(
                'id'    => 'Mamis_Shippit_'.$carrierTitle .'_' . $premiumQuote->delivery_date . '_' . $premiumQuote->delivery_window,// . '_' . uniqid(),
                'label' => $methodTitle,
                'cost'  => $premiumQuote->price,
                'taxes' => false,
            );

            $this->add_rate($rate);
        }
    }



    /**
     * Checks if we can ship the products in the cart
     * @return [type] [description]
     */
    public function canShipEnabledProducts($package)
    {
        if ($this->filter_enabled == 'no') {
            return true;
        }

        $allowedProducts = $this->filter_enabled_products;

        $products = $package['contents'];
        $productIds = array();

        foreach ($products as $itemKey => $product) {
            $productIds[] = $product['product_id'];
        }

        if (count($allowedProducts) > 0) {
            // If item is not enabled return false
            if ($productIds != array_intersect($productIds, $allowedProducts)) {
                error_log('canShipEnabledProducts is passing false');
                return false;
            }
        }

        error_log('canShipEnabledProducts is passing true');

        return true;
    }

    public function canShipEnabledAttributes($package)
    {
        if ($this->filter_attribute == 'no') {
            return true;
        }

        $attributeCode = $this->filter_attribute_code;
        $attributeValue = $this->filter_attribute_value;

        // @todo - use wp_query to get product ids matching the query
        // if (strpos($attributeValue, '*') !== FALSE) {
        //     $attributeValue = str_replace('*', '%', $attributeValue);
        // }

        $products = $package['contents'];

        // @todo use the package from calculate_shipping to grab cart contents
        foreach ($products as $itemKey => $product) {
            $productObject = new WC_Product($product['product_id']);
            $productAttributeValue = $productObject->get_attribute($attributeCode);

            error_log($productAttributeValue);

            if (strpos($productAttributeValue, $attributeValue) === false) {
                error_log('canShipEnabledAttributes is passing false');
                return false;
            }
        }

        error_log('canShipEnabledAttributes is passing true');

        return true;
    }
}