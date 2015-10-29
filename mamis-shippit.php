<?PHP
/*
 * Plugin Name: 	WooCommerce Shippit
 * Description: 	WooCommerce Shippit
 * Version: 		1.0.0
 * Author: 			Mamis I.T
 * Text Domain: 	woocommerce-shippit
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

register_activation_hook(__FILE__, array('Mamis_Shippit','activate_plugin'));
register_deactivation_hook(__FILE__, array('Mamis_Shippit','deactivate_plugin'));

/**
 * Class Mamis_Shippit
 */
class Mamis_Shippit {

	/**
	 * Version.
	 */
	public $version = '1.0.0';

	/**
	 * Instace of Mamis Shippit
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() 
	{
		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		
		// Check if WooCommerce is active
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) :
			if ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) :
				return;
			endif;
		endif;

		$this->init();
	}

	/**
	 * Instance.
	 *
	 * An global instance of the class. Used to retrieve the instance
	 * to use on other files/plugins/themes.
	 *
	 * @since 1.1.0
	 *
	 * @return object Instance of the class.
	 */
	public static function instance() 
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init.
	 *
	 * Initialize plugin parts.
	 *
	 * @since 1.1.0
	 */
	public function init() 
	{
		add_action('shippitSyncOrders', array($this,'syncOrdersCron'));
		// Add hooks/filters
		$this->hooks();
	}

	public static function activate_plugin() 
	{
		if (!wp_next_scheduled( 'shippitSyncOrders') ) {
			wp_schedule_event( current_time ( 'timestamp' ), 'hourly', 'shippitSyncOrders' ); 
		}
	}

	public static function deactivate_plugin()
	{
		wp_clear_scheduled_hook('shippitSyncOrders');
	}

	public function syncOrdersCron() 
	{
		  if ( class_exists('WC_Order') ) {
		  	require_once( plugin_dir_path( __FILE__ ) . 'includes/sync.php');
		   	$sync = new Mamis_Shippit_Order_Sync();
		   	$sync->syncOrders();
		  } 
		  else {
		    
		  }
	}

	public function testSync() 
	{
		  if ( class_exists('WC_Order') ) {
		  	require_once( plugin_dir_path( __FILE__ ) . 'includes/sync.php');
		   	// $sync = new Mamis_Shippit_Order_Sync();
		   	// $sync->syncOrders();
		  } 
	}

 	/**
	 * Hooks.
	 * Initialize all class hooks.
	 */
	public function hooks() {

		// Initialize shipping method class
		add_action( 'woocommerce_shipping_init', array( $this, 'shippit_shipping' ) );

		// Add shipping method
		add_action( 'woocommerce_shipping_methods', array( $this, 'shippit_add_shipping_method' ) );
		
		// Does the order need to be sync'd
		add_action('woocommerce_order_status_processing', array($this, 'shippit_should_sync') );

		// If the order is changed from any state to on-hold check if mamis_shippit_sync exists
		add_action('woocommerce_order_status_on-hold', array($this, 'shippit_remove_sync') );

		// Add authority to leave field to checkout
		add_action( 'woocommerce_after_order_notes', array($this,'authority_to_leave') );

		// Update the order meta with authority to leave value
		add_action( 'woocommerce_checkout_update_order_meta', array($this, 'authority_to_leave_update_order_meta' ) );

		// Display the authority to leave on the orders edit page
		// @todo move out of hooks()
		add_action( 'woocommerce_admin_order_data_after_shipping_address', 'authority_to_leave_display_admin_order_meta', 10, 1 );

		function authority_to_leave_display_admin_order_meta($order){
		    echo '<p><strong>'.__('Authority to leave').':</strong> ' . get_post_meta( $order->id, 'authority_to_leave', true ) . '</p>';
		}
		// For testing purposes
		add_action('plugins_loaded', array($this, 'testSync'));
		//add_action( 'woocommerce_shipping_init', array( $this, 'testSync' ) );
	}

	function authority_to_leave( $checkout ) {

	    echo '<div id="authority_to_leave"><h2>' . __('Authority to leave') . '</h2>';

	    woocommerce_form_field( 'authority_to_leave', array(
	        'type'          => 'select',
	        'class'         => array('my-field-class form-row-wide'),
	        'options' 		=> array(
	        	'No' => 'No',
	        	'Yes'  => 'Yes'
	        	),
	        ), 
	    $checkout->get_value( 'authority_to_leave' ));

	    echo '</div>';
	}

	function authority_to_leave_update_order_meta( $order_id ) {
	    if ( ! empty( $_POST['authority_to_leave'] ) ) {
	        update_post_meta( $order_id, 'authority_to_leave', sanitize_text_field( $_POST['authority_to_leave'] ) );
	    }
	}

    public function getShippingConfig()
    {
        $shippingConfig = get_option('woocommerce_mamis_shippit_settings');

        return $shippingConfig;
    }

    public function getSyncSettings()
    {
    	$shippingConfig = $this->getShippingConfig();
    	$syncSettings = $shippingConfig['shippit_send_orders'];
    	return $syncSettings;
    }

    public function isEnabled()
    {
    	$shippingConfig = $this->getShippingConfig();
    	$isEnabled = $shippingConfig['enabled'];
    	return $isEnabled;
    }

	public function shippit_should_sync($order_id) 
	{
		$isEnabled = $this->isEnabled();
		if ($isEnabled == 'no') {
			return;
		}

		// Get the orders_item_id meta with key shipping
        $order = $this->getOrder($order_id);
        $items = $order->get_items('shipping');
        
        $countryToShip = $order->shipping_country;
        $syncAllOrders = $this->getSyncSettings();

        if ($syncAllOrders == 'yes' && $countryToShip == 'AU')
        {
        	add_post_meta($order_id, 'mamis_shippit_sync', 'false', true);
        }
        else {
	        foreach ($items as $key => $item) {
	            // Check if the shipping method chosen was Mamis_Shippit
	            $isShippit = strpos($item['method_id'],'Mamis_Shippit');
	            if ($isShippit !== false) {
	                // Add mamis_shippit_sync flag if shippit method
	                add_post_meta($order_id, 'mamis_shippit_sync', 'false', true);
	            } 
	            else {
	            	return;
	            }
	        }
	    }
	}

	public function shippit_remove_sync($order_id)
	{
		// Get the orders_item_id meta with key shipping
        $order = $this->getOrder($order_id);
        $items = $order->get_items('shipping');

        foreach ($items as $key => $item) {
            // Check if the shipping method chosen was Mamis_Shippit
            $isShippit = strpos($item['method_id'],'Mamis_Shippit');
            if ($isShippit !== false) {
               // Check if mamis shippit sync exists and if it's false remove it
                if (get_post_meta($order_id, 'mamis_shippit_sync', true) == 'false') {
                	delete_post_meta($order_id, 'mamis_shippit_sync');
                }
            }  
        }
	}

	public function shippit_authority_to_leave($checkout) 
	{
    	echo '<div id="shippit_authority_to_leave"><h2>' . __('Authority to leave') . '</h2>';

    	woocommerce_form_field( 'authority_to_leave', array(
	        'type'          => 'select',
	        'class'         => array('my-field-class form-row-wide'),
	        'label'         => __('Fill in this field'),
	        'placeholder'   => __('Enter something'),
            'options'  => array(
                'no'  => __( 'No'),
                'yes' => __( 'Yes'),
            ),
        ), 
        $checkout->get_value( 'authority_to_leave' ));

    	echo '</div>';
	}

	public function getOrder($order_id) 
	{
		$this->order = new WC_Order($order_id);
		return $this->order;
	}

	public function getShippitObject() 
	{
		require_once plugin_dir_path( __FILE__ ) . 'includes/shippit-shipping-method.php';
		$this->shippit = new Shippit_Shipping();

		return $this->shippit;
	}

	/**
	 * Shipping method.
	 *
	 * Include the WooCommerce shipping method class.
	 *
	 * @since 1.0.0 
	 */
	public function shippit_shipping() 
	{
		/**
		 * Shippit shipping method
		 */
		require_once plugin_dir_path( __FILE__ ) . 'includes/shippit-shipping-method.php';
		$this->shippit_method = new Shippit_Shipping();
	}


	/**
	 * Add shipping method.
	 *
	 * Add shipping method to WooCommerce.
	 *
	 */
	public function shippit_add_shipping_method( $methods ) 
	{
		if ( class_exists( 'Shippit_Shipping' ) ) :
			$methods[] = 'Shippit_Shipping';
		endif;

		return $methods;
	}

}


/**
 *
 * @return object Mamis_Shippit class object.
 */
if ( ! function_exists( 'SHIPPIT' ) ) :

 	function SHIPPIT() 
 	{
		return Mamis_Shippit::instance();
	}

endif;

SHIPPIT();
