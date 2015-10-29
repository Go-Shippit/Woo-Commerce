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
        // $sync = new Mamis_Shippit_Order_Sync();

        // $sync->syncOrders();
    }

    /**
     * Initialize Shippit method.
     */
    function init() {

        $this->load_helper();
        $this->init_form_fields();
        $this->init_settings();

        $this->enabled                 = $this->get_option('enabled');
        $this->shippit_api_key         = $this->settings['shippit_api_key'];
        $this->debug                   = $this->settings['shippit_debug'];
        $this->allowed_methods         = $this->settings['shippit_allowed_methods'];
        $this->shippit_send_all_orders = $this->settings['shippit_send_orders'];
        $this->shippit_title           = $this->settings['shippit_title'];
        $this->max_timeslots           = $this->settings['shippit_max_timeslots'];
        // $this->hide_shipping       = $this->settings['hide_other_shipping'];
        $this->allowedProducts         = $this->settings['shippit_allowed_products'];
        $this->filterEnabled           = $this->settings['shippit_filter_by_enabled'];

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

            /*
            * @todo Filter by product attribute
            */

            // 'shippit_filter_by_meta' => array(
            //     'title'    => __( 'Filter by product meta', 'mamis_shippit' ),
            //     'id'       => 'shippit_filter_by_meta',
            //     'class'    => 'wc-enhanced-select',
            //     'css'      => 'min-width:300px;',
            //     'default'  => '',
            //     'type'     => 'select',
            //     'options'  => array(
            //         'no'  => __( 'No', 'mamis_shippit' ),
            //         'yes' => __( 'Yes', 'mamis_shippit' ),
            //     ),
            // ),
            // 'shippit_allowed_meta' => array(
            //     'title'    => __( 'Product attribute code', 'mamis_shippit' ),
            //     'desc'     => '',
            //     'id'       => 'shippit_allowed_methods',
            //     'type'     => 'select',
            //     'options'  => $this->getProductMeta(),
            //     'css'      => 'min-width:300px;',
            // ),
            // 'shippit_meta_value' => array(
            //     'title'    => __( 'Product attribute value', 'mamis_shippit' ),
            //     'desc'     => 'Meta value to filter by',
            //     'id'       => 'shippit_meta_value',
            //     'name'     => 'shippit_meta_value',
            //     'type'     => 'text',
            //     'css'      => 'min-width:300px;',
            // ),
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

    public function getProductAttributes()
    {

    }

    public function canShip() 
    {
        // Check filtered by enabled products is active return true (let item be shipped)
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
            // If item is not enabled return false
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
        if ($this->enabled == 'no' || !$this->allowed_methods) {
            return;
        }

        $country = $package['destination']['country'];
        $quoteDestination = $package['destination'];
        $quoteCart = $package['contents'];

        if($this->canShip() && $country == 'AU') {
            // @todo check if filtering by product attribute is enabled
            $this->_processShippingQuotes($quoteDestination, $quoteCart);
        }
    }

    private function _processShippingQuotes($quoteDestination, $quoteCart)
    {
        $allowedMethods = $this->allowed_methods;
        $apiKey = $this->shippit_api_key;
        $debug = $this->debug;

        $isPremiumAvailable = in_array('premium', $allowedMethods);
        $isStandardAvailable = in_array('standard', $allowedMethods);

        $customerSuburb = $quoteDestination['city'];
        $customerPostcode = $quoteDestination['postcode'];
        $customerState = $quoteDestination['state'];
        if ($quoteCart['qty']) {
            $qty = $quoteCart['qty'];
        }

        /*
        * Product weight
        * @todo handle when weight hasn't been entered
        */
        $totalWeight = 0.1;

        if ( WC()->cart->cart_contents_weight == 0) {
            $totalWeight = 0;
        }
        else {
            $totalWeight = WC()->cart->cart_contents_weight;
        }

        $requestData = array(
            'order_date' => '',
            'dropoff_suburb' => $customerSuburb,
            'dropoff_postcode' => $customerPostcode,
            'dropoff_state' => $customerState,
            'parcel_attributes' => array(
                array(
                    'qty' => $qty,
                    'weight' => $totalWeight
                )
            ),
        );

        try {
            $shippingQuotes = $this->api_helper->getQuote($requestData, $apiKey, $debug);
        }
        catch (Exception $e) {
            // if ($this->helper->isDebugActive() && $this->bugsnag) {
            //     $this->bugsnag->notifyError('API - Quote Request', $e->getMessage());
            // }       
            error_log($e);
            return false;
        }

        if($shippingQuotes->response) {
            foreach($shippingQuotes->response as $shippingQuote) {
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
        foreach($shippingQuote->quotes as $standardQuote) {
            $rate = array(
                'id' => 'Mamis_Shippit_'.$shippingQuote->courier_type.'_'.uniqid(),
                'label' => 'Couriers Please',
                'cost' => $standardQuote->price,
                'taxes' => false,
            );
            $this->add_rate($rate);
        }
    }

    private function _addPremiumQuote($shippingQuote) 
    {
        $timeSlotCount = 0;
        $maxTimeSlots = $this->max_timeslots;

        foreach($shippingQuote->quotes as $premiumQuote) {
            if(!empty($maxTimeSlots) && $maxTimeSlots <= $timeSlotCount) {
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
                'id' => 'Mamis_Shippit_'.$carrierTitle .'_' . $premiumQuote->delivery_date . '_' . $premiumQuote->delivery_window . '_' . uniqid(),
                'label' => $methodTitle,
                'cost' => $premiumQuote->price,
                'taxes' => false,
            );
            $this->add_rate($rate);
        }
    }
}
