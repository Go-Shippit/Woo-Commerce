<?php
require_once( plugin_dir_path( __FILE__ ) . 'api-helper.php');

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

class Mamis_Shippit_Order_Sync 
{
    const CARRIER_CODE = 'mamis_shippit';

    /**
     * Constructor.
     */
    public function __construct() 
    {  
        $this->init();
        $this->api_helper = new Mamis_Shippit_Helper_Api();
    }

    function init() {

    }

    /**
    * Add Sync Meta
    *   
    * Add mamis_shippit_sync meta key value to all orders that
    * are using the Mamis_Shippit Method
    */

    public function addSyncMeta() 
    {
        $orders = array(
            'post_status' => 'wc-processing',
            'post_type' => 'shop_order',
        ); 

        // Get all woocommerce orders that are processing
        $shopOrders = get_posts($orders);

        foreach ($shopOrders as $shopOrder) {
            // Get the orders_item_id meta with key shipping
            $order = new WC_Order($shopOrder->ID);
            $items = $order->get_items('shipping');

            foreach ($items as $key => $item) {
                // Check if the shipping method chosen was Mamis_Shippit
                $isShippit = strpos($item['method_id'],'Mamis_Shippit');
                if ($isShippit !== false) {
                    // If it was Mamis_Shippit method, check if mamis_shippit_sync meta key is present
                    if(get_post_meta($shopOrder->ID, 'mamis_shippit_sync', true )) {
                        echo 'true';
                    }
                    // If there is no mamis_shippit_sync meta key, add it
                    else {
                        add_post_meta($shopOrder->ID, 'mamis_shippit_sync', 'false', true);
                    }
                } 
            }

        }
    }

    public function syncOrder()
    {
       var_dump($this->api_helper->sendOrder());

    }

}