<?php
/*
 * Plugin Name:     WooCommerce Shippit
 * Description:     WooCommerce Shippit
 * Version:         1.2.2
 * Author:          Shippit Pty Ltd
 * Author URL:      http://www.shippit.com
 * Text Domain:     woocommerce-shippit
 */

define('MAMIS_SHIPPIT_VERSION', '1.2.2');

// import core classes
include_once('includes/class-shippit-settings.php');
include_once('includes/class-shippit-core.php');

function init_shippit_core()
{
    global $shippitOtherShippingMethods;

    // import helper classes
    include_once('vendor/Bugsnag/Autoload.php');
    include_once('includes/class-shippit-log.php');
    include_once('includes/class-shippit-api.php');
    include_once('includes/class-shippit-order.php');

    $shippit = Mamis_Shippit_Core::instance();

    $shippingMethods = WC()->shipping()->load_shipping_methods();

    foreach ($shippingMethods as $shippingMethod) {
        if ($shippingMethod->id == 'mamis_shippit') {
            continue;
        }

        $shippitOtherShippingMethods[$shippingMethod->id] = $shippingMethod->title;
    }
}

// add shippit core functionality
add_action('woocommerce_init', 'init_shippit_core', 99999);


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