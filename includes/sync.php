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

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

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
        //$this->init_settings();
    }

    public function getShippingConfig()
    {
        $shippingConfig = get_option('woocommerce_mamis_shippit_settings');

        return $shippingConfig;
    }

    public function getApiKey() 
    {
        $shippingConfig = $this->getShippingConfig();
        $apiKey = $shippingConfig['shippit_api_key'];
        return $apiKey;
    }

    public function getDebug() 
    {
        $shippingConfig = $this->getShippingConfig();
        $debugOption = $shippingConfig['shippit_debug'];
        return $debugOption;
    }

    /**
    * Add Sync Meta
    *   
    * Add mamis_shippit_sync meta key value to all orders that
    * are using the Mamis_Shippit Method
    */

    public function syncOrders() 
    {
        global $woocommerce;
        $this->api_helper = new Mamis_Shippit_Helper_Api();
        $orderPostArg = array(
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

        $orderPosts = get_posts($orderPostArg);

        foreach ($orderPosts as $orderPost) {
            $shippingMethodId = null;

            // Get the orders_item_id meta with key shipping
            $order = new WC_Order($orderPost->ID);
            $orderItems = $order->get_items('shipping');
            //var_dump($items);
            foreach ($orderItems as $key => $orderItem) {
                foreach ($orderItem['item_meta_array'] as $orderItemMeta) {
                    if ($orderItemMeta->key == 'method_id') {
                        $shippingMethodId = $orderItemMeta->value;
                    }
                }
            }

            if (is_null($shippingMethodId)) {
                continue;
            }



            // Check if the shipping method chosen was Mamis_Shippit
            $shippingOptions = str_replace('Mamis_Shippit' . '_', '', $shippingMethodId);
            $shippingOptions = explode('_',$shippingOptions);
            $courierType = $shippingOptions[0];
            var_dump($shippingOptions);
            if(isset($shippingOptions[1])) {
                $deliveryDate = $shippingOptions[1];
            }
            else {
                $deliveryDate = '';
            }
            if (isset($shippingOptions[2])) {
                $deliveryWindow = $shippingOptions[2];
            }
            else {
                $deliveryWindow = '';
            }

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

            $parcelData = array(
                array(
                    'qty' => $itemQuantity,
                    'weight' => $totalWeight
                    )
                );

            $authorityToLeave = get_post_meta( $shopOrder->ID, 'authority_to_leave', true );

            $orderData = array(
                'user_attributes' => $userAttributes,
                'parcel_attributes' => $parcelData,
                'courier_type' => $courierType,
                'delivery_postcode' => $order->shipping_postcode,
                'delivery_address' => $order->shipping_address_1,
                'delivery_suburb' => $order->shipping_city,
                'delivery_state' => $order->shipping_state,
                'delivery_date' => $deliveryDate,
                'delivery_window' => $deliveryWindow,
                'delivery_instructions' => $order->customer_message,
                'receiver_name' => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                'receiver_contact_number' => $order->receiver_contact_number,
                'authority_to_leave' => $authorityToLeave,
                'retailer_invoice' => $order->get_order_number()
            );

            $apiKey = $this->getApiKey();
            $debug = $this->getDebug();

            if ($apiResponse = $this->api_helper->syncOrder($apiKey, $debug, $orderData)) {
                $orderComment = 'Order sync with Shippit successful. Tracking number: ' . $apiResponse->response->tracking_number . '.';
                $order->add_order_note($orderComment, 0);
            }
        }
    }
}