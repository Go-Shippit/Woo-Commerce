<?php
/*
 * Plugin Name:             WooCommerce Shippit
 * Description:             WooCommerce Shippit
 * Version:                 2.0.4
 * Author:                  Shippit Pty Ltd
 * Author URL:              http://www.shippit.com
 * Text Domain:             woocommerce-shippit
 * WC requires at least:    6.0.0
 * WC Tested Up To:         10.2.2
 */

define('MAMIS_SHIPPIT_VERSION', '2.0.4');

// import core classes
include_once('includes/class-shippit-helper.php');
include_once('includes/class-shippit-settings.php');
include_once('includes/class-shippit-settings-method.php');
include_once('includes/class-shippit-core.php');

function init_shippit_core()
{
    include_once('includes/class-upgrade.php');
    $upgrade = new Mamis_Shippit_Upgrade();
    $upgrade->run();

    // import helper classes
    include_once('includes/class-shippit-log-handler.php');
    include_once('includes/class-shippit-log.php');
    include_once('includes/class-shippit-api.php');
    include_once('includes/class-shippit-order.php');
    include_once('includes/class-shippit-object.php');
    include_once('includes/class-shippit-data-mapper-order.php');
    include_once('includes/class-shippit-data-mapper-order-item.php');
    include_once('includes/class-shippit-shipment.php');

    $shippit = Mamis_Shippit_Core::instance();

    add_filter(
        'woocommerce_settings_tabs_array',
        array(
            'Mamis_Shippit_Settings',
            'addSettingsTab',
        ),
        50
    );
}

// Declare copmatability with HPOS
add_action(
    'before_woocommerce_init',
    function() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true
            );
        }
    }
);

// add shippit core functionality
add_action('woocommerce_init', 'init_shippit_core', 99999);

// register shippit script
add_action('admin_enqueue_scripts', 'register_shippit_script');

function register_shippit_script()
{
    wp_register_script(
        'shippit-script',
        plugin_dir_url(__FILE__) . 'assets/js/shippit.js',
        array('jquery'),
        MAMIS_SHIPPIT_VERSION,
        true
    );
}

function init_shippit_method()
{
    include_once('includes/class-shippit-log-handler.php');
    include_once('includes/class-shippit-log.php');
    include_once('includes/class-shippit-api.php');
    include_once('includes/class-shippit-method.php');

    // add shipping methods
    add_filter('woocommerce_shipping_methods', array('Mamis_Shippit_Method', 'add_shipping_method'));
}

// add shipping method class
add_action('woocommerce_shipping_init', 'init_shippit_method');

// register the cron job hooks when activating / de-activating the module
register_activation_hook(__FILE__, array('Mamis_Shippit_Core', 'order_sync_schedule'));
register_deactivation_hook(__FILE__, array('Mamis_Shippit_Core', 'order_sync_deschedule'));
