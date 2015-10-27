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

require_once( plugin_dir_path( __FILE__ ) . '../vendor/CurlWrapper.php');

class Mamis_Shippit_Helper_Api
{
    const API_ENDPOINT = 'http://goshippit.herokuapp.com/api/3';
    const API_ENDPOINT_STAGING = 'http://shippit-staging.herokuapp.com/api/3';
    const API_TIMEOUT = 5;
    const API_USER_AGENT = 'Mamis_Shippit for WooCommerce';
    const API_KEY = 'R6XVx2B-lXsOzOH1Z7ew6w';

    protected $api;
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    public function __construct()
    {
        //add_action( 'the_content', array( $this, 'get_post_response' ) );
        //$this->curl = new CurlWrapper();
    }

    function getCurl() 
    {
        try {
            $curl = new CurlWrapper();
        } 
        catch (CurlWrapperException $e) {
            echo $e->getMessage();
        }

        return $curl;
    }

    public function get_post_response($api_key, $suburb, $postcode, $state, $qty, $weight) 
    {
        $requestData = array(
            'quote' => array(
                'order_date' => '', 
                'dropoff_suburb' => $suburb,
                'dropoff_postcode' => $postcode,
                'dropoff_state' => $state,
                'parcel_attributes' => array(
                    array(
                        'qty' => $qty,
                        'weight' => $weight
                    )
                ),
            )
        );

        $encoded = json_encode($requestData);

        $curl = $this->getCurl();

        $curl->addHeader('Content-Type', 'application/json');
        $response = $curl->rawPost('http://goshippit.herokuapp.com/api/3/quotes?auth_token='.$api_key.'', $encoded);

        $apiResponseBody = json_decode($response, false);

        return $apiResponseBody;
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    } // end get_instance

    public function getApiUri($path, $authToken = null)
    {
        if (is_null($authToken)) {
            $authToken = $this->helper->getApiKey();
        }

        return self::API_ENDPOINT . '/' . $path . '?auth_token=' . $authToken;
    }

    public function call()
    {

    }

    public function getQuote($requestData)
    {

    }

    public function sendOrder()
    {
        // $requestData = array(
        //     'order' => $requestData;
        // );
        $requestData = array(
            'order' => array(
                'user_attributes' => array (
                    'email' => 'giolliano@mamis.com.au',
                    'first_name' => 'giolliano',
                    'last_name' => 'sulit'
                ),
                'parcels_attributes' => array(array( 
                    'qty' => '2',
                    'weight' => '1'
                )),
                'courier_type' => 'Bonds',
                'delivery_postcode' => '2009',
                'delivery_address' => '26-32 Pirrama Road',
                'delivery_suburb' => 'Pyrmont',
                'delivery_state' => 'NSW',
                'delivery_date' => '28/10/2014',
                'delivery_window' => '07:00-10:00',
                'delivery_instructions' => 'Test',
                'receiver_name' => 'Robert Smith',
                'receiver_contact_number' => '04040404',
                'authority_to_leave' => 'yes',
                'retailer_invoice' => 'invoicenumber'
            )
        );

        $encoded = json_encode($requestData);
        return $encoded;
        $curl = $this->getCurl();

        $curl->addHeader('Content-Type', 'application/json');
        $response = $curl->rawPost('http://goshippit.herokuapp.com/api/3/orders?auth_token=R6XVx2B-lXsOzOH1Z7ew6w', $encoded);

        $apiResponseBody = json_decode($response, false);

        //return $apiResponseBody;
    }

    public function getMerchant()
    {

    }
}