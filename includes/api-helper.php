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
    const API_TIMEOUT = 5;
    const API_USER_AGENT = 'Mamis_Shippit for WooCommerce';

    protected $api;
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

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

    public function __construct()
    {

    }

    public function getCurl() 
    {
        try {
            $curl = new CurlWrapper();
        } 
        catch (CurlWrapperException $e) {
            error_log($e->getMessage());
        }
        $curl->addHeader('Content-Type', 'application/json');
        $curl->setUserAgent(self::API_USER_AGENT);
        $curl->setTimeout(self::API_TIMEOUT); 

        return $curl;
    }

    public function getApiUri($path, $apiKey)
    {
        return self::API_ENDPOINT . '/' . $path . '?auth_token=' . $apiKey;
    }

    public function call($uri, $apiKey, $debugActive, $requestData, $exceptionOnResponseError = true) 
    {
        $uri = $this->getApiUri($uri,$apiKey);
        $jsonRequestData = json_encode($requestData);

        if ($debugActive == 'Yes') {
            error_log('-- SHIPPIT - API REQUEST: --');
            error_log($uri);
            error_log($requestData);
        }

        $curl = $this->getCurl();

        try {
            $response = $curl->rawPost($uri, $jsonRequestData);
        }
        catch (Exception $e) {
            // $this->prepareBugsnagReport($uri, $jsonRequestData, $apiResponse);
            var_dump($e);
            error_log('API Request Error' . $e);
        }
        $apiResponse = json_decode($response, false);

        if ($debugActive == 'Yes') {
            error_log('-- SHIPPIT - API RESPONSE --');
            error_log($apiResponse);
        }

        return $apiResponse;
    }

    public function getQuote($requestData, $apiKey, $debugActive)
    {
        $requestData = array(
            'quote' => $requestData
        );

        return $this->call('quotes', $apiKey, $debugActive, $requestData);
    }

    public function syncOrder($apiKey, $debug, $orderData) 
    {
        $requestData = array(
            'order' => $orderData
        );
        
        return $this->call('orders', $apiKey, $debugActive, $requestData);
    }

    public function getMerchant()
    {

    }
}