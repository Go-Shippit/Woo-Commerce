<?php
/*
 * Plugin Name:     WooCommerce Shippit
 * Description:     WooCommerce Shippit
 * Version:         1.1.3
 * Author:          Shippit Pty Ltd
 * Author URL:      http://www.shippit.com
 * Text Domain:     woocommerce-shippit
 */

define('MAMIS_SHIPPIT_VERSION', '1.1.3');

include_once('includes/class-shippit-settings.php');
include_once('includes/class-shippit-core.php');

function init_shippit_core()
{
    // import helper classes
    include_once('vendor/Bugsnag/Autoload.php');
    include_once('includes/class-shippit-log.php');
    include_once('includes/class-shippit-api.php');
    include_once('includes/class-shippit-order.php');

    $shippit = Mamis_Shippit_Core::instance();
}
// add shippit core functionality
add_action('plugins_loaded', 'init_shippit_core');


function init_shippit_method()
{
    include_once('vendor/Bugsnag/Autoload.php');
    include_once('includes/class-shippit-log.php');
    include_once('includes/class-shippit-api.php');
    include_once('includes/class-shippit-method.php');
    
    $method = new Mamis_Shippit_Method();
}
// add shipping method class
add_action('woocommerce_shipping_init', 'init_shippit_method');

// register the cron job hooks when activating / de-activating the module
register_activation_hook(__FILE__, array('Mamis_Shippit_Core', 'order_sync_schedule'));
register_deactivation_hook(__FILE__, array('Mamis_Shippit_Core', 'order_sync_deschedule'));