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

class Mamis_Shippit_Core {

    /**
     * Version.
     */
    public $version = '1.0.0';
    public $id = 'mamis_shippit';

    /**
     * Instace of Mamis Shippit
     */
    private static $instance;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }
        
        // Check if WooCommerce is active
        if ( !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
            if (!is_plugin_active_for_network('woocommerce/woocommerce.php')) {
                return;
            }
        }

        $this->s = new Mamis_Shippit_Settings();
        $this->log = new Mamis_Shippit_Log();
        
        $this->init();

    }

    /**
     * Instance.
     *
     * An global instance of the class. Used to retrieve the instance
     * to use on other files/plugins/themes.
     *
     * @since 1.0.0
     *
     * @return object Instance of the class.
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin parts.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('syncOrders', array($this, 'syncOrders'));

        // *****************
        // Order Sync
        // *****************
        
        $order = new Mamis_Shippit_Order;
        // add order processing event
        add_action('woocommerce_order_status_processing', array($order, 'addPendingSync'));
        // If the order is changed from any state to on-hold check if mamis_shippit_sync exists
        add_action('woocommerce_order_status_on-hold', array($order, 'removePendingSync'));

        // *****************
        // Authority To Leave
        // *****************
        
        // Add authority to leave field to checkout
        add_action('woocommerce_after_order_notes', array($this, 'add_authority_to_leave'));

        // Update the order meta with authority to leave value
        add_action('woocommerce_checkout_update_order_meta', array($this, 'update_authority_to_leave_order_meta'));

        // Display the authority to leave on the orders edit page
        add_action('woocommerce_admin_order_data_after_shipping_address', 'authority_to_leave_display_admin_order_meta', 10, 1);

        function authority_to_leave_display_admin_order_meta($order){
            echo '<p><strong>'.__('Authority to leave').':</strong> ' . get_post_meta( $order->id, 'authority_to_leave', true ) . '</p>';
        }

        // Validate the api key when the setting is changed
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'validate_shippit_request'));

        //**********************/
        // Webhook functionality
        //**********************/
        // create filter to get $_GET['shippit_api_key'] 
        add_filter('query_vars', array($this, 'add_query_vars'), 0);
        // handle API request if 'shippit_api_key' is set
        add_action('parse_request', array($this, 'shippit_endpoint_request'), 0);
        // create 'shippit/shipment_create' endpoint
        add_action('init', array($this, 'shippit_endpoint'), 0);

    }

    public function add_query_vars($vars){
        $vars[] = 'shippit_api_key';
        return $vars;
    }

    // Validate api key, validate order id and validate the state
    public function shippit_endpoint_request() 
    {
        global $wp;

        // Grab the stored api key
        $stored_api_key = $this->s->getSetting('api_key');

        // Grab the posted shippit API key
        $posted_api_key = $wp->query_vars['shippit_api_key'];

        // if there is no shippit api key set, exit
        if (isset($posted_api_key)) {
            
            // If the current API key matches the one stored, keep processing
            if( strcmp( $stored_api_key, $posted_api_key) == 0 ) {
              
                // Grab the posted JSON object
                $shippit_post_data = json_decode(file_get_contents('php://input'));

                if($shippit_post_data == NULL) {
                    exit;
                }
                        
                // Grab the values from the posted JSON object
                $posted_order_id = $shippit_post_data->retailer_order_number;        
                $posted_order_status = $shippit_post_data->current_state;

                // Check if order exists
                $should_order_sync = $this->does_order_exist($posted_order_id);

               // If order exists and the current state is completed, update the woocommerce status to complete
                if( $should_order_sync && (strcmp($posted_order_status, 'completed') == 0) ) {
                    $order = new WC_Order($posted_order_id);
                    $order->update_status('completed', 'order_note'); 
                    add_action( 'woocommerce_order_status_completed_notification', 'action_woocommerce_order_status_completed_notification', 10, 2 );
                    header('content-type: application/json; charset=utf-8');
                    http_response_code(200);
                }
                exit;
            }
            else {
                header('content-type: application/json; charset=utf-8');
                http_response_code(400);
                exit;
            }
        }
    }

    public function does_order_exist($posted_order_id) {
        global $woocommerce;
        global $post;

        $order_post_arguments = array(
            'post_type' => 'shop_order',
            'post_status' => 'wc-processing'
        );

        // Get all woocommerce orders that are processing
        $orders_to_be_updated = get_posts($order_post_arguments);

        if(!$orders_to_be_updated) {
            header('content-type: application/json; charset=utf-8');
            http_response_code(400);
            exit;
        }

        // Check if order exists, return true if it does
        foreach ($orders_to_be_updated as $order_to_be_updated) {
            if( strcmp($order_to_be_updated->ID, $posted_order_id) == 0) {
                return true;
            }
        }

    }

    public function shippit_endpoint() 
    {
        add_rewrite_rule('^shippit/shipment_create/?([0-9]+)?/?','index.php?shippit_api_key=$matches[1],','top');
    }

    public function validate_shippit_request()
    {
        $oldApiKey = $this->s->getSetting('api_key');
        $newApiKey = $_POST['woocommerce_mamis_shippit_api_key'];

        $this->log->add(
            'Validating API Key',
            $newApiKey,
            array(
                'old_api_key' => $oldApiKey,
                'new_api_key' => $newApiKey
            )
        );

        if (strcmp($newApiKey, $oldApiKey) != 0) {
            $this->api = new Mamis_Shippit_Api();
            // Set the api key temporarily to the requested key
            $this->api->setApiKey($newApiKey);

            try {
                $apiResponse = $this->api->getMerchant();

                if (property_exists($apiResponse, 'error')) {
                    $this->log->add(
                        'Validating API Key Result',
                        'API Key ' . $newApiKey . 'is INVALID'
                    );

                    $this->show_api_notice(false);
                }
                
                if (property_exists($apiResponse, 'response')) {
                    $this->log->add(
                        'Validating API Key Result',
                        'API Key ' . $newApiKey . 'is VALID'
                    );

                    $this->show_api_notice(true);
                }


            }
            catch (Exception $e) {
                $this->log->exception($e);
            }
        }
    }

    public function show_api_notice($isValid)
    {
        if (!$isValid) {
            echo '<div class="error notice">'
                . '<p>Invalid Shippit API Key detected - Shippit will not function correctly.</p>'
                . '</div>';
        }
        else {
            echo '<div class="updated notice">'
                . '<p>Shippit API Key is Valid</p>'
                . '</div>';
        }
    }

    /**
     * Add the shippit order sync to the cron scheduler
     */
    public static function order_sync_schedule()
    {
        if (!wp_next_scheduled('syncOrders')) {
            wp_schedule_event(current_time('timestamp'), 'hourly', 'syncOrders');
        }
    }

    /**
     * Remove the shippit order sync to the cron scheduler
     */
    public static function order_sync_deschedule()
    {
        wp_clear_scheduled_hook('syncOrders');
    }

    public function syncOrders()
    {
        if (class_exists('WC_Order')) {
            $orders = new Mamis_Shippit_Order();
            $orders->syncOrders();
        }
    }

    public function add_authority_to_leave($checkout)
    {
        echo '<div id="authority_to_leave"><h2>' . __('Authority to leave') . '</h2>';

        woocommerce_form_field( 'authority_to_leave', array(
            'type'          => 'select',
            'class'         => array('my-field-class form-row-wide'),
            'options'       => array(
                'No' => 'No',
                'Yes'  => 'Yes'
                ),
            ),
        $checkout->get_value( 'authority_to_leave' ));

        echo '</div>';
    }

    public function update_authority_to_leave_order_meta($order_id)
    {
        if (!empty($_POST['authority_to_leave'])) {
            update_post_meta(
                $order_id,
                'authority_to_leave',
                sanitize_text_field($_POST['authority_to_leave'])
            );
        }
    }
}