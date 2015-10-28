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

        // Check if module is enabled

    }

    /**
    * Add Sync Meta
    *   
    * Add mamis_shippit_sync meta key value to all orders that
    * are using the Mamis_Shippit Method
    */

    public function syncOrders() 
    {
        $orders = array(
            'post_status' => 'wc-processing',
            'post_type' => 'shop_order',
            'meta_query' => array(
                array(
                'key' => 'mamis_shippit_sync',
                'value' => 'false',
                'compare' => '='
                )
            ),
        ); 

        // Get all woocommerce orders that are processing
        $shopOrders = get_posts($orders);

        foreach ($shopOrders as $shopOrder) {
            // Get the orders_item_id meta with key shipping
            $order = new WC_Order($shopOrder->ID);
            $items = $order->get_items('shipping');

            // Get user attributes
            $userAttributes = array(
                'email' => $order->billing_email,
                'first_name' => $order->billing_first_name,
                'last_name' => $order->billing_last_name
            );

            $products = $order->get_items('line_item');
            // var_dump($products);
            $itemQuantity = $order->get_item_count();
            $totalWeight = 0;
            foreach($products as $key => $product) {
                //echo(count($products));
                $productDetails = new WC_Product($product['product_id']);
                $itemTotalWeight = 0;
                if ($productDetails->has_weight()){
                    // Multiply by quantity for total weight
                    $itemWeight = $productDetails->get_weight();
                    $totalWeight = $itemQuantity * $itemWeight;
                }
                else {
                    /*
                    * @todo handle when weight hasn't been entered
                    */
                    $itemWeight = 0;
                }
            }

            $totalWeight;
            $parcelData = array(
                array(
                    'qty' => $itemQuantity,
                    'weight' => $totalWeight
                    )
                );

           $orderData = array(
                'order' => array(
                    'user_attributes' => $userAttributes,
                    'parcel_attributes' => $parcelData,
                    'courier_type' => 'CouriersPlease',
                    'delivery_postcode' => $order->shipping_postcode,
                    'delivery_address' => $order->shipping_address_1,
                    'delivery_suburb' => $order->shipping_city,
                    'delivery_state' => $order->shipping_state,
                    'delivery_instructions' => 'Delivery instructions',
                    'receiver_name' => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                    'receiver_contact_number' => $order->receiver_contact_number,
                    'authority_to_leave' => 'No',
                    'retailer_invoice' => $order->get_order_number()
                )
            );

            if ($apiResponse = $this->api_helper->syncOrder($orderData)) {
                update_post_meta($shopOrder->ID, 'mamis_shippit_sync', 'true', 'false');

                $orderComment = 'Order sync with Shippit successful. Tracking number: ' . $apiResponse->response->tracking_number . '.';
                $order->add_order_note($orderComment, 0);
            }

            foreach ($items as $key => $item) {

                //var_dump($test);
                // Check if the shipping method chosen was Mamis_Shippit
                $isShippit = strpos($item['method_id'],'Mamis_Shippit');
                if ($isShippit !== false) {
                    // If it was Mamis_Shippit method, check if mamis_shippit_sync meta key is present
                    // echo $test = get_post_meta($shopOrder->ID, 'mamis_shippit_sync');
                    // var_dump($test);
                } 
            }

        }
    }

    public function sendOrder($orderData)
    {
        
    }

    public function syncOrder()
    {
       var_dump($this->api_helper->sendOrder());
    }

    public function getCustomerDetails() 
    {
        $userAttributes = array();

        $orders = array(
            'post_status' => 'wc-processing',
            'post_type' => 'shop_order',
        ); 

        // Get all woocommerce orders that are processing
        $shopOrders = get_posts($orders);

        foreach ($shopOrders as $shopOrder) {
            // Get the orders_item_id meta with key shipping
            $order = new WC_Order($shopOrder->ID);
            $customer = new WC_Customer($order);
            // loop through items and grab 

            $products = $order->get_items();
                
            var_dump($products);

            foreach($products as $product) {
                $productDetails = new WC_Product($product['product_id']);

                if ($productDetails->has_weight()){
                    // Multiply by quantity for total weight
                    $itemWeight = $productDetails->get_weight();
                }
                else {
                    /*
                    * @todo handle when weight hasn't been entered
                    */
                    $itemWeight = 0;
                }
            }
            // var_dump($order->get_item_count());
        }
    }

}