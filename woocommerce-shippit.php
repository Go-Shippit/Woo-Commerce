<?php
/*
 * Plugin Name:     WooCommerce Shippit
 * Description:     WooCommerce Shippit
 * Version:         1.3.7
 * Author:          Shippit Pty Ltd
 * Author URL:      http://www.shippit.com
 * Text Domain:     woocommerce-shippit
 */

define('MAMIS_SHIPPIT_VERSION', '1.3.7');

// import core classes
include_once('includes/class-shippit-helper.php');
include_once('includes/class-shippit-settings.php');
include_once('includes/class-shippit-settings-method.php');
include_once('includes/class-shippit-core.php');

function init_shippit_core()
{
    global $shippitOtherShippingMethods;

    include_once('includes/class-upgrade.php');
    $upgrade = new Mamis_Shippit_Upgrade();
    $upgrade->run();

    // import helper classes
    include_once('includes/class-shippit-log.php');
    include_once('includes/class-shippit-api.php');
    include_once('includes/class-shippit-order.php');

    $shippit = Mamis_Shippit_Core::instance();

    $shippingMethods = WC()->shipping()->load_shipping_methods();

    foreach ($shippingMethods as $shippingMethod) {
        if ($shippingMethod->id == 'mamis_shippit') {
            continue;
        }

        $shippitOtherShippingMethods[$shippingMethod->id] = (property_exists($shippingMethod, 'method_title') ? $shippingMethod->method_title : $shippingMethod->title);
    }

    add_filter('woocommerce_settings_tabs_array', 'Mamis_Shippit_Settings::addSettingsTab', 50);
}

// add shippit core functionality
add_action('woocommerce_init', 'init_shippit_core', 99999);


function init_shippit_method()
{
    include_once('includes/class-shippit-log.php');
    include_once('includes/class-shippit-api.php');
    include_once('includes/class-shippit-method.php');
    include_once('includes/class-shippit-method-legacy.php');

    // add shipping methods
    add_filter('woocommerce_shipping_methods', array('Mamis_Shippit_Method', 'add_shipping_method'));
    add_filter('woocommerce_shipping_methods', array('Mamis_Shippit_Method_Legacy', 'add_shipping_method'));
}

// add shipping method class
add_action('woocommerce_shipping_init', 'init_shippit_method');

// register the cron job hooks when activating / de-activating the module
register_activation_hook(__FILE__, array('Mamis_Shippit_Core', 'order_sync_schedule'));
register_deactivation_hook(__FILE__, array('Mamis_Shippit_Core', 'order_sync_deschedule'));
