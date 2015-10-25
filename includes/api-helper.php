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

    public function get_post_response( $api_key ) 
    {
        $requestData = array(
            'quote' => array(
                'order_date' => '2015-10-30', 
                'dropoff_suburb' => 'Sydney',
                'dropoff_postcode' => '2000',
                'dropoff_state' => 'NSW',
                'parcel_attributes' => array(
                    array(
                        'qty' => 1,
                        'length' => 0.1,
                        'width' => 0.10,
                        'depth' => 0.15,
                        'weight' => 3
                    )
                ),
            )
        );

        $encoded = json_encode($requestData);

        try {
            $curl = new CurlWrapper();
        } 
        catch (CurlWrapperException $e) {
            echo $e->getMessage();
        }

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

    public function sendOrder($requestData)
    {

    }

    public function getMerchant()
    {

    }
}