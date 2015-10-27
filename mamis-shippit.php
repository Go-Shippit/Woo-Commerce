<?PHP
/*
 * Plugin Name: 	WooCommerce Shippit
 * Description: 	WooCommerce Shippit
 * Version: 		1.0.0
 * Author: 			Mamis I.T
 * Text Domain: 	woocommerce-shippit
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
		// Add hooks/filters
		$this->hooks();
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
	}

	public function shippit_should_sync($order_id) 
	{
		// Get the orders_item_id meta with key shipping
        $order = $this->getOrder($order_id);
        $items = $order->get_items('shipping');

        foreach ($items as $key => $item) {
            // Check if the shipping method chosen was Mamis_Shippit
            $isShippit = strpos($item['method_id'],'Mamis_Shippit');
            if ($isShippit !== false) {
                // Add mamis_shippit_sync flag if shippit method
                add_post_meta($order_id, 'mamis_shippit_sync', 'false', true);
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
