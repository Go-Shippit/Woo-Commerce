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

class Mamis_Shippit_Helper_Api
{
    const API_ENDPOINT = 'http://goshippit.herokuapp.com/api/3';
    const API_ENDPOINT_STAGING = 'http://shippit-staging.herokuapp.com/api/3';
    const API_TIMEOUT = 5;
    const API_USER_AGENT = 'Mamis_Shippit for WooCommerce';
    const API_KEY = 'R6XVx2B-lXsOzOH1Z7ew6w';

    protected $api;

    public function __construct()
    {

        // Testing to see if wp_remote_post() will work
        $requestData = array(
            'quote' => array(
                'order_date' => '2015-12-13', 
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

        $this->api = array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => $requestData,
            'cookies'     => array(),
        );
        
        $args = array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => $requestData,
            'cookies'     => array(),
        ); 

        $data = json_encode($args);

        //$requestData;

        $response = wp_remote_post( 'http://goshippit.herokuapp.com/api/3/quotes?auth_token=R6XVx2B-lXsOzOH1Z7ew6w', array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 15,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $requestData,
                'cookies' => array()
            ) 
        );
        // if ( is_wp_error( $response ) ) {
        //    $error_message = $response->get_error_message();
        //    echo "Something went wrong: $error_message";
        // } 

        // else 
        // {
        //    echo 'Response:<pre>';
        //    print_r( $response );
        //    echo '</pre>';
        // }

        // $response = wp_remote_get( 'http://goshippit.herokuapp.com/api/3/merchant?auth_token=R6XVx2B-lXsOzOH1Z7ew6w' );
        // if( is_array($response) ) {
        //   $header = $response['headers']; // array of http header lines
        //   echo $body = $response['body']; // use the content
        // }
    }

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