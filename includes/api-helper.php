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
        add_action( 'the_content', array( $this, 'get_post_response' ) );
    }

    public function get_post_response( $content ) 
    {

        $requestData = array(
            'quote' => array(
                'order_date' => '2015-12-12', 
                'dropoff_suburb' => 'Melbourne ',
                'dropoff_postcode' => '3028',
                'dropoff_state' => 'VIC',
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

        $vars = array( 
            'quote' => array(
                'order_date' => '2015-12-12', 
                'dropoff_suburb' => 'Melbourne ',
                'dropoff_postcode' => '3028',
                'dropoff_state' => 'VIC',
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

        $curl->addHeader('Content-Type', 'application/json');
        $response = $curl->rawPost('http://goshippit.herokuapp.com/api/3/quotes?auth_token=R6XVx2B-lXsOzOH1Z7ew6w', $encoded);

        var_dump($response);

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