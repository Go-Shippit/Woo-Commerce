<?php
require_once( plugin_dir_path( __FILE__ ) . 'api-helper.php');
require_once( plugin_dir_path( __FILE__ ) . 'sync.php');

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'Shippit_Shipping' ) ) return; // Stop if the class already exists


class Shippit_Shipping extends WC_Shipping_Method {

    /**
     * Configuration Helper
     */
    protected $helper;
    protected $api;

    /**
     * Constructor.
     */
    public function __construct() 
    {
        $this->id                   = 'mamis_shippit';
        $this->title                = __( 'Shippit', 'shippit' );
        $this->method_title         = __( 'Shippit', 'shippit' );
        $this->method_description   = __( 'Configure Shippit' ); 
        
        $this->init();
        // var_dump($this->filterEnabled);
        $sync = new Mamis_Shippit_Order_Sync();
        $sync->syncOrder();

    }

    /**
     * Initialize Shippit method.
     */
    function init() {

        $this->load_helper();
        $this->init_form_fields();
        $this->init_settings();

        $this->enabled             = $this->get_option('enabled');
        $this->shippit_api_key     = $this->settings['shippit_api_key'];
        $this->debug               = $this->settings['shippit_debug'];
        $this->allowed_methods     = $this->settings['shippit_allowed_methods'];
        $this->shippit_send_orders = $this->settings['shippit_send_orders'];
        $this->shippit_title       = $this->settings['shippit_title'];
        // $this->hide_shipping       = $this->settings['hide_other_shipping'];
        $this->allowedProducts     = $this->settings['shippit_allowed_products'];
        $this->filterEnabled       = $this->settings['shippit_filter_by_enabled'];
        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    function load_helper()
    {
        $this->api_helper = new Mamis_Shippit_Helper_Api();
    }

    /**
     * Init fields.
     *
     * Add fields to the Shippit settings page.
     *
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enabled', 'woocommerce' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable Shippit', 'shippit' ),
                'default'       => 'yes'
            ),
            'shippit_api_key' => array(
                'title'    => __( 'API Key', 'mamis_shippit' ),
                'desc'     => '',
                'id'       => 'shippit_api_key',
                'name'     => 'shippit_api_key',
                'type'     => 'text',
                'css'      => 'min-width:300px;',
            ),
            'shippit_debug' => array(
                'title'    => __( 'Debug', 'mamis_shippit' ),
                'id'       => 'shippit_debug',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __( 'No', 'mamis_shippit' ),
                    'yes' => __( 'Yes', 'mamis_shippit' ),
                ),
            ),
            'shippit_send_orders' => array(
                'title'    => __( 'Send All Orders to Shippit', 'mamis_shippit' ),
                'id'       => 'shippit_send_orders',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __( 'No', 'mamis_shippit' ),
                    'yes' => __( 'Yes', 'mamis_shippit' ),
                ),
            ),
            'shippit_title' => array(
                'title'    => __( 'Title', 'mamis_shippit' ),
                'desc'     => '',
                'id'       => 'shippit_title',
                'type'     => 'text',
                'css'      => 'min-width:300px;',
            ),
            'shippit_allowed_methods' => array(
                'title'    => __( 'Allowed Methods', 'mamis_shippit' ),
                'desc'     => '',
                'id'       => 'shippit_allowed_methods',
                'type'     => 'multiselect',
                'options'  => array(
                    'standard' => __( 'Standard', 'mamis_shippit'),
                    'premium'  => __( 'Premium', 'mamis_shippit'),
                    ),
                'css'      => 'min-width:300px;',
            ),
            'shippit_max_timeslots' => array(
                'title'    => __( 'Maximum Timeslots', 'mamis_shippit' ),
                'id'       => 'shippit_max_timeslots',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '',
                'type'     => 'select',
                'options'  => array(
                    '0' => __('-- No Max Timeslots --', 'mamis_shippit'),
                    '1' => __('1 Timeslots', 'mamis_shippit'),
                    '2' => __('2 Timeslots', 'mamis_shippit'),
                    '3' => __('3 Timeslots', 'mamis_shippit'),
                    '4' => __('4 Timeslots', 'mamis_shippit'),
                    '5' => __('5 Timeslots', 'mamis_shippit'),
                    '6' => __('6 Timeslots', 'mamis_shippit'),
                    '7' => __('7 Timeslots', 'mamis_shippit'),
                    '8' => __('8 Timeslots', 'mamis_shippit'),
                    '9' => __('9 Timeslots', 'mamis_shippit'),
                    '10' => __('10 Timeslots', 'mamis_shippit'),
                    '11' => __('11 Timeslots', 'mamis_shippit'),
                    '12' => __('12 Timeslots', 'mamis_shippit'),
                    '13' => __('13 Timeslots', 'mamis_shippit'),
                    '14' => __('14 Timeslots', 'mamis_shippit'),
                    '15' => __('15 Timeslots', 'mamis_shippit'),
                    '16' => __('16 Timeslots', 'mamis_shippit'),
                    '17' => __('17 Timeslots', 'mamis_shippit'),
                    '18' => __('18 Timeslots', 'mamis_shippit'),
                    '19' => __('19 Timeslots', 'mamis_shippit'),
                    '20' => __('20 Timeslots', 'mamis_shippit'),
                ),
            ),
            'shippit_filter_by_enabled' => array(
                'title'    => __( 'Filter by enabled products', 'mamis_shippit' ),
                'id'       => 'shippit_filter_by_enabled',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __( 'No', 'mamis_shippit' ),
                    'yes' => __( 'Yes', 'mamis_shippit' ),
                ),
            ),
            'shippit_allowed_products' => array(
                'title'    => __( 'Allowed products', 'mamis_shippit' ),
                'desc'     => '',
                'id'       => 'shippit_allowed_methods',
                'type'     => 'multiselect',
                'options'  => $this->getProducts(),
                'css'      => 'min-width:300px;',
            ),
            'woocommerce_ship_to_countries' => array(
                'title'    => __( 'Restrict shipping to Location(s)', 'woocommerce' ),
                'desc'     => sprintf( __( 'Choose which countries you want to ship to, or choose to ship to all <a href="%s">locations you sell to</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) ),
                'id'       => 'woocommerce_ship_to_countries',
                'default'  => '',
                'type'     => 'select',
                'class'    => 'wc-enhanced-select',
                'desc_tip' => false,
                'options'  => array(
                    ''         => __( 'Ship to all countries you sell to', 'woocommerce' ),
                    'all'      => __( 'Ship to all countries', 'woocommerce' ),
                    'specific' => __( 'Ship to specific countries only', 'woocommerce' )
                )
            ),
            'woocommerce_specific_ship_to_countries' => array(
                'title'   => __( 'Specific Countries', 'woocommerce' ),
                'desc'    => '',
                'id'      => 'woocommerce_specific_ship_to_countries',
                'css'     => '',
                'default' => '',
                'type'    => 'multi_select_countries'
            ),
            'shippit_show_method' => array(
                'title'    => __( 'Show Method if Not Applicable', 'mamis_shippit' ),
                'id'       => 'shippit_show_method',
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;',
                'default'  => '',
                'type'     => 'select',
                'options'  => array(
                    'no'  => __( 'No', 'mamis_shippit' ),
                    'yes' => __( 'Yes', 'mamis_shippit' ),
                ),
            ),
        );
    }

    public function getProducts() 
    {
        $args = array( 'post_type' => 'product', 'posts_per_page' => -1);

        $loop = new WP_Query( $args );

        $productList = array();

        while ( $loop->have_posts() ) : $loop->the_post(); 
            $productList[get_the_ID()] = __(get_the_title(), 'mamis_shippit');
        endwhile; 
        wp_reset_query(); 

        return $productList;
    }

    public function canShip() 
    {
        if($this->filterEnabled != 'yes') {
            return true;
        }

        $allowedProducts = $this->allowedProducts;

        $itemInCart = WC()->cart->get_cart();
        $itemIds = array();

        foreach($itemInCart as $item => $values) {      
            $itemIds[] = $values['product_id'];
        }

        if (count($allowedProducts) > 0) {
            if ($itemIds != array_intersect($itemIds, $allowedProducts)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calculate shipping.
     *
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package ) 
    {
        if($this->canShip()) {
            $this->_processShippingQuotes();
        }
    }

    public function _processShippingQuotes()
    {
        $allowedMethods = $this->allowed_methods;
        $api_key = $this->shippit_api_key;

        $isPremiumAvailable = in_array('premium', $allowedMethods);
        $isStandardAvailable = in_array('standard', $allowedMethods);

        $customerSuburb = WC()->customer->get_shipping_city();
        $customerPostcode = WC()->customer->get_shipping_postcode();
        $customerState = WC()->customer->get_shipping_state();
        $qty =  WC()->cart->cart_contents_count;

        $totalWeight = 1;

        if ( WC()->cart->cart_contents_weight == 0) {
            $totalWeight = 1;
        }
        else {
            $totalWeight = WC()->cart->cart_contents_weight;
        }

        $results = $this->api_helper->get_post_response($api_key, $customerSuburb, $customerPostcode, $customerState, $qty, $totalWeight);

        if( !$results ) {
            return;
        }

        foreach($results->response as $result) {
            if ($result->success) {
                if ($result->courier_type == 'Bonds'
                    && $isPremiumAvailable) {
                    $this->_addPremiumQuote($results, $result);
                }
                elseif ($result->courier_type != 'Bonds' 
                    && $isStandardAvailable) {
                    $this->_addStandardQuote($results, $result);
                }
            }
        }


    }

    public function _addStandardQuote($results, $result) 
    {
        foreach($result->quotes as $shippingQuote) {
            $shippingQuote->price;
            $rate = array(
                'id' => 'Mamis_Shippit_'.$result->courier_type.'_'.uniqid(),
                'label' => $result->courier_type,
                'cost' => $shippingQuote->price,
                'taxes' => false,
            );
            $this->add_rate($rate);
        }
    }

    public function _addPremiumQuote($results, $result) 
    {
        $timeSlotCount = 0;
        $maxTimeSlots = 10;

        foreach($result->quotes as $shippingQuote) {
            if (property_exists($shippingQuote, 'delivery_date')
                && property_exists($shippingQuote, 'delivery_window')
                && property_exists($shippingQuote, 'delivery_window_desc')) {
                // $timeSlotCount++;
                $carrierTitle = $result->courier_type;
                $method = $result->courier_type . '_' . $shippingQuote->delivery_date . '_' . $shippingQuote->delivery_window;
                $methodTitle = 'Premium' . ' - Delivered ' . $shippingQuote->delivery_date. ' Between ' . $shippingQuote->delivery_window_desc;
            }   
            else {
                $carrierTitle = $result->courier_type;
                $method = $result->courier_type;
                $methodTitle = 'Premium';
            }
            $rate = array(
                'id' => 'Mamis_Shippit_'.$carrierTitle .'_'. uniqid(),
                'label' => $methodTitle,
                'cost' => $shippingQuote->price,
                'taxes' => false,
            );
            $this->add_rate($rate);
        }
    }

}
